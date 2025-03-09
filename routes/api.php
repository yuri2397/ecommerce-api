<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductCommentController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Routes publiques (sans authentification)
Route::prefix('categories')->group(function () {
    // Liste publique des catégories
    Route::get('/', [CategoryController::class, 'index'])
        ->name('categories.public.index');

    // Détail d'une catégorie publique
    Route::get('/{category:slug}', [CategoryController::class, 'show'])
        ->name('categories.public.show');
});

// Routes publiques pour les produits
Route::prefix('products')->group(function () {
    // Liste des produits
    Route::get('/', [ProductController::class, 'index'])
        ->name('products.public.index');

    // Détail d'un produit
    Route::get('/{product:sku}', [ProductController::class, 'show'])
        ->name('products.public.show');

    // Produits d'une catégorie
    Route::get('/category/{category:slug}', [ProductController::class, 'byCategory'])
        ->name('products.public.by-category');

    // Routes pour les commentaires publics
    Route::get('/{product}/comments', [ProductCommentController::class, 'getProductComments'])
        ->name('products.comments.index');
});

// Routes nécessitant authentification
Route::middleware(['auth:sanctum'])->group(function () {
    // Routes de commentaires
    Route::prefix('comments')->group(function () {
        // Création d'un commentaire
        Route::post('/', [ProductCommentController::class, 'store'])
            ->name('comments.store');

        // Mise à jour d'un commentaire
        Route::put('/{comment}', [ProductCommentController::class, 'update'])
            ->name('comments.update');

        // Suppression d'un commentaire (l'utilisateur ne peut supprimer que ses propres commentaires)
        Route::delete('/{comment}', [ProductCommentController::class, 'destroy'])
            ->name('comments.destroy');
    });

    // Routes pour le panier
    Route::prefix('cart')->group(function () {
        // Récupérer le panier actuel de l'utilisateur
        Route::get('/', [CartController::class, 'getCurrentCart'])
            ->name('cart.current');

        // Vider le panier
        Route::delete('/clear', [CartController::class, 'clear'])
            ->name('cart.clear');

        // Ajouter un produit au panier
        Route::post('/items', [CartItemController::class, 'addToCart'])
            ->name('cart.items.add');

        // Mettre à jour la quantité d'un article
        Route::put('/items/{cartItem}', [CartItemController::class, 'updateQuantity'])
            ->name('cart.items.update');

        // Supprimer un article du panier
        Route::delete('/items/{cartItem}', [CartItemController::class, 'removeFromCart'])
            ->name('cart.items.remove');

        // Liste des articles du panier
        Route::get('/items', [CartItemController::class, 'index'])
            ->name('cart.items.index');
    });

    // Routes pour les commandes (client)
    Route::prefix('orders')->group(function () {
        // Liste des commandes de l'utilisateur
        Route::get('/', [OrderController::class, 'myOrders'])
            ->name('orders.my-orders');

        // Détail d'une commande
        Route::get('/{order}', [OrderController::class, 'showMyOrder'])
            ->name('orders.show');

        // Créer une commande à partir du panier
        Route::post('/checkout', [OrderController::class, 'createFromCart'])
            ->name('orders.checkout');

        // Annuler une commande
        Route::patch('/{order}/cancel', [OrderController::class, 'cancelMyOrder'])
            ->name('orders.cancel');

        // Effectuer un paiement
        Route::post('/{order}/pay', [PaymentController::class, 'processPayment'])
            ->name('orders.pay');
    });
});

