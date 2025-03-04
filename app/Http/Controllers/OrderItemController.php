<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderItemRequest;
use App\Http\Requests\UpdateOrderItemRequest;
use App\Http\Resources\OrderItemResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderItemController extends Controller
{
    /**
     * Liste des articles d'une commande
     */
    public function index(Order $order)
    {
        $orderItems = $order->orderItems()->with('product')->get();

        return OrderItemResource::collection($orderItems);
    }

    /**
     * Afficher un article spécifique d'une commande
     */
    public function show(OrderItem $orderItem)
    {
        $orderItem->load('product');

        return new OrderItemResource($orderItem);
    }

    /**
     * Ajouter un article à une commande (admin)
     */
    public function store(StoreOrderItemRequest $request, Order $order)
    {
        $validated = $request->validated();

        // Vérifier que la commande n'est pas déjà expédiée ou livrée
        if (in_array($order->status, ['shipped', 'delivered', 'cancelled'])) {
            return response()->json([
                'message' => 'Impossible de modifier une commande ' . $order->status
            ], 400);
        }

        // Récupérer le produit
        $product = Product::findOrFail($validated['product_id']);

        // Vérifier le stock
        if ($validated['quantity'] > $product->stock_quantity) {
            return response()->json([
                'message' => 'Stock insuffisant',
                'available' => $product->stock_quantity
            ], 400);
        }

        try {
            return DB::transaction(function () use ($order, $validated, $product) {
                // Calculer le prix
                $price = $product->sale_price ?? $product->price;

                // Créer l'article
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $validated['product_id'],
                    'quantity' => $validated['quantity'],
                    'price' => $price
                ]);

                // Mettre à jour le stock
                $product->stock_quantity -= $validated['quantity'];
                $product->save();

                // Mettre à jour le montant total de la commande
                $totalAmount = $order->orderItems->sum(function ($item) {
                    return $item->price * $item->quantity;
                });
                $totalAmount += $price * $validated['quantity'];

                $order->total_amount = $totalAmount;
                $order->save();

                // Charger la relation produit
                $orderItem->load('product');

                return new OrderItemResource($orderItem);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mettre à jour un article d'une commande (admin)
     */
    public function update(UpdateOrderItemRequest $request, OrderItem $orderItem)
    {
        $validated = $request->validated();

        // Récupérer la commande
        $order = $orderItem->order;

        // Vérifier que la commande n'est pas déjà expédiée ou livrée
        if (in_array($order->status, ['shipped', 'delivered', 'cancelled'])) {
            return response()->json([
                'message' => 'Impossible de modifier une commande ' . $order->status
            ], 400);
        }

        // Récupérer le produit
        $product = $orderItem->product;

        // Calculer la différence de quantité
        $quantityDiff = $validated['quantity'] - $orderItem->quantity;

        // Vérifier le stock si on augmente la quantité
        if ($quantityDiff > 0 && $quantityDiff > $product->stock_quantity) {
            return response()->json([
                'message' => 'Stock insuffisant',
                'available' => $product->stock_quantity
            ], 400);
        }

        try {
            return DB::transaction(function () use ($orderItem, $validated, $product, $order, $quantityDiff) {
                // Mettre à jour l'article
                $orderItem->quantity = $validated['quantity'];
                $orderItem->save();

                // Mettre à jour le stock
                if ($quantityDiff !== 0) {
                    $product->stock_quantity -= $quantityDiff;
                    $product->save();
                }

                // Recalculer le montant total de la commande
                $totalAmount = $order->orderItems->sum(function ($item) {
                    return $item->price * $item->quantity;
                });

                $order->total_amount = $totalAmount;
                $order->save();

                // Charger la relation produit
                $orderItem->load('product');

                return new OrderItemResource($orderItem);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer un article d'une commande (admin)
     */
    public function destroy(OrderItem $orderItem)
    {
        // Récupérer la commande
        $order = $orderItem->order;

        // Vérifier que la commande n'est pas déjà expédiée ou livrée
        if (in_array($order->status, ['shipped', 'delivered', 'cancelled'])) {
            return response()->json([
                'message' => 'Impossible de modifier une commande ' . $order->status
            ], 400);
        }

        try {
            return DB::transaction(function () use ($orderItem, $order) {
                // Restituer le stock
                $product = $orderItem->product;
                $product->stock_quantity += $orderItem->quantity;
                $product->save();

                // Sauvegarder les informations pour recalculer le total
                $itemAmount = $orderItem->price * $orderItem->quantity;

                // Supprimer l'article
                $orderItem->delete();

                // Mettre à jour le montant total de la commande
                $order->total_amount -= $itemAmount;
                $order->save();

                return response()->json(null, 204);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
