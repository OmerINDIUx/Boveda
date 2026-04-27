<?php

use App\Http\Controllers\ProjectController;

Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::post('/projects/{project}/upload', [ProjectController::class, 'upload'])->name('projects.upload');
Route::get('/projects/{project}/transmittals', [ProjectController::class, 'transmittals'])->name('projects.transmittals');
Route::post('/projects/{project}/transmittals', [ProjectController::class, 'sendTransmittal'])->name('projects.transmittals.send');
Route::post('/documents/{document}/log-view', [ProjectController::class, 'logView'])->name('documents.log-view');
Route::get('/documents/{document}/history', [ProjectController::class, 'history'])->name('documents.history');

