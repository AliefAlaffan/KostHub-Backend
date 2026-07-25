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
        $query = Property::query();

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
        return response()->json($property->load('roomTypes', 'rooms'));
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
        ]);

        $property->update($data);
        return response()->json($property);
    }

    public function destroy(Request $request, Property $property)
    {
        $this->authorize('delete', $property);
        $property->delete();
        return response()->json(['message' => 'Properti dihapus.']);
    }
}