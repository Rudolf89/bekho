<?php

namespace App\Livewire;

use App\Enums\EstadoAsistencia;
use App\Enums\TipoPago;
use App\Models\Academia;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Convocatoria;
use App\Models\Estudiante;
use App\Models\Pago;
use App\Models\Sede;
use App\Services\ServicioExamenes;
use App\Services\ServicioPagos;
use App\Support\Tenancy\Academia as Tenant;
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

        // Chip de academia enfocada. Si el tenant filtra lecturas hay una academia
        // acotada (la del usuario, o la elegida por el admin-plataforma); si no,
        // el admin-plataforma está viendo "Todas las academias".
        $academiaActiva = Tenant::filtraLecturas() && Tenant::id()
            ? Academia::find(Tenant::id())
            : null;

        if ($academiaActiva) {
            // Una sede representativa (ya viene acotada a la academia por el scope).
            $sede = Sede::where('activo', true)->orderBy('nombre')->first();
            $chip = $academiaActiva->nombre.($sede && $sede->comuna ? ' · '.$sede->comuna : '');
        } else {
            $chip = 'Todas las academias';
        }

        // KPIs.
        $alumnosActivos = Estudiante::activos()->count();
        $alumnosNuevosMes = Estudiante::activos()
            ->where('created_at', '>=', $hoy->copy()->startOfMonth())->count();

        $clasesHoy = Clase::activas()->with(['sede', 'planilla'])
            ->where('dia_semana', $diaHoy)
            ->orderBy('hora_inicio')->get();

        $esperadosHoy = $clasesHoy->sum(fn (Clase $c) => $c->estudiantesEsperados()->count());
        $presentesHoy = Asistencia::whereDate('fecha', $hoy->toDateString())
            ->where('estado', EstadoAsistencia::Presente->value)->count();

        $pagosMes = Pago::where('tipo', TipoPago::Mensualidad->value)
            ->whereDate('periodo', $pagos->periodo())->count();
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
