<?php

use App\Features\Admin\Livewire\AdminUserStockList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Auth\Livewire\Login;
use App\Features\Auth\Livewire\Register;
use App\Features\Stock\Livewire\StockList;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/cached-assets/app.css', function (Request $request) {
    $assetUrl = Vite::asset('resources/css/app.css');
    $assetPath = parse_url($assetUrl, PHP_URL_PATH);
    abort_unless(is_string($assetPath), 404);

    $assetName = basename($assetPath);
    abort_unless(hash_equals($assetName, (string) $request->query('v')), 404);

    $file = public_path(ltrim($assetPath, '/'));
    abort_unless(is_file($file), 404);

    return response()->file($file, [
        'Cache-Control' => 'public, max-age=31536000, immutable',
        'Content-Type' => 'text/css; charset=UTF-8',
        'Expires' => now()->addYear()->toRfc7231String(),
    ]);
})->withoutMiddleware([
    PreventRequestForgery::class,
    ShareErrorsFromSession::class,
    StartSession::class,
])->name('assets.app-css');

Route::middleware(['throttle:1000-per-second'])->group(function () {
    // Root Redirection
    Route::get('/', function () {
        if (Auth::check()) {
            return Auth::user()->isAdmin()
                ? redirect()->route('admin.users')
                : redirect()->route('stocks.index');
        }
        return redirect()->route('login');
    });

    // Guest Authentication Routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', Login::class)->name('login');
        Route::get('/register', Register::class)->name('register');
    });

    // Authenticated Routes
    Route::middleware('auth')->group(function () {
        Route::post('/logout', function (Request $request) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login');
        })->name('logout');

        // Member Stock Routes protected by ownership middleware
        Route::get('/stocks', StockList::class)
            ->middleware('owns.stock')
            ->name('stocks.index');
        Route::get('/stocks/{stock}', \App\Features\Stock\Livewire\StockDetail::class)
            ->middleware('owns.stock')
            ->name('stocks.show');

        // User Profile Route
        Route::get('/profile', \App\Features\Profile\Livewire\Profile::class)->name('profile');

        // Admin Routes protected by Role middleware
        Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
            Route::get('/users', UserList::class)->name('users');
            Route::get('/users/{user}/stocks', AdminUserStockList::class)->name('users.stocks');
        });
    });
});
