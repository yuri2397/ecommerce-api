<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    // Liste des relations autorisées
    protected $allowedRelations = ['user', 'orderItems', 'orderItems.product', 'products', 'payments'];

    /**
     * Liste des commandes de l'utilisateur actuel
     */
    public function myOrders(Request $request)
    {
        $user = Auth::user();

        // Validation des paramètres de requête
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1|max:50',
            'orderBy' => 'nullable|in:created_at,total_amount',
            'orderDirection' => 'nullable|in:asc,desc',
            'with' => 'nullable|array',
            'with.*' => [
                'nullable',
                Rule::in($this->allowedRelations)
            ],
            'filter' => 'nullable|array',
            'filter.status' => 'nullable|in:pending,processing,shipped,delivered,cancelled'
        ]);

        // Configuration par défaut
        $page = $validated['page'] ?? 1;
        $perPage = $validated['perPage'] ?? 10;
        $orderBy = $validated['orderBy'] ?? 'created_at';
        $orderDirection = $validated['orderDirection'] ?? 'desc';

        // Requête de base
        $query = Order::where('user_id', $user->id);

        // Appliquer les filtres
        if (isset($validated['filter']['status'])) {
            $query->where('status', $validated['filter']['status']);
        }

        // Tri
        $query->orderBy($orderBy, $orderDirection);

        // Charger les relations si demandé
        $relations = $validated['with'] ?? [];
        $query->with($this->validateRelations($relations));

        // Pagination
        $orders = $query->paginate($perPage, ['*'], 'page', $page);

        // Transformation en ressource
        return OrderResource::collection($orders);
    }

    /**
     * Détails d'une commande de l'utilisateur actuel
     */
    public function showMyOrder(Request $request, Order $order)
    {
        $user = Auth::user();

        // Vérifier que la commande appartient à l'utilisateur
        if ($order->user_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

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
        $order->load($this->validateRelations($relations));

        return new OrderResource($order);
    }

    /**
     * Créer une commande à partir du panier actuel
     */
    public function createFromCart(StoreOrderRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();

        // Récupérer le panier actif de l'utilisateur
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['cartItems.product'])
            ->first();

        if (!$cart || $cart->cartItems->isEmpty()) {
            return response()->json(['message' => 'Panier vide ou inexistant'], 400);
        }

        try {
            // Commencer une transaction
            return DB::transaction(function () use ($cart, $validated, $user) {
                // Calculer le montant total
                $totalAmount = 0;

                foreach ($cart->cartItems as $item) {
                    $price = $item->product->sale_price ?? $item->product->price;
                    $totalAmount += $price * $item->quantity;

                    // Vérifier le stock
                    if ($item->quantity > $item->product->stock_quantity) {
                        throw new \Exception("Stock insuffisant pour : {$item->product->name}");
                    }
                }

                // Créer la commande
                $order = Order::create([
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'total_amount' => $totalAmount,
                    'shipping_address' => $validated['shipping_address'],
                    'billing_address' => $validated['billing_address'] ?? $validated['shipping_address']
                ]);

                // Créer les articles de la commande
                foreach ($cart->cartItems as $item) {
                    $price = $item->product->sale_price ?? $item->product->price;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'price' => $price
                    ]);

                    // Mettre à jour le stock du produit
                    $product = $item->product;
                    $product->stock_quantity -= $item->quantity;
                    $product->save();
                }

                // Marquer le panier comme converti
                $cart->status = 'converted';
                $cart->save();

                // Charger les relations pour la réponse
                $order->load(['orderItems.product']);

                return new OrderResource($order);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Annuler une commande
     */
    public function cancelMyOrder(Order $order)
    {
        $user = Auth::user();

        // Vérifier que la commande appartient à l'utilisateur
        if ($order->user_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Vérifier que la commande peut être annulée
        if (!in_array($order->status, ['pending', 'processing'])) {
            return response()->json(['message' => 'Cette commande ne peut plus être annulée'], 400);
        }

        try {
            // Commencer une transaction
            return DB::transaction(function () use ($order) {
                // Mettre à jour le statut de la commande
                $order->status = 'cancelled';
                $order->save();

                // Restituer les stocks
                foreach ($order->orderItems as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->stock_quantity += $item->quantity;
                        $product->save();
                    }
                }

                return new OrderResource($order);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Liste de toutes les commandes (admin)
     */
    public function index(Request $request)
    {
        // Validation des paramètres de requête
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'orderBy' => 'nullable|in:created_at,total_amount,status',
            'orderDirection' => 'nullable|in:asc,desc',
            'with' => 'nullable|array',
            'with.*' => [
                'nullable',
                Rule::in($this->allowedRelations)
            ],
            'filter' => 'nullable|array',
            'filter.status' => 'nullable|in:pending,processing,shipped,delivered,cancelled',
            'filter.user_id' => 'nullable|uuid|exists:users,id',
            'filter.min_amount' => 'nullable|numeric|min:0',
            'filter.max_amount' => 'nullable|numeric|min:0',
            'filter.date_from' => 'nullable|date',
            'filter.date_to' => 'nullable|date|after_or_equal:filter.date_from'
        ]);

        // Configuration par défaut
        $page = $validated['page'] ?? 1;
        $perPage = $validated['perPage'] ?? 15;
        $orderBy = $validated['orderBy'] ?? 'created_at';
        $orderDirection = $validated['orderDirection'] ?? 'desc';

        // Requête de base
        $query = Order::query();

        // Filtres
        $this->applyFilters($query, $validated['filter'] ?? []);

        // Recherche
        if (!empty($validated['search'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('id', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('shipping_address', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('billing_address', 'like', '%' . $validated['search'] . '%');
            });
        }

        // Tri
        $query->orderBy($orderBy, $orderDirection);

        // Charger les relations si demandé
        $relations = $validated['with'] ?? [];
        $query->with($this->validateRelations($relations));

        // Pagination
        $orders = $query->paginate($perPage, ['*'], 'page', $page);

        // Transformation en ressource
        return OrderResource::collection($orders);
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
        // Filtre par statut
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtre par utilisateur
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filtre par montant minimum
        if (isset($filters['min_amount'])) {
            $query->where('total_amount', '>=', $filters['min_amount']);
        }

        // Filtre par montant maximum
        if (isset($filters['max_amount'])) {
            $query->where('total_amount', '<=', $filters['max_amount']);
        }

        // Filtre par date de début
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        // Filtre par date de fin
        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
    }

    /**
     * Afficher une commande spécifique (admin)
     */
    public function show(Request $request, Order $order)
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
        $order->load($this->validateRelations($relations));

        return new OrderResource($order);
    }

    /**
     * Mise à jour d'une commande (admin)
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $validated = $request->validated();

        // Si le statut passe à "cancelled" et qu'il ne l'était pas avant, restituer les stocks
        $wasNotCancelled = $order->status !== 'cancelled';
        $becomingCancelled = isset($validated['status']) && $validated['status'] === 'cancelled';

        try {
            return DB::transaction(function () use ($order, $validated, $wasNotCancelled, $becomingCancelled) {
                // Mise à jour de la commande
                $order->update($validated);

                // Restituer les stocks si nécessaire
                if ($wasNotCancelled && $becomingCancelled) {
                    foreach ($order->orderItems as $item) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $product->stock_quantity += $item->quantity;
                            $product->save();
                        }
                    }
                }

                return new OrderResource($order);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Suppression d'une commande (admin)
     */
    public function destroy(Order $order)
    {
        // Vérifier si la commande a des paiements
        if ($order->payments()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer une commande avec des paiements enregistrés'
            ], 400);
        }

        try {
            return DB::transaction(function () use ($order) {
                // Si la commande n'est pas annulée, restituer les stocks
                if ($order->status !== 'cancelled') {
                    foreach ($order->orderItems as $item) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $product->stock_quantity += $item->quantity;
                            $product->save();
                        }
                    }
                }

                // Supprimer les articles de la commande d'abord
                $order->orderItems()->delete();

                // Puis supprimer la commande
                $order->delete();

                return response()->json(null, 204);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Changer le statut d'une commande (admin)
     */
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled'
        ]);

        // Si le statut passe à "cancelled" et qu'il ne l'était pas avant, restituer les stocks
        $wasNotCancelled = $order->status !== 'cancelled';
        $becomingCancelled = $validated['status'] === 'cancelled';

        try {
            return DB::transaction(function () use ($order, $validated, $wasNotCancelled, $becomingCancelled) {
                // Mise à jour du statut
                $order->status = $validated['status'];
                $order->save();

                // Restituer les stocks si nécessaire
                if ($wasNotCancelled && $becomingCancelled) {
                    foreach ($order->orderItems as $item) {
                        $product = Product::find($item->product_id);
                        if ($product) {
                            $product->stock_quantity += $item->quantity;
                            $product->save();
                        }
                    }
                }

                return new OrderResource($order);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Statistiques sur les commandes (admin)
     */
    public function stats(Request $request)
    {
        // Validation des paramètres de filtrage
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        // Requête de base
        $query = Order::query();

        // Appliquer les filtres de date si présents
        if (isset($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (isset($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        // Nombre total de commandes
        $totalOrders = $query->count();

        // Montant total des commandes
        $totalAmount = $query->sum('total_amount');

        // Nombre de commandes par statut
        $byStatus = $query->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        // Commandes récentes
        $recentOrders = Order::with(['user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Montant moyen des commandes
        $averageAmount = $totalOrders > 0 ? $totalAmount / $totalOrders : 0;

        return response()->json([
            'total_orders' => $totalOrders,
            'total_amount' => $totalAmount,
            'average_amount' => round($averageAmount, 2),
            'by_status' => $byStatus,
            'recent_orders' => OrderResource::collection($recentOrders)
        ]);
    }
}
