<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Tenancy\Grupo as GrupoActivo;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();
        $this->configureTenancy();
    }

    /**
     * Aislamiento por grupo (tenancy) a nivel de cola. Grupo guarda el grupo
     * activo en propiedades ESTÁTICAS; en un worker de larga vida ese estado se
     * filtraría de un trabajo al siguiente. Se limpia el grupo activo antes de
     * cada trabajo para que arranque sin tenant (y, al fallar cerrado, no vea
     * otros grupos por accidente). El trabajo que necesite operar sobre todos los
     * grupos debe declararlo con Grupo::comoSistema().
     */
    protected function configureTenancy(): void
    {
        Queue::before(fn () => GrupoActivo::olvidar());
    }

    /**
     * Puertas (gates) transversales.
     */
    protected function configureGates(): void
    {
        // Acceso a la pantalla de exámenes: la abren tanto quien los gestiona
        // (dirección/federación) como quien solo inscribe (instructor). Las
        // acciones de escritura se controlan aparte, dentro de cada componente.
        Gate::define('ver examenes', fn (User $user) => $user->canAny(['gestionar examenes', 'inscribir examenes']));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Fechas y meses en español (p. ej. "julio", no "July").
        Carbon::setLocale('es');
        CarbonImmutable::setLocale('es');

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
