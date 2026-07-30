<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request, Property $property)
    {
        return response()->json($property->reviews()->with('tenant.user')->latest()->get());
    }

    /** Hanya tenant, dan minimal sudah 1 bulan sejak mulai kontrak di properti tsb */
    public function store(Request $request, Property $property)
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 403, 'Hanya penghuni yang bisa memberi ulasan.');

        $hasQualifyingContract = $tenant->contracts()
            ->whereHas('room', fn ($q) => $q->where('property_id', $property->id))
            ->where('start_date', '<=', now()->subMonth())
            ->exists();

        abort_unless($hasQualifyingContract, 422, 'Anda perlu menyewa minimal 1 periode sebelum bisa memberi ulasan.');

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = $property->reviews()->create([...$data, 'tenant_id' => $tenant->id]);

        return response()->json($review, 201);
    }

    public function reply(Request $request, Review $review)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate(['owner_reply' => 'required|string|max:1000']);
        $review->update($data);

        return response()->json($review);
    }
}