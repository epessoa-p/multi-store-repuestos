<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\Loyalty\LoyaltyReward;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LoyaltyRewardController extends Controller
{
    public function index()
    {
        $cid = $this->companyScope();

        $rewards = LoyaltyReward::with('product:id,name')
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->orderByDesc('active')
            ->orderBy('points_cost')
            ->paginate(20);

        return view('loyalty.rewards.index', compact('rewards'));
    }

    public function create()
    {
        return view('loyalty.rewards.form', [
            'reward'   => new LoyaltyReward(['active' => true]),
            'products' => $this->products(),
        ]);
    }

    public function store(Request $request)
    {
        $cid = auth()->user()->getCurrentCompany()?->id;
        abort_unless($cid, 403, 'Selecciona una empresa.');

        $data = $this->validateData($request);

        $data['company_id'] = $cid;
        $data['created_by'] = auth()->id();
        $data['active']     = (bool) $request->boolean('active');
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('loyalty/rewards', 'public');
        }

        LoyaltyReward::create($data);

        return redirect()->route('loyalty.rewards.index')->with('success', 'Recompensa creada.');
    }

    public function edit(LoyaltyReward $reward)
    {
        $this->authorizeReward($reward);

        return view('loyalty.rewards.form', [
            'reward'   => $reward,
            'products' => $this->products(),
        ]);
    }

    public function update(Request $request, LoyaltyReward $reward)
    {
        $this->authorizeReward($reward);

        $data = $this->validateData($request);
        $data['active'] = (bool) $request->boolean('active');

        if ($request->hasFile('image')) {
            if ($reward->image) {
                Storage::disk('public')->delete($reward->image);
            }
            $data['image'] = $request->file('image')->store('loyalty/rewards', 'public');
        }

        $reward->update($data);

        return redirect()->route('loyalty.rewards.index')->with('success', 'Recompensa actualizada.');
    }

    public function destroy(LoyaltyReward $reward)
    {
        $this->authorizeReward($reward);
        $reward->delete();

        return back()->with('success', 'Recompensa eliminada.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'points_cost' => 'required|integer|min:1',
            'product_id'  => 'nullable|exists:products,id',
            'stock'       => 'nullable|integer|min:0',
            'image'       => 'nullable|image|max:2048',
        ]);
    }

    private function products()
    {
        $cid = $this->companyScope();
        return Product::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('name')->get(['id', 'name']);
    }

    private function authorizeReward(LoyaltyReward $reward): void
    {
        $user = auth()->user();
        if (!$user->is_super_admin && $reward->company_id !== $user->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function companyScope(): ?int
    {
        $user = auth()->user();
        return $user->is_super_admin ? null : $user->getCurrentCompany()?->id;
    }
}
