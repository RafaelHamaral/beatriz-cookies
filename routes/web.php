<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactorSetupController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\ProductPageController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalogo/{category:slug}', [CatalogController::class, 'category'])->name('catalog.category');
Route::get('/produtos/{product:slug}', ProductPageController::class)->name('products.show');
Route::get('/midia/{image:public_id}', ProductImageController::class)->name('products.image');
Route::get('/contato', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contato', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
Route::get('/avaliar', [ReviewController::class, 'create'])->name('reviews.create');
Route::post('/avaliar', [ReviewController::class, 'store'])->middleware('throttle:5,1')->name('reviews.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/esqueci-a-senha', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/esqueci-a-senha', [PasswordResetController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/redefinir-senha/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/redefinir-senha', [PasswordResetController::class, 'update'])
        ->middleware('throttle:5,1')
        ->name('password.update');

    Route::get('/dois-fatores', [TwoFactorChallengeController::class, 'create'])->name('two-factor.login');
    Route::post('/dois-fatores', [TwoFactorChallengeController::class, 'store'])->name('two-factor.login.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware(['auth', 'no.store'])
    ->name('logout');

Route::middleware(['auth', 'no.store'])->group(function () {
    Route::get('/area-restrita/seguranca/dois-fatores', [TwoFactorSetupController::class, 'create'])
        ->name('two-factor.setup');
    Route::post('/area-restrita/seguranca/dois-fatores', [TwoFactorSetupController::class, 'store'])
        ->name('two-factor.confirm');
});

Route::middleware(['auth', 'admin.2fa', 'no.store', 'restricted.notifications'])->prefix('area-restrita')->group(function () {
    Route::get('/', DashboardController::class)
        ->middleware('role:admin,editor,viewer')
        ->name('dashboard');

    Route::get('/seguranca/codigos-de-recuperacao', [TwoFactorSetupController::class, 'recovery'])
        ->name('two-factor.recovery');

    Route::middleware('role:admin')->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
        Route::patch('/usuarios/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('role:admin,editor')->group(function () {
        Route::get('/categorias', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categorias', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categorias/{category}/editar', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categorias/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categorias/{category}/ativacao', [CategoryController::class, 'activation'])->name('categories.activation');

        Route::get('/produtos', [ProductController::class, 'index'])->name('products.index');
        Route::get('/produtos/novo', [ProductController::class, 'create'])->name('products.create');
        Route::post('/produtos', [ProductController::class, 'store'])->name('products.store');
        Route::get('/produtos/{product}/editar', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/produtos/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('/produtos/{product}/ativacao', [ProductController::class, 'activation'])->name('products.activation');

        Route::get('/mensagens', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/mensagens/{message}', [MessageController::class, 'show'])->name('messages.show');
        Route::patch('/mensagens/{message}', [MessageController::class, 'update'])->name('messages.update');

        Route::get('/avaliacoes', [AdminReviewController::class, 'index'])->name('reviews.index');
        Route::patch('/avaliacoes/{review}', [AdminReviewController::class, 'update'])->name('reviews.update');
    });
});

// Session locking serializes cart/checkout submissions from the same customer.
Route::middleware('no.store')->group(function () {
    Route::get('/carrinho', [\App\Http\Controllers\CartController::class, 'index'])->name('cart.index')->block();
    Route::put('/carrinho/{product}', [\App\Http\Controllers\CartController::class, 'update'])->whereNumber('product')->middleware('throttle:60,1')->name('cart.update')->block();
    Route::get('/checkout', [\App\Http\Controllers\CheckoutController::class, 'create'])->name('checkout.create')->block();
    Route::post('/checkout', [\App\Http\Controllers\CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store')->block();
    Route::get('/pedido-confirmado/{number}', [\App\Http\Controllers\CheckoutController::class, 'success'])->name('checkout.success');
});
Route::middleware(['auth', 'admin.2fa', 'no.store', 'restricted.notifications', 'role:admin,editor'])->prefix('area-restrita')->group(function () {
    Route::get('/pedidos', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/pedidos/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('orders.show');
    Route::patch('/pedidos/{order}/atendimento', [\App\Http\Controllers\Admin\OrderController::class, 'update'])->name('orders.update');
    Route::patch('/pedidos/{order}/pagamento', [\App\Http\Controllers\Admin\OrderController::class, 'update'])->name('orders.payment');
});
