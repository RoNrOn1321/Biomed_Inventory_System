<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', App\Http\Controllers\DashboardController::class)->name('dashboard');
    Route::get('Inventory', [App\Http\Controllers\EquipmentController::class, 'index'])->name('inventory.index');
    Route::get('/api/equipments/search', [App\Http\Controllers\EquipmentController::class, 'search'])->name('equipment.search');

    Route::get('/JobRequests', [App\Http\Controllers\JobRequestController::class, 'index'])->name('job-requests.index');
    Route::put('/JobRequests/{jobRequest}/accept', [App\Http\Controllers\JobRequestController::class, 'accept'])->name('job-requests.accept');
    Route::post('/JobRequests/{jobRequest}/complete', [App\Http\Controllers\JobRequestController::class, 'complete'])->name('job-requests.complete');

    Route::get('/request-service', [App\Http\Controllers\EndUserJobRequestController::class, 'create'])->name('end-user.job-request.create');
    Route::post('/request-service', [App\Http\Controllers\EndUserJobRequestController::class, 'store'])->name('end-user.job-request.store');
    Route::get('/request-history', [App\Http\Controllers\EndUserJobRequestController::class, 'history'])->name('end-user.job-request.history');

    Route::get('/manage-accounts', [App\Http\Controllers\ManageAccountsController::class, 'index'])->name('manage-accounts.index');
    Route::put('/manage-accounts/{user}', [App\Http\Controllers\ManageAccountsController::class, 'update'])->name('manage-accounts.update');
    Route::put('/manage-accounts/{user}/password', [App\Http\Controllers\ManageAccountsController::class, 'updatePassword'])->name('manage-accounts.password');
    Route::delete('/manage-accounts/{user}', [App\Http\Controllers\ManageAccountsController::class, 'destroy'])->name('manage-accounts.destroy');

    Route::get('/equipment/export', [App\Http\Controllers\EquipmentController::class, 'export'])->name('equipment.export');
    Route::post('/equipment', [App\Http\Controllers\EquipmentController::class, 'store'])->name('equipment.store');
    Route::put('/equipment/{equipment}', [App\Http\Controllers\EquipmentController::class, 'update'])->name('equipment.update');
    Route::delete('/equipment/{equipment}', [App\Http\Controllers\EquipmentController::class, 'destroy'])->name('equipment.destroy');

    Route::get('/equipment/{equipmentId}/documents', [App\Http\Controllers\EquipmentDocumentController::class, 'index'])->name('equipment.documents.index');
    Route::post('/equipment/{equipmentId}/documents', [App\Http\Controllers\EquipmentDocumentController::class, 'store'])->name('equipment.documents.store');
    Route::get('/equipment/documents/{documentId}/preview', [App\Http\Controllers\EquipmentDocumentController::class, 'preview'])->name('equipment.documents.preview');
    Route::get('/equipment/documents/{documentId}/download', [App\Http\Controllers\EquipmentDocumentController::class, 'download'])->name('equipment.documents.download');
    Route::delete('/equipment/documents/{documentId}', [App\Http\Controllers\EquipmentDocumentController::class, 'destroy'])->name('equipment.documents.destroy');

    Route::get('/equipment/{equipmentId}/calibrations', [App\Http\Controllers\EquipmentCalibrationController::class, 'index'])->name('equipment.calibrations.index');
    Route::post('/equipment/{equipmentId}/calibrations', [App\Http\Controllers\EquipmentCalibrationController::class, 'store'])->name('equipment.calibrations.store');
    Route::get('/equipment/calibrations/{fileId}/preview', [App\Http\Controllers\EquipmentCalibrationController::class, 'preview'])->name('equipment.calibrations.preview');
    Route::get('/equipment/calibrations/{fileId}/download', [App\Http\Controllers\EquipmentCalibrationController::class, 'download'])->name('equipment.calibrations.download');
    Route::delete('/equipment/calibrations/{fileId}', [App\Http\Controllers\EquipmentCalibrationController::class, 'destroy'])->name('equipment.calibrations.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
