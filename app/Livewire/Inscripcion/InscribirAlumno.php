<?php

namespace App\Livewire\Inscripcion;

use App\Enums\Genero;
use App\Enums\GrupoEtario;
use App\Livewire\Concerns\SugiereGrupoEtario;
use App\Models\Estudiante;
use App\Models\Sede;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Inscripción de alumno nuevo')]
class InscribirAlumno extends Component
{
    use SugiereGrupoEtario;

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

    /** Muestra/oculta los datos del segundo apoderado (opcional). */
    public bool $agregarApoderado2 = false;

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
     * Al cambiar de región se limpia la comuna elegida.
     */
    public function updatedRegion(): void
    {
        $this->comuna = '';
    }

    /**
     * Al desmarcar el segundo apoderado se limpian sus datos, para no enviar
     * información de un apoderado que ya no se quiere registrar.
     */
    public function updatedAgregarApoderado2(bool $value): void
    {
        if (! $value) {
            $this->apoderado_2 = null;
            $this->telefono_contacto_2 = null;
            $this->email_contacto_2 = null;
        }
    }

    /**
     * ¿Se exige apoderado? Por defecto sí (la escuela es mayormente de menores):
     * solo es opcional cuando el alumno es del grupo Jóvenes y Adultos, que puede
     * ser mayor de edad. Así el campo queda marcado como obligatorio desde el
     * inicio y no se desalinea con el teléfono/correo, que siempre lo son.
     */
    public function requiereApoderado(): bool
    {
        return $this->grupo_etario !== GrupoEtario::JovenesAdultos->value;
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
