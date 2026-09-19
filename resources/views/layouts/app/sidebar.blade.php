<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#f5f2ec] dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            @role('admin-plataforma')
                <div class="mb-2 border-b border-zinc-200 px-2 pb-3 dark:border-zinc-700">
                    <flux:text size="xs" class="mb-1.5 block font-semibold uppercase tracking-wide text-zinc-400">Grupo activo</flux:text>
                    <livewire:selector-grupo />
                </div>
            @endrole

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>

                    @php($noLeidas = auth()->user()->unreadNotifications()->count())
                    <flux:sidebar.item icon="bell" :href="route('notificaciones.index')" :current="request()->routeIs('notificaciones.*')" wire:navigate>
                        Notificaciones
                        @if ($noLeidas > 0)
                            <flux:badge size="sm" color="red" class="ml-auto">{{ $noLeidas }}</flux:badge>
                        @endif
                    </flux:sidebar.item>

                    @can('gestionar usuarios')
                        <flux:sidebar.item icon="users" :href="route('usuarios.index')" :current="request()->routeIs('usuarios.*')" wire:navigate>
                            Usuarios
                        </flux:sidebar.item>
                    @endcan
                    @can('gestionar sedes')
                        <flux:sidebar.item icon="building-office" :href="route('sedes.index')" :current="request()->routeIs('sedes.*')" wire:navigate>
                            Sedes
                        </flux:sidebar.item>
                    @endcan
                    @can('gestionar grupos')
                        <flux:sidebar.item icon="building-library" :href="route('grupos.index')" :current="request()->routeIs('grupos.*')" wire:navigate>
                            Grupos
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>

                @canany(['gestionar alumnos', 'gestionar clases', 'tomar asistencia', 'registrar pagos', 'gestionar examenes', 'gestionar planillas', 'gestionar recompensas', 'gestionar legacy'])
                    <flux:sidebar.group heading="Gestión" class="grid">
                        @can('viewAny', App\Models\Matricula::class)
                            <flux:sidebar.item icon="identification" :href="route('estudiantes.index')" :current="request()->routeIs('estudiantes.*')" wire:navigate>
                                Alumnos
                            </flux:sidebar.item>
                        @endcan
                        @can('gestionar alumnos')
                            <flux:sidebar.item icon="user-plus" :href="route('inscripcion.crear')" :current="request()->routeIs('inscripcion.*')" wire:navigate>
                                Inscribir alumno
                            </flux:sidebar.item>
                        @endcan
                        @can('gestionar clases')
                            <flux:sidebar.item icon="calendar-days" :href="route('clases.index')" :current="request()->routeIs('clases.*')" wire:navigate>
                                Clases
                            </flux:sidebar.item>
                        @endcan
                        @can('tomar asistencia')
                            <flux:sidebar.item icon="clipboard-document-check" :href="route('asistencia.tomar')" :current="request()->routeIs('asistencia.*')" wire:navigate>
                                Asistencia
                            </flux:sidebar.item>
                        @endcan
                        @can('registrar pagos')
                            <flux:sidebar.item icon="banknotes" :href="route('pagos.index')" :current="request()->routeIs('pagos.*')" wire:navigate>
                                Pagos
                            </flux:sidebar.item>
                        @endcan
                        @can('ver examenes')
                            <flux:sidebar.item icon="trophy" :href="route('examenes.index')" :current="request()->routeIs('examenes.*')" wire:navigate>
                                Exámenes
                            </flux:sidebar.item>
                        @endcan
                        @can('gestionar recompensas')
                            <flux:sidebar.item icon="gift" :href="route('recompensas.index')" :current="request()->routeIs('recompensas.index')" wire:navigate>
                                Recompensas
                            </flux:sidebar.item>
                        @endcan
                        @can('gestionar legacy')
                            <flux:sidebar.item icon="academic-cap" :href="route('legacy.index')" :current="request()->routeIs('legacy.*')" wire:navigate>
                                Legacy
                            </flux:sidebar.item>
                        @endcan
                        @can('gestionar planillas')
                            <flux:sidebar.item icon="arrow-path-rounded-square" :href="route('ciclos.index')" :current="request()->routeIs('ciclos.*')" wire:navigate>
                                Ciclos
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="book-open" :href="route('planificador.index')" :current="request()->routeIs('planificador.*')" wire:navigate>
                                Planificador
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="rectangle-stack" :href="route('biblioteca.index')" :current="request()->routeIs('biblioteca.*')" wire:navigate>
                                Biblioteca
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="swatch" :href="route('cinturones.index')" :current="request()->routeIs('cinturones.*')" wire:navigate>
                                Cinturones
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="squares-2x2" :href="route('cuadrantes.index')" :current="request()->routeIs('cuadrantes.*')" wire:navigate>
                                Cuadrantes
                            </flux:sidebar.item>
                            <flux:sidebar.item icon="clipboard-document-list" :href="route('planillas.index')" :current="request()->routeIs('planillas.*')" wire:navigate>
                                Planillas
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endcanany

                @role('apoderado')
                    <flux:sidebar.group heading="Apoderado" class="grid">
                        <flux:sidebar.item icon="users" :href="route('mis-estudiantes.index')" :current="request()->routeIs('mis-estudiantes.*')" wire:navigate>
                            Mis estudiantes
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="gift" :href="route('recompensas.mis-logros')" :current="request()->routeIs('recompensas.mis-logros')" wire:navigate>
                            Logros
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endrole

                @canany(['ver formacion', 'rendir cuestionarios'])
                    <flux:sidebar.group heading="Formación" class="grid">
                        @can('ver formacion')
                            <flux:sidebar.item icon="academic-cap" :href="route('formacion.index')" :current="request()->routeIs('formacion.index') || request()->routeIs('formacion.nivel') || request()->routeIs('formacion.contenido')" wire:navigate>
                                Aprender
                            </flux:sidebar.item>
                        @endcan

                        @can('rendir cuestionarios')
                            <flux:sidebar.item icon="clipboard-document-check" :href="route('cuestionarios.index')" :current="request()->routeIs('cuestionarios.index') || request()->routeIs('cuestionarios.rendir') || request()->routeIs('cuestionarios.mis-intentos') || request()->routeIs('cuestionarios.crear') || request()->routeIs('cuestionarios.editar')" wire:navigate>
                                Cuestionarios
                            </flux:sidebar.item>
                        @endcan

                        @can('ver recompensas')
                            <flux:sidebar.item icon="gift" :href="route('recompensas.mis-logros')" :current="request()->routeIs('recompensas.mis-logros')" wire:navigate>
                                Mis logros
                            </flux:sidebar.item>
                        @endcan

                        @can('gestionar cuestionarios')
                            <flux:sidebar.item icon="chart-bar" :href="route('cuestionarios.resultados')" :current="request()->routeIs('cuestionarios.resultados')" wire:navigate>
                                Resultados
                            </flux:sidebar.item>
                        @endcan

                        @can('gestionar formacion')
                            <flux:sidebar.item icon="cog-6-tooth" :href="route('formacion.admin.niveles')" :current="request()->routeIs('formacion.admin.*')" wire:navigate>
                                Administrar
                            </flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endcanany
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
