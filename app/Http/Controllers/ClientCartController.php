<?php

namespace App\Http\Controllers;

use App\Http\Resources\CartResource;
use App\Http\Resources\CartItemResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientCartController extends Controller
{
    /**
     * Récupérer le panier actuel de l'utilisateur
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

        // Charger les relations
        $cart->load(['cartItems.product']);

        return new CartResource($cart);
    }

    /**
     * Ajouter un produit au panier
     */
    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:products,id,is_active,1',
            'quantity' => 'required|integer|min:1',
            'replace' => 'nullable|boolean'
        ]);

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
        $newTotalQuantity = $validated['replace'] ?? false ? $requestedQuantity : ($currentQuantity + $requestedQuantity);

        if ($newTotalQuantity > $product->stock_quantity) {
            return response()->json([
                'message' => 'La quantité demandée dépasse le stock disponible',
                'available_stock' => $product->stock_quantity
            ], 422);
        }

        try {
            DB::beginTransaction();

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

            DB::commit();

            // Charger la relation produit
            $cartItem->load('product');

            return new CartItemResource($cartItem);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de l\'ajout au panier: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mettre à jour la quantité d'un article dans le panier
     */
    public function updateCartItem(Request $request, CartItem $cartItem)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $user = Auth::user();

        // Vérifier que l'utilisateur est propriétaire du panier
        $cart = Cart::findOrFail($cartItem->cart_id);
        if ($cart->user_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Vérifier la disponibilité du stock
        $product = Product::findOrFail($cartItem->product_id);
        if ($validated['quantity'] > $product->stock_quantity) {
            return response()->json([
                'message' => 'La quantité demandée dépasse le stock disponible',
                'available_stock' => $product->stock_quantity
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Mettre à jour la quantité
            $cartItem->quantity = $validated['quantity'];
            $cartItem->save();

            DB::commit();

            // Charger la relation produit
            $cartItem->load('product');

            return new CartItemResource($cartItem);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer un article du panier
     */
    public function removeCartItem(CartItem $cartItem)
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est propriétaire du panier
        $cart = Cart::findOrFail($cartItem->cart_id);
        if ($cart->user_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        try {
            DB::beginTransaction();

            $cartItem->delete();

            DB::commit();

            return response()->json(['message' => 'Article supprimé du panier'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Vider le panier
     */
    public function clearCart()
    {
        $user = Auth::user();

        // Récupérer le panier actif
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$cart) {
            return response()->json(['message' => 'Panier introuvable'], 404);
        }

        try {
            DB::beginTransaction();

            // Supprimer tous les articles du panier
            $cart->cartItems()->delete();

            DB::commit();

            return response()->json(['message' => 'Panier vidé avec succès'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors du vidage du panier: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Calculer le total du panier
     */
    public function getCartTotal()
    {
        $user = Auth::user();

        // Récupérer le panier actif
        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['cartItems.product'])
            ->first();

        if (!$cart) {
            return response()->json([
                'items_count' => 0,
                'total_amount' => 0
            ]);
        }

        $total = 0;
        $itemsCount = 0;

        foreach ($cart->cartItems as $item) {
            $product = $item->product;
            $price = $product->sale_price ?? $product->price;
            $total += $price * $item->quantity;
            $itemsCount += $item->quantity;
        }

        return response()->json([
            'items_count' => $itemsCount,
            'total_amount' => $total
        ]);
    }
}