<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::post('/projects/{project}/upload', [ProjectController::class, 'upload'])->name('projects.upload');
Route::post('/projects/{project}/disciplines', [ProjectController::class, 'storeDiscipline'])->name('projects.disciplines.store');
Route::get('/projects/{project}/transmittals', [ProjectController::class, 'transmittals'])->name('projects.transmittals');
Route::post('/projects/{project}/transmittals', [ProjectController::class, 'sendTransmittal'])->name('projects.transmittals.send');
Route::post('/documents/{document}/log-view', [ProjectController::class, 'logView'])->name('documents.log-view');
Route::get('/documents/{document}/history', [ProjectController::class, 'history'])->name('documents.history');
Route::post('/documents/{document}/toggle-lock', [ProjectController::class, 'toggleLock'])->name('documents.toggle-lock');
Route::post('/revisions/{revision}/note', [ProjectController::class, 'addRevisionNote'])->name('revisions.note');
Route::post('/notes/{note}/update', [ProjectController::class, 'updateRevisionNote'])->name('notes.update');
Route::post('/notes/{note}/toggle-resolve', [ProjectController::class, 'toggleResolveNote'])->name('notes.toggle-resolve');
Route::get('/transmittals/{transmittal}/download', [\App\Http\Controllers\TransmittalPDFController::class, 'download'])->name('transmittals.download');

// RFI (Request for Information)
Route::get('/rfis', [\App\Http\Controllers\RfiController::class, 'globalIndex'])->name('rfis.global');
Route::get('/projects/{project}/rfis', [\App\Http\Controllers\RfiController::class, 'index'])->name('projects.rfis');
Route::post('/projects/{project}/rfis', [\App\Http\Controllers\RfiController::class, 'store'])->name('projects.rfis.store');
Route::get('/rfis/{rfi}', [\App\Http\Controllers\RfiController::class, 'show'])->name('rfis.show');
Route::post('/rfis/{rfi}/responses', [\App\Http\Controllers\RfiController::class, 'addResponse'])->name('rfis.responses.store');
Route::patch('/rfis/{rfi}/status', [\App\Http\Controllers\RfiController::class, 'updateStatus'])->name('rfis.update-status');

// Project Mailbox
Route::get('/projects/{project}/mailbox', [\App\Http\Controllers\ProjectMailboxController::class, 'index'])->name('projects.mailbox');
Route::get('/projects/{project}/mailbox/{email}', [\App\Http\Controllers\ProjectMailboxController::class, 'show'])->name('projects.mailbox.show');

// User Management
Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
Route::post('/users', [\App\Http\Controllers\UserController::class, 'store'])->name('users.store');

// Global Disciplines Catalog
Route::get('/disciplines', [\App\Http\Controllers\DisciplineController::class, 'index'])->name('disciplines.index');
Route::post('/disciplines', [\App\Http\Controllers\DisciplineController::class, 'store'])->name('disciplines.store');
Route::delete('/disciplines/{discipline}', [\App\Http\Controllers\DisciplineController::class, 'destroy'])->name('disciplines.destroy');

// Folders
Route::post('/projects/{project}/folders', [FolderController::class, 'store'])->name('projects.folders.store');
Route::patch('/folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');
Route::post('/folders/{folder}/restore', [FolderController::class, 'restore'])->name('folders.restore');

Route::post('/documents/{document}/move', [FolderController::class, 'moveDocument'])->name('documents.move');
Route::delete('/documents/{document}', [ProjectController::class, 'destroyDocument'])->name('documents.destroy');
Route::post('/documents/{document}/restore', [ProjectController::class, 'restoreDocument'])->name('documents.restore');

Route::get('/projects/{project}/recycle-bin', [ProjectController::class, 'recycleBin'])->name('projects.recycle-bin');

// Dashboard and Analytics
Route::get('/projects/{project}/dashboard', [ProjectController::class, 'dashboard'])->name('projects.dashboard');

// Approval Workflows
Route::get('/projects/{project}/workflows', [\App\Http\Controllers\ApprovalWorkflowController::class, 'index'])->name('projects.workflows');
Route::post('/projects/{project}/workflows', [\App\Http\Controllers\ApprovalWorkflowController::class, 'store'])->name('projects.workflows.store');
Route::put('/workflows/{workflow}', [\App\Http\Controllers\ApprovalWorkflowController::class, 'update'])->name('workflows.update');
Route::delete('/workflows/{workflow}', [\App\Http\Controllers\ApprovalWorkflowController::class, 'destroy'])->name('workflows.destroy');

// Approval Execution
Route::post('/revisions/{revision}/request-approval', [\App\Http\Controllers\ApprovalRequestController::class, 'store'])->name('revisions.request-approval');
Route::post('/approval-requests/{approval_request}/review', [\App\Http\Controllers\ApprovalRequestController::class, 'review'])->name('approval.review');

