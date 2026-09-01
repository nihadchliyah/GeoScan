<?php

use App\Http\Controllers\HostController;
use App\Http\Controllers\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/searches/create');

Route::get('/hosts', function (Request $request) {
    $ip = trim((string) $request->query('ip'));
    abort_if($ip === '', 404);

    return redirect()->route('hosts.show', $ip);
})->name('hosts.lookup');

Route::get('/searches/create', [SearchController::class, 'create'])->name('searches.create');
Route::post('/searches', [SearchController::class, 'store'])->name('searches.store');
Route::get('/searches', [SearchController::class, 'index'])->name('searches.index');
Route::get('/searches/{search}', [SearchController::class, 'show'])->name('searches.show');

Route::get('/hosts/{ip}', [HostController::class, 'show'])
    ->where('ip', '[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}')
    ->name('hosts.show');
