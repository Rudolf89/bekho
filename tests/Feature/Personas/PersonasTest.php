<?php

use App\Enums\TipoDocumento;
use App\Models\DocumentoPersona;
use App\Models\Persona;
use App\Support\Rut;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('la persona es transversal: no lleva grupo_id', function () {
    expect(Schema::hasColumn('personas', 'grupo_id'))->toBeFalse();
});

test('el nombre completo une nombres y apellidos disponibles', function () {
    $p = Persona::create([
        'nombres' => 'Ana', 'apellido_paterno' => 'Pérez', 'apellido_materno' => 'Soto',
        'fecha_nacimiento' => '2000-01-01',
    ]);

    expect($p->nombreCompleto())->toBe('Ana Pérez Soto');

    $sinMaterno = Persona::create([
        'nombres' => 'Beto', 'apellido_paterno' => 'Rojas', 'fecha_nacimiento' => '2000-01-01',
    ]);

    expect($sinMaterno->nombreCompleto())->toBe('Beto Rojas');
});

test('la edad y la mayoría de edad se derivan de la fecha de nacimiento', function () {
    $menor = Persona::create(['nombres' => 'Niña', 'fecha_nacimiento' => now()->subYears(10)]);
    $mayor = Persona::create(['nombres' => 'Adulta', 'fecha_nacimiento' => now()->subYears(30)]);

    expect($menor->edad())->toBe(10)
        ->and($menor->esMayorDeEdad())->toBeFalse()
        ->and($mayor->edad())->toBe(30)
        ->and($mayor->esMayorDeEdad())->toBeTrue();
});

test('un documento es único por tipo, número y país', function () {
    $p1 = Persona::create(['nombres' => 'Uno', 'fecha_nacimiento' => '1990-01-01']);
    $p2 = Persona::create(['nombres' => 'Dos', 'fecha_nacimiento' => '1990-01-01']);

    DocumentoPersona::create(['persona_id' => $p1->id, 'tipo' => 'rut', 'numero' => '12345678-5', 'pais' => 'CL', 'principal' => true]);

    expect(fn () => DocumentoPersona::create([
        'persona_id' => $p2->id, 'tipo' => 'rut', 'numero' => '12345678-5', 'pais' => 'CL',
    ]))->toThrow(QueryException::class);
});

test('un extranjero puede tener pasaporte y luego RUT (documentos distintos)', function () {
    $p = Persona::create(['nombres' => 'Extranjero', 'fecha_nacimiento' => '1985-05-05']);

    $p->documentos()->create(['tipo' => 'pasaporte', 'numero' => 'X1234567', 'pais' => 'AR', 'principal' => true]);
    $p->documentos()->create(['tipo' => 'rut', 'numero' => '25123456-3', 'pais' => 'CL']);

    expect($p->documentos()->count())->toBe(2)
        ->and($p->documentos()->where('tipo', TipoDocumento::Rut->value)->exists())->toBeTrue();
});

test('el RUT valida su dígito verificador (módulo 11)', function () {
    expect(Rut::esValido('11.111.111-1'))->toBeTrue()
        ->and(Rut::esValido('12345678-5'))->toBeTrue()
        ->and(Rut::esValido('12345678-9'))->toBeFalse()
        ->and(Rut::esValido('7654321-6'))->toBeTrue()   // DV = K se prueba aparte
        ->and(Rut::esValido('no-es-rut'))->toBeFalse();
});

test('el RUT normaliza puntos, guion y dígito K en mayúscula', function () {
    expect(Rut::normalizar('12.345.678-5'))->toBe('12345678-5')
        ->and(Rut::normalizar('0012345678k'))->toBe('12345678-K')
        ->and(Rut::digitoVerificador('11111111'))->toBe('1');
});
