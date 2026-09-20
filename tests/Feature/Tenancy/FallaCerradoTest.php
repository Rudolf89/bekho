<?php

use App\Models\Grupo;
use App\Models\Sede;
use App\Support\Tenancy\Grupo as Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->bekho = Grupo::create(['nombre' => 'BEKHO', 'activo' => true]);
    $this->otra = Grupo::create(['nombre' => 'OTRA', 'activo' => true]);

    // Sede usa PerteneceGrupo (contenido operativo con grupo_id).
    Tenant::comoSistema(function () {
        Sede::create(['grupo_id' => $this->bekho->id, 'nombre' => 'De BEKHO', 'activo' => true]);
        Sede::create(['grupo_id' => $this->otra->id, 'nombre' => 'De OTRA', 'activo' => true]);
    });
});

afterEach(fn () => Tenant::olvidar());

test('sin grupo fijado, un modelo con PerteneceGrupo no devuelve nada (falla cerrado)', function () {
    Tenant::olvidar();

    expect(Sede::count())->toBe(0);
});

test('con grupo activo y filtro, solo se ve ese grupo', function () {
    Tenant::set($this->bekho->id);
    expect(Sede::count())->toBe(1)
        ->and(Sede::first()->nombre)->toBe('De BEKHO');
});

test('con filtraLecturas=false (admin-plataforma) se ve todo', function () {
    Tenant::set($this->bekho->id, filtraLecturas: false);

    expect(Sede::count())->toBe(2);
});

test('comoSistema ve todos los grupos y restaura el estado al terminar', function () {
    Tenant::olvidar();

    $total = Tenant::comoSistema(fn () => Sede::count());

    expect($total)->toBe(2)
        // Al salir, vuelve a fallar cerrado (el estado se restauró).
        ->and(Tenant::esSistema())->toBeFalse()
        ->and(Sede::count())->toBe(0);
});

test('comoSistema restaura el estado incluso si el callable lanza', function () {
    Tenant::olvidar();

    expect(fn () => Tenant::comoSistema(function () {
        throw new RuntimeException('boom');
    }))->toThrow(RuntimeException::class);

    expect(Tenant::esSistema())->toBeFalse()
        ->and(Sede::count())->toBe(0);
});

test('comoSistema es anidable y respeta el estado anterior', function () {
    Tenant::comoSistema(function () {
        expect(Tenant::esSistema())->toBeTrue();

        Tenant::comoSistema(fn () => expect(Tenant::esSistema())->toBeTrue());

        // Al volver del anidado, sigue en modo sistema.
        expect(Tenant::esSistema())->toBeTrue();
    });

    expect(Tenant::esSistema())->toBeFalse();
});
