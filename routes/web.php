<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return redirect('/recap');
});


Route::get('/recap', [ChatController::class, 'index'])->name('recap');
Route::post('/recap/process', [ChatController::class, 'process'])->name('recap.process');
