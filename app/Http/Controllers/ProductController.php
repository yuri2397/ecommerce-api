<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    // Liste des relations autorisées
    protected $allowedRelations = ['category', 'comments'];

    /**
     * Afficher la liste des produits
     */
    public function index(Request $request)
    {

        // Validation des paramètres de requête
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'orderBy' => 'nullable|in:name,price,created_at,stock_quantity',
            'orderDirection' => 'nullable|in:asc,desc',
            'with' => 'nullable|array',
            'with.*' => [
                'nullable',
                Rule::in($this->allowedRelations)
            ],
            'filter' => 'nullable|array',
            'filter.is_active' => 'nullable|boolean',
            'filter.is_featured' => 'nullable|boolean',
            'filter.category_id' => 'nullable|uuid|exists:categories,id',
            'filter.min_price' => 'nullable|numeric|min:0',
            'filter.max_price' => 'nullable|numeric|min:0',
            'filter.in_stock' => 'nullable|boolean'
        ]);

        // Configuration par défaut
        $page = $validated['page'] ?? 1;
        $perPage = $validated['perPage'] ?? 15;
        $orderBy = $validated['orderBy'] ?? 'created_at';
        $orderDirection = $validated['orderDirection'] ?? 'desc';

        // Requête de base
        $query = Product::query();

        // Appliquer les filtres
        $this->applyFilters($query, $validated['filter'] ?? []);

        // Recherche
        if (!empty($validated['search'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('name', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('description', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('sku', 'like', '%' . $validated['search'] . '%');
            });
        }

        // Tri
        $query->orderBy($orderBy, $orderDirection);

        // Charger les relations si demandé
        $relations = $validated['with'] ?? [];
        $query->with($this->validateRelations($relations));

        // Pagination
        $products = $query->paginate($perPage, ['*'], 'page', $page);

        // Transformation en ressource
        return ProductResource::collection($products);
    }

    /**
     * Valider et filtrer les relations
     */
    protected function validateRelations(array $requestedRelations): array
    {
        return array_intersect($requestedRelations, $this->allowedRelations);
    }

    /**
     * Appliquer les filtres à la requête
     */
    protected function applyFilters($query, array $filters)
    {
        // Filtre par statut actif
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Filtre par mise en avant
        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        // Filtre par catégorie
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filtre par prix minimum
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        // Filtre par prix maximum
        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Filtre par disponibilité en stock
        if (isset($filters['in_stock'])) {
            if ($filters['in_stock']) {
                $query->where('stock_quantity', '>', 0);
            } else {
                $query->where('stock_quantity', '=', 0);
            }
        }
    }

    /**
     * Afficher un produit spécifique
     */
    public function show(Request $request, Product $product)
    {
        // Validation des relations demandées
        $validated = $request->validate([
            'with' => 'nullable|array',
            'with.*' => [
                'nullable',
                Rule::in($this->allowedRelations)
            ]
        ]);

        // Charger les relations si demandé
        $relations = $validated['with'] ?? [];
        $product->load($this->validateRelations($relations));

        return new ProductResource($product);
    }

    /**
     * Récupérer les produits par catégorie
     */
    public function byCategory(Request $request, Category $category)
    {
        // Validation des paramètres de requête
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1|max:100',
            'orderBy' => 'nullable|in:name,price,created_at',
            'orderDirection' => 'nullable|in:asc,desc',
            'include_subcategories' => 'nullable|boolean'
        ]);

        // Configuration par défaut
        $page = $validated['page'] ?? 1;
        $perPage = $validated['perPage'] ?? 15;
        $orderBy = $validated['orderBy'] ?? 'created_at';
        $orderDirection = $validated['orderDirection'] ?? 'desc';
        $includeSubcategories = $validated['include_subcategories'] ?? false;

        // Récupérer les IDs des catégories (catégorie principale + sous-catégories si demandé)
        $categoryIds = [$category->id];

        if ($includeSubcategories) {
            // Récupérer les IDs des sous-catégories
            $subCategoryIds = $category->children()->pluck('id')->toArray();
            $categoryIds = array_merge($categoryIds, $subCategoryIds);
        }

        // Requête pour récupérer les produits
        $products = Product::whereIn('category_id', $categoryIds)
            ->where('is_active', true)
            ->orderBy($orderBy, $orderDirection)
            ->paginate($perPage, ['*'], 'page', $page);

        return ProductResource::collection($products);
    }

    /**
     * Récupérer les produits en vedette
     */
    public function featured(Request $request)
    {
        // Validation des paramètres de requête
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1|max:100',
            'category_id' => 'nullable|uuid|exists:categories,id'
        ]);

        // Configuration par défaut
        $page = $validated['page'] ?? 1;
        $perPage = $validated['perPage'] ?? 15;

        // Requête de base
        $query = Product::where('is_featured', true)
            ->where('is_active', true);

        // Filtrer par catégorie si demandé
        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        // Récupérer les produits
        $products = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return ProductResource::collection($products);
    }

    /**
     * Rechercher des produits
     */
    public function search(Request $request)
    {
        // Validation des paramètres de requête
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1|max:100',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'in_stock' => 'nullable|boolean'
        ]);

        // Configuration par défaut
        $page = $validated['page'] ?? 1;
        $perPage = $validated['perPage'] ?? 15;
        $searchTerm = $validated['q'];

        // Requête de base
        $query = Product::where('is_active', true)
            ->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhere('sku', 'like', "%{$searchTerm}%");
            });

        // Filtrer par catégorie
        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        // Filtrer par prix minimum
        if (isset($validated['min_price'])) {
            $query->where('price', '>=', $validated['min_price']);
        }

        // Filtrer par prix maximum
        if (isset($validated['max_price'])) {
            $query->where('price', '<=', $validated['max_price']);
        }

        // Filtrer par disponibilité en stock
        if (isset($validated['in_stock']) && $validated['in_stock']) {
            $query->where('stock_quantity', '>', 0);
        }

        // Récupérer les produits
        $products = $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return ProductResource::collection($products);
    }

    /**
     * Création d'un nouveau produit
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // Générer un SKU unique si non fourni
            if (empty($validated['sku'])) {
                $validated['sku'] = $this->generateUniqueSku($validated['name']);
            }

            // Création du produit
            $product = Product::create($validated);

            // Traitement des images
            if ($request->hasFile('images')) {
                // add many files from the request
                $product->addMediaFromRequest('images')
                    ->toMediaCollection('product_images');
            }

            // Traitement de l'image principale (thumbnail)
            if ($request->hasFile('thumbnail')) {
                $product->addMedia($request->file('thumbnail'))
                    ->toMediaCollection('product_thumbnail');
            } elseif ($request->hasFile('images')) {
                // Utiliser la première image comme thumbnail si aucune n'est spécifiée
                $product->addMedia($request->file('images')[0])
                    ->toMediaCollection('product_thumbnail');
            }
            DB::commit();
            return new ProductResource($product);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création du produit',
                'error' => $th->getMessage()
            ], 500);
        }

        return new ProductResource($product);
    }

    /**
     * Mise à jour d'un produit
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validated();

        $product->update($validated);

        // Traitement des images si demandé
        if ($request->hasFile('images')) {
            // Supprimer les anciennes images si replace_images est true
            if ($request->input('replace_images', false)) {
                $product->clearMediaCollection('product_images');
            }

            // Ajouter les nouvelles images
            foreach ($request->file('images') as $image) {
                $product->addMedia($image)
                    ->toMediaCollection('product_images');
            }
        }

        // Traitement de l'image principale (thumbnail)
        if ($request->hasFile('thumbnail')) {
            $product->clearMediaCollection('product_thumbnail');
            $product->addMedia($request->file('thumbnail'))
                ->toMediaCollection('product_thumbnail');
        }

        return new ProductResource($product);
    }

    /**
     * Suppression d'un produit
     */
    public function destroy(Product $product)
    {
        // Vérifier si le produit est présent dans des commandes
        if ($product->orderItems()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer un produit présent dans des commandes'
            ], 400);
        }

        $product->delete();

        return response()->json(null, 204);
    }

    /**
     * Mettre en avant un produit
     */
    public function feature(Product $product)
    {
        $product->update(['is_featured' => true]);
        return new ProductResource($product);
    }

    /**
     * Retirer la mise en avant d'un produit
     */
    public function unfeature(Product $product)
    {
        $product->update(['is_featured' => false]);
        return new ProductResource($product);
    }

    /**
     * Mettre à jour le stock d'un produit
     */
    public function updateStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'stock_quantity' => 'required|integer|min:0',
            'operation' => 'nullable|in:add,subtract,set'
        ]);

        $operation = $validated['operation'] ?? 'set';
        $quantity = $validated['stock_quantity'];

        switch ($operation) {
            case 'add':
                $product->stock_quantity += $quantity;
                break;
            case 'subtract':
                $product->stock_quantity = max(0, $product->stock_quantity - $quantity);
                break;
            case 'set':
            default:
                $product->stock_quantity = $quantity;
                break;
        }

        $product->save();

        return new ProductResource($product);
    }

    /**
     * Liste des produits pour dropdown
     */
    public function dropdown(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|uuid|exists:categories,id',
            'is_active' => 'nullable|boolean',
            'search' => 'nullable|string|max:50',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        $query = Product::query()
            ->select('id', 'name', 'price', 'sku', 'stock_quantity', 'category_id');

        // Filtrer par catégorie
        if (isset($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        // Filtrer par statut
        if (isset($validated['is_active'])) {
            $query->where('is_active', $validated['is_active']);
        }

        // Recherche par nom ou SKU
        if (!empty($validated['search'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('name', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('sku', 'like', '%' . $validated['search'] . '%');
            });
        }

        // Limiter le nombre de résultats
        $limit = $validated['limit'] ?? 20;

        return response()->json([
            'data' => $query->limit($limit)->get()
        ]);
    }

    /**
     * Statistiques sur les produits
     */
    public function stats()
    {
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $featuredProducts = Product::where('is_featured', true)->count();
        $outOfStockProducts = Product::where('stock_quantity', 0)->count();
        $lowStockProducts = Product::where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', 5)
            ->count();

        // Prix moyen, minimum et maximum
        $priceStats = Product::selectRaw('AVG(price) as avg_price,
                                        MIN(price) as min_price,
                                        MAX(price) as max_price')
            ->first();

        // Produits par catégorie
        $productsByCategory = Category::withCount('products')
            ->orderBy('products_count', 'desc')
            ->limit(10)
            ->get();

        // Produits récemment ajoutés
        $recentlyAdded = Product::orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'price', 'created_at']);

        return response()->json([
            'total' => $totalProducts,
            'active' => $activeProducts,
            'featured' => $featuredProducts,
            'out_of_stock' => $outOfStockProducts,
            'low_stock' => $lowStockProducts,
            'price_stats' => $priceStats,
            'by_category' => $productsByCategory,
            'recently_added' => $recentlyAdded
        ]);
    }

    /**
     * Générer un SKU unique
     */
    private function generateUniqueSku(string $name): string
    {
        $base = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 3));
        $unique = strtoupper(Str::random(5));
        $sku = $base . '-' . $unique;

        // S'assurer que le SKU est unique
        while (Product::where('sku', $sku)->exists()) {
            $unique = strtoupper(Str::random(5));
            $sku = $base . '-' . $unique;
        }

        return $sku;
    }
}
