@extends('layouts.app')

@section('title', 'Editar Cliente')

@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-person-gear"></i> Editar Cliente</h1>
            <p class="text-muted mb-0">Actualiza la información del cliente.</p>
        </div>
        <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('clients.update', $client) }}" method="POST" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-6">
                    <label class="form-label">Nombre completo *</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $client->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Documento</label>
                    <input type="text" name="id_number" class="form-control @error('id_number') is-invalid @enderror" value="{{ old('id_number', $client->id_number) }}" placeholder="DUI, NIT, Cédula">
                    @error('id_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $client->phone) }}">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $client->email) }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $client->address) }}">
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Notas</label>
                    <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $client->notes) }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="active" name="active" value="1" {{ old('active', $client->active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="active">Cliente activo</label>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Guardar cambios</button>
                    <a href="{{ route('clients.index') }}" class="btn btn-light border">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
