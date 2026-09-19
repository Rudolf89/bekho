<?php

namespace App\Services;

use App\Enums\ResultadoBusqueda;
use App\Models\AccesoDato;
use App\Models\DocumentoPersona;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\PersonalGrupo;
use App\Models\User;
use App\Support\Rut;

/**
 * Búsqueda de una persona por documento (alta por documento / traslado). Usa
 * withTrashed para restaurar en vez de duplicar y registra cada consulta en
 * accesos_datos (auditoría de acceso entre grupos).
 */
class BuscadorPersonas
{
    /**
     * @return array{resultado: ResultadoBusqueda, persona: ?Persona}
     */
    public function buscar(string $documento, ?User $usuario = null, ?int $grupoId = null): array
    {
        $persona = $this->encontrarPersona($documento);
        $resultado = $this->clasificar($persona);

        AccesoDato::create([
            'user_id' => $usuario?->id,
            'grupo_id' => $grupoId,
            'documento_consultado' => $documento,
            'resultado' => $resultado->value,
        ]);

        return ['resultado' => $resultado, 'persona' => $persona];
    }

    /**
     * Encuentra la persona por su documento (RUT normalizado o número tal cual),
     * incluyendo las eliminadas con SoftDeletes.
     */
    private function encontrarPersona(string $documento): ?Persona
    {
        $candidatos = array_unique(array_filter([$documento, Rut::normalizar($documento)]));

        $doc = DocumentoPersona::whereIn('numero', $candidatos)->first();

        if (! $doc) {
            return null;
        }

        return Persona::withTrashed()->find($doc->persona_id);
    }

    private function clasificar(?Persona $persona): ResultadoBusqueda
    {
        if (! $persona) {
            return ResultadoBusqueda::NoExiste;
        }

        if ($persona->trashed()) {
            return ResultadoBusqueda::Eliminada;
        }

        $tieneMatriculaActiva = Matricula::withoutGlobalScopes()->activas()
            ->where('persona_id', $persona->id)->exists();

        if ($tieneMatriculaActiva) {
            return ResultadoBusqueda::ExisteConMatricula;
        }

        $esPersonal = PersonalGrupo::withoutGlobalScopes()
            ->where('persona_id', $persona->id)->exists();

        if ($esPersonal) {
            return ResultadoBusqueda::ExistePersonal;
        }

        return ResultadoBusqueda::ExisteSinMatricula;
    }
}
