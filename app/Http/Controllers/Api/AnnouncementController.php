<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Announcement::with('property', 'creator');

        if ($user->isTenant()) {
            $propertyId = $user->tenant?->activeContract?->room?->property_id;
            $query->where(function ($q) use ($propertyId) {
                $q->whereNull('property_id')->orWhere('property_id', $propertyId);
            });
        }

        return response()->json($query->latest()->get());
    }

    public function store(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'staff']), 403);

        $data = $request->validate([
            'property_id' => 'nullable|exists:properties,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'target' => 'required|in:all,property',
        ]);

        $announcement = Announcement::create([...$data, 'created_by' => $request->user()->id]);

        return response()->json($announcement, 201);
    }
}