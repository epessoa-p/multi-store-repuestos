<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    /** Empresa activa del usuario (null si super admin sin empresa). */
    private function currentCompanyId(): ?int
    {
        $user = auth()->user();
        return $user->is_super_admin ? null : $user->getCurrentCompany()?->id;
    }

    /** Verifica que la sucursal pertenezca a la empresa del usuario (o super admin). */
    private function authorizeBranch(Branch $branch): void
    {
        $cid = $this->currentCompanyId();
        if ($cid !== null && $branch->company_id !== $cid) {
            abort(403);
        }
    }

    /** Listado de sucursales con su link/QR de catálogo público. */
    public function index()
    {
        $cid = $this->currentCompanyId();

        $branches = Branch::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->orderBy('name')
            ->get();

        // Garantizar token en cada sucursal (para poder mostrar el link/QR).
        foreach ($branches as $branch) {
            $branch->ensureCatalogToken();
        }

        return view('inventory.catalog.index', compact('branches'));
    }

    /** Activa/desactiva el catálogo público de una sucursal. */
    public function toggle(Branch $branch)
    {
        $this->authorizeBranch($branch);

        $branch->catalog_enabled = !$branch->catalog_enabled;
        $branch->save();

        $estado = $branch->catalog_enabled ? 'activado' : 'desactivado';

        return back()->with('success', "Catálogo de {$branch->name} {$estado}.");
    }

    /** Regenera el token (invalida el enlace anterior) de una sucursal. */
    public function regenerate(Branch $branch)
    {
        $this->authorizeBranch($branch);

        $branch->catalog_token = Str::random(40);
        $branch->save();

        return back()->with('success', "Se generó un nuevo enlace para {$branch->name}. El anterior dejó de funcionar.");
    }
}
