<?php

namespace App\Livewire\Reportes;

use App\Enums\EstadoCargo;
use App\Models\Cargo;
use App\Models\Matricula;
use App\Models\Sede;
use App\Services\ServicioPagos;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reportes de gestión (solo lectura): distribución por cinturón, altas por mes y
 * comparativa de sedes. Respeta el aislamiento por grupo del tenant. Exporta la
 * comparativa a CSV.
 */
#[Title('Reportes')]
class Reportes extends Component
{
    /**
     * Distribución de matrículas activas por grado (cinturón), de mayor a menor.
     *
     * @return list<array{nombre: string, color: string|null, total: int}>
     */
    protected function distribucionPorCinturon(): array
    {
        $porGrado = Matricula::activas()->with('persona.grado')->get()
            ->groupBy(fn (Matricula $m) => $m->persona->grado->nombre ?? 'Sin grado');

        $filas = [];

        foreach ($porGrado as $nombre => $matriculas) {
            $filas[] = $this->filaCinturon((string) $nombre, $matriculas);
        }

        usort($filas, fn (array $a, array $b) => $b['total'] <=> $a['total']);

        return $filas;
    }

    /**
     * Una fila de la distribución: nombre del cinturón, color y total.
     *
     * @param  Collection<int, Matricula>  $matriculas
     * @return array{nombre: string, color: string|null, total: int}
     */
    private function filaCinturon(string $nombre, Collection $matriculas): array
    {
        return [
            'nombre' => $nombre,
            'color' => $matriculas->first()?->persona?->grado?->color,
            'total' => $matriculas->count(),
        ];
    }

    /**
     * Altas (matrículas creadas) por mes en los últimos 12 meses.
     *
     * @return Collection<int, array{mes: string, total: int}>
     */
    protected function altasPorMes(): Collection
    {
        $desde = CarbonImmutable::now()->startOfMonth()->subMonths(11);

        $porMes = Matricula::where('created_at', '>=', $desde)->get()
            // La consulta ya acota por created_at, así que el bucket de reserva
            // no se usa; está para que la clave del agrupado sea siempre un texto.
            ->groupBy(fn (Matricula $m) => $m->created_at?->format('Y-m') ?? 'sin-fecha')
            ->map->count();

        return collect(range(0, 11))->map(function (int $i) use ($desde, $porMes) {
            $mes = $desde->addMonths($i);

            return ['mes' => $mes->translatedFormat('M'), 'total' => (int) ($porMes[$mes->format('Y-m')] ?? 0)];
        });
    }

    /**
     * Comparativa por sede: activos, morosos e ingresos cobrados del mes.
     *
     * @return Collection<int, array{sede: string, activos: int, morosos: int, cobrado: int}>
     */
    protected function comparativaSedes(ServicioPagos $pagos): Collection
    {
        $periodo = $pagos->periodo();
        $morosos = $pagos->morosos()->groupBy('sede_id')->map->count();

        return Sede::orderBy('nombre')->get()
            ->map(fn (Sede $sede) => $this->filaSede($sede, $periodo, (int) ($morosos[$sede->id] ?? 0)));
    }

    /**
     * Una fila de la comparativa: activos, morosos e ingresos cobrados del mes.
     *
     * @return array{sede: string, activos: int, morosos: int, cobrado: int}
     */
    private function filaSede(Sede $sede, CarbonInterface $periodo, int $morosos): array
    {
        return [
            'sede' => $sede->nombre,
            'activos' => Matricula::activas()->where('sede_id', $sede->id)->count(),
            'morosos' => $morosos,
            'cobrado' => (int) Cargo::where('sede_id', $sede->id)
                ->where('estado', EstadoCargo::Pagado->value)
                ->whereDate('periodo', $periodo)
                ->sum('monto'),
        ];
    }

    public function exportarCsv(ServicioPagos $pagos): StreamedResponse
    {
        $filas = $this->comparativaSedes($pagos);

        return response()->streamDownload(function () use ($filas) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fputcsv($out, ['Sede', 'Activos', 'Morosos', 'Cobrado del mes']);
            foreach ($filas as $f) {
                fputcsv($out, [$f['sede'], $f['activos'], $f['morosos'], $f['cobrado']]);
            }
            fclose($out);
        }, 'comparativa-sedes-'.now()->format('Y-m').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render(ServicioPagos $pagos): View
    {
        $distribucion = collect($this->distribucionPorCinturon());
        $altas = $this->altasPorMes();

        return view('livewire.reportes.reportes', [
            'alumnosActivos' => Matricula::activas()->count(),
            'altasMes' => (int) ($altas->last()['total'] ?? 0),
            'morosos' => $pagos->morosos()->count(),
            'distribucion' => $distribucion,
            'maxDistribucion' => (int) ($distribucion->max('total') ?: 1),
            'altas' => $altas,
            'maxAltas' => (int) ($altas->max('total') ?: 1),
            'comparativa' => $this->comparativaSedes($pagos),
        ]);
    }
}
