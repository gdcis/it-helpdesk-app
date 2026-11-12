<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('tickets.index');
});

Route::resource('tickets', TicketController::class);
Route::post('tickets/{ticket}/replies', [TicketController::class, 'storeReply'])
    ->name('tickets.replies.store');