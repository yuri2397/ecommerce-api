<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductCommentController;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

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

// Routes de commentaires (nécessitant authentification)
Route::prefix('comments')->middleware(['auth:sanctum'])->group(function () {
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

// Routes admin (avec authentification et permissions)
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Routes pour les catégories
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])
            ->name('categories.admin.index');

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
