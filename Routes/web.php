<?php

use Illuminate\Support\Facades\Route;
use Modules\CimsSignature\Http\Controllers\SignatureController;

Route::middleware(['auth'])->prefix('signatures')->group(function () {
    Route::get('/', [SignatureController::class, 'index'])->name('signatures.index');
    Route::get('/capture', [SignatureController::class, 'create'])->name('signatures.capture');
    Route::get('/quick', [SignatureController::class, 'quickCapture'])->name('signatures.quick');
    Route::post('/', [SignatureController::class, 'store'])->name('signatures.store');
    Route::get('/{signature}', [SignatureController::class, 'show'])->name('signatures.show');
    Route::delete('/{signature}', [SignatureController::class, 'destroy'])->name('signatures.destroy');
    Route::get('/{signature}/image', [SignatureController::class, 'getImage'])->name('signatures.image');
    Route::get('/client/{clientReference}', [SignatureController::class, 'getLatest'])->name('signatures.latest');
});
