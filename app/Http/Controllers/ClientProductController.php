<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Category;
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

            // Filtre par catégorie
            if (isset($validated['category_id'])) {
                // Inclure les sous-catégories
                $categoryIds = $this->getCategoryAndChildrenIds($validated['category_id']);
                $query->whereIn('category_id', $categoryIds);
            }

            // Filtre pour les produits avec réduction
            if (isset($validated['with_discount']) && $validated['with_discount']) {
                $query->whereNotNull('sale_price')
                    ->whereRaw('sale_price < price');
            }

            // Filtre par prix minimum
            if (isset($validated['price_min'])) {
                $query->where(function ($q) use ($validated) {
                    $q->whereNull('sale_price')->where('price', '>=', $validated['price_min'])
                        ->orWhere(function ($sq) use ($validated) {
                            $sq->whereNotNull('sale_price')->where('sale_price', '>=', $validated['price_min']);
                        });
                });
            }

            // Filtre par prix maximum
            if (isset($validated['price_max'])) {
                $query->where(function ($q) use ($validated) {
                    $q->whereNull('sale_price')->where('price', '<=', $validated['price_max'])
                        ->orWhere(function ($sq) use ($validated) {
                            $sq->whereNotNull('sale_price')->where('sale_price', '<=', $validated['price_max']);
                        });
                });
            }

            // Charger les relations si nécessaire
            if (isset($validated['with_images']) && $validated['with_images']) {
                $query->with('media');
            }

            // Récupérer les produits
            $products = $query->orderBy('created_at', 'desc')
                ->take($limit)
                ->get();

            return ProductResource::collection($products);
        });
    }

    /**
     * Récupère l'ID de la catégorie et de ses enfants
     *
     * @param string $categoryId
     * @return array
     */
    private function getCategoryAndChildrenIds($categoryId)
    {
        $ids = [$categoryId];

        $category = Category::find($categoryId);
        if ($category) {
            // Récupérer récursivement tous les IDs des sous-catégories
            $this->addChildCategoryIds($category, $ids);
        }

        return $ids;
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
