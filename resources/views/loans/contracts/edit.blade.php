@extends('layouts.app')

@section('title', 'Contrato de Préstamo')

@section('page')
<div class="container-fluid" style="max-width: 1100px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1"><i class="bi bi-file-earmark-text"></i> Contrato de Préstamo</h1>
            <p class="text-muted mb-0">Préstamo #{{ $loan->id }} · {{ $loan->client?->name ?? 'Sin cliente' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('loans.contract.download.word', $loan) }}" class="btn btn-outline-primary"><i class="bi bi-file-word"></i> Word</a>
            <a href="{{ route('loans.contract.download.excel', $loan) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</a>
            <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
            <a href="{{ route('loans.show', $loan) }}" class="btn btn-light border"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>
    </div>

    <form action="{{ route('loans.contract.update', $loan) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Redacción del contrato</h5>
                    </div>
                    <div class="card-body">
                        <textarea id="content" name="content" class="form-control" rows="18" placeholder="Redacta aquí el contrato...">{{ old('content', $contract?->content) }}</textarea>
                        <small class="text-muted">Tip: puedes pegar texto desde Word y mantener el formato básico.</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Adjuntar documentos</h6>
                    </div>
                    <div class="card-body">
                        <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">PDF, JPG, PNG. Puedes subir varios archivos.</small>
                        <hr>
                        <ul class="list-group list-group-flush">
                            @forelse($contract?->attachments ?? [] as $file)
                                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <span class="small text-truncate" style="max-width: 220px;">{{ $file->file_name }}</span>
                                    <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ asset('storage/' . $file->file_path) }}"><i class="bi bi-eye"></i></a>
                                </li>
                            @empty
                                <li class="list-group-item px-0 text-muted">Sin adjuntos.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Firma digital</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="signature-pad" width="320" height="180" style="width: 100%; border: 1px dashed #adb5bd; border-radius: 8px; background: #fff;"></canvas>
                        <input type="hidden" id="signature_data" name="signature_data">
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" id="clear-signature" class="btn btn-sm btn-light border">Limpiar</button>
                            <button type="button" id="save-signature" class="btn btn-sm btn-outline-primary">Guardar firma en contrato</button>
                        </div>
                        @if($contract?->signature_path)
                            <div class="mt-3">
                                <small class="text-muted d-block">Firma guardada:</small>
                                <img src="{{ asset('storage/' . $contract->signature_path) }}" class="img-fluid rounded border" alt="Firma">
                            </div>
                        @endif
                    </div>
                </div>

                <div class="d-grid">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Guardar contrato</button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
    (() => {
        const textarea = document.querySelector('#content');
        if (textarea) {
            ClassicEditor.create(textarea).catch(() => {});
        }

        const canvas = document.getElementById('signature-pad');
        const ctx = canvas.getContext('2d');
        const hidden = document.getElementById('signature_data');
        const clearBtn = document.getElementById('clear-signature');
        const saveBtn = document.getElementById('save-signature');

        let drawing = false;

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const t = e.touches ? e.touches[0] : e;
            return { x: t.clientX - rect.left, y: t.clientY - rect.top };
        }

        function start(e) {
            drawing = true;
            const p = getPos(e);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            e.preventDefault();
        }

        function move(e) {
            if (!drawing) return;
            const p = getPos(e);
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#1f2937';
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            e.preventDefault();
        }

        function end() {
            drawing = false;
        }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        window.addEventListener('mouseup', end);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        canvas.addEventListener('touchend', end);

        clearBtn.addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hidden.value = '';
        });

        saveBtn.addEventListener('click', () => {
            hidden.value = canvas.toDataURL('image/png');
        });
    })();
</script>
@endpush
@endsection