// Routes admin (avec authentification et permissions)
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Routes pour les catégories
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])
            ->name('categories.admin.index');

        Route::get('/dropdown', [CategoryController::class, 'dropdown'])
            ->name('categories.admin.dropdown');

        Route::post('/', [CategoryController::class, 'store'])
            ->name('categories.admin.store')
            ->middleware('permission:category.create');

        Route::get('/{category}', [CategoryController::class, 'show'])
            ->name('categories.admin.show');

        Route::put('/{category}', [CategoryController::class, 'update'])
            ->name('categories.admin.update')
            ->middleware('permission:category.update');

        Route::delete('/{category}', [CategoryController::class, 'destroy'])
            ->name('categories.admin.destroy')
            ->middleware('permission:category.delete');

        Route::patch('/{category}/activate', [CategoryController::class, 'activate'])
            ->name('categories.admin.activate');

        Route::patch('/{category}/deactivate', [CategoryController::class, 'deactivate'])
            ->name('categories.admin.deactivate');


    });

    // Routes CRUD principales pour les produits
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index'])
            ->name('products.admin.index')
            ->middleware('permission:product.view');

        Route::post('/', [ProductController::class, 'store'])
            ->name('products.admin.store')
            ->middleware('permission:product.create');

        Route::get('/{product}', [ProductController::class, 'show'])
            ->name('products.admin.show')
            ->middleware('permission:product.view');

        Route::put('/{product}', [ProductController::class, 'update'])
            ->name('products.admin.update')
            ->middleware('permission:product.update');

        Route::delete('/{product}', [ProductController::class, 'destroy'])
            ->name('products.admin.destroy')
            ->middleware('permission:product.delete');

        // Routes pour la gestion des stocks
        Route::patch('/{product}/stock', [ProductController::class, 'updateStock'])
            ->name('products.admin.updateStock')
            ->middleware('permission:product.update');

        // Routes pour la mise en avant des produits
        Route::patch('/{product}/feature', [ProductController::class, 'feature'])
            ->name('products.admin.feature')
            ->middleware('permission:product.update');

        Route::patch('/{product}/unfeature', [ProductController::class, 'unfeature'])
            ->name('products.admin.unfeature')
            ->middleware('permission:product.update');

        Route::get('/dropdown', [ProductController::class, 'dropdown']);
        Route::get('/stats', [ProductController::class, 'stats']);
    });

    // Routes admin pour les commentaires
    Route::prefix('comments')->group(function () {
        Route::get('/', [ProductCommentController::class, 'index'])
            ->name('comments.admin.index')
            ->middleware('permission:comment.view');

        Route::get('/{comment}', [ProductCommentController::class, 'show'])
            ->name('comments.admin.show')
            ->middleware('permission:comment.view');

        Route::put('/{comment}', [ProductCommentController::class, 'update'])
            ->name('comments.admin.update')
            ->middleware('permission:comment.update');

        Route::delete('/{comment}', [ProductCommentController::class, 'destroy'])
            ->name('comments.admin.destroy')
            ->middleware('permission:comment.delete');

        Route::get('/stats', [ProductCommentController::class, 'stats'])
            ->name('comments.admin.stats')
            ->middleware('permission:comment.view');
    });

    // Routes admin pour les paniers
    Route::prefix('carts')->group(function () {
        Route::get('/', [CartController::class, 'index'])
            ->name('carts.admin.index')
            ->middleware('permission:cart.view');

        Route::post('/', [CartController::class, 'store'])
            ->name('carts.admin.store')
            ->middleware('permission:cart.create');

        Route::get('/{cart}', [CartController::class, 'show'])
            ->name('carts.admin.show')
            ->middleware('permission:cart.view');

        Route::put('/{cart}', [CartController::class, 'update'])
            ->name('carts.admin.update')
            ->middleware('permission:cart.update');

        Route::delete('/{cart}', [CartController::class, 'destroy'])
            ->name('carts.admin.destroy')
            ->middleware('permission:cart.delete');

        Route::patch('/{cart}/clear', [CartController::class, 'clear'])
            ->name('carts.admin.clear')
            ->middleware('permission:cart.update');

        Route::patch('/{cart}/mark-as-abandoned', [CartController::class, 'markAsAbandoned'])
            ->name('carts.admin.mark-as-abandoned')
            ->middleware('permission:cart.update');

        Route::patch('/{cart}/mark-as-converted', [CartController::class, 'markAsConverted'])
            ->name('carts.admin.mark-as-converted')
            ->middleware('permission:cart.update');

        Route::get('/stats', [CartController::class, 'stats'])
            ->name('carts.admin.stats')
            ->middleware('permission:cart.view');

        Route::get('/{cart}/total', [CartItemController::class, 'calculateCartTotal'])
            ->name('carts.admin.total')
            ->middleware('permission:cart.view');
    });

    // Routes admin pour les commandes
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index'])
            ->name('orders.admin.index')
            ->middleware('permission:order.view');

        Route::get('/{order}', [OrderController::class, 'show'])
            ->name('orders.admin.show')
            ->middleware('permission:order.view');

        Route::put('/{order}', [OrderController::class, 'update'])
            ->name('orders.admin.update')
            ->middleware('permission:order.update');

        Route::delete('/{order}', [OrderController::class, 'destroy'])
            ->name('orders.admin.destroy')
            ->middleware('permission:order.delete');

        Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])
            ->name('orders.admin.update-status')
            ->middleware('permission:order.update');

        Route::get('/stats', [OrderController::class, 'stats'])
            ->name('orders.admin.stats')
            ->middleware('permission:order.view');
    });

    // Routes admin pour les articles de commande
    Route::prefix('order-items')->group(function () {
        Route::get('/order/{order}', [OrderItemController::class, 'index'])
            ->name('order-items.admin.index')
            ->middleware('permission:order.view');

        Route::get('/{orderItem}', [OrderItemController::class, 'show'])
            ->name('order-items.admin.show')
            ->middleware('permission:order.view');

        Route::post('/order/{order}', [OrderItemController::class, 'store'])
            ->name('order-items.admin.store')
            ->middleware('permission:order.update');

        Route::put('/{orderItem}', [OrderItemController::class, 'update'])
            ->name('order-items.admin.update')
            ->middleware('permission:order.update');

        Route::delete('/{orderItem}', [OrderItemController::class, 'destroy'])
            ->name('order-items.admin.destroy')
            ->middleware('permission:order.update');
    });

    // Routes admin pour les paiements
    Route::prefix('payments')->group(function () {
        Route::get('/order/{order}', [PaymentController::class, 'index'])
            ->name('payments.admin.index')
            ->middleware('permission:payment.view');

        Route::get('/{payment}', [PaymentController::class, 'show'])
            ->name('payments.admin.show')
            ->middleware('permission:payment.view');

        Route::post('/order/{order}', [PaymentController::class, 'store'])
            ->name('payments.admin.store')
            ->middleware('permission:payment.create');

        Route::put('/{payment}', [PaymentController::class, 'update'])
            ->name('payments.admin.update')
            ->middleware('permission:payment.update');

        Route::delete('/{payment}', [PaymentController::class, 'destroy'])
            ->name('payments.admin.destroy')
            ->middleware('permission:payment.delete');
    });

    // Routes pour la gestion des médias
    Route::prefix('media')->group(function () {
        // Supprimer un média
        Route::delete('/{media}', [MediaController::class, 'destroy'])
            ->name('media.admin.destroy')
            ->middleware('permission:media.delete');

        // Définir une image comme principale pour un produit
        Route::post('/products/{product}/media/{media}/set-as-thumbnail', [MediaController::class, 'setAsThumbnail'])
            ->name('media.admin.set-as-thumbnail')
            ->middleware('permission:media.update');

        // Réorganiser les images d'un produit
        Route::post('/products/{product}/reorder', [MediaController::class, 'reorder'])
            ->name('media.admin.reorder')
            ->middleware('permission:media.update');
    });
});
