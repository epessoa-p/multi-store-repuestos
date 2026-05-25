<?php

namespace App\Http\Controllers\Loans;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanContract;
use App\Models\LoanContractAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LoanContractController extends Controller
{
    public function __construct()
    {
        $this->middleware('check-permission:loans.view');
    }

    public function edit(Loan $loan)
    {
        $this->authorizeCompany($loan);

        $loan->load('client', 'product', 'contract.attachments');
        $contract = $loan->contract;

        return view('loans.contracts.edit', compact('loan', 'contract'));
    }

    public function update(Request $request, Loan $loan)
    {
        $this->authorizeCompany($loan);

        try {
            $validated = $request->validate([
                'content' => 'nullable|string',
                'lender_signature_data' => 'nullable|string',
                'client_signature_data' => 'nullable|string',
                'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);

            $contract = LoanContract::firstOrCreate(
                ['loan_id' => $loan->id],
                [
                    'company_id' => $loan->company_id,
                    'status' => 'draft',
                ]
            );

            $payload = [
                'content' => $validated['content'] ?? $contract->content,
                'status' => 'draft',
            ];

            // Firma del prestamista
            if (!empty($validated['lender_signature_data']) && str_starts_with($validated['lender_signature_data'], 'data:image/png;base64,')) {
                $encoded = substr($validated['lender_signature_data'], strlen('data:image/png;base64,'));
                $binary = base64_decode(str_replace(' ', '+', $encoded));

                if ($binary !== false) {
                    $path = 'loan-contracts/signatures/lender-' . $loan->id . '-' . now()->format('YmdHis') . '.png';
                    Storage::disk('public')->put($path, $binary);
                    $payload['lender_signature_path'] = $path;
                    $payload['lender_signed_at'] = now();
                    $payload['lender_signed_by'] = auth()->id();
                }
            }

            // Firma del cliente
            if (!empty($validated['client_signature_data']) && str_starts_with($validated['client_signature_data'], 'data:image/png;base64,')) {
                $encoded = substr($validated['client_signature_data'], strlen('data:image/png;base64,'));
                $binary = base64_decode(str_replace(' ', '+', $encoded));

                if ($binary !== false) {
                    $path = 'loan-contracts/signatures/client-' . $loan->id . '-' . now()->format('YmdHis') . '.png';
                    Storage::disk('public')->put($path, $binary);
                    $payload['client_signature_path'] = $path;
                    $payload['client_signed_at'] = now();
                }
            }

            // Si ambas firmas existen, marcar como firmado
            $lenderSigned = !empty($payload['lender_signature_path']) || $contract->lender_signature_path;
            $clientSigned = !empty($payload['client_signature_path']) || $contract->client_signature_path;
            if ($lenderSigned && $clientSigned) {
                $payload['status'] = 'signed';
                $payload['signed_at'] = now();
                $payload['signed_by'] = auth()->id();
            }

            $contract->update($payload);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if (!$file) {
                        continue;
                    }

                    $path = $file->store('loan-contracts/attachments', 'public');

                    LoanContractAttachment::create([
                        'loan_contract_id' => $contract->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'size_bytes' => $file->getSize(),
                    ]);
                }
            }

            return redirect()->route('loans.show', $loan)->with('success', 'Contrato guardado correctamente.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Error al guardar contrato de préstamo', [
                'loan_id' => $loan->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'No fue posible guardar el contrato.']);
        }
    }

    public function downloadPdf(Request $request, Loan $loan)
    {
        $this->authorizeCompany($loan);

        // If request comes from the saved-contract button (GET), require persisted content.
        if (!$request->isMethod('post')) {
            $savedContent = (string) ($loan->contract?->content ?? '');
            if (trim(strip_tags($savedContent)) === '') {
                return redirect()->route('loans.show', $loan)
                    ->with('error', 'No hay contrato guardado para exportar en PDF.');
            }
        }

        $content = $this->resolveContractContent($request, $loan);

        return view('loans.contracts.pdf', compact('content'));
    }

    public function downloadWord(Request $request, Loan $loan)
    {
        $this->authorizeCompany($loan);

        $content = $this->resolveContractContent($request, $loan);

        return response($this->documentHtml($content), 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="contrato-prestamo-' . $loan->id . '.doc"',
        ]);
    }

    public function downloadExcel(Loan $loan)
    {
        $this->authorizeCompany($loan);

        $contract = $loan->contract;
        $content = strip_tags($contract?->content ?? '');

        $table = '<table border="1"><tr><th>Campo</th><th>Valor</th></tr>'
            . '<tr><td>Prestamo</td><td>#' . $loan->id . '</td></tr>'
            . '<tr><td>Cliente</td><td>' . e($loan->client?->name ?? '-') . '</td></tr>'
            . '<tr><td>Monto</td><td>' . number_format((float) $loan->amount, 2) . '</td></tr>'
            . '<tr><td>Total a pagar</td><td>' . number_format((float) $loan->total_to_pay, 2) . '</td></tr>'
            . '<tr><td>Redaccion contrato</td><td>' . e($content) . '</td></tr>'
            . '</table>';

        return response($table, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="contrato-prestamo-' . $loan->id . '.xls"',
        ]);
    }

    protected function documentHtml(string $content): string
    {
        return '<html><head><meta charset="UTF-8"></head><body>'
            . $content
            . '</body></html>';
    }

    protected function resolveContractContent(Request $request, Loan $loan): string
    {
        $fromEditor = (string) $request->input('content', '');
        if (trim($fromEditor) !== '') {
            return $fromEditor;
        }

        return (string) ($loan->contract?->content ?? '');
    }

    protected function authorizeCompany(Loan $loan): void
    {
        if (!auth()->user()->is_super_admin && $loan->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
