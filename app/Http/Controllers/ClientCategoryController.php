<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryTreeResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ClientCategoryController extends Controller
{
    /**
     * Récupère toutes les catégories actives
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getCategories(Request $request)
    {
        // Validation des paramètres
        $validated = $request->validate([
            'parent_id' => 'nullable|uuid|exists:categories,id',
            'with_products_count' => 'nullable|boolean',
            'with_children' => 'nullable|boolean',
            'featured_only' => 'nullable|boolean'
        ]);

        $cacheKey = 'client_categories_' . md5(json_encode($validated));

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($validated) {
            $query = Category::where('is_active', true);

            // Filtrer par catégorie parente si spécifié
            if (isset($validated['parent_id'])) {
                $query->where('parent_id', $validated['parent_id']);
            } else {
                // Par défaut, récupérer uniquement les catégories parentes (niveau supérieur)
                $query->whereNull('parent_id');
            }

            // Filtrer les catégories mises en avant si demandé
            if (isset($validated['featured_only']) && $validated['featured_only']) {
                $query->where('is_featured', true);
            }

            // Charger les relations si nécessaire
            $relations = [];
            if (isset($validated['with_children']) && $validated['with_children']) {
                $relations[] = 'children';
            }

            if (!empty($relations)) {
                $query->with($relations);
            }

            // Tri des catégories
            $categories = $query->orderBy('name')->get();

            // Ajouter le comptage des produits si demandé
            if (isset($validated['with_products_count']) && $validated['with_products_count']) {
                $categories->loadCount(['products' => function ($query) {
                    $query->where('is_active', true);
                }]);
            }

            return CategoryResource::collection($categories);
        });
    }

    /**
     * Récupère la structure arborescente complète des catégories
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getCategoryTree(Request $request)
    {
        // Validation des paramètres
        $validated = $request->validate([
            'with_products_count' => 'nullable|boolean',
            'active_only' => 'nullable|boolean',
            'featured_only' => 'nullable|boolean'
        ]);

        $activeOnly = $validated['active_only'] ?? true;
        $featuredOnly = $validated['featured_only'] ?? false;
        $withProductsCount = $validated['with_products_count'] ?? false;

        $cacheKey = 'client_category_tree_' . md5(json_encode($validated));

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($activeOnly, $featuredOnly, $withProductsCount) {
            $query = Category::whereNull('parent_id');

            if ($activeOnly) {
                $query->where('is_active', true);
            }

            if ($featuredOnly) {
                $query->where('is_featured', true);
            }

            $query->with(['children' => function ($query) use ($activeOnly, $featuredOnly) {
                if ($activeOnly) {
                    $query->where('is_active', true);
                }

                if ($featuredOnly) {
                    $query->where('is_featured', true);
                }

                $query->orderBy('name');
            }]);

            $rootCategories = $query->orderBy('name')->get();

            if ($withProductsCount) {
                $this->loadProductsCountRecursive($rootCategories);
            }

            return CategoryTreeResource::collection($rootCategories);
        });
    }

    /**
     * Récupère les détails d'une catégorie spécifique
     *
     * @param Request $request
     * @param Category $category
     * @return CategoryResource
     */
    public function getCategoryDetails(Request $request, Category $category)
    {
        // Vérifier que la catégorie est active
        if (!$category->is_active) {
            return response()->json(['message' => 'Catégorie non disponible'], 404);
        }

        // Validation des paramètres
        $validated = $request->validate([
            'with_parent' => 'nullable|boolean',
            'with_children' => 'nullable|boolean',
            'with_products_count' => 'nullable|boolean',
            'with_breadcrumbs' => 'nullable|boolean'
        ]);

        // Charger les relations demandées
        $relations = [];

        if (isset($validated['with_parent']) && $validated['with_parent']) {
            $relations[] = 'parent';
        }

        if (isset($validated['with_children']) && $validated['with_children']) {
            $relations[] = 'children';
        }

        if (!empty($relations)) {
            $category->load($relations);
        }

        // Ajouter le comptage des produits si demandé
        if (isset($validated['with_products_count']) && $validated['with_products_count']) {
            $category->loadCount(['products' => function ($query) {
                $query->where('is_active', true);
            }]);
        }

        // Ajouter les informations de fil d'Ariane si demandé
        if (isset($validated['with_breadcrumbs']) && $validated['with_breadcrumbs']) {
            $category->breadcrumbs = $this->generateBreadcrumbs($category);
        }

        return new CategoryResource($category);
    }

    /**
     * Récupère les sous-catégories d'une catégorie
     *
     * @param Request $request
     * @param Category $category
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getSubcategories(Request $request, Category $category)
    {
        // Vérifier que la catégorie est active
        if (!$category->is_active) {
            return response()->json(['message' => 'Catégorie non disponible'], 404);
        }

        // Validation des paramètres
        $validated = $request->validate([
            'active_only' => 'nullable|boolean',
            'with_products_count' => 'nullable|boolean',
            'featured_only' => 'nullable|boolean'
        ]);

        $activeOnly = $validated['active_only'] ?? true;
        $featuredOnly = $validated['featured_only'] ?? false;
        $withProductsCount = $validated['with_products_count'] ?? false;

        $query = $category->children();

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        if ($featuredOnly) {
            $query->where('is_featured', true);
        }

        $subcategories = $query->orderBy('name')->get();

        if ($withProductsCount) {
            $subcategories->loadCount(['products' => function ($query) {
                $query->where('is_active', true);
            }]);
        }

        return CategoryResource::collection($subcategories);
    }

    /**
     * Récupère les catégories populaires basées sur le nombre de produits
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getPopularCategories(Request $request)
    {
        // Validation des paramètres
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:20',
            'with_images' => 'nullable|boolean'
        ]);

        $limit = $validated['limit'] ?? 10;
        $withImages = $validated['with_images'] ?? false;

        $cacheKey = 'popular_categories_' . $limit . '_' . ($withImages ? '1' : '0');

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($limit, $withImages) {
            $categories = Category::where('is_active', true)
                ->withCount(['products' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->orderByDesc('products_count')
                ->take($limit)
                ->get();

            if ($withImages) {
                $categories->load('media');
            }

            return CategoryResource::collection($categories);
        });
    }

    /**
     * Génère un fil d'Ariane pour une catégorie
     *
     * @param Category $category
     * @return array
     */
    private function generateBreadcrumbs(Category $category)
    {
        $breadcrumbs = [];
        $current = $category;
        $breadcrumbs[] = [
            'id' => $current->id,
            'name' => $current->name,
            'slug' => $current->slug
        ];

        while ($current->parent_id) {
            $current = Category::find($current->parent_id);
            if ($current) {
                array_unshift($breadcrumbs, [
                    'id' => $current->id,
                    'name' => $current->name,
                    'slug' => $current->slug
                ]);
            } else {
                break;
            }
        }

        return $breadcrumbs;
    }

    /**
     * Charge récursivement le nombre de produits pour les catégories et leurs enfants
     *
     * @param \Illuminate\Database\Eloquent\Collection $categories
     * @return void
     */
    private function loadProductsCountRecursive($categories)
    {
        $categories->loadCount(['products' => function ($query) {
            $query->where('is_active', true);
        }]);

        foreach ($categories as $category) {
            if ($category->children && $category->children->count() > 0) {
                $this->loadProductsCountRecursive($category->children);
            }
        }
    }
}
