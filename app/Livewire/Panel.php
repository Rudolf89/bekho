<?php

namespace App\Livewire;

use App\Enums\EstadoAsistencia;
use App\Enums\EstadoPago;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Convocatoria;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Sede;
use App\Services\ServicioExamenes;
use App\Services\ServicioPagos;
use App\Support\Tenancy\Grupo as Tenant;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Resumen')]
class Panel extends Component
{
    public function render(ServicioPagos $pagos, ServicioExamenes $examenes)
    {
        $usuario = Auth::user();
        $hoy = now();
        $diaHoy = (int) $hoy->dayOfWeekIso; // 1 = lunes … 7 = domingo

        // Chip de grupo enfocada. Si el tenant filtra lecturas hay un grupo
        // acotada (la del usuario, o la elegida por el admin-plataforma); si no,
        // el admin-plataforma está viendo "Todos los grupos".
        $grupoActivo = Tenant::filtraLecturas() && Tenant::id()
            ? Grupo::find(Tenant::id())
            : null;

        if ($grupoActivo) {
            // Una sede representativa (ya viene acotada a el grupo por el scope).
            $sede = Sede::where('activo', true)->orderBy('nombre')->first();
            $chip = $grupoActivo->nombre.($sede && $sede->comuna ? ' · '.$sede->comuna : '');
        } else {
            $chip = 'Todos los grupos';
        }

        // KPIs (por matrícula: el vínculo alumno ↔ grupo).
        $alumnosActivos = Matricula::activas()->count();
        $alumnosNuevosMes = Matricula::activas()
            ->where('created_at', '>=', $hoy->copy()->startOfMonth())->count();

        // Clases con un horario hoy; se anota la hora de inicio de ese día para
        // mostrarla y ordenar la agenda.
        $clasesHoy = Clase::activas()->with(['sede', 'planilla', 'horarios'])
            ->whereHas('horarios', fn ($q) => $q->where('dia_semana', $diaHoy))
            ->get()
            ->map(function (Clase $c) use ($diaHoy) {
                $h = $c->horarios->firstWhere(fn ($h) => $h->dia_semana->value === $diaHoy);
                $c->setAttribute('hora_hoy', $h ? substr((string) $h->hora_inicio, 0, 5) : null);

                return $c;
            })
            ->sortBy('hora_hoy')
            ->values();

        $esperadosHoy = $clasesHoy->sum(fn (Clase $c) => $c->matriculasEsperadas()->count());
        $presentesHoy = Asistencia::whereDate('fecha', $hoy->toDateString())
            ->where('estado', EstadoAsistencia::Presente->value)->count();

        $pagosMes = Pago::where('estado', EstadoPago::Verificado->value)
            ->whereBetween('fecha_pago', [$hoy->copy()->startOfMonth(), $hoy->copy()->endOfMonth()])
            ->count();
        $porCobrar = $pagos->morosos()->count();

        $proximaConvocatoria = Convocatoria::whereDate('fecha', '>=', $hoy->toDateString())
            ->withCount('inscripciones')->orderBy('fecha')->first();

        // Mi progreso de collar (conteo en cascada del usuario).
        $conteoCollar = $examenes->conteoEnCascada($usuario);
        $collar = $examenes->collarDe($usuario);

        // Por revisar.
        $porRevisar = [];
        if ($porCobrar > 0) {
            $porRevisar[] = ['color' => 'amber', 'texto' => "{$porCobrar} mensualidades por cobrar este mes."];
        }
        if ($proximaConvocatoria) {
            $porRevisar[] = ['color' => 'blue', 'texto' => "Examen {$proximaConvocatoria->fecha->format('d-m')}: {$proximaConvocatoria->inscripciones_count} inscritos."];
        }
        if ($alumnosNuevosMes > 0) {
            $porRevisar[] = ['color' => 'green', 'texto' => "{$alumnosNuevosMes} alumnos nuevos este mes."];
        }

        return view('livewire.panel', [
            'chip' => $chip,
            'alumnosActivos' => $alumnosActivos,
            'alumnosNuevosMes' => $alumnosNuevosMes,
            'presentesHoy' => $presentesHoy,
            'esperadosHoy' => $esperadosHoy,
            'clasesHoy' => $clasesHoy,
            'pagosMes' => $pagosMes,
            'porCobrar' => $porCobrar,
            'proximaConvocatoria' => $proximaConvocatoria,
            'conteoCollar' => $conteoCollar,
            'collar' => $collar,
            'porRevisar' => $porRevisar,
            'puedeAsistencia' => $usuario->can('tomar asistencia'),
        ]);
    }
}
