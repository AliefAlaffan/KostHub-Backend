<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(
            $request->user()->notifications()->latest()->paginate(20)
        );
    }

    public function read(Request $request, string $notification)
    {
        $notif = $request->user()->notifications()->where('id', $notification)->firstOrFail();
        $notif->markAsRead();

        return response()->json(['message' => 'Notifikasi ditandai dibaca.']);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Semua notifikasi ditandai dibaca.']);
    }
}