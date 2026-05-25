@extends('layouts.auth')

@section('title', 'Login - Sistema de Préstamos')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="logo"><i class="bi bi-graph-up"></i></div>
            <h1>Sistema de Préstamos</h1>
            <p>Inicia sesión para acceder</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('login.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror"
                           placeholder="correo@ejemplo.com" value="{{ old('email') }}" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
                           placeholder="••••••••" required>
                </div>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember" style="margin: 0; cursor: pointer;">Recuerda mi información</label>
            </div>

            <button type="submit" class="btn btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
            </button>
        </form>

        <div class="mt-4 p-3 bg-light rounded" style="font-size: 0.85rem;">
            <strong>Datos de acceso de demostración:</strong>
            <div class="mt-2">
                <span class="badge bg-info">Super Admin</span>
                <br>Email: <code>superadmin@sistema.com</code>
                <br>Contraseña: <code>Admin@1234</code>
            </div>
            <div class="mt-2">
                <span class="badge bg-success">Admin</span>
                <br>Email: <code>admin@empresademo.com</code>
                <br>Contraseña: <code>Admin@1234</code>
            </div>
        </div>
    </div>
</div>
@endsection
