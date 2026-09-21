<?php

namespace App\Livewire\Programas;

use App\Enums\EstadoLegacy;
use App\Models\Cuestionario;
use App\Models\EtapaPrograma;
use App\Models\InscripcionPrograma;
use App\Models\Programa;
use App\Models\RequisitoEtapa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Avance personal en el Programa Legacy (track de formación de instructores):
 * en qué nivel va la persona que ha iniciado sesión, cuánto lleva de la etapa
 * en curso y qué le falta para certificar.
 *
 * Es la vista del trainee, de solo lectura: las horas y los requisitos los
 * registra su instructor desde "Inscripciones" (permiso `gestionar
 * inscripciones`), y el ascenso lo aprueba quien corresponda. Lo único que el
 * trainee hace desde aquí es rendir la prueba escrita cuando su etapa la exige.
 */
#[Title('Programa Legacy')]
class ProgresoLegacy extends Component
{
    /**
     * Nombre del programa en el catálogo de la federación (EtapasProgramaSeeder).
     */
    private const PROGRAMA = 'Legacy';

    public function render(): View
    {
        $programa = Programa::where('nombre', self::PROGRAMA)->first();
        $personaId = Auth::user()->persona_id;

        $inscripcion = $programa && $personaId
            ? InscripcionPrograma::with(['etapaActual.requisitos.cuestionario', 'ascensos'])
                ->where('programa_id', $programa->id)
                ->where('persona_id', $personaId)
                ->first()
            : null;

        // Las cifras de la etapa en curso se calculan una sola vez: las usan
        // tanto la cabecera como el nivel "En curso" de la lista.
        $resumen = $inscripcion ? $this->resumen($inscripcion) : null;

        return view('livewire.programas.progreso-legacy', [
            'programa' => $programa,
            'inscripcion' => $inscripcion,
            'resumen' => $resumen,
            'niveles' => $programa ? $this->niveles($programa, $inscripcion, $resumen) : collect(),
            'pendientes' => $inscripcion ? $this->pendientes($inscripcion) : [],
            'cuestionarioPendiente' => $inscripcion ? $this->cuestionarioPendiente($inscripcion) : null,
        ]);
    }

    /**
     * Los niveles del programa con el estado de la persona en cada uno. Son las
     * etapas que exigen horas: el programa cuelga además una etapa contenedora
     * con el manual para estudiar, que no es un nivel de la ruta.
     *
     * @param  array{porcentaje: int, horas: float, horasRequeridas: int, requisitosCumplidos: int, requisitosTotal: int}|null  $resumen
     * @return Collection<int, array{etapa: EtapaPrograma, estado: string, porcentaje: int}>
     */
    private function niveles(Programa $programa, ?InscripcionPrograma $inscripcion, ?array $resumen): Collection
    {
        $etapas = $programa->etapas()
            ->whereNotNull('horas_requeridas')
            ->with(['requisitos', 'gradoMinimo'])
            ->orderBy('orden')
            ->get();

        $aprobadas = $inscripcion
            ? $inscripcion->ascensos->pluck('etapa_programa_id')->all()
            : [];

        $niveles = [];

        foreach ($etapas as $etapa) {
            $estado = match (true) {
                $inscripcion === null => 'bloqueada',
                in_array($etapa->id, $aprobadas, true) => 'aprobada',
                $inscripcion->estado === EstadoLegacy::Aprobado => 'aprobada',
                $inscripcion->etapa_actual_id === $etapa->id => 'en_curso',
                default => 'bloqueada',
            };

            $niveles[] = [
                'etapa' => $etapa,
                'estado' => $estado,
                'porcentaje' => match ($estado) {
                    'aprobada' => 100,
                    'en_curso' => $resumen['porcentaje'] ?? 0,
                    default => 0,
                },
            ];
        }

        return collect($niveles);
    }

    /**
     * Cifras de la etapa en curso: horas acumuladas, requisitos cumplidos y el
     * porcentaje de la barra.
     *
     * @return array{porcentaje: int, horas: float, horasRequeridas: int, requisitosCumplidos: int, requisitosTotal: int}|null
     */
    private function resumen(InscripcionPrograma $inscripcion): ?array
    {
        $etapa = $inscripcion->etapaActual;

        if ($etapa === null) {
            return null;
        }

        $requisitos = $etapa->requisitos;
        $cumplidos = $requisitos->filter(fn (RequisitoEtapa $r) => $inscripcion->cumpleRequisito($r))->count();

        $horasRequeridas = (int) $etapa->horas_requeridas;
        $acumuladas = $inscripcion->horasAcumuladas();

        // Las horas cuentan como una condición más junto a cada requisito, pero
        // aportan en proporción: a media asistencia, media condición.
        $fraccionHoras = $horasRequeridas > 0 ? min(1.0, $acumuladas / $horasRequeridas) : 1.0;
        $condiciones = $requisitos->count() + 1;

        return [
            'porcentaje' => (int) round((($cumplidos + $fraccionHoras) / $condiciones) * 100),
            'horas' => $acumuladas,
            'horasRequeridas' => $horasRequeridas,
            'requisitosCumplidos' => $cumplidos,
            'requisitosTotal' => $requisitos->count(),
        ];
    }

    /**
     * Lo que FALTA para certificar la etapa en curso: las horas primero (si no
     * están completas) y después cada requisito del manual sin cumplir. Lo ya
     * cumplido se resume arriba, en la cabecera.
     *
     * @return list<array{texto: string, requisito: RequisitoEtapa|null}>
     */
    private function pendientes(InscripcionPrograma $inscripcion): array
    {
        $etapa = $inscripcion->etapaActual;

        if ($etapa === null) {
            return [];
        }

        $items = [];

        if (! $inscripcion->horasCompletas()) {
            $acumuladas = $this->horas($inscripcion->horasAcumuladas());
            $requeridas = (int) $etapa->horas_requeridas;

            $items[] = [
                'texto' => "Registro de asistencia: {$acumuladas} de {$requeridas} h",
                'requisito' => null,
            ];
        }

        foreach ($etapa->requisitos as $requisito) {
            if ($inscripcion->cumpleRequisito($requisito)) {
                continue;
            }

            $items[] = [
                'texto' => $requisito->descripcion,
                'requisito' => $requisito,
            ];
        }

        return $items;
    }

    /**
     * Cuestionario de la prueba escrita de la etapa en curso, si la exige y
     * todavía no está aprobada. Es lo único accionable por el trainee.
     */
    private function cuestionarioPendiente(InscripcionPrograma $inscripcion): ?Cuestionario
    {
        $requisito = $inscripcion->etapaActual?->requisitos
            ->first(fn (RequisitoEtapa $r) => $r->esAutomatico() && ! $inscripcion->cumpleRequisito($r));

        return $requisito?->cuestionario;
    }

    /**
     * Horas sin decimales cuando son enteras (100 h, no 100,0 h).
     */
    private function horas(float $horas): string
    {
        return rtrim(rtrim(number_format($horas, 1, ',', '.'), '0'), ',');
    }
}
