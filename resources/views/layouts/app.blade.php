@extends('layouts.base')

@section('content')
@php
    $currentCompany = auth()->user()->getCurrentCompany();
    $activeCompanies = auth()->user()->activeCompanies()->get();
@endphp

<div class="app-shell d-flex">
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <button class="btn btn-link p-0 me-2 d-lg-none text-dark" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebarMobile" aria-controls="appSidebarMobile">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div class="brand-icon"><i class="bi bi-grid-1x2-fill"></i></div>
            <div>
                <div class="brand-title">MATERIAL ADMIN PRO</div>
                <small class="text-muted">Sistema de Prestamos</small>
            </div>
        </div>

        <div class="sidebar-section-title">Interface</div>
        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-house"></i> Overview
                </a>
            </li>
            @if(auth()->user()->hasPermissionInCompany('loans.view', $currentCompany))
                <li class="nav-item">
                    <a class="nav-link app-link {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}">
                        <i class="bi bi-people"></i> Clientes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link app-link {{ request()->routeIs('loans.*') && !request()->routeIs('loans.payments.*') ? 'active' : '' }}" href="{{ route('loans.index') }}">
                        <i class="bi bi-cash-coin"></i> Prestamos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link app-link {{ request()->routeIs('loans.payments.*') ? 'active' : '' }}" href="{{ route('loans.payments.index') }}">
                        <i class="bi bi-receipt-cutoff"></i> Pagos préstamos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link app-link {{ request()->routeIs('document-templates.*') ? 'active' : '' }}" href="{{ route('document-templates.index') }}">
                        <i class="bi bi-file-earmark-ruled"></i> Plantillas
                    </a>
                </li>
            @endif
        </ul>

        <div class="sidebar-section-title mt-4">Administracion</div>
        <ul class="nav flex-column gap-1">
            @if(auth()->user()->is_super_admin)
                <li class="nav-item">
                    <a class="nav-link app-link {{ request()->routeIs('companies.*') ? 'active' : '' }}" href="{{ route('companies.index') }}">
                        <i class="bi bi-building"></i> Empresas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link app-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                        <i class="bi bi-shield-lock"></i> Roles
                    </a>
                </li>
            @endif
            @if(auth()->user()->hasPermissionInCompany('users.view', $currentCompany))
                <li class="nav-item">
                    <a class="nav-link app-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                        <i class="bi bi-person-gear"></i> Usuarios
                    </a>
                </li>
            @endif
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}">
                    <i class="bi bi-diagram-2"></i> Sucursales
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                    <i class="bi bi-box-seam"></i> Productos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('credit-categories.*') ? 'active' : '' }}" href="{{ route('credit-categories.index') }}">
                    <i class="bi bi-tags"></i> Categorías crédito
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}" href="{{ route('warehouses.index') }}">
                    <i class="bi bi-building-add"></i> Almacenes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('cargos.*') ? 'active' : '' }}" href="{{ route('cargos.index') }}">
                    <i class="bi bi-briefcase"></i> Cargos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('personal.*') ? 'active' : '' }}" href="{{ route('personal.index') }}">
                    <i class="bi bi-person-vcard"></i> Personal
                </a>
            </li>
        </ul>
    </aside>

    <main class="app-main">
        <nav class="navbar navbar-expand-lg app-topbar mb-4">
            <div class="container-fluid px-0">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebarMobile" aria-controls="appSidebarMobile">
                        <i class="bi bi-list"></i>
                    </button>
                    <span class="topbar-label">Overview</span>
                    <span class="topbar-separator">|</span>
                    <span class="text-muted small">{{ auth()->user()->is_super_admin ? 'Modo Global' : ($currentCompany?->name ?? 'Sin empresa activa') }}</span>

                    @if(!auth()->user()->is_super_admin && $activeCompanies->count() > 1)
                        <div class="dropdown">
                            <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-buildings"></i> Empresa
                            </button>
                            <ul class="dropdown-menu shadow border-0">
                                @foreach($activeCompanies as $company)
                                    <li>
                                        <form action="{{ route('set-company', $company->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item d-flex justify-content-between align-items-center">
                                                <span>{{ $company->name }}</span>
                                                @if($currentCompany && $currentCompany->id === $company->id)
                                                    <i class="bi bi-check-lg text-success"></i>
                                                @endif
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-2">
                    <div class="dropdown">
                        <button class="btn btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><span class="dropdown-item-text text-muted">{{ auth()->user()->name }}</span></li>
                            <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->email }}</span></li>
                        </ul>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button class="btn btn-logout" type="submit">
                            <i class="bi bi-box-arrow-right"></i> Cerrar sesion
                        </button>
                    </form>
                </div>
            </div>
        </nav>

        @if(isset($breadcrumbs))
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    @foreach($breadcrumbs as $label => $url)
                        @if($loop->last)
                            <li class="breadcrumb-item active">{{ $label }}</li>
                        @else
                            <li class="breadcrumb-item"><a href="{{ $url }}" class="text-decoration-none">{{ $label }}</a></li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endif

        @if($message = session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($message = session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('page')
    </main>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="appSidebarMobile" aria-labelledby="appSidebarMobileLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="appSidebarMobileLabel">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <nav class="p-3">
            <ul class="nav flex-column gap-1">
                <li><a class="nav-link app-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Overview</a></li>
                @if(auth()->user()->hasPermissionInCompany('loans.view', $currentCompany))
                    <li><a class="nav-link app-link {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}">Clientes</a></li>
                    <li><a class="nav-link app-link {{ request()->routeIs('loans.*') && !request()->routeIs('loans.payments.*') ? 'active' : '' }}" href="{{ route('loans.index') }}">Prestamos</a></li>
                    <li><a class="nav-link app-link {{ request()->routeIs('loans.payments.*') ? 'active' : '' }}" href="{{ route('loans.payments.index') }}">Pagos préstamos</a></li>
                    <li><a class="nav-link app-link {{ request()->routeIs('document-templates.*') ? 'active' : '' }}" href="{{ route('document-templates.index') }}">Plantillas</a></li>
                @endif
                @if(auth()->user()->is_super_admin)
                    <li><a class="nav-link app-link {{ request()->routeIs('companies.*') ? 'active' : '' }}" href="{{ route('companies.index') }}">Empresas</a></li>
                    <li><a class="nav-link app-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">Roles</a></li>
                @endif
                @if(auth()->user()->hasPermissionInCompany('users.view', $currentCompany))
                    <li><a class="nav-link app-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">Usuarios</a></li>
                @endif
                <li><a class="nav-link app-link {{ request()->routeIs('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}">Sucursales</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">Productos</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('credit-categories.*') ? 'active' : '' }}" href="{{ route('credit-categories.index') }}">Categorías crédito</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}" href="{{ route('warehouses.index') }}">Almacenes</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('cargos.*') ? 'active' : '' }}" href="{{ route('cargos.index') }}">Cargos</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('personal.*') ? 'active' : '' }}" href="{{ route('personal.index') }}">Personal</a></li>
            </ul>
        </nav>
    </div>
