<?php

namespace App\Http\Controllers\DocumentTemplates;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use App\Models\Loan;
use App\Models\LoanContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DocumentTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('check-permission:loans.view');
    }

    public function index(Request $request)
    {
        $user    = auth()->user();
        $company = $user->getCurrentCompany();

        $query = DocumentTemplate::latest();

        if (!$user->is_super_admin) {
            $query->where(function ($q) use ($company) {
                $q->where('company_id', $company?->id)
                  ->orWhereNull('company_id');
            });
        }

        $q = trim((string) $request->get('q', ''));
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $type = $request->get('type', '');
        if ($type !== '') {
            $query->where('type', $type);
        }

        $active = $request->get('active', '');
        if ($active !== '') {
            $query->where('active', (bool) $active);
        }

        $templates = $query->paginate(15)->withQueryString();

        return view('document-templates.index', compact('templates', 'q', 'type', 'active'));
    }

    public function create()
    {
        return view('document-templates.create');
    }

    public function store(Request $request)
    {
        $user    = auth()->user();
        $company = $user->getCurrentCompany();

        try {
            $validated = $request->validate([
                'name'        => 'required|string|max:255',
                'type'        => 'required|in:' . implode(',', array_keys(DocumentTemplate::TYPES)),
                'description' => 'nullable|string|max:1000',
                'content'     => 'nullable|string',
                'active'      => 'nullable|boolean',
            ]);

            DocumentTemplate::create([
                'company_id'  => $user->is_super_admin ? null : $company?->id,
                'name'        => $validated['name'],
                'type'        => $validated['type'],
                'description' => $validated['description'] ?? null,
                'content'     => $validated['content'] ?? '',
                'active'      => isset($request->active),
                'created_by'  => $user->id,
            ]);

            return redirect()->route('document-templates.index')
                ->with('success', 'Plantilla creada exitosamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error al crear plantilla', ['message' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'No fue posible guardar la plantilla.']);
        }
    }

    public function show(DocumentTemplate $documentTemplate, Request $request)
    {
        $this->authorizeTemplate($documentTemplate);

        $user    = auth()->user();
        $company = $user->getCurrentCompany();

        $loansQuery = Loan::with('client')->orderBy('id', 'desc')->limit(200);
        if (!$user->is_super_admin) {
            $loansQuery->where('company_id', $company?->id);
        }
        $loans = $loansQuery->get();

        $previewContent = null;
        $previewLoan    = null;

        if ($loanId = $request->integer('loan_id')) {
            $previewLoan = Loan::with('client', 'company', 'branch')->find($loanId);
            if ($previewLoan && ($user->is_super_admin || $previewLoan->company_id === $company?->id)) {
                $previewContent = $documentTemplate->applyToLoan($previewLoan);
            }
        }

        return view('document-templates.show', compact(
            'documentTemplate',
            'loans',
            'previewContent',
            'previewLoan'
        ));
    }

    public function downloadWord(DocumentTemplate $documentTemplate, Request $request)
    {
        $this->authorizeTemplate($documentTemplate);

        [$renderedContent, $loan] = $this->resolveRenderedContent($documentTemplate, $request);

        $title = $documentTemplate->name . ($loan ? ' - Prestamo #' . $loan->id : '');
        $html = $this->buildExportHtml($title, $renderedContent);

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla-' . $documentTemplate->id . '.doc"',
        ]);
    }

    public function exportPdf(DocumentTemplate $documentTemplate, Request $request)
    {
        $this->authorizeTemplate($documentTemplate);

        [$renderedContent, $loan] = $this->resolveRenderedContent($documentTemplate, $request);

        return view('document-templates.pdf', [
            'documentTemplate' => $documentTemplate,
            'renderedContent' => $renderedContent,
            'loan' => $loan,
        ]);
    }

    public function edit(DocumentTemplate $documentTemplate)
    {
        $this->authorizeTemplate($documentTemplate);

        return view('document-templates.edit', compact('documentTemplate'));
    }

    public function update(Request $request, DocumentTemplate $documentTemplate)
    {
        $this->authorizeTemplate($documentTemplate);

        try {
            $validated = $request->validate([
                'name'        => 'required|string|max:255',
                'type'        => 'required|in:' . implode(',', array_keys(DocumentTemplate::TYPES)),
                'description' => 'nullable|string|max:1000',
                'content'     => 'nullable|string',
                'active'      => 'nullable|boolean',
            ]);

            $documentTemplate->update([
                'name'        => $validated['name'],
                'type'        => $validated['type'],
                'description' => $validated['description'] ?? null,
                'content'     => $validated['content'] ?? '',
                'active'      => isset($request->active),
            ]);

            return redirect()->route('document-templates.index')
                ->with('success', 'Plantilla actualizada exitosamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Error al actualizar plantilla', [
                'id'      => $documentTemplate->id,
                'message' => $e->getMessage(),
            ]);
            return back()->withInput()->withErrors(['error' => 'No fue posible actualizar la plantilla.']);
        }
    }

    public function destroy(DocumentTemplate $documentTemplate)
    {
        $this->authorizeTemplate($documentTemplate);

        $documentTemplate->delete();

        return redirect()->route('document-templates.index')
            ->with('success', 'Plantilla eliminada.');
    }

    /**
     * Render the template with a specific loan's data and copy the result
     * into that loan's contract, then redirect to the contract editor.
     */
    public function applyToLoan(Request $request, DocumentTemplate $documentTemplate)
    {
        $this->authorizeTemplate($documentTemplate);

        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
        ]);

        $user    = auth()->user();
        $company = $user->getCurrentCompany();

        $loan = Loan::with('client', 'company', 'branch')->findOrFail($validated['loan_id']);

        if (!$user->is_super_admin && $loan->company_id !== $company?->id) {
            abort(403);
        }

        $renderedContent = $documentTemplate->applyToLoan($loan);

        LoanContract::updateOrCreate(
            ['loan_id' => $loan->id],
            [
                'company_id' => $loan->company_id,
                'content'    => $renderedContent,
                'status'     => 'draft',
            ]
        );

        return redirect()->route('loans.contract.edit', $loan)
            ->with('success', "Plantilla \"{$documentTemplate->name}\" aplicada al préstamo #{$loan->id}.");
    }

    protected function authorizeTemplate(DocumentTemplate $template): void
    {
        $user = auth()->user();
        if ($user->is_super_admin) {
            return;
        }

        $company = $user->getCurrentCompany();
        if ($template->company_id !== null && $template->company_id !== $company?->id) {
            abort(403);
        }
    }

    protected function resolveRenderedContent(DocumentTemplate $documentTemplate, Request $request): array
    {
        $user = auth()->user();
        $company = $user->getCurrentCompany();

        $loan = null;
        $content = (string) $documentTemplate->content;

        if ($loanId = $request->integer('loan_id')) {
            $loan = Loan::with('client', 'company', 'branch')->find($loanId);
            if ($loan && ($user->is_super_admin || $loan->company_id === $company?->id)) {
                $content = $documentTemplate->applyToLoan($loan);
            } else {
                $loan = null;
            }
        }

        return [$content, $loan];
    }

    protected function buildExportHtml(string $title, string $content): string
    {
        return '<html><head><meta charset="UTF-8"></head><body>'
            . '<h2 style="margin:0 0 10px 0;">' . e($title) . '</h2>'
            . '<hr style="margin:0 0 14px 0;">'
            . $content
            . '</body></html>';
    }
}
