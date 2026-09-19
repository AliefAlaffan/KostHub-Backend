<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Property::query()->withCount([
            'rooms',
            'rooms as occupied_rooms_count' => fn ($q) => $q->where('status', 'occupied'),
            'rooms as available_rooms_count' => fn ($q) => $q->where('status', 'available'),
        ]);

        if ($user->isAdmin()) {
            $query->where('admin_id', $user->id);
        } elseif ($user->isStaff()) {
            $query->whereIn('id', $user->accessiblePropertyIds());
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request)
    {
        $this->authorize('create', Property::class);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'type' => 'required|in:putra,putri,campur',
            'description' => 'nullable|string',
            'facilities' => 'nullable|array',
            'facilities.*' => 'string',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:100',
            'account_holder' => 'nullable|string',
        ]);

        $property = Property::create([
            ...$data,
            'admin_id' => $request->user()->id,
            'status' => 'active',
        ]);

        return response()->json($property, 201);
    }

    public function show(Request $request, Property $property)
    {
        $this->authorize('view', $property);
        return response()->json(
            $property->load('roomTypes', 'rooms.activeContract.tenant.user')
        );
    }

    public function update(Request $request, Property $property)
    {
        $this->authorize('update', $property);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string',
            'city' => 'sometimes|string|max:100',
            'type' => 'sometimes|in:putra,putri,campur',
            'description' => 'nullable|string',
            'facilities' => 'nullable|array',
            'facilities.*' => 'string',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:100',
            'account_holder' => 'nullable|string',
        ]);

        $property->update($data);
        return response()->json($property);
    }

    public function uploadPhoto(Request $request, Property $property)
    {
        $this->authorize('update', $property);

        $request->validate([
            'photo' => 'required|image|max:5120',
        ]);

        if ($property->photo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($property->photo);
        }

        $path = $request->file('photo')->store("properties/{$property->id}/photo", 'public');
        $property->update(['photo' => $path]);

        return response()->json($property);
    }

    public function deletePhoto(Request $request, Property $property)
    {
        $this->authorize('update', $property);

        if ($property->photo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($property->photo);
            $property->update(['photo' => null]);
        }

        return response()->json(['message' => 'Foto dihapus.']);
    }

    public function destroy(Request $request, Property $property)
    {
        $this->authorize('delete', $property);
        $property->delete();
        return response()->json(['message' => 'Properti dihapus.']);
    }

    public function uploadQris(Request $request, Property $property)
    {
        $request->validate([
            'qris_image' => 'required|image|max:5120',
        ]);

        if ($property->qris_image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($property->qris_image);
        }

        $path = $request->file('qris_image')->store("properties/{$property->id}/qris", 'public');
        $property->update(['qris_image' => $path]);

        return response()->json($property);
    }

    public function deleteQris(Request $request, Property $property)
    {
        if ($property->qris_image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($property->qris_image);
            $property->update(['qris_image' => null]);
        }

        return response()->json(['message' => 'QRIS dihapus.']);
    }
}