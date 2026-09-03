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
        $user = $request->user();
        $query = Tenant::query()->with('user', 'activeContract.room.property');

        if ($user->isStaff() || $user->isAdmin()) {
            $propertyIds = $user->accessiblePropertyIds();
            $query->whereHas('contracts.room', fn ($q) => $q->whereIn('property_id', $propertyIds));
        }

        if ($request->filled('property_id')) {
            $query->whereHas(
                'activeContract.room',
                fn ($q) => $q->where('property_id', $request->integer('property_id'))
            );
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($q2) => $q2->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('activeContract.room', fn ($q2) => $q2->where('room_number', 'like', "%{$search}%"));
            });
        }

        return response()->json($query->get());
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

    public function show(Request $request, Tenant $tenant)
    {
        return response()->json(
            $tenant->load('user', 'documents', 'contracts.room.property')
        );
    }

    public function uploadDocuments(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'doc_type' => ['required', \Illuminate\Validation\Rule::in(['ktp', 'kk', 'other'])],
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $path = $request->file('file')->store("tenants/{$tenant->id}/documents", 'public');

        $document = $tenant->documents()->create([
            'doc_type' => $data['doc_type'],
            'file_path' => $path,
            'verified' => false,
        ]);

        return response()->json($document, 201);
    }

    public function deleteDocument(Request $request, Tenant $tenant, \App\Models\TenantDocument $document)
    {
        abort_unless($document->tenant_id === $tenant->id, 404);

        \Illuminate\Support\Facades\Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response()->json(['message' => 'Dokumen dihapus.']);
    }

    public function destroy(Request $request, Tenant $tenant)
    {
        $hasActiveContract = $tenant->contracts()
            ->whereIn('status', ['active', 'ending_soon'])
            ->exists();

        if ($hasActiveContract) {
            return response()->json([
                'message' => 'Penghuni ini masih memiliki kontrak aktif. Proses check-out terlebih dahulu di halaman Kontrak sebelum menghapus.',
            ], 422);
        }

        if ($tenant->user) {
            $tenant->user->update(['status' => 'inactive']);
        }

        $tenant->delete();

        return response()->json(['message' => 'Penghuni berhasil dihapus.']);
    }
}