<?php

use App\Enums\EstadoAsistencia;
use App\Livewire\Asistencia\TomarAsistencia;
use App\Models\Asistencia;
use App\Models\Clase;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Sede;
use App\Models\User;
use App\Support\Tenancy\Grupo as Tenant;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesPermisosSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->bekho = Grupo::where('nombre', 'BEKHO Power Academy')->first();
    Tenant::set($this->bekho->id);

    $this->sede = Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'Central', 'activo' => true]);
    $this->clase = Clase::create([
        'grupo_id' => $this->bekho->id, 'sede_id' => $this->sede->id, 'nombre' => 'Kids Lunes',
        'grupo_etario' => 'for_kids', 'dia_semana' => 1, 'hora_inicio' => '18:00', 'activo' => true,
    ]);
    $this->alumno = Estudiante::create([
        'grupo_id' => $this->bekho->id, 'nombre' => 'Pedrito', 'sede_id' => $this->sede->id,
        'grupo_etario' => 'for_kids', 'activo' => true,
    ]);

    $this->instructor = User::factory()->create(['grupo_id' => $this->bekho->id]);
    $this->instructor->assignRole('instructor');
});

test('el calendario muestra las clases de la semana', function () {
    Livewire::actingAs($this->instructor)->test(TomarAsistencia::class)
        ->assertSee('Kids Lunes');
});

test('se puede navegar entre semanas y volver a hoy', function () {
    $comp = Livewire::actingAs($this->instructor)->test(TomarAsistencia::class);
    $siguiente = Carbon::parse($comp->get('semanaInicio'))->addWeek()->format('Y-m-d');

    $comp->call('semanaSiguiente')->assertSet('semanaInicio', $siguiente)
        ->call('irAHoy')->assertSet('semanaInicio', now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'));
});

test('abrir una clase del calendario y marcar presente registra la asistencia', function () {
    $lunes = now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

    Livewire::actingAs($this->instructor)->test(TomarAsistencia::class)
        ->call('abrirClase', $this->clase->id, $lunes)
        ->assertSet('claseId', (string) $this->clase->id)
        ->assertSet('fecha', $lunes)
        ->assertSee('Pedrito')
        ->call('marcar', $this->alumno->id, 'presente')
        ->call('volver')
        ->assertSet('claseId', '');

    expect(Asistencia::where('clase_id', $this->clase->id)
        ->where('estudiante_id', $this->alumno->id)
        ->whereDate('fecha', $lunes)
        ->where('estado', EstadoAsistencia::Presente->value)
        ->exists())->toBeTrue();
});
