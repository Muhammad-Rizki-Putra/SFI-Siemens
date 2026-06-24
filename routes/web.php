<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagementController;

Route::get('/unauthorized', function () {
    return response('Unauthorized. Please access via the company portal.', 401);
})->name('unauthorized');

Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('ideas.index');
    }
    
    return in_array(Auth::user()->role, ['sps', 'technical_reviewer', 'technical_manager', 'financial_manager'], true)
        ? redirect()->route('admin.ideas.index')
        : redirect()->route('ideas.index');
});

// Public routes
Route::get('/ideas', [IdeaController::class, 'index'])->name('ideas.index');
Route::get('/leaderboard', [IdeaController::class, 'leaderboard'])->name('ideas.leaderboard');

// Protected routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return in_array(Auth::user()->role, ['sps', 'technical_reviewer', 'technical_manager', 'financial_manager'], true)
            ? redirect()->route('admin.ideas.index')
            : redirect()->route('ideas.index');
    })->name('dashboard');

    Route::get('/ideas/create', [IdeaController::class, 'create'])->name('ideas.create');
    Route::post('/ideas', [IdeaController::class, 'store'])->name('ideas.store');
    Route::get('/ideas/{idea}/edit', [IdeaController::class, 'edit'])->name('ideas.edit');
    Route::post('/ideas/{idea}', [IdeaController::class, 'update'])->name('ideas.update.post');
    Route::get('/ideas/{idea}', [IdeaController::class, 'show'])->name('ideas.show');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [\App\Http\Controllers\Auth\PasswordController::class, 'update'])->name('password.update');
    Route::get('/attachments/{attachment}', [IdeaController::class, 'attachment'])
        ->name('attachments.show');
    Route::get('/attachments/{attachment}/preview', [IdeaController::class, 'attachmentPreview'])
        ->name('attachments.preview');
    Route::post('/attachments/{attachment}/delete', [IdeaController::class, 'attachmentDelete'])
        ->name('attachments.delete');

    // Admin routes
    Route::middleware(['role:sps,technical_reviewer,technical_manager,financial_manager'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/ideas', [AdminController::class, 'index'])->name('ideas.index');
            Route::get('/ideas/{idea}', [AdminController::class, 'show'])->name('ideas.show');
            Route::get('/ideas/{idea}/sps', [AdminController::class, 'showSps'])->name('ideas.sps.show');
            Route::get('/ideas/{idea}/technical', [AdminController::class, 'showTechnical'])->name('ideas.technical.show');
            Route::get('/ideas/{idea}/financial', [AdminController::class, 'showFinancial'])->name('ideas.financial.show');
            Route::post('/ideas/{idea}/review', [AdminController::class, 'review'])->name('ideas.review');
            Route::post('/ideas/{idea}/score', [AdminController::class, 'score'])->name('ideas.score');
            Route::post('/ideas/{idea}/workflow', [AdminController::class, 'workflow'])->name('ideas.workflow');

            // User management (SPS only)
            Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
            Route::post('/users/{user}/role', [UserManagementController::class, 'updateRole'])->name('users.updateRole');
        });
});

require __DIR__.'/auth.php';
