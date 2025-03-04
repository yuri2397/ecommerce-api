<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductCommentRequest;
use App\Http\Requests\UpdateProductCommentRequest;
use App\Http\Resources\ProductCommentResource;
use App\Models\Product;
use App\Models\ProductComment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductCommentController extends Controller
{
    // Liste des relations autorisées
    protected $allowedRelations = ['product', 'user'];

    /**
     * Afficher la liste des commentaires
     */
    public function index(Request $request)
    {
        // Validation des paramètres de requête
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'orderBy' => 'nullable|in:created_at,rating',
            'orderDirection' => 'nullable|in:asc,desc',
            'with' => 'nullable|array',
            'with.*' => [
                'nullable',
                Rule::in($this->allowedRelations)
            ],
            'filter' => 'nullable|array',
            'filter.product_id' => 'nullable|uuid|exists:products,id',
            'filter.user_id' => 'nullable|uuid|exists:users,id',
            'filter.min_rating' => 'nullable|integer|min:1|max:5',
            'filter.max_rating' => 'nullable|integer|min:1|max:5'
        ]);

        // Configuration par défaut
        $page = $validated['page'] ?? 1;
        $perPage = $validated['perPage'] ?? 15;
        $orderBy = $validated['orderBy'] ?? 'created_at';
        $orderDirection = $validated['orderDirection'] ?? 'desc';

        // Requête de base
        $query = ProductComment::query();

        // Filtres
        $this->applyFilters($query, $validated['filter'] ?? []);

        // Recherche
        if (!empty($validated['search'])) {
            $query->where('content', 'like', '%' . $validated['search'] . '%');
        }

        // Tri
        $query->orderBy($orderBy, $orderDirection);

        // Charger les relations si demandé
        $relations = $validated['with'] ?? [];
        $query->with($this->validateRelations($relations));

        // Pagination
        $comments = $query->paginate($perPage, ['*'], 'page', $page);

        // Transformation en ressource
        return ProductCommentResource::collection($comments);
    }

    /**
     * Récupérer les commentaires d'un produit spécifique
     */
    public function getProductComments(Request $request, Product $product)
    {
        // Validation des paramètres de requête
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1|max:100',
            'orderBy' => 'nullable|in:created_at,rating',
            'orderDirection' => 'nullable|in:asc,desc',
            'with' => 'nullable|array',
            'with.*' => [
                'nullable',
                Rule::in($this->allowedRelations)
            ],
            'filter' => 'nullable|array',
            'filter.min_rating' => 'nullable|integer|min:1|max:5',
            'filter.max_rating' => 'nullable|integer|min:1|max:5'
        ]);

        // Configuration par défaut
        $page = $validated['page'] ?? 1;
        $perPage = $validated['perPage'] ?? 15;
        $orderBy = $validated['orderBy'] ?? 'created_at';
        $orderDirection = $validated['orderDirection'] ?? 'desc';

        // Requête de base pour récupérer les commentaires du produit
        $query = ProductComment::where('product_id', $product->id);

        // Filtres supplémentaires
        if (isset($validated['filter']['min_rating'])) {
            $query->where('rating', '>=', $validated['filter']['min_rating']);
        }

        if (isset($validated['filter']['max_rating'])) {
            $query->where('rating', '<=', $validated['filter']['max_rating']);
        }

        // Tri
        $query->orderBy($orderBy, $orderDirection);

        // Charger les relations si demandé
        $relations = $validated['with'] ?? [];
        $query->with($this->validateRelations($relations));

        // Pagination
        $comments = $query->paginate($perPage, ['*'], 'page', $page);

        // Transformation en ressource
        return ProductCommentResource::collection($comments);
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
        // Filtre par produit
        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        // Filtre par utilisateur
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filtre par note minimale
        if (isset($filters['min_rating'])) {
            $query->where('rating', '>=', $filters['min_rating']);
        }

        // Filtre par note maximale
        if (isset($filters['max_rating'])) {
            $query->where('rating', '<=', $filters['max_rating']);
        }
    }

    /**
     * Afficher un commentaire spécifique
     */
    public function show(Request $request, ProductComment $comment)
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
        $comment->load($this->validateRelations($relations));

        return new ProductCommentResource($comment);
    }

    /**
     * Création d'un nouveau commentaire
     */
    public function store(StoreProductCommentRequest $request)
    {
        $validated = $request->validated();

        // Création du commentaire
        $comment = ProductComment::create($validated);

        // Mise à jour du nombre de commentaires et moyenne des notes pour le produit
        $this->updateProductRatingStats($comment->product_id);

        return new ProductCommentResource($comment);
    }

    /**
     * Mise à jour d'un commentaire
     */
    public function update(UpdateProductCommentRequest $request, ProductComment $comment)
    {
        $validated = $request->validated();

        $comment->update($validated);

        // Mise à jour du nombre de commentaires et moyenne des notes pour le produit
        $this->updateProductRatingStats($comment->product_id);

        return new ProductCommentResource($comment);
    }

    /**
     * Suppression d'un commentaire
     */
    public function destroy(ProductComment $comment)
    {
        $productId = $comment->product_id;

        $comment->delete();

        // Mise à jour du nombre de commentaires et moyenne des notes pour le produit
        $this->updateProductRatingStats($productId);

        return response()->json(null, 204);
    }

    /**
     * Mise à jour des statistiques de notation pour un produit
     */
    private function updateProductRatingStats($productId)
    {
        // Cette méthode serait utilisée pour mettre à jour les statistiques de notation
        // Si vous avez des champs comme average_rating ou comments_count dans la table products
        // Vous pouvez les mettre à jour ici

        // Exemple: Calculer la note moyenne
        $averageRating = ProductComment::where('product_id', $productId)
            ->whereNotNull('rating')
            ->avg('rating');

        // Exemple: Compter le nombre de commentaires
        $commentsCount = ProductComment::where('product_id', $productId)->count();

        // Si vous avez ces colonnes dans votre table products, vous pourriez
        // mettre à jour le produit ici
        // Product::where('id', $productId)->update([
        //     'average_rating' => $averageRating,
        //     'comments_count' => $commentsCount
        // ]);
    }

    /**
     * Statistiques sur les commentaires
     */
    public function stats(Request $request)
    {
        // Validation des paramètres de requête
        $validated = $request->validate([
            'product_id' => 'nullable|uuid|exists:products,id'
        ]);

        $query = ProductComment::query();

        // Filtrer par produit si demandé
        if (isset($validated['product_id'])) {
            $query->where('product_id', $validated['product_id']);
        }

        // Nombre total de commentaires
        $totalComments = $query->count();

        // Distribution des notes
        $ratingDistribution = $query->whereNotNull('rating')
            ->selectRaw('rating, count(*) as count')
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->get();

        // Note moyenne
        $averageRating = $query->whereNotNull('rating')->avg('rating');

        // Commentaires récents
        $recentComments = ProductComment::with(['user:id,name', 'product:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_comments' => $totalComments,
            'average_rating' => round($averageRating, 1),
            'rating_distribution' => $ratingDistribution,
            'recent_comments' => ProductCommentResource::collection($recentComments)
        ]);
    }
}
