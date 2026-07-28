<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use Illuminate\Http\Request;

class MaintenanceRequestController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            MaintenanceRequest::with('tenant.user', 'room.property', 'assignee')->latest()->get()
        );
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
        $data = $request->validate(['assigned_to' => 'required|exists:users,id']);
        $maintenanceRequest->update(['assigned_to' => $data['assigned_to']]);
        return response()->json($maintenanceRequest);
    }
}