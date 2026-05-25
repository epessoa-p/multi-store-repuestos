<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CreditCategory;
use App\Models\CreditCategoryRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreditCategoryController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = CreditCategory::with(['company', 'rules'])->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }

        return view('admin.credit-categories.index', [
            'categories' => $query->paginate(15),
        ]);
    }

    public function create()
    {
        $user = auth()->user();

        return view('admin.credit-categories.create', [
            'companies' => $user->is_super_admin
                ? Company::orderBy('name')->get()
                : collect([$user->getCurrentCompany()])->filter(),
            'creditCategory' => null,
            'rules' => [],
        ]);
    }

    public function store()
    {
        $user = auth()->user();
        $companyId = $user->is_super_admin ? request('company_id') : $user->getCurrentCompany()?->id;

        try {
            $validated = request()->validate([
                'company_id' => 'nullable|exists:companies,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'min_amount' => 'nullable|numeric|min:0',
                'max_amount' => 'nullable|numeric|min:0',
                'active' => 'sometimes|boolean',
                'rules' => 'nullable|array',
                'rules.*.name' => 'nullable|string|max:255',
                'rules.*.interest_rate' => 'nullable|numeric|min:0|max:999',
                'rules.*.interest_period' => 'nullable|in:monthly,quarterly,semiannual,annual',
                'rules.*.term_months_limit' => 'nullable|integer|min:1',
                'rules.*.min_amount' => 'nullable|numeric|min:0',
                'rules.*.max_amount' => 'nullable|numeric|min:0',
                'rules.*.active' => 'nullable|boolean',
            ]);

            DB::transaction(function () use ($validated, $companyId) {
                $category = CreditCategory::create([
                    'company_id' => $companyId,
                    'name' => $validated['name'],
                    'slug' => Str::slug($validated['name']),
                    'description' => $validated['description'] ?? null,
                    'min_amount' => $validated['min_amount'] ?? null,
                    'max_amount' => $validated['max_amount'] ?? null,
                    'active' => request()->boolean('active', true),
                ]);

                foreach (($validated['rules'] ?? []) as $rule) {
                    if (empty($rule['interest_rate']) || empty($rule['term_months_limit'])) {
                        continue;
                    }

                    CreditCategoryRule::create([
                        'company_id' => $companyId,
                        'credit_category_id' => $category->id,
                        'name' => $rule['name'] ?? null,
                        'interest_rate' => $rule['interest_rate'],
                        'interest_period' => $rule['interest_period'] ?? 'monthly',
                        'term_months_limit' => $rule['term_months_limit'],
                        'min_amount' => $rule['min_amount'] ?? null,
                        'max_amount' => $rule['max_amount'] ?? null,
                        'active' => !empty($rule['active']),
                    ]);
                }
            });

            return redirect()->route('credit-categories.index')->with('success', 'Categoría de crédito creada exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al crear categoría de crédito', [
                'company_id' => $companyId,
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'No fue posible crear la categoría.']);
        }
    }

    public function show(CreditCategory $creditCategory)
    {
        $this->authorizeCategory($creditCategory);
        $creditCategory->load('company', 'rules');

        return view('admin.credit-categories.show', compact('creditCategory'));
    }

    public function edit(CreditCategory $creditCategory)
    {
        $this->authorizeCategory($creditCategory);
        $user = auth()->user();

        return view('admin.credit-categories.edit', [
            'creditCategory' => $creditCategory,
            'rules' => $creditCategory->rules()->orderBy('id')->get(),
            'companies' => $user->is_super_admin
                ? Company::orderBy('name')->get()
                : collect([$user->getCurrentCompany()])->filter(),
        ]);
    }

    public function update(CreditCategory $creditCategory)
    {
        $this->authorizeCategory($creditCategory);
        $user = auth()->user();
        $companyId = $user->is_super_admin ? request('company_id', $creditCategory->company_id) : $creditCategory->company_id;

        try {
            $validated = request()->validate([
                'company_id' => 'nullable|exists:companies,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'min_amount' => 'nullable|numeric|min:0',
                'max_amount' => 'nullable|numeric|min:0',
                'active' => 'sometimes|boolean',
                'rules' => 'nullable|array',
                'rules.*.id' => 'nullable|integer',
                'rules.*.name' => 'nullable|string|max:255',
                'rules.*.interest_rate' => 'nullable|numeric|min:0|max:999',
                'rules.*.interest_period' => 'nullable|in:monthly,quarterly,semiannual,annual',
                'rules.*.term_months_limit' => 'nullable|integer|min:1',
                'rules.*.min_amount' => 'nullable|numeric|min:0',
                'rules.*.max_amount' => 'nullable|numeric|min:0',
                'rules.*.active' => 'nullable|boolean',
            ]);

            DB::transaction(function () use ($creditCategory, $validated, $companyId) {
                $creditCategory->update([
                    'company_id' => $companyId,
                    'name' => $validated['name'],
                    'slug' => Str::slug($validated['name']),
                    'description' => $validated['description'] ?? null,
                    'min_amount' => $validated['min_amount'] ?? null,
                    'max_amount' => $validated['max_amount'] ?? null,
                    'active' => request()->boolean('active', false),
                ]);

                $existingIds = $creditCategory->rules()->pluck('id')->all();
                $receivedIds = [];

                foreach (($validated['rules'] ?? []) as $ruleData) {
                    if (empty($ruleData['interest_rate']) || empty($ruleData['term_months_limit'])) {
                        continue;
                    }

                    $payload = [
                        'company_id' => $companyId,
                        'credit_category_id' => $creditCategory->id,
                        'name' => $ruleData['name'] ?? null,
                        'interest_rate' => $ruleData['interest_rate'],
                        'interest_period' => $ruleData['interest_period'] ?? 'monthly',
                        'term_months_limit' => $ruleData['term_months_limit'],
                        'min_amount' => $ruleData['min_amount'] ?? null,
                        'max_amount' => $ruleData['max_amount'] ?? null,
                        'active' => !empty($ruleData['active']),
                    ];

                    if (!empty($ruleData['id']) && in_array((int) $ruleData['id'], $existingIds, true)) {
                        CreditCategoryRule::where('id', $ruleData['id'])->update($payload);
                        $receivedIds[] = (int) $ruleData['id'];
                    } else {
                        $created = CreditCategoryRule::create($payload);
                        $receivedIds[] = $created->id;
                    }
                }

                $toDelete = array_diff($existingIds, $receivedIds);
                if (!empty($toDelete)) {
                    CreditCategoryRule::whereIn('id', $toDelete)->delete();
                }
            });

            return redirect()->route('credit-categories.index')->with('success', 'Categoría de crédito actualizada exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al actualizar categoría de crédito', [
                'category_id' => $creditCategory->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'No fue posible actualizar la categoría.']);
        }
    }

    public function destroy(CreditCategory $creditCategory)
    {
        $this->authorizeCategory($creditCategory);

        try {
            $creditCategory->rules()->delete();
            $creditCategory->delete();

            return redirect()->route('credit-categories.index')->with('success', 'Categoría eliminada exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al eliminar categoría de crédito', [
                'category_id' => $creditCategory->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors(['error' => 'No fue posible eliminar la categoría.']);
        }
    }

    protected function authorizeCategory(CreditCategory $creditCategory): void
    {
        if (!auth()->user()->is_super_admin && $creditCategory->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
