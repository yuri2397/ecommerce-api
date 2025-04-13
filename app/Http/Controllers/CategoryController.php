<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryDropdownResource;
use App\Http\Resources\CategoryTreeResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    // Liste des relations autorisées
    protected $allowedRelations = ['children', 'parent', 'products'];

    /**
     * Afficher la liste des catégories
     */
    public function index(Request $request)
    {
        // Validation des paramètres de requête
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'orderBy' => 'nullable|in:name,created_at',
            'orderDirection' => 'nullable|in:asc,desc',
            'with' => 'nullable|array',
            'with.*' => [
                'nullable',
                Rule::in($this->allowedRelations)
            ],
            'filter' => 'nullable|array',
            'filter.is_active' => 'nullable|in:true,false',
            'filter.has_parent' => 'nullable|boolean',
            'filter.has_no_parent' => 'nullable|boolean'
        ]);

        // Configuration par défaut
        $page = $validated['page'] ?? 1;
        $perPage = $validated['perPage'] ?? 15;
        $orderBy = $validated['orderBy'] ?? 'created_at';
        $orderDirection = $validated['orderDirection'] ?? 'desc';

        // Requête de base
        $query = Category::with($request->with ?? []);

        // Filtres
        $this->applyFilters($query, $validated['filter'] ?? []);

        // Recherche
        if (!empty($validated['search'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('name', 'like', '%' . $validated['search'] . '%')
                    ->orWhere('description', 'like', '%' . $validated['search'] . '%');
            });
        }

        // Tri
        $query->orderBy($orderBy, $orderDirection);


        // Pagination
        $categories = $query->paginate($perPage, ['*'], 'page', $page);

        // Transformation en ressource
        return CategoryResource::collection($categories);
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
        if (isset($filters['is_active']) && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        // Catégories avec parent
        if (isset($filters['has_parent'])) {
            $query->whereNotNull('parent_id');
        }

        // Catégories sans parent
        if (isset($filters['has_no_parent'])) {
            $query->whereNull('parent_id');
        }
    }

    /**
     * Afficher une catégorie spécifique
     */
    public function show(Request $request, Category $category)
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

        $category->load($this->validateRelations($relations));

        return new CategoryResource($category);
    }

    /**
     * Création d'une nouvelle catégorie
     */
    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();

        // Générer un slug unique
        $validated['slug'] = $this->generateUniqueSlug($validated['name']);

        // Création de la catégorie
        $category = Category::create($validated);

        if ($request->hasFile('icon')) {
            $category->addMediaFromRequest('icon')->toMediaCollection(Category::ICON_COLLECTION);
        }

        return new CategoryResource($category);
    }

    /**
     * Mise à jour d'une catégorie
     */
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $validated = $request->validated();

        // Générer un nouveau slug si le nom a changé
        if (isset($validated['name']) && $validated['name'] !== $category->name) {
            $validated['slug'] = $this->generateUniqueSlug($validated['name']);
        }

        $category->update($validated);

        if ($request->hasFile('icon')) {
            // remove old icon
            $category->clearMediaCollection(Category::ICON_COLLECTION);
            $category->addMediaFromRequest('icon')->toMediaCollection(Category::ICON_COLLECTION);
        }

        return new CategoryResource($category);
    }

    /**
     * Suppression d'une catégorie
     */
    public function destroy(Category $category)
    {
        // Vérifier s'il y a des produits ou sous-catégories
        if ($category->products()->exists() || $category->children()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer une catégorie avec des produits ou sous-catégories'
            ], 400);
        }

        $category->delete();

        return response()->json(null, 204);
    }

    /**
     * Générer un slug unique
     */
    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    /**
     * Liste des catégories pour dropdown
     */
    public function dropdown(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|uuid|exists:categories,id',
            'is_active' => 'nullable'
        ]);

        $query = Category::query();

        if (isset($validated['parent_id'])) {
            $query->where('parent_id', $validated['parent_id']);
        }

        if (isset($validated['is_active'])) {
            $query->where('is_active', (bool) $validated['is_active']);
        }

        return CategoryDropdownResource::collection(
            $query->select('id', 'name', 'parent_id')->get()
        );
    }

    /**
     * Arbre complet des catégories
     */
    public function tree()
    {
        $rootCategories = Category::whereNull('parent_id')->get();

        return CategoryTreeResource::collection($rootCategories);
    }

    /**
     * Activer une catégorie
     */
    public function activate(Category $category)
    {
        $category->update(['is_active' => true]);
        return new CategoryResource($category);
    }

    /**
     * Désactiver une catégorie
     */
    public function deactivate(Category $category)
    {
        $category->update(['is_active' => false]);
        return new CategoryResource($category);
    }
}
