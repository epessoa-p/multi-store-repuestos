@extends('layouts.app')
@section('title', 'Nueva recepción de vehículo')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-clipboard2-plus me-2 text-danger"></i>Nueva recepción</h1>
            <p class="text-muted mb-0 small">Registra la entrada de un vehículo al taller y crea la orden de trabajo.</p>
        </div>
        <a href="{{ route('workshop.dashboard') }}" class="btn btn-light border">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form action="{{ route('workshop.reception.store') }}" method="POST" id="receptionForm">
        @csrf

        <div class="row g-4">

            {{-- Main column --}}
            <div class="col-lg-8">

                {{-- Cliente y vehículo --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2 text-muted"></i>Cliente y vehículo</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="client_id">
                                    Cliente <span class="text-danger">*</span>
                                </label>
                                <select id="client_id" name="client_id"
                                        class="form-select @error('client_id') is-invalid @enderror"
                                        required onchange="filterVehiclesByClient()">
                                    <option value="">— Seleccionar cliente —</option>
                                    @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('client_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->full_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="vehicle_id">
                                    Vehículo <span class="text-danger">*</span>
                                </label>
                                <select id="vehicle_id" name="vehicle_id"
                                        class="form-select @error('vehicle_id') is-invalid @enderror"
                                        required>
                                    <option value="">— Seleccionar vehículo —</option>
                                    @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}"
                                            data-client="{{ $v->client_id }}"
                                            {{ old('vehicle_id') == $v->id ? 'selected' : '' }}>
                                        {{ $v->display_name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('vehicle_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    ¿No aparece el vehículo?
                                    <a href="{{ route('vehicles.create') }}" target="_blank">Regístralo aquí</a>.
                                </div>
                            </div>

                            @if(isset($branches) && $branches->count() > 1)
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="branch_id">Sucursal</label>
                                <select id="branch_id" name="branch_id"
                                        class="form-select @error('branch_id') is-invalid @enderror">
                                    <option value="">— Seleccionar sucursal —</option>
                                    @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>
                                        {{ $b->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            @endif

                        </div>
                    </div>
                </div>

                {{-- Datos de recepción --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard2 me-2 text-muted"></i>Datos de recepción</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="reception_date">
                                    Fecha de recepción <span class="text-danger">*</span>
                                </label>
                                <input type="date" id="reception_date" name="reception_date"
                                       class="form-control @error('reception_date') is-invalid @enderror"
                                       value="{{ old('reception_date', now()->format('Y-m-d')) }}"
                                       required>
                                @error('reception_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold" for="mileage">Kilometraje</label>
                                <div class="input-group">
                                    <input type="number" id="mileage" name="mileage" min="0"
                                           class="form-control @error('mileage') is-invalid @enderror"
                                           value="{{ old('mileage') }}"
                                           placeholder="0">
                                    <span class="input-group-text bg-light">km</span>
                                </div>
                                @error('mileage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold" for="fuel_level">Nivel de combustible</label>
                                <select id="fuel_level" name="fuel_level"
                                        class="form-select @error('fuel_level') is-invalid @enderror">
                                    <option value="">— Seleccionar —</option>
                                    <option value="vacio" {{ old('fuel_level') === 'vacio' ? 'selected' : '' }}>Vacío</option>
                                    <option value="1/4" {{ old('fuel_level') === '1/4' ? 'selected' : '' }}>1/4</option>
                                    <option value="1/2" {{ old('fuel_level') === '1/2' ? 'selected' : '' }}>1/2</option>
                                    <option value="3/4" {{ old('fuel_level') === '3/4' ? 'selected' : '' }}>3/4</option>
                                    <option value="lleno" {{ old('fuel_level') === 'lleno' ? 'selected' : '' }}>Lleno</option>
                                </select>
                                @error('fuel_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="reported_issue">Falla reportada por el cliente</label>
                                <textarea id="reported_issue" name="reported_issue" rows="3"
                                          class="form-control @error('reported_issue') is-invalid @enderror"
                                          placeholder="Describe la falla o los síntomas reportados por el cliente...">{{ old('reported_issue') }}</textarea>
                                @error('reported_issue')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="received_items">Objetos / accesorios recibidos</label>
                                <textarea id="received_items" name="received_items" rows="2"
                                          class="form-control @error('received_items') is-invalid @enderror"
                                          placeholder="Ej: Llaves de repuesto, documentos, herramienta de abordo...">{{ old('received_items') }}</textarea>
                                @error('received_items')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="notes">Notas adicionales</label>
                                <textarea id="notes" name="notes" rows="2"
                                          class="form-control @error('notes') is-invalid @enderror"
                                          placeholder="Observaciones internas...">{{ old('notes') }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            {{-- Right sidebar --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top:20px">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-person-gear me-2 text-muted"></i>Mecánico asignado</h6>
                    </div>
                    <div class="card-body p-4">
                        <label class="form-label fw-semibold" for="mechanic_id">Mecánico (opcional)</label>
                        <select id="mechanic_id" name="mechanic_id"
                                class="form-select @error('mechanic_id') is-invalid @enderror">
                            <option value="">Sin asignar</option>
                            @foreach($mechanics as $m)
                            <option value="{{ $m->id }}" {{ old('mechanic_id') == $m->id ? 'selected' : '' }}>
                                {{ $m->name }}{{ $m->specialty ? ' — ' . $m->specialty : '' }}
                            </option>
                            @endforeach
                        </select>
                        @error('mechanic_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <p class="text-muted small mt-2 mb-0">Puedes asignar el mecánico más adelante desde la OT.</p>
                    </div>
                    <div class="card-footer bg-white border-top p-4">
                        <div class="d-flex flex-column gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-lg me-1"></i>Registrar recepción
                            </button>
                            <a href="{{ route('workshop.dashboard') }}" class="btn btn-light border w-100">
                                <i class="bi bi-x-lg me-1"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

@push('scripts')
<script>
function filterVehiclesByClient() {
    const clientId = document.getElementById('client_id').value;
    const vehicleSelect = document.getElementById('vehicle_id');
    const options = vehicleSelect.querySelectorAll('option');
    let firstVisible = null;

    options.forEach(opt => {
        if (!opt.value) return; // skip placeholder
        if (!clientId || opt.dataset.client === clientId) {
            opt.style.display = '';
            if (!firstVisible) firstVisible = opt;
        } else {
            opt.style.display = 'none';
        }
    });

    // Reset selection if current vehicle doesn't match
    const current = vehicleSelect.options[vehicleSelect.selectedIndex];
    if (current && current.value && clientId && current.dataset.client !== clientId) {
        vehicleSelect.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Apply filter on load if client is pre-selected (old input)
    filterVehiclesByClient();
});
</script>
@endpush

@endsection
