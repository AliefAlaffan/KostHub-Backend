<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\MaintenanceRequestController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserManagementController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/properties', [PropertyController::class, 'index']);
        Route::post('/properties', [PropertyController::class, 'store']);
        Route::get('/properties/{property}', [PropertyController::class, 'show']);
        Route::put('/properties/{property}', [PropertyController::class, 'update']);
        Route::delete('/properties/{property}', [PropertyController::class, 'destroy']);

        Route::get('/rooms', [RoomController::class, 'index']);
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::patch('/rooms/{room}/status', [RoomController::class, 'updateStatus']);

        Route::get('/tenants', [TenantController::class, 'index']);
        Route::post('/tenants', [TenantController::class, 'store']);

        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);

        Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store']);
        Route::patch('/payments/{payment}/verify', [PaymentController::class, 'verify']);
        Route::patch('/payments/{payment}/reject', [PaymentController::class, 'reject']);

        Route::get('/contracts', [ContractController::class, 'index']);
        Route::get('/contracts/{contract}', [ContractController::class, 'show']);
        Route::post('/contracts/{contract}/renew', [ContractController::class, 'renew']);
        Route::post('/contracts/{contract}/checkout', [ContractController::class, 'checkout']);

        Route::get('/maintenance-requests', [MaintenanceRequestController::class, 'index']);
        Route::post('/maintenance-requests', [MaintenanceRequestController::class, 'store']);
        Route::patch('/maintenance-requests/{maintenanceRequest}/status', [MaintenanceRequestController::class, 'updateStatus']);
        Route::patch('/maintenance-requests/{maintenanceRequest}/assign', [MaintenanceRequestController::class, 'assign']);

        Route::get('/announcements', [AnnouncementController::class, 'index']);
        Route::post('/announcements', [AnnouncementController::class, 'store']);

        Route::prefix('reports')->group(function () {
            Route::get('/occupancy', [ReportController::class, 'occupancy']);
            Route::get('/revenue', [ReportController::class, 'revenue']);
            Route::get('/outstanding-invoices', [ReportController::class, 'outstandingInvoices']);
            Route::get('/expenses', [ReportController::class, 'expenses']);
        });

        Route::get('/users', [UserManagementController::class, 'index']);
        Route::post('/users/staff', [UserManagementController::class, 'storeStaff']);
        Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword']);
        Route::patch('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus']);
    });
});