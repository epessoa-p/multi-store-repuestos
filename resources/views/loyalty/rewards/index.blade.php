@extends('layouts.app')
@section('title', 'Fidelización · Recompensas')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-gift me-2 text-danger"></i>Recompensas</h1>
            <p class="text-muted mb-0 small">Catálogo de premios canjeables por puntos.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-rewards.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('loyalty.rewards.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva recompensa</a>
        @endif
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        @forelse($rewards as $reward)
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card border-0 shadow-sm h-100 {{ $reward->active ? '' : 'opacity-50' }}">
                @if($reward->image_url)
                <img src="{{ $reward->image_url }}" class="card-img-top" style="height:140px;object-fit:cover;" alt="{{ $reward->name }}">
                @else
                <div class="d-flex align-items-center justify-content-center bg-light" style="height:140px;"><i class="bi bi-gift fs-1 text-muted opacity-50"></i></div>
                @endif
                <div class="card-body p-3">
                    <div class="fw-semibold small mb-1">{{ $reward->name }}</div>
                    <div class="badge bg-primary-subtle text-primary border border-primary-subtle mb-2">{{ number_format($reward->points_cost, 0) }} pts</div>
                    <div class="text-muted" style="font-size:.72rem;">
                        @if($reward->product) <i class="bi bi-box-seam me-1"></i>{{ $reward->product->name }}<br>@endif
                        Stock: {{ $reward->stock === null ? 'Ilimitado' : $reward->stock }}
                    </div>
                </div>
                <div class="card-footer bg-white border-top d-flex gap-2 py-2">
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-rewards.edit', auth()->user()->getCurrentCompany()))
                    <a href="{{ route('loyalty.rewards.edit', $reward) }}" class="btn btn-sm btn-light border flex-grow-1"><i class="bi bi-pencil"></i></a>
                    @endif
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-rewards.delete', auth()->user()->getCurrentCompany()))
                    <form action="{{ route('loyalty.rewards.destroy', $reward) }}" method="POST" onsubmit="return confirm('¿Eliminar esta recompensa?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5 text-muted">
                    <i class="bi bi-gift fs-1 d-block mb-2 opacity-25"></i>
                    <p class="mb-0">Aún no hay recompensas. Crea la primera.</p>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $rewards->links() }}</div>
</div>
@endsection
