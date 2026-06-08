@php
    $isEdit = isset($vehicle);
    $action = $isEdit ? route('vehicles.update', $vehicle) : route('vehicles.store');
    $method = $isEdit ? 'PUT' : 'POST';
@endphp

@if($errors->any())
<div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i>
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form action="{{ $action }}" method="POST">
    @csrf
    @method($method)

    <div class="row g-4">

        {{-- ── LEFT ─────────────────────────────────────────────────── --}}
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-car-front me-2 text-muted"></i>Datos del vehículo</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="client_id">
                                Cliente <span class="text-danger">*</span>
                            </label>
                            <select id="client_id" name="client_id"
                                    class="form-select @error('client_id') is-invalid @enderror" required>
                                <option value="">— Seleccionar cliente —</option>
                                @foreach($clients as $c)
                                <option value="{{ $c->id }}"
                                    {{ old('client_id', $isEdit ? $vehicle->client_id : '') == $c->id ? 'selected' : '' }}>
                                    {{ $c->full_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('client_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="brand">
                                Marca <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="brand" name="brand"
                                   class="form-control @error('brand') is-invalid @enderror"
                                   value="{{ old('brand', $isEdit ? $vehicle->brand : '') }}"
                                   required maxlength="100"
                                   placeholder="Ej: Honda, Yamaha, Suzuki...">
                            @error('brand')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="model">Modelo</label>
                            <input type="text" id="model" name="model"
                                   class="form-control @error('model') is-invalid @enderror"
                                   value="{{ old('model', $isEdit ? $vehicle->model : '') }}"
                                   maxlength="100"
                                   placeholder="Ej: CB 125F, FZ-S, XR 150L...">
                            @error('model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="plate">Placa</label>
                            <input type="text" id="plate" name="plate"
                                   class="form-control @error('plate') is-invalid @enderror"
                                   value="{{ old('plate', $isEdit ? $vehicle->plate : '') }}"
                                   maxlength="20"
                                   placeholder="Ej: 1234-ABC">
                            @error('plate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="engine_cc">Cilindrada (cc)</label>
                            <input type="number" id="engine_cc" name="engine_cc"
                                   class="form-control @error('engine_cc') is-invalid @enderror"
                                   value="{{ old('engine_cc', $isEdit ? $vehicle->engine_cc : '') }}"
                                   min="0" placeholder="125">
                            @error('engine_cc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="year">Año</label>
                            <input type="number" id="year" name="year"
                                   class="form-control @error('year') is-invalid @enderror"
                                   value="{{ old('year', $isEdit ? $vehicle->year : '') }}"
                                   min="1900" max="{{ now()->year + 2 }}"
                                   placeholder="{{ now()->year }}">
                            @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="color">Color</label>
                            <input type="text" id="color" name="color"
                                   class="form-control @error('color') is-invalid @enderror"
                                   value="{{ old('color', $isEdit ? $vehicle->color : '') }}"
                                   maxlength="60"
                                   placeholder="Ej: Rojo, Negro...">
                            @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="vin">VIN / N° de chasis</label>
                            <input type="text" id="vin" name="vin"
                                   class="form-control @error('vin') is-invalid @enderror"
                                   value="{{ old('vin', $isEdit ? $vehicle->vin : '') }}"
                                   maxlength="100"
                                   placeholder="Número de identificación del vehículo">
                            @error('vin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="notes">Notas</label>
                            <textarea id="notes" name="notes"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Observaciones, historial de mantenimiento, etc.">{{ old('notes', $isEdit ? $vehicle->notes : '') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
            </div>

        </div>

        {{-- ── RIGHT ────────────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top:20px;">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-sliders me-2 text-muted"></i>Configuración</h6>
                </div>
                <div class="card-body p-4">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                               {{ old('active', $isEdit ? $vehicle->active : true) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="active">Vehículo activo</label>
                    </div>
                    <p class="text-muted small mb-0">Los vehículos inactivos no aparecen en las búsquedas principales.</p>
                </div>
            </div>
        </div>

    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('vehicles.index') }}" class="btn btn-light border px-4">
            <i class="bi bi-x-lg me-1"></i>Cancelar
        </a>
        <button type="submit" class="btn btn-primary px-5">
            <i class="bi bi-check-lg me-1"></i>
            {{ $isEdit ? 'Guardar cambios' : 'Registrar vehículo' }}
        </button>
    </div>

</form>
