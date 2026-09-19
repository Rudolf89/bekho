<?php

namespace App\Livewire\Inscripcion;

use App\Enums\EstadoMatricula;
use App\Enums\Genero;
use App\Enums\GrupoEtario;
use App\Livewire\Concerns\SugiereGrupoEtario;
use App\Models\Matricula;
use App\Models\Persona;
use App\Models\Sede;
use App\Models\User;
use App\Support\Rut;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
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

        // El grupo del alumno es el de su sede (funciona también para el
        // admin-plataforma, que gestiona por grupo).
        $grupoId = Sede::sinGrupo()->findOrFail($datos['sede_id'])->grupo_id;

        // 1) Persona (identidad): datos personales + apoderado como contacto de
        //    emergencia. El vínculo formal con un apoderado va por tutelas.
        $persona = Persona::create([
            'nombres' => $datos['nombres'],
            'apellido_paterno' => $datos['apellido_paterno'],
            'apellido_materno' => $datos['apellido_materno'],
            'fecha_nacimiento' => $datos['fecha_nacimiento'],
            'genero' => $datos['genero'],
            'telefono' => $datos['telefono_contacto'],
            'email' => $datos['email_contacto'],
            'direccion' => $datos['direccion'],
            'region' => $datos['region'],
            'comuna' => $datos['comuna'],
            'contacto_emergencia_nombre' => $datos['apoderado_1'] ?: null,
            'contacto_emergencia_telefono' => ($datos['telefono_contacto_2'] ?? null) ?: $datos['telefono_contacto'],
            'contacto_emergencia_relacion' => ($datos['apoderado_1'] ?? null) ? 'Apoderado' : null,
            // grado_id queda nulo: alumno nuevo => Blanco => Principiantes.
        ]);

        // 2) Documento (RUT normalizado).
        $persona->documentos()->create([
            'tipo' => 'rut',
            'numero' => Rut::normalizar($datos['rut']) ?? $datos['rut'],
            'pais' => 'CL',
            'principal' => true,
        ]);

        // 3) Matrícula (vínculo con el grupo).
        Matricula::create([
            'grupo_id' => $grupoId,
            'persona_id' => $persona->id,
            'sede_id' => $datos['sede_id'],
            'grupo_etario' => $datos['grupo_etario'],
            'estado' => EstadoMatricula::Activa->value,
            'fecha_ingreso' => now()->toDateString(),
            'instructor_persona_id' => User::find($datos['instructor_id'])?->persona_id,
            'dia_vencimiento' => $datos['dia_vencimiento'],
            'acepto_reglamento_at' => now(),
            // Quién acepta: la persona del alumno (cuando la inscripción cree la
            // persona del apoderado y su tutela, apuntará al responsable del menor).
            'acepto_reglamento_persona_id' => $persona->id,
            'aceptado_por_user_id' => Auth::id(),
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
