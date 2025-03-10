<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductCommentResource;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ClientProductController extends Controller
{
    /**
     * Récupère les produits mis en avant
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getFeaturedProducts(Request $request)
    {
        // Validation des paramètres
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'with_discount' => 'nullable|boolean',
            'with_images' => 'nullable|boolean',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0'
        ]);

        // Paramètres par défaut
        $limit = $validated['limit'] ?? 10;
        $cacheKey = 'featured_products_' . md5(json_encode($validated));

        // Utilisation du cache pour optimiser les performances
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($validated, $limit) {
            $query = Product::where('is_active', true)
                ->where('is_featured', true)
                ->where('stock_quantity', '>', 0);

            // Appliquer les filtres
            $query = $this->applyFilters($query, $validated);

            // Récupérer les produits
            $products = $query->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();

            return ProductResource::collection($products);
        });
    }

    /**
     * Récupère les nouveaux produits
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getNewProducts(Request $request)
    {
        // Validation des paramètres
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'days' => 'nullable|integer|min:1|max:90',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'with_discount' => 'nullable|boolean',
            'with_images' => 'nullable|boolean',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0'
        ]);

        // Paramètres par défaut
        $limit = $validated['limit'] ?? 10;
        $days = $validated['days'] ?? 30;
        $cacheKey = 'new_products_' . md5(json_encode($validated));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($validated, $limit, $days) {
            $query = Product::where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->where('created_at', '>=', now()->subDays($days));

            // Appliquer les filtres
            $query = $this->applyFilters($query, $validated);

            // Récupérer les produits
            $products = $query->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();

            return ProductResource::collection($products);
        });
    }

    /**
     * Récupère les produits en promotion
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getProductsOnSale(Request $request)
    {
        // Validation des paramètres
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'with_images' => 'nullable|boolean',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'discount_min' => 'nullable|integer|min:1|max:100'
        ]);

        // Paramètres par défaut
        $limit = $validated['limit'] ?? 10;
        $discountMin = $validated['discount_min'] ?? 5;
        $cacheKey = 'sale_products_' . md5(json_encode($validated));

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($validated, $limit, $discountMin) {
            $query = Product::where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->whereNotNull('sale_price')
                ->whereRaw('sale_price < price')
                ->whereRaw('(1 - (sale_price / price)) * 100 >= ?', [$discountMin]);

            // Appliquer les filtres
            $query = $this->applyFilters($query, $validated);

            // Récupérer les produits
            $products = $query->orderByRaw('(1 - (sale_price / price)) DESC')
                ->take($limit)
                ->get();

            return ProductResource::collection($products);
        });
    }

    /**
     * Recherche de produits
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function searchProducts(Request $request)
    {
        // Validation des paramètres
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
            'limit' => 'nullable|integer|min:1|max:50',
            'page' => 'nullable|integer|min:1',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'with_images' => 'nullable|boolean',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'sort_by' => 'nullable|in:price,created_at,name',
            'sort_dir' => 'nullable|in:asc,desc'
        ]);

        $query = Product::where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->where(function ($q) use ($validated) {
                $searchTerm = '%' . $validated['q'] . '%';
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('description', 'like', $searchTerm)
                    ->orWhere('sku', 'like', $searchTerm);
            });

        // Appliquer les filtres
        $query = $this->applyFilters($query, $validated);

        // Tri
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $limit = $validated['limit'] ?? 10;
        $page = $validated['page'] ?? 1;
        $products = $query->paginate($limit, ['*'], 'page', $page);

        return ProductResource::collection($products);
    }

    /**
     * Récupère les produits d'une catégorie
     *
     * @param Request $request
     * @param Category $category
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getProductsByCategory(Request $request, Category $category)
    {
        // Vérifier que la catégorie est active
        if (!$category->is_active) {
            return response()->json(['message' => 'Catégorie non disponible'], 404);
        }

        // Validation des paramètres
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'page' => 'nullable|integer|min:1',
            'with_subcategories' => 'nullable|boolean',
            'with_images' => 'nullable|boolean',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'with_discount' => 'nullable|boolean',
            'sort_by' => 'nullable|in:price,created_at,name',
            'sort_dir' => 'nullable|in:asc,desc'
        ]);

        $withSubcategories = $validated['with_subcategories'] ?? true;

        $query = Product::where('is_active', true)
            ->where('stock_quantity', '>', 0);

        if ($withSubcategories) {
            // Récupérer les IDs des sous-catégories
            $categoryIds = $this->getCategoryAndChildrenIds($category->id);
            $query->whereIn('category_id', $categoryIds);
        } else {
            $query->where('category_id', $category->id);
        }

        // Appliquer les filtres
        $query = $this->applyFilters($query, $validated);

        // Tri
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        // Pagination
        $limit = $validated['limit'] ?? 15;
        $page = $validated['page'] ?? 1;
        $products = $query->paginate($limit, ['*'], 'page', $page);

        return ProductResource::collection($products);
    }

    /**
     * Récupère les détails d'un produit
     *
     * @param Request $request
     * @param Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductDetails(Request $request, Product $product)
    {
        // Vérifier que le produit est actif
        if (!$product->is_active) {
            return response()->json(['message' => 'Produit non disponible'], 404);
        }

        // Validation des paramètres
        $validated = $request->validate([
            'with_images' => 'nullable|boolean',
            'with_related' => 'nullable|boolean',
            'with_comments' => 'nullable|boolean'
        ]);

        // Charger les relations
        $relations = [];

        if (isset($validated['with_images']) && $validated['with_images']) {
            $relations[] = 'media';
        }

        if (isset($validated['with_comments']) && $validated['with_comments']) {
            $relations[] = 'comments.user';
        }

        $relations[] = 'category';

        $product->load($relations);

        return new ProductResource($product);
    }

    /**
     * Récupère les commentaires d'un produit
     *
     * @param Request $request
     * @param Product $product
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getProductComments(Request $request, Product $product)
    {
        // Validation des paramètres
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
            'page' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|in:created_at,rating',
            'sort_dir' => 'nullable|in:asc,desc',
            'rating_min' => 'nullable|integer|min:1|max:5'
        ]);

        // Paramètres par défaut
        $limit = $validated['limit'] ?? 10;
        $page = $validated['page'] ?? 1;
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';

        $query = ProductComment::where('product_id', $product->id);

        // Filtrer par note minimale si spécifié
        if (isset($validated['rating_min'])) {
            $query->where('rating', '>=', $validated['rating_min']);
        }

        // Tri et pagination
        $comments = $query->orderBy($sortBy, $sortDir)
            ->with('user:id,name')
            ->paginate($limit, ['*'], 'page', $page);

        return ProductCommentResource::collection($comments);
    }

    /**
     * Récupère les produits similaires à un produit donné
     *
     * @param Request $request
     * @param Product $product
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getRelatedProducts(Request $request, Product $product)
    {
        // Validation des paramètres
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:12',
            'with_images' => 'nullable|boolean'
        ]);

        // Paramètres par défaut
        $limit = $validated['limit'] ?? 8;
        $cacheKey = 'related_products_' . $product->id . '_' . $limit;

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($product, $limit, $validated) {
            // 1. Produits de la même catégorie
            $query = Product::where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->where('id', '!=', $product->id)
                ->where('category_id', $product->category_id);

            // Charger les images si demandé
            if (isset($validated['with_images']) && $validated['with_images']) {
                $query->with('media');
            }

            // Récupérer les produits similaires
            $relatedProducts = $query->take($limit)->get();

            // Si on n'a pas assez de produits, compléter avec d'autres produits populaires
            if ($relatedProducts->count() < $limit) {
                $existingIds = $relatedProducts->pluck('id')->push($product->id)->toArray();
                $remainingCount = $limit - $relatedProducts->count();

                $additionalProducts = Product::where('is_active', true)
                    ->where('stock_quantity', '>', 0)
                    ->whereNotIn('id', $existingIds)
                    ->orderBy('created_at', 'desc')
                    ->take($remainingCount)
                    ->get();

                // Fusionner les collections
                $relatedProducts = $relatedProducts->concat($additionalProducts);
            }

            return ProductResource::collection($relatedProducts);
        });
    }

    /**
     * Récupère plusieurs produits par leurs IDs
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getProductsByIds(Request $request)
    {
        // Validation des paramètres
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|uuid|exists:products,id',
            'with_images' => 'nullable|boolean'
        ]);

        $query = Product::whereIn('id', $validated['ids'])
            ->where('is_active', true);

        // Charger les images si demandé
        if (isset($validated['with_images']) && $validated['with_images']) {
            $query->with('media');
        }

        // Maintenir l'ordre des IDs fournis
        $products = $query->get()
            ->sortBy(function ($product) use ($validated) {
                return array_search($product->id, $validated['ids']);
            })
            ->values();

        return ProductResource::collection($products);
    }

    /**
     * Applique les filtres communs aux requêtes de produits
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyFilters($query, array $filters)
    {
        // Filtre par catégorie
        if (isset($filters['category_id'])) {
            $categoryIds = $this->getCategoryAndChildrenIds($filters['category_id']);
            $query->whereIn('category_id', $categoryIds);
        }

        // Filtre pour les produits avec réduction
        if (isset($filters['with_discount']) && $filters['with_discount']) {
            $query->whereNotNull('sale_price')
                ->whereRaw('sale_price < price');
        }

        // Filtre par prix minimum
        if (isset($filters['price_min'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('sale_price')->where('price', '>=', $filters['price_min'])
                    ->orWhere(function ($sq) use ($filters) {
                        $sq->whereNotNull('sale_price')->where('sale_price', '>=', $filters['price_min']);
                    });
            });
        }

        // Filtre par prix maximum
        if (isset($filters['price_max'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('sale_price')->where('price', '<=', $filters['price_max'])
                    ->orWhere(function ($sq) use ($filters) {
                        $sq->whereNotNull('sale_price')->where('sale_price', '<=', $filters['price_max']);
                    });
            });
        }

        // Charger les relations si nécessaire
        if (isset($filters['with_images']) && $filters['with_images']) {
            $query->with('media');
        }

        return $query;
    }

    /**
     * Récupère l'ID de la catégorie et de ses enfants
     *
     * @param string $categoryId
     * @return array
     */
    private function getCategoryAndChildrenIds($categoryId)
    {
        $cacheKey = 'category_tree_' . $categoryId;

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($categoryId) {
            $ids = [$categoryId];

            $category = Category::find($categoryId);
            if ($category) {
                // Récupérer récursivement tous les IDs des sous-catégories
                $this->addChildCategoryIds($category, $ids);
            }

            return $ids;
        });
    }

    /**
     * Ajoute récursivement les IDs des catégories enfants
     *
     * @param Category $category
     * @param array &$ids
     * @return void
     */
    private function addChildCategoryIds(Category $category, array &$ids)
    {
        foreach ($category->children as $child) {
            $ids[] = $child->id;
            $this->addChildCategoryIds($child, $ids);
        }
    }
}
