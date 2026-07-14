<?php

use App\Livewire\Academias\GestionAcademias;
use App\Livewire\Clases\GestionClases;
use App\Livewire\Estudiantes\GestionEstudiantes;
use App\Livewire\Examenes\GestionConvocatorias;
use App\Livewire\Planillas\GestionPlanillas;
use App\Livewire\Sedes\GestionSedes;
use App\Livewire\Usuarios\GestionUsuarios;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    foreach (['admin-plataforma', 'direccion', 'instructor', 'apoderado'] as $rol) {
        Role::findOrCreate($rol, 'web');
    }
});

function superAdminUi(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin-plataforma');
    $user->forceFill(['two_factor_confirmed_at' => now()])->save();

    return $user;
}

$componentes = [
    GestionUsuarios::class,
    GestionSedes::class,
    GestionAcademias::class,
    GestionEstudiantes::class,
    GestionClases::class,
    GestionConvocatorias::class,
    GestionPlanillas::class,
];

foreach ($componentes as $componente) {
    test("{$componente} renderiza y ordena sin error", function () use ($componente) {
        actingAs(superAdminUi());

        Livewire::test($componente)
            ->assertOk()
            ->call('ordenarPor', 'nombre')
            ->assertOk()
            ->call('ordenarPor', 'nombre')
            ->assertOk();
    });
}
