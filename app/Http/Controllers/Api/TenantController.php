<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantOnboardingService;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(Tenant::with('user', 'activeContract.room')->get());
    }

    public function store(Request $request, TenantOnboardingService $service)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'ktp_number' => 'required|string|max:30|unique:tenants,ktp_number',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'occupation' => 'nullable|string',
            'room_id' => 'required|exists:rooms,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'rent_amount' => 'nullable|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
        ]);

        $result = $service->onboard($data, $request->user());

        return response()->json([
            'tenant' => $result['tenant']->load('user'),
            'contract' => $result['contract'],
            'plain_password' => $result['plain_password'],
        ], 201);
    }
}