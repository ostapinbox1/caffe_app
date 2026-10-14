<?php

use App\Http\Middleware\EnsureUserIsManager;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('requests', 'pages::requests.index')->name('requests.index');
    Route::livewire('requests/create', 'pages::requests.create')->name('requests.create');
    Route::livewire('requests/{purchaseRequest}', 'pages::requests.show')->name('requests.show');

    Route::livewire('checklists', 'pages::checklists.index')->name('checklists.index');
    Route::livewire('checklists/{run}', 'pages::checklists.show')->name('checklists.show');

    Route::livewire('team', 'pages::team')->middleware(EnsureUserIsManager::class)->name('team');

    Route::livewire('notifications', 'pages::notifications')->name('notifications');
});

require __DIR__.'/settings.php';
