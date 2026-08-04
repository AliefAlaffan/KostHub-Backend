<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Room;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function occupancy(Request $request)
    {
        $total = Room::count();
        $occupied = Room::where('status', 'occupied')->count();
        $available = Room::where('status', 'available')->count();

        return response()->json([
            'total_rooms' => $total,
            'occupied' => $occupied,
            'available' => $available,
            'occupancy_rate' => $total > 0 ? round($occupied / $total * 100, 1) : 0,
        ]);
    }

    public function revenue(Request $request)
    {
        $total = Invoice::where('status', 'paid')->sum('total_amount');

        $monthly = Invoice::where('status', 'paid')
            ->selectRaw('period, SUM(total_amount) as total')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json(['total_revenue' => $total, 'monthly' => $monthly]);
    }

    public function outstandingInvoices(Request $request)
    {
        $invoices = Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->with('contract.tenant.user', 'contract.room')
            ->get();

        return response()->json([
            'count' => $invoices->count(),
            'total_outstanding' => $invoices->sum('total_amount'),
            'invoices' => $invoices,
        ]);
    }

    public function expenses(Request $request)
    {
        $expenses = Expense::all();

        return response()->json([
            'total_expenses' => $expenses->sum('amount'),
            'by_category' => $expenses->groupBy('category')->map->sum('amount'),
        ]);
    }

    public function dashboardSummary(Request $request)
    {
        $propertyIds = $this->scopedPropertyIds($request);

        $totalRooms = \App\Models\Room::whereIn('property_id', $propertyIds)->count();
        $occupied = \App\Models\Room::whereIn('property_id', $propertyIds)->where('status', 'occupied')->count();
        $available = \App\Models\Room::whereIn('property_id', $propertyIds)->where('status', 'available')->count();

        $totalRevenue = \App\Models\Invoice::whereHas('contract.room', fn ($q) => $q->whereIn('property_id', $propertyIds))
            ->where('status', 'paid')->sum('total_amount');

        $monthlyRevenue = \App\Models\Invoice::whereHas('contract.room', fn ($q) => $q->whereIn('property_id', $propertyIds))
            ->where('status', 'paid')
            ->selectRaw('period, SUM(total_amount) as total')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $outstandingInvoices = \App\Models\Invoice::whereHas('contract.room', fn ($q) => $q->whereIn('property_id', $propertyIds))
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->with('contract.tenant.user', 'contract.room')
            ->get();

        return response()->json([
            'occupancy' => [
                'total_rooms' => $totalRooms,
                'occupied' => $occupied,
                'available' => $available,
                'occupancy_rate' => $totalRooms > 0 ? round($occupied / $totalRooms * 100, 1) : 0,
            ],
            'revenue' => [
                'total_revenue' => $totalRevenue,
                'monthly' => $monthlyRevenue,
            ],
            'outstanding' => [
                'count' => $outstandingInvoices->count(),
                'total_outstanding' => $outstandingInvoices->sum('total_amount'),
                'invoices' => $outstandingInvoices,
            ],
        ]);
    }
}