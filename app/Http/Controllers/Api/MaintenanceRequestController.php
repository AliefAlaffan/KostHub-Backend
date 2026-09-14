<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;

class MaintenanceRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = MaintenanceRequest::with('tenant.user', 'room.property', 'assignee');

        if ($user->isTenant()) {
            $query->whereHas('tenant', fn ($q) => $q->where('user_id', $user->id));
        } else {
            $propertyIds = $user->accessiblePropertyIds();
            $query->whereHas('room', fn ($q) => $q->whereIn('property_id', $propertyIds));
        }

        return response()->json($query->latest()->get());
    }

    /** Hanya tenant yang bisa mengajukan komplain */
    public function store(Request $request)
    {
        $tenant = $request->user()->tenant;
        abort_if(!$tenant, 403, 'Hanya penghuni yang bisa mengajukan komplain.');

        $room = $tenant->activeContract?->room;
        abort_if(!$room, 422, 'Anda tidak memiliki kamar aktif.');

        $data = $request->validate([
            'category' => 'required|in:kerusakan,kebersihan,keamanan,lainnya',
            'priority' => 'required|in:low,medium,high,urgent',
            'description' => 'required|string',
        ]);

        $maintenanceRequest = MaintenanceRequest::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'status' => 'new',
        ]);

        return response()->json($maintenanceRequest, 201);
    }

    public function updateStatus(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $this->authorizeStaffAccess($request, $maintenanceRequest);

        $data = $request->validate([
            'status' => 'required|in:new,in_progress,done,closed',
            'repair_cost' => 'nullable|numeric|min:0',
        ]);

        $maintenanceRequest->update(['status' => $data['status']]);

        // Biaya perbaikan otomatis masuk laporan pengeluaran (sekali saja)
        if (!empty($data['repair_cost']) && !$maintenanceRequest->repair_cost) {
            $maintenanceRequest->update(['repair_cost' => $data['repair_cost']]);

            $maintenanceRequest->room->property->expenses()->create([
                'category' => 'maintenance',
                'amount' => $data['repair_cost'],
                'expense_date' => now()->toDateString(),
                'description' => 'Perbaikan: '.$maintenanceRequest->description,
            ]);
        }

        return response()->json($maintenanceRequest);
    }

    public function assign(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $this->authorizeStaffAccess($request, $maintenanceRequest);

        $data = $request->validate(['assigned_to' => 'required|exists:users,id']);
        $maintenanceRequest->update(['assigned_to' => $data['assigned_to']]);
        return response()->json($maintenanceRequest);
    }

    /** Cuma admin/staff yang property-nya mencakup kamar dari komplain ini. */
    private function authorizeStaffAccess(Request $request, MaintenanceRequest $maintenanceRequest): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isStaff(), 403);

        $propertyIds = $user->accessiblePropertyIds();
        abort_unless(in_array($maintenanceRequest->room->property_id, $propertyIds), 403);
    }
}