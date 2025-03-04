<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartItemController extends Controller
{
    /**
     * Ajouter un produit au panier courant
     */
    public function addToCart(StoreCartItemRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();
        
        // Récupérer le panier actif ou en créer un nouveau
        $cart = Cart::firstOrCreate(
            ['user_id' => $user->id, 'status' => 'active']
        );
        
        // Vérifier si le produit existe déjà dans le panier
        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $validated['product_id'])
            ->first();
        
        // Vérifier la disponibilité du stock
        $product = Product::findOrFail($validated['product_id']);
        $requestedQuantity = $validated['quantity'];
        $currentQuantity = $existingItem ? $existingItem->quantity : 0;
        $newTotalQuantity = $validated['replace'] ? $requestedQuantity : ($currentQuantity + $requestedQuantity);
        
        if ($newTotalQuantity > $product->stock_quantity) {
            return response()->json([
                'message' => 'La quantité demandée dépasse le stock disponible',
                'available_stock' => $product->stock_quantity
            ], 422);
        }
        
        // Si le produit existe déjà, mettre à jour la quantité
        if ($existingItem) {
            if ($validated['replace'] ?? false) {
                $existingItem->quantity = $requestedQuantity;
            } else {
                $existingItem->quantity += $requestedQuantity;
            }
            $existingItem->save();
            $cartItem = $existingItem;
        } else {
            // Sinon, créer un nouvel article
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $validated['product_id'],
                'quantity' => $requestedQuantity
            ]);
        }
        
        // Charger la relation produit
        $cartItem->load('product');
        
        return new CartItemResource($cartItem);
    }
    
    /**
     * Mettre à jour la quantité d'un article dans le panier
     */
    public function updateQuantity(UpdateCartItemRequest $request, CartItem $cartItem)
    {
        $validated = $request->validated();
        
        // Vérifier la disponibilité du stock
        $product = Product::findOrFail($cartItem->product_id);
        if ($validated['quantity'] > $product->stock_quantity) {
            return response()->json([
                'message' => 'La quantité demandée dépasse le stock disponible',
                'available_stock' => $product->stock_quantity
            ], 422);
        }
        
        // Mettre à jour la quantité
        $cartItem->quantity = $validated['quantity'];
        $cartItem->save();
        
        // Charger la relation produit
        $cartItem->load('product');
        
        return new CartItemResource($cartItem);
    }
    
    /**
     * Supprimer un article du panier
     */
    public function removeFromCart(CartItem $cartItem)
    {
        // Vérifier que l'utilisateur est propriétaire du panier
        $user = Auth::user();
        $cart = Cart::findOrFail($cartItem->cart_id);
        
        if ($cart->user_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        $cartItem->delete();
        
        return response()->json(['message' => 'Article supprimé du panier'], 200);
    }
    
    /**
     * Liste des articles d'un panier
     */
    public function index(Request $request, Cart $cart)
    {
        // Vérifier que l'utilisateur est propriétaire du panier
        $user = Auth::user();
        if ($cart->user_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        // Récupérer les articles avec leurs produits
        $cartItems = $cart->cartItems()->with('product')->get();
        
        return CartItemResource::collection($cartItems);
    }
    
    /**
     * Afficher un article spécifique
     */
    public function show(CartItem $cartItem)
    {
        // Vérifier que l'utilisateur est propriétaire du panier
        $user = Auth::user();
        $cart = Cart::findOrFail($cartItem->cart_id);
        
        if ($cart->user_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        // Charger la relation produit
        $cartItem->load('product');
        
        return new CartItemResource($cartItem);
    }
    
    /**
     * Calculer le total du panier (pour les admins)
     */
    public function calculateCartTotal(Cart $cart)
    {
        $total = 0;
        
        foreach ($cart->cartItems as $item) {
            $product = $item->product;
            $price = $product->sale_price ?? $product->price;
            $total += $price * $item->quantity;
        }
        
        return response()->json([
            'cart_id' => $cart->id,
            'items_count' => $cart->cartItems->count(),
            'total_amount' => $total
        ]);
    }
}
