<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Room::query()->with('roomType');

        $query->whereIn('property_id', $user->accessiblePropertyIds());

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->integer('property_id'));
        }

        return response()->json($query->orderBy('floor')->orderBy('room_number')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'room_number' => 'required|string|max:20',
            'floor' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        // Validasi nomor kamar duplikat per lantai (Design Guidelines - error spesifik)
        $exists = Room::where('property_id', $data['property_id'])
            ->where('floor', $data['floor'])
            ->where('room_number', $data['room_number'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => "Nomor kamar {$data['room_number']} sudah dipakai di lantai ini.",
            ], 422);
        }

        $room = Room::create([...$data, 'status' => 'available']);

        return response()->json($room, 201);
    }

    public function updateStatus(Request $request, Room $room)
    {
        $data = $request->validate([
            'status' => 'required|in:available,maintenance,inactive',
        ]);

        return response()->json($room->tap(fn ($r) => $r->update($data)));
    }
}