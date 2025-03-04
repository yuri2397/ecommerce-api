<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    // Liste des relations autorisées
    protected $allowedRelations = ['user', 'cartItems', 'cartItems.product', 'products'];

    /**
     * Récupérer le panier de l'utilisateur actuel
     */
    public function getCurrentCart(Request $request)
    {
        $user = Auth::user();
        
        // Récupérer le panier actif de l'utilisateur ou en créer un nouveau
        $cart = Cart::firstOrCreate(
            [
                'user_id' => $user->id,
                'status' => 'active'
            ]
        );

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
        $cart->load($this->validateRelations($relations));

        return new CartResource($cart);
    }

    /**
     * Liste des paniers (pour admin)
     */
    public function index(Request $request)
    {
        // Validation des paramètres de requête
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'orderBy' => 'nullable|in:created_at,updated_at',
            'orderDirection' => 'nullable|in:asc,desc',
            'with' => 'nullable|array',
            'with.*' => [
                'nullable',
                Rule::in($this->allowedRelations)
            ],
            'filter' => 'nullable|array',
            'filter.user_id' => 'nullable|uuid|exists:users,id',
            'filter.status' => 'nullable|in:active,converted,abandoned'
        ]);

        // Configuration par défaut
        $page = $validated['page'] ?? 1;
        $perPage = $validated['perPage'] ?? 15;
        $orderBy = $validated['orderBy'] ?? 'created_at';
        $orderDirection = $validated['orderDirection'] ?? 'desc';

        // Requête de base
        $query = Cart::query();

        // Filtres
        $this->applyFilters($query, $validated['filter'] ?? []);

        // Tri
        $query->orderBy($orderBy, $orderDirection);

        // Charger les relations si demandé
        $relations = $validated['with'] ?? [];
        $query->with($this->validateRelations($relations));

        // Pagination
        $carts = $query->paginate($perPage, ['*'], 'page', $page);

        // Transformation en ressource
        return CartResource::collection($carts);
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
        // Filtre par utilisateur
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filtre par statut
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }

    /**
     * Afficher un panier spécifique
     */
    public function show(Request $request, Cart $cart)
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
        $cart->load($this->validateRelations($relations));

        return new CartResource($cart);
    }

    /**
     * Création d'un nouveau panier
     */
    public function store(StoreCartRequest $request)
    {
        $validated = $request->validated();
        $validated['status'] = $validated['status'] ?? 'active';

        // Création du panier
        $cart = Cart::create($validated);

        return new CartResource($cart);
    }

    /**
     * Mise à jour d'un panier
     */
    public function update(UpdateCartRequest $request, Cart $cart)
    {
        $validated = $request->validated();
        
        $cart->update($validated);

        return new CartResource($cart);
    }

    /**
     * Suppression d'un panier
     */
    public function destroy(Cart $cart)
    {
        // Supprimer tous les articles du panier d'abord
        $cart->cartItems()->delete();
        
        // Puis supprimer le panier
        $cart->delete();

        return response()->json(null, 204);
    }

    /**
     * Vider le panier (supprimer tous les articles)
     */
    public function clear(Cart $cart)
    {
        // Supprimer tous les articles du panier
        $cart->cartItems()->delete();

        return response()->json(['message' => 'Panier vidé avec succès'], 200);
    }

    /**
     * Marquer un panier comme abandonné
     */
    public function markAsAbandoned(Cart $cart)
    {
        $cart->update(['status' => 'abandoned']);
        
        return new CartResource($cart);
    }

    /**
     * Marquer un panier comme converti (transformé en commande)
     */
    public function markAsConverted(Cart $cart)
    {
        $cart->update(['status' => 'converted']);
        
        return new CartResource($cart);
    }

    /**
     * Statistiques sur les paniers
     */
    public function stats()
    {
        $totalCarts = Cart::count();
        $activeCarts = Cart::where('status', 'active')->count();
        $convertedCarts = Cart::where('status', 'converted')->count();
        $abandonedCarts = Cart::where('status', 'abandoned')->count();

        // Paniers récemment créés
        $recentCarts = Cart::with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'total' => $totalCarts,
            'active' => $activeCarts,
            'converted' => $convertedCarts,
            'abandoned' => $abandonedCarts,
            'recent' => CartResource::collection($recentCarts)
        ]);
    }
}
