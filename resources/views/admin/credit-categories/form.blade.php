<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">{{ $creditCategory ? 'Editar categoría' : 'Nueva categoría de crédito' }}</h1>
            <p class="text-muted mb-0">Diseña reglas por producto con interés mensual, trimestral, semestral o anual.</p>
        </div>
        <a href="{{ route('credit-categories.index') }}" class="btn btn-outline-secondary">Volver</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ $action }}" method="POST" id="credit-category-form">
                @csrf
                @if($method !== 'POST') @method($method) @endif

                <div class="row g-3 mb-4">
                    @if($companies->count() > 1)
                        <div class="col-md-6">
                            <label class="form-label">Empresa</label>
                            <select name="company_id" class="form-select">
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}" {{ (string) old('company_id', $creditCategory?->company_id) === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-6">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $creditCategory?->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Monto probable mínimo</label>
                        <input type="number" step="0.01" name="min_amount" class="form-control" value="{{ old('min_amount', $creditCategory?->min_amount) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Monto probable máximo</label>
                        <input type="number" step="0.01" name="max_amount" class="form-control" value="{{ old('max_amount', $creditCategory?->max_amount) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $creditCategory?->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="active" value="1" {{ old('active', $creditCategory?->active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label">Categoría activa</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0"><i class="bi bi-sliders"></i> Reglas de crédito</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-rule-btn"><i class="bi bi-plus-lg"></i> Agregar regla</button>
                </div>

                <div id="rules-container" class="d-flex flex-column gap-3 mb-4">
                    @php
                        $oldRules = old('rules');
                        $rulesData = is_array($oldRules)
                            ? $oldRules
                            : (is_array($rules) ? $rules : ($rules?->toArray() ?? []));
                    @endphp
                    @forelse($rulesData as $idx => $rule)
                        <div class="border rounded p-3 rule-item">
                            <input type="hidden" name="rules[{{ $idx }}][id]" value="{{ $rule['id'] ?? '' }}">
                            <div class="row g-3">
                                <div class="col-md-3"><label class="form-label">Nombre</label><input type="text" name="rules[{{ $idx }}][name]" class="form-control" value="{{ $rule['name'] ?? '' }}"></div>
                                <div class="col-md-2"><label class="form-label">Interés %</label><input type="number" step="0.01" name="rules[{{ $idx }}][interest_rate]" class="form-control" value="{{ $rule['interest_rate'] ?? '' }}"></div>
                                <div class="col-md-3"><label class="form-label">Periodicidad</label><select name="rules[{{ $idx }}][interest_period]" class="form-select"><option value="monthly" {{ ($rule['interest_period'] ?? '') === 'monthly' ? 'selected' : '' }}>Mensual</option><option value="quarterly" {{ ($rule['interest_period'] ?? '') === 'quarterly' ? 'selected' : '' }}>Trimestral</option><option value="semiannual" {{ ($rule['interest_period'] ?? '') === 'semiannual' ? 'selected' : '' }}>Semestral</option><option value="annual" {{ ($rule['interest_period'] ?? '') === 'annual' ? 'selected' : '' }}>Anual</option></select></div>
                                <div class="col-md-2"><label class="form-label">Plazo tope (meses)</label><input type="number" name="rules[{{ $idx }}][term_months_limit]" class="form-control" value="{{ $rule['term_months_limit'] ?? '' }}"></div>
                                <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-outline-danger w-100 remove-rule-btn"><i class="bi bi-x-lg"></i> Quitar</button></div>
                                <div class="col-md-3"><label class="form-label">Monto mínimo</label><input type="number" step="0.01" name="rules[{{ $idx }}][min_amount]" class="form-control" value="{{ $rule['min_amount'] ?? '' }}"></div>
                                <div class="col-md-3"><label class="form-label">Monto máximo</label><input type="number" step="0.01" name="rules[{{ $idx }}][max_amount]" class="form-control" value="{{ $rule['max_amount'] ?? '' }}"></div>
                                <div class="col-md-3 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="rules[{{ $idx }}][active]" value="1" {{ !empty($rule['active']) ? 'checked' : '' }}><label class="form-check-label">Activa</label></div></div>
                            </div>
                        </div>
                    @empty
                    @endforelse
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar categoría</button>
                    <a href="{{ route('credit-categories.index') }}" class="btn btn-light border">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (() => {
        const container = document.getElementById('rules-container');
        const addBtn = document.getElementById('add-rule-btn');
        let index = container.querySelectorAll('.rule-item').length;

        const template = (i) => `
            <div class="border rounded p-3 rule-item">
                <input type="hidden" name="rules[${i}][id]" value="">
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Nombre</label><input type="text" name="rules[${i}][name]" class="form-control" placeholder="Ej: 10% x 3 meses"></div>
                    <div class="col-md-2"><label class="form-label">Interés %</label><input type="number" step="0.01" name="rules[${i}][interest_rate]" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Periodicidad</label><select name="rules[${i}][interest_period]" class="form-select"><option value="monthly">Mensual</option><option value="quarterly">Trimestral</option><option value="semiannual">Semestral</option><option value="annual">Anual</option></select></div>
                    <div class="col-md-2"><label class="form-label">Plazo tope (meses)</label><input type="number" name="rules[${i}][term_months_limit]" class="form-control"></div>
                    <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-outline-danger w-100 remove-rule-btn"><i class="bi bi-x-lg"></i> Quitar</button></div>
                    <div class="col-md-3"><label class="form-label">Monto mínimo</label><input type="number" step="0.01" name="rules[${i}][min_amount]" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Monto máximo</label><input type="number" step="0.01" name="rules[${i}][max_amount]" class="form-control"></div>
                    <div class="col-md-3 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="rules[${i}][active]" value="1" checked><label class="form-check-label">Activa</label></div></div>
                </div>
            </div>`;

        addBtn?.addEventListener('click', () => {
            container.insertAdjacentHTML('beforeend', template(index++));
        });

        container.addEventListener('click', (e) => {
            const btn = e.target.closest('.remove-rule-btn');
            if (!btn) return;
            btn.closest('.rule-item')?.remove();
        });

        if (container.children.length === 0) {
            addBtn?.click();
        }
    })();
</script>
@endpush
