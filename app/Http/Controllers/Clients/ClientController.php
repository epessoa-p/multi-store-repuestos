<?php

namespace App\Http\Controllers\Clients;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware('check-permission:loans.view');
    }

    public function index()
    {
        $user = auth()->user();
        $company = $user->getCurrentCompany();
        $search = trim((string) request('q', ''));

        $query = Client::query()
            ->with('company')
            ->withCount('loans')
            ->withSum('loans as total_borrowed', 'amount')
            ->withSum('loans as total_paid', 'total_paid');

        if (!$user->is_super_admin) {
            $query->where('company_id', $company?->id);
        }

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('id_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $clients = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('clients.index', [
            'clients' => $clients,
            'search' => $search,
            'company' => $company,
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $company = $user->getCurrentCompany();

        if (!$user->is_super_admin && !$company) {
            return redirect()->route('clients.index')->withErrors(['error' => 'No tienes una empresa activa para crear clientes.']);
        }

        return view('clients.create', [
            'company' => $company,
        ]);
    }

    public function store()
    {
        $user = auth()->user();
        $company = $user->getCurrentCompany();

        if (!$user->is_super_admin && !$company) {
            return redirect()->route('clients.index')->withErrors(['error' => 'No tienes una empresa activa para crear clientes.']);
        }

        try {
            $validated = request()->validate([
                'name' => 'required|string|max:255',
                'id_number' => [
                    'nullable',
                    'string',
                    'max:30',
                    Rule::unique('clients', 'id_number')->where(fn ($q) => $q->where('company_id', $company?->id)),
                ],
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'active' => 'sometimes|boolean',
            ]);

            $client = Client::create([
                ...$validated,
                'company_id' => $company?->id,
                'active' => request()->boolean('active', true),
            ]);

            return redirect()->route('clients.index')->with('success', "Cliente {$client->name} creado exitosamente.");
        } catch (\Throwable $exception) {
            Log::error('Error al crear cliente', [
                'company_id' => $company?->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'No fue posible crear el cliente.']);
        }
    }

    public function edit(Client $client)
    {
        $user = auth()->user();
        if (!$user->is_super_admin && $client->company_id !== $user->getCurrentCompany()?->id) {
            abort(403);
        }

        return view('clients.edit', compact('client'));
    }

    public function show(Client $client)
    {
        $user = auth()->user();
        if (!$user->is_super_admin && $client->company_id !== $user->getCurrentCompany()?->id) {
            abort(403);
        }

        $client->loadCount('loans');
        $client->loadSum('loans as total_borrowed', 'amount');
        $client->loadSum('loans as total_paid_sum', 'total_paid');

        $loans = $client->loans()
            ->with('product', 'creditCategory')
            ->latest()
            ->get();

        return view('clients.show', compact('client', 'loans'));
    }

    public function update(Client $client)
    {
        $user = auth()->user();
        if (!$user->is_super_admin && $client->company_id !== $user->getCurrentCompany()?->id) {
            abort(403);
        }

        try {
            $validated = request()->validate([
                'name' => 'required|string|max:255',
                'id_number' => [
                    'nullable',
                    'string',
                    'max:30',
                    Rule::unique('clients', 'id_number')
                        ->where(fn ($q) => $q->where('company_id', $client->company_id))
                        ->ignore($client->id),
                ],
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'active' => 'sometimes|boolean',
            ]);

            $client->update([
                ...$validated,
                'active' => request()->boolean('active', false),
            ]);

            return redirect()->route('clients.index')->with('success', "Cliente {$client->name} actualizado exitosamente.");
        } catch (\Throwable $exception) {
            Log::error('Error al actualizar cliente', [
                'client_id' => $client->id,
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['error' => 'No fue posible actualizar el cliente.']);
        }
    }
}
