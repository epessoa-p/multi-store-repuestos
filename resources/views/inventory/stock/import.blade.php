@extends('layouts.app')

@section('title', 'Migrar inventario desde Excel')

@section('page')
<div class="container-fluid">

    {{-- ── HEADER ─────────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4">
                <i class="bi bi-cloud-upload me-2 text-danger"></i>Migrar inventario
            </h1>
            <p class="text-muted mb-0 small">
                Carga masiva de productos y stock desde archivo Excel (.xlsx / .xls).
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('inventory.stock.template') }}"
               class="btn btn-light border">
                <i class="bi bi-file-earmark-arrow-down me-1"></i>Descargar plantilla
            </a>
            <a href="{{ route('inventory.stock') }}"
               class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i>Volver al inventario
            </a>
        </div>
    </div>

    {{-- ── IMPORT RESULT ─────────────────────────────────────────────── --}}
    @if(session('import_result'))
    @php $r = session('import_result'); @endphp
    <div class="alert border-0 shadow-sm mb-4
                {{ ($r['created'] ?? 0) + ($r['updated'] ?? 0) > 0 ? 'alert-success' : 'alert-secondary' }}">
        <div class="d-flex align-items-start gap-3 flex-wrap">
            <i class="bi
               {{ ($r['created'] ?? 0) + ($r['updated'] ?? 0) > 0 ? 'bi-check-circle-fill' : 'bi-info-circle-fill' }}
               fs-4 mt-1 flex-shrink-0"></i>
            <div>
                <div class="fw-semibold mb-2">Migración completada</div>
                <div class="d-flex gap-2 flex-wrap" style="font-size:.88rem;">
                    <span class="badge bg-success fs-6 fw-normal px-3 py-2">
                        <i class="bi bi-plus-circle me-1"></i>{{ $r['created'] ?? 0 }} creados
                    </span>
                    <span class="badge bg-primary fs-6 fw-normal px-3 py-2">
                        <i class="bi bi-arrow-repeat me-1"></i>{{ $r['updated'] ?? 0 }} actualizados
                    </span>
                </div>
            </div>
        </div>
        @if(!empty($r['errors']))
        <hr class="my-3">
        <div class="small text-danger">
            <div class="fw-semibold mb-1">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Filas con error ({{ count($r['errors']) }}):
            </div>
            <ul class="mb-0 ps-3 mt-1">
                @foreach($r['errors'] as $err)
                <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif

    {{-- ── VALIDATION ERRORS ───────────────────────────────────────────── --}}
    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mb-4 border-0 shadow-sm">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ $errors->first() }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- ── TWO-COLUMN LAYOUT ──────────────────────────────────────────── --}}
    <div class="row g-4">

        {{-- LEFT: Upload form --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-upload me-2 text-muted"></i>Seleccionar archivo
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('inventory.stock.import.process') }}"
                          method="POST"
                          enctype="multipart/form-data"
                          id="importForm">
                        @csrf

                        {{-- Warehouse selector (REQUIRED) --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="warehouse_id">
                                Almacén destino
                                <span class="text-danger">*</span>
                            </label>
                            <select name="warehouse_id"
                                    id="warehouse_id"
                                    class="form-select @error('warehouse_id') is-invalid @enderror"
                                    required>
                                <option value="" disabled selected>Selecciona el almacén destino…</option>
                                @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}"
                                        {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                    {{ $wh->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('warehouse_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="text-muted mt-1" style="font-size:.78rem;">
                                La columna "Cantidad" del archivo fijará el stock en este almacén.
                            </div>
                        </div>

                        {{-- Dropzone --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="file">
                                Archivo Excel
                            </label>
                            <div id="dropZone"
                                 class="rounded-3 p-5 text-center"
                                 style="border:2px dashed #ddd;cursor:pointer;background:#fafafa;transition:border-color .2s,background .2s;">
                                <i class="bi bi-file-earmark-spreadsheet d-block mb-2 text-muted opacity-50"
                                   style="font-size:2.4rem;"></i>
                                <div class="fw-semibold mb-1">Arrastra el archivo aquí</div>
                                <div class="text-muted small mb-3">o haz clic para seleccionarlo</div>
                                <input type="file"
                                       id="file"
                                       name="file"
                                       class="d-none"
                                       accept=".xlsx,.xls">
                                <button type="button"
                                        class="btn btn-light border px-4"
                                        onclick="document.getElementById('file').click()">
                                    <i class="bi bi-folder2-open me-1"></i>Explorar archivos
                                </button>
                            </div>

                            {{-- File selected indicator --}}
                            <div id="fileSelected" class="mt-2 d-none">
                                <div class="d-flex align-items-center gap-2 p-2 rounded-3 border bg-light">
                                    <i class="bi bi-file-earmark-spreadsheet text-success fs-5"></i>
                                    <span id="fileName"
                                          class="fw-semibold flex-grow-1 text-truncate"
                                          style="font-size:.88rem;"></span>
                                    <button type="button"
                                            class="btn btn-sm btn-light border"
                                            id="clearFile"
                                            title="Quitar archivo">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="text-muted mt-1" style="font-size:.78rem;">
                                Formatos aceptados: .xlsx, .xls &mdash; Tamaño máximo: 5 MB
                            </div>
                        </div>

                        {{-- Submit --}}
                        <button type="submit"
                                class="btn btn-primary w-100 py-2"
                                id="btnImport"
                                disabled>
                            <span id="btnIcon"><i class="bi bi-cloud-upload me-1"></i></span>
                            <span class="spinner-border spinner-border-sm me-1 d-none"
                                  id="btnSpinner"></span>
                            <span id="btnText">Selecciona un archivo para continuar</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- RIGHT: Instructions + column reference --}}
        <div class="col-lg-5">

            {{-- Column reference --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-table me-2 text-muted"></i>Columnas del archivo
                    </h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width:46px;">Col.</th>
                                <th>Campo</th>
                                <th class="pe-4">Ejemplo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-dark" style="font-size:.72rem;">A</span>
                                </td>
                                <td class="fw-semibold small">
                                    Nombre producto <span class="text-danger">*</span>
                                </td>
                                <td class="text-muted small pe-4">(01) Carburador Trueno</td>
                            </tr>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-dark" style="font-size:.72rem;">B</span>
                                </td>
                                <td class="fw-semibold small">Categoría</td>
                                <td class="text-muted small pe-4">Carburación (999)</td>
                            </tr>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-dark" style="font-size:.72rem;">C</span>
                                </td>
                                <td class="fw-semibold small">Marca</td>
                                <td class="text-muted small pe-4">Trueno</td>
                            </tr>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-dark" style="font-size:.72rem;">D</span>
                                </td>
                                <td class="fw-semibold small">Modelo(s)</td>
                                <td class="text-muted small pe-4">CG150, CG200</td>
                            </tr>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-dark" style="font-size:.72rem;">E</span>
                                </td>
                                <td class="fw-semibold small">Notas</td>
                                <td class="text-muted small pe-4">Incluye filtro</td>
                            </tr>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-dark" style="font-size:.72rem;">F</span>
                                </td>
                                <td class="fw-semibold small">Costo</td>
                                <td class="text-muted small pe-4">104</td>
                            </tr>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-dark" style="font-size:.72rem;">G</span>
                                </td>
                                <td class="fw-semibold small">Precio</td>
                                <td class="text-muted small pe-4">140</td>
                            </tr>
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-dark" style="font-size:.72rem;">H</span>
                                </td>
                                <td class="fw-semibold small">Cantidad</td>
                                <td class="text-muted small pe-4">25</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Behavior notes --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-lightbulb me-2 text-warning"></i>Comportamiento del importador
                    </h6>
                </div>
                <div class="card-body p-4">
                    <ul class="list-unstyled mb-0" style="font-size:.84rem;">
                        <li class="d-flex gap-2 mb-3">
                            <i class="bi bi-plus-circle text-success flex-shrink-0 mt-1"></i>
                            <span>
                                <strong>Crea</strong> el producto, categoría y marca si no existen.
                                El código entre paréntesis de categoría y producto se guarda como código.
                            </span>
                        </li>
                        <li class="d-flex gap-2 mb-3">
                            <i class="bi bi-arrow-repeat text-primary flex-shrink-0 mt-1"></i>
                            <span>
                                <strong>Actualiza</strong> costo, precio y cantidad de los existentes
                                (coincide por nombre, sin distinguir mayúsculas).
                            </span>
                        </li>
                        <li class="d-flex gap-2 mb-3">
                            <i class="bi bi-bicycle text-secondary flex-shrink-0 mt-1"></i>
                            <span>
                                La columna <strong>Modelo(s)</strong> (separados por coma) se
                                <strong>busca o crea</strong> en el catálogo de Modelos de motos
                                y se asocia al producto.
                            </span>
                        </li>
                        <li class="d-flex gap-2 mb-3">
                            <i class="bi bi-building text-secondary flex-shrink-0 mt-1"></i>
                            <span>
                                La columna <strong>Cantidad</strong> <em>fija</em> el stock del
                                almacén seleccionado (no acumula, reemplaza).
                            </span>
                        </li>
                        <li class="d-flex gap-2">
                            <i class="bi bi-eraser text-danger flex-shrink-0 mt-1"></i>
                            <span>
                                Los códigos entre paréntesis como <code>(01)</code> o <code>(999)</code>
                                se eliminan automáticamente del nombre visible.
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const input      = document.getElementById('file');
    const dropZone   = document.getElementById('dropZone');
    const selected   = document.getElementById('fileSelected');
    const fileName   = document.getElementById('fileName');
    const clearBtn   = document.getElementById('clearFile');
    const btn        = document.getElementById('btnImport');
    const btnText    = document.getElementById('btnText');
    const btnIcon    = document.getElementById('btnIcon');
    const btnSpinner = document.getElementById('btnSpinner');
    const whSelect   = document.getElementById('warehouse_id');

    const MAX_BYTES = 5 * 1024 * 1024; // 5 MB

    function canSubmit() {
        return input.files && input.files.length > 0;
    }

    function updateBtn() {
        btn.disabled = !canSubmit();
        if (canSubmit()) {
            btnText.textContent = 'Migrar inventario';
        } else {
            btnText.textContent = 'Selecciona un archivo para continuar';
        }
    }

    function setFile(file) {
        if (!file) return;

        if (file.size > MAX_BYTES) {
            alert('El archivo supera los 5 MB permitidos.');
            return;
        }

        fileName.textContent = file.name;
        selected.classList.remove('d-none');
        dropZone.style.borderColor = 'var(--brand-black, #0a0a0a)';
        dropZone.style.background  = '#f5f5f5';
        updateBtn();
    }

    function clearFile() {
        input.value = '';
        selected.classList.add('d-none');
        dropZone.style.borderColor = '#ddd';
        dropZone.style.background  = '#fafafa';
        updateBtn();
    }

    // Input events
    input.addEventListener('change', function () { setFile(input.files[0]); });
    clearBtn.addEventListener('click', clearFile);

    // Dropzone click → forward to hidden input (avoid infinite loop)
    dropZone.addEventListener('click', function (e) {
        if (e.target === clearBtn || clearBtn.contains(e.target)) return;
        input.click();
    });

    // Drag & drop
    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropZone.style.borderColor = 'var(--brand-black, #0a0a0a)';
        dropZone.style.background  = '#f0f0f0';
    });

    dropZone.addEventListener('dragleave', function () {
        if (!input.files[0]) {
            dropZone.style.borderColor = '#ddd';
            dropZone.style.background  = '#fafafa';
        }
    });

    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        const f = e.dataTransfer.files[0];
        if (!f) return;
        const ext = f.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls'].includes(ext)) {
            alert('Solo se aceptan archivos .xlsx o .xls');
            dropZone.style.borderColor = '#ddd';
            dropZone.style.background  = '#fafafa';
            return;
        }
        const dt = new DataTransfer();
        dt.items.add(f);
        input.files = dt.files;
        setFile(f);
    });

    // Submit: show spinner
    document.getElementById('importForm').addEventListener('submit', function () {
        if (!canSubmit()) { return; }
        btn.disabled = true;
        btnIcon.classList.add('d-none');
        btnSpinner.classList.remove('d-none');
        btnText.textContent = 'Procesando…';
    });

})();
</script>
@endpush

@endsection
