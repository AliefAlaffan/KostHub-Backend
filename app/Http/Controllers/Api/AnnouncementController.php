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
        } elseif ($user->isStaff()) {
            $propertyIds = $user->accessiblePropertyIds();
            $query->where(function ($q) use ($propertyIds) {
                $q->whereNull('property_id')->orWhereIn('property_id', $propertyIds);
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

    /** BARU */
    public function show(Request $request, Announcement $announcement)
    {
        $this->authorizeAccess($request, $announcement);
        return response()->json($announcement->load('property', 'creator'));
    }

    /** BARU */
    public function update(Request $request, Announcement $announcement)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'staff']), 403);
        $this->authorizeAccess($request, $announcement);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
        ]);

        $announcement->update($data);
        return response()->json($announcement);
    }

    /** BARU */
    public function destroy(Request $request, Announcement $announcement)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'staff']), 403);
        $this->authorizeAccess($request, $announcement);

        $announcement->delete();
        return response()->json(['message' => 'Pengumuman dihapus.']);
    }

    private function authorizeAccess(Request $request, Announcement $announcement): void
    {
        $user = $request->user();
        if ($user->isAdmin() || $announcement->property_id === null) {
            return;
        }

        $propertyIds = $user->accessiblePropertyIds();
        abort_unless(in_array($announcement->property_id, $propertyIds), 403);
    }
}