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
Route::post('/documents/{document}/toggle-lock', [ProjectController::class, 'toggleLock'])->name('documents.toggle-lock');
Route::post('/revisions/{revision}/note', [ProjectController::class, 'addRevisionNote'])->name('revisions.note');
Route::post('/notes/{note}/update', [ProjectController::class, 'updateRevisionNote'])->name('notes.update');
Route::post('/notes/{note}/toggle-resolve', [ProjectController::class, 'toggleResolveNote'])->name('notes.toggle-resolve');
Route::get('/transmittals/{transmittal}/download', [\App\Http\Controllers\TransmittalPDFController::class, 'download'])->name('transmittals.download');