</div>

@push('styles')
<style>
    .app-shell {
        min-height: 100vh;
        background: #f2f2f2;
    }

    .app-sidebar {
        width: 250px;
        background: #f8f8f8;
        border-right: 1px solid #dfdfdf;
        padding: 12px 10px;
        position: sticky;
        top: 0;
        height: 100vh;
    }

    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
        padding: 10px 8px 14px;
        border-bottom: 1px solid #e0e0e0;
    }

    .brand-icon {
        width: 30px;
        height: 30px;
        border-radius: 6px;
        display: grid;
        place-items: center;
        color: #4b4b4b;
        background: #ececec;
        font-size: 0.9rem;
    }

    .brand-title {
        font-weight: 700;
        font-size: 0.68rem;
        letter-spacing: 0.14em;
        line-height: 1.1;
        color: #333;
    }

    .sidebar-section-title {
        color: #828282;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: none;
        letter-spacing: 0;
        padding: 2px 10px 6px;
    }

    .app-link {
        border-radius: 6px;
        padding: 8px 10px;
        color: #464646;
        border: 1px solid transparent;
        font-size: 0.88rem;
        transition: all 0.2s ease;
    }

    .app-link:hover {
        background: #ebebeb;
        color: #202020;
    }

    .app-link.active {
        background: #ffffff;
        border-color: #d7d7d7;
        color: #111;
        font-weight: 600;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    }

    .app-main {
        flex: 1;
        padding: 14px 18px 24px;
    }

    .app-topbar {
        background: #1e1e1e;
        border: 0;
        border-radius: 0;
        padding: 8px 14px;
        margin-left: -18px;
        margin-right: -18px;
        margin-top: -14px;
    }

    .topbar-label {
        color: #f2f2f2;
        font-size: 0.82rem;
        font-weight: 500;
    }

    .topbar-separator {
        color: #8f8f8f;
        font-size: 0.8rem;
    }

    .btn-icon {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        border: 1px solid #3a3a3a;
        background: #232323;
        color: #e8e8e8;
        display: grid;
        place-items: center;
        padding: 0;
    }

    .btn-icon:hover {
        background: #2d2d2d;
        color: #fff;
    }

    .btn-logout {
        border: 1px solid #474747;
        background: #2a2a2a;
        color: #f0f0f0;
        border-radius: 8px;
        padding: 6px 10px;
        font-size: 0.82rem;
    }

    .btn-logout:hover {
        background: #363636;
        color: #fff;
    }

    @media (max-width: 991.98px) {
        .app-sidebar {
            display: none;
        }

        .app-main {
            padding: 16px;
        }

        .app-topbar {
            margin-left: -16px;
            margin-right: -16px;
            margin-top: -16px;
        }
    }
</style>
@endpush
@endsection
