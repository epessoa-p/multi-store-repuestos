<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanPayment;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $company = $user->getCurrentCompany();

        // Super admin ves estadísticas globales
        if ($user->is_super_admin) {
            $totalLoans = Loan::count();
            $activeLoans = Loan::where('status', 'active')->count();
            $completedLoans = Loan::where('status', 'finished')->count();
            $overdueLoans = Loan::where('status', 'overdue')->count();
            $totalAmount = Loan::sum('amount');
            $totalPaid = Loan::sum('total_paid');
            $totalPending = $totalAmount - $totalPaid;
            $recentLoans = Loan::with('client')->latest()->limit(10)->get();
        } else {
            // Usuario ve solo de su empresa
            $query = Loan::where('company_id', $company?->id);
            $totalLoans = (clone $query)->count();
            $activeLoans = (clone $query)->where('status', 'active')->count();
            $completedLoans = (clone $query)->where('status', 'finished')->count();
            $overdueLoans = (clone $query)->where('status', 'overdue')->count();
            $totalAmount = (clone $query)->sum('amount');
            $totalPaid = (clone $query)->sum('total_paid');
            $totalPending = $totalAmount - $totalPaid;
            $recentLoans = (clone $query)->with('client')->latest()->limit(10)->get();
        }

        return view('dashboard.index', compact(
            'totalLoans',
            'activeLoans',
            'completedLoans',
            'overdueLoans',
            'totalAmount',
            'totalPaid',
            'totalPending',
            'recentLoans',
            'company'
        ));
    }
}
