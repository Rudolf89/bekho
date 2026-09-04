<?php

namespace App\Livewire\Inscripcion;

use App\Enums\Genero;
use App\Enums\GrupoEtario;
use App\Models\Estudiante;
use App\Models\Sede;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Inscripción de alumno nuevo')]
class InscribirAlumno extends Component
{
    // Lugar de entrenamiento e instructor
    public string $sede_id = '';

    public string $instructor_id = '';

    // Datos del alumno
    public string $nombres = '';

    public string $apellido_paterno = '';

    public string $apellido_materno = '';

    public string $rut = '';

    public ?string $fecha_nacimiento = null;

    public string $genero = '';

    public string $grupo_etario = '';

    // Domicilio
    public string $direccion = '';

    public string $region = '';

    public string $comuna = '';

    // Apoderados y contacto
    public ?string $apoderado_1 = null;

    public ?string $apoderado_2 = null;

    public string $telefono_contacto = '';

    public ?string $telefono_contacto_2 = null;

    public string $email_contacto = '';

    public ?string $email_contacto_2 = null;

    // Pago y declaración
    public string $dia_vencimiento = '';

    public bool $acepto_reglamento = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'sede_id' => ['required', Rule::exists('sedes', 'id')],
            // El instructor debe estar asignado a la sede elegida (pivote sede_user).
            'instructor_id' => ['required', Rule::exists('sede_user', 'user_id')->where('sede_id', $this->sede_id)],
            'nombres' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['required', 'string', 'max:255'],
            'rut' => ['required', 'string', 'max:20'],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'genero' => ['required', Rule::enum(Genero::class)],
            'grupo_etario' => ['required', Rule::enum(GrupoEtario::class)],
            'direccion' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', Rule::in($this->regiones())],
            'comuna' => ['required', 'string', Rule::in($this->comunas())],
            // El apoderado 1 es obligatorio según el grupo etario (Tigers y For
            // Kids); el apoderado 2 siempre es opcional.
            'apoderado_1' => [$this->requiereApoderado() ? 'required' : 'nullable', 'string', 'max:255'],
            'apoderado_2' => ['nullable', 'string', 'max:255'],
            'telefono_contacto' => ['required', 'string', 'max:50'],
            'telefono_contacto_2' => ['nullable', 'string', 'max:50'],
            'email_contacto' => ['required', 'email', 'max:255'],
            'email_contacto_2' => ['nullable', 'email', 'max:255'],
            'dia_vencimiento' => ['required', Rule::in($this->diasVencimiento())],
            'acepto_reglamento' => ['accepted'],
        ];
    }

    /**
     * Al cambiar la sede se limpia el instructor elegido: los instructores
     * disponibles dependen de la sede (pivote sede_user), así que uno de otra
     * sede dejaría de ser válido.
     */
    public function updatedSedeId(): void
    {
        $this->instructor_id = '';
    }

    /**
     * Al cambiar la fecha de nacimiento, sugiere el grupo etario por edad
     * (el instructor lo puede ajustar, por el solapamiento a los 12 años).
     */
    public function updatedFechaNacimiento(): void
    {
        $edad = $this->edad();

        if ($edad !== null) {
            $this->grupo_etario = GrupoEtario::sugerirPorEdad($edad)->value;
        }
    }

    /**
     * Edad actual del alumno (años cumplidos) según la fecha de nacimiento, o
     * null si no hay fecha válida.
     */
    public function edad(): ?int
    {
        if (! $this->fecha_nacimiento) {
            return null;
        }

        try {
            return (int) Carbon::parse($this->fecha_nacimiento)->age;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Meses hasta el próximo cumpleaños (0 si es este mes), o null si no hay fecha.
     */
    protected function mesesHastaProximoCumple(): ?int
    {
        if (! $this->fecha_nacimiento) {
            return null;
        }

        try {
            $nacimiento = Carbon::parse($this->fecha_nacimiento);
        } catch (\Exception) {
            return null;
        }

        $proximo = $nacimiento->copy()->year(now()->year);
        if ($proximo->lessThan(now()->startOfDay())) {
            $proximo->addYear();
        }

        return (int) floor(now()->startOfDay()->diffInMonths($proximo));
    }

    /**
     * Si el alumno está por cumplir (dentro de ~4 meses) la edad que lo pasaría
     * al grupo siguiente, devuelve esa sugerencia para ofrecer el cambio. El
     * campo sigue siendo editable a mano; esto es solo una ayuda.
     *
     * @return array{grupo: GrupoEtario, meses: int, edadProxima: int}|null
     */
    public function sugerenciaProximoGrupo(): ?array
    {
        $edad = $this->edad();

        if ($edad === null || $this->grupo_etario === '') {
            return null;
        }

        $siguiente = GrupoEtario::from($this->grupo_etario)->siguiente();
        if (! $siguiente) {
            return null;
        }

        $meses = $this->mesesHastaProximoCumple();
        if ($meses === null || $meses > 4) {
            return null;
        }

        // Edad que tendrá en el próximo cumpleaños: solo sugiere si con ella
        // alcanza el piso del grupo siguiente.
        $edadProxima = $edad + 1;
        if ($edadProxima < $siguiente->edadMinima()) {
            return null;
        }

        return ['grupo' => $siguiente, 'meses' => $meses, 'edadProxima' => $edadProxima];
    }

    /**
     * Cambia el grupo etario elegido (usado por la sugerencia de "pasar al
     * grupo siguiente" cuando el alumno está por cumplir la edad).
     */
    public function cambiarGrupo(string $grupo): void
    {
        if (GrupoEtario::tryFrom($grupo)) {
            $this->grupo_etario = $grupo;
        }
    }

    /**
     * Al cambiar de región se limpia la comuna elegida.
     */
    public function updatedRegion(): void
    {
        $this->comuna = '';
    }

    /**
     * ¿Se exige apoderado? Según el grupo etario: Tigers y For Kids son siempre
     * menores (apoderado obligatorio); Jóvenes y Adultos puede ser mayor de edad
     * (opcional).
     */
    public function requiereApoderado(): bool
    {
        return in_array(
            $this->grupo_etario,
            [GrupoEtario::Tigers->value, GrupoEtario::ForKids->value],
            true,
        );
    }

    /**
     * Días permitidos para el vencimiento de la mensualidad.
     *
     * @return list<int>
     */
    public function diasVencimiento(): array
    {
        return [1, 5, 10, 15];
    }

    /**
     * @return list<string>
     */
    public function regiones(): array
    {
        return array_keys(config('regiones', []));
    }

    /**
     * Comunas de la región seleccionada.
     *
     * @return list<string>
     */
    public function comunas(): array
    {
        return config('regiones.'.$this->region, []);
    }

    public function inscribir(): void
    {
        $datos = $this->validate();

        // La academia del alumno es la de su sede (funciona también para el
        // admin-plataforma, que gestiona por academia).
        $academiaId = Sede::sinAcademia()->findOrFail($datos['sede_id'])->academia_id;

        Estudiante::create([
            'academia_id' => $academiaId,
            'sede_id' => $datos['sede_id'],
            'instructor_id' => $datos['instructor_id'],
            'nombre' => trim("{$datos['nombres']} {$datos['apellido_paterno']} {$datos['apellido_materno']}"),
            'rut' => $datos['rut'],
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'genero' => $datos['genero'],
            'grupo_etario' => $datos['grupo_etario'],
            'direccion' => $datos['direccion'],
            'region' => $datos['region'],
            'comuna' => $datos['comuna'],
            'apoderado_1' => $datos['apoderado_1'] ?: null,
            'apoderado_2' => $datos['apoderado_2'] ?: null,
            'telefono_contacto' => $datos['telefono_contacto'],
            'telefono_contacto_2' => $datos['telefono_contacto_2'] ?: null,
            'email_contacto' => $datos['email_contacto'],
            'email_contacto_2' => $datos['email_contacto_2'] ?: null,
            'dia_vencimiento' => $datos['dia_vencimiento'],
            'activo' => true,
            'acepto_reglamento' => true,
            'acepto_reglamento_at' => now(),
            // grado_id queda nulo: alumno nuevo => Blanco => Principiantes.
        ]);

        Flux::toast(variant: 'success', text: 'Alumno inscrito correctamente.');

        $this->redirectRoute('estudiantes.index', navigate: true);
    }

    public function render()
    {
        // Los instructores disponibles son los asignados a la sede elegida
        // (pivote sede_user). Sin sede elegida no se muestra ninguno.
        $instructores = $this->sede_id !== ''
            ? User::role(['instructor', 'direccion'])
                ->whereHas('sedes', fn ($q) => $q->whereKey($this->sede_id))
                ->orderBy('name')
                ->get()
            : collect();

        return view('livewire.inscripcion.inscribir-alumno', [
            'sedes' => Sede::orderBy('nombre')->get(),
            'instructores' => $instructores,
            'grupos' => GrupoEtario::cases(),
            'generos' => Genero::cases(),
            'regiones' => $this->regiones(),
            'comunasRegion' => $this->comunas(),
            'diasVencimiento' => $this->diasVencimiento(),
        ]);
    }
}
