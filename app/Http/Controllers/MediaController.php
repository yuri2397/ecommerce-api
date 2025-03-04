<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{
    /**
     * Supprimer une image spécifique
     */
    public function destroy(Media $media)
    {
        $media->delete();

        return response()->json(['message' => 'Media supprimé avec succès'], 200);
    }

    /**
     * Définir une image comme principale pour un produit
     */
    public function setAsThumbnail(Request $request, Product $product, Media $media)
    {
        // Vérifier que le media appartient au produit
        if ($media->model_id !== $product->id || $media->model_type !== Product::class) {
            return response()->json(['message' => 'Ce média n\'appartient pas à ce produit'], 400);
        }

        // Supprimer l'ancienne image principale
        $product->clearMediaCollection('product_thumbnail');

        // Créer une copie du média dans la collection thumbnail
        $product->addMedia($media->getPath())
            ->preservingOriginal()
            ->toMediaCollection('product_thumbnail');

        return response()->json(['message' => 'Image définie comme principale'], 200);
    }

    /**
     * Réorganiser les images d'un produit
     */
    public function reorder(Request $request, Product $product)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'required|integer|exists:media,id'
        ]);

        $mediaItems = $product->getMedia('product_images');

        // Vérifier que tous les médias appartiennent bien au produit
        $mediaIds = $mediaItems->pluck('id')->toArray();
        foreach ($validated['order'] as $mediaId) {
            if (!in_array($mediaId, $mediaIds)) {
                return response()->json(['message' => 'Certains médias n\'appartiennent pas à ce produit'], 400);
            }
        }

        // Réorganiser les médias
        Media::setNewOrder($validated['order']);

        return response()->json(['message' => 'Ordre des images mis à jour'], 200);
    }
}
