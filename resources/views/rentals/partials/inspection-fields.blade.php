{{-- Checklist de componentes + fotos para inspección de salida/entrada --}}
<hr class="my-4">
<h6 class="fw-semibold small text-uppercase text-muted mb-3" style="letter-spacing:.04em;"><i class="bi bi-card-checklist me-1"></i>Checklist de inspección</h6>
<div class="row g-2">
    @foreach(\App\Models\Rentals\RentalInspection::CHECKLIST_ITEMS as $key => $label)
    <div class="col-md-6">
        <div class="d-flex align-items-center gap-2 border rounded-2 px-2 py-1">
            <span class="small flex-grow-1">{{ $label }}</span>
            <select name="checklist[{{ $key }}][condition]" class="form-select form-select-sm" style="width:auto;">
                <option value="">—</option>
                @foreach(\App\Models\Rentals\RentalInspection::CONDITIONS as $cv => $cl)
                <option value="{{ $cv }}">{{ $cl }}</option>
                @endforeach
            </select>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-3">
    <label class="form-label fw-semibold small" for="photos_{{ $prefix }}"><i class="bi bi-camera me-1"></i>Fotos (opcional)</label>
    <input type="file" id="photos_{{ $prefix }}" name="photos[]" class="form-control" accept="image/*" multiple onchange="previewPhotos_{{ $prefix }}(this)">
    <div class="d-flex flex-wrap gap-2 mt-2" id="preview_{{ $prefix }}"></div>
</div>

@push('scripts')
<script>
function previewPhotos_{{ $prefix }}(input) {
    const box = document.getElementById('preview_{{ $prefix }}');
    box.innerHTML = '';
    Array.from(input.files).slice(0, 12).forEach(file => {
        const img = document.createElement('img');
        img.style.cssText = 'width:64px;height:64px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;';
        img.src = URL.createObjectURL(file);
        box.appendChild(img);
    });
}
</script>
@endpush
