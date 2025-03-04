<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Liste des paiements d'une commande
     */
    public function index(Order $order)
    {
        $payments = $order->payments()->orderBy('created_at', 'desc')->get();

        return PaymentResource::collection($payments);
    }

    /**
     * Afficher un paiement spécifique
     */
    public function show(Payment $payment)
    {
        return new PaymentResource($payment);
    }

    /**
     * Effectuer un paiement sur une commande (client)
     */
    public function processPayment(StorePaymentRequest $request, Order $order)
    {
        $validated = $request->validated();
        $user = Auth::user();

        // Vérifier que la commande appartient à l'utilisateur
        if ($order->user_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Vérifier que la commande est en attente de paiement
        if (!in_array($order->status, ['pending', 'processing'])) {
            return response()->json([
                'message' => 'Cette commande ne peut pas être payée dans son état actuel'
            ], 400);
        }

        // Vérifier si le montant est valide (doit être égal au montant de la commande)
        if ($validated['amount'] != $order->total_amount) {
            return response()->json([
                'message' => 'Le montant du paiement ne correspond pas au montant de la commande',
                'expected_amount' => $order->total_amount
            ], 400);
        }

        try {
            return DB::transaction(function () use ($order, $validated) {
                // Dans un système réel, vous intégreriez ici un processeur de paiement
                // Pour cet exemple, nous simulons un paiement réussi

                // Générer un ID de transaction
                $transactionId = Str::uuid()->toString();

                // Créer l'enregistrement de paiement
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'payment_method' => $validated['payment_method'],
                    'transaction_id' => $transactionId,
                    'amount' => $validated['amount'],
                    'status' => 'completed',
                    'payment_details' => $validated['payment_details'] ?? []
                ]);

                // Mettre à jour le statut de la commande
                $order->status = 'processing';
                $order->save();

                return new PaymentResource($payment);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Enregistrer un paiement (admin)
     */
    public function store(StorePaymentRequest $request, Order $order)
    {
        $validated = $request->validated();

        try {
            return DB::transaction(function () use ($order, $validated) {
                // Créer l'enregistrement de paiement
                $payment = Payment::create([
                    'order_id' => $order->id,
                    'payment_method' => $validated['payment_method'],
                    'transaction_id' => $validated['transaction_id'] ?? Str::uuid()->toString(),
                    'amount' => $validated['amount'],
                    'status' => $validated['status'] ?? 'completed',
                    'payment_details' => $validated['payment_details'] ?? []
                ]);

                // Si le paiement est complété, mettre à jour le statut de la commande
                if ($payment->status === 'completed' && $order->status === 'pending') {
                    $order->status = 'processing';
                    $order->save();
                }

                return new PaymentResource($payment);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mettre à jour un paiement (admin)
     */
    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        $validated = $request->validated();

        try {
            return DB::transaction(function () use ($payment, $validated) {
                // Sauvegarder l'ancien statut
                $oldStatus = $payment->status;

                // Mettre à jour le paiement
                $payment->update($validated);

                // Si le statut est passé de "pending" à "completed", mettre à jour la commande
                if ($oldStatus !== 'completed' && $payment->status === 'completed') {
                    $order = $payment->order;
                    if ($order->status === 'pending') {
                        $order->status = 'processing';
                        $order->save();
                    }
                }

                return new PaymentResource($payment);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer un paiement (admin)
     */
    public function destroy(Payment $payment)
    {
        // Vérifier que la commande n'est pas déjà expédiée ou livrée
        $order = $payment->order;
        if (in_array($order->status, ['shipped', 'delivered'])) {
            return response()->json([
                'message' => 'Impossible de supprimer un paiement d\'une commande ' . $order->status
            ], 400);
        }

        try {
            return DB::transaction(function () use ($payment, $order) {
                // Si c'était le seul paiement complété et que la commande est en processing, revenir à pending
                $completedPaymentsCount = $order->payments()
                    ->where('status', 'completed')
                    ->where('id', '!=', $payment->id)
                    ->count();

                if ($completedPaymentsCount === 0 && $order->status === 'processing') {
                    $order->status = 'pending';
                    $order->save();
                }

                // Supprimer le paiement
                $payment->delete();

                return response()->json(null, 204);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
