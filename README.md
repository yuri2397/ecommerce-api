# E-Commerce API - Laravel

Une API RESTful complète pour une plateforme e-commerce modeste spécialisée dans la mode, développée avec Laravel.

## Table des matières

- [Aperçu](#aperçu)
- [Fonctionnalités](#fonctionnalités)
- [Structure du projet](#structure-du-projet)
- [Installation](#installation)
- [Configuration](#configuration)
- [Base de données](#base-de-données)
- [Modèles et relations](#modèles-et-relations)
- [API Endpoints](#api-endpoints)
- [Authentification](#authentification)
- [Gestion des médias](#gestion-des-médias)
- [Validation](#validation)
- [Autorisations](#autorisations)
- [Exemples d'utilisation](#exemples-dutilisation)
- [Tests](#tests)
- [Déploiement](#déploiement)
- [Licence](#licence)

## Aperçu

Cette API sert de backend pour une boutique en ligne de mode modeste. Elle gère les produits, catégories, commentaires utilisateurs, paniers d'achats et commandes à travers une interface RESTful complète. Elle est conçue pour être robuste, bien documentée et facilement extensible.

## Fonctionnalités

- **Gestion des produits** : CRUD complet, filtrage avancé, recherche
- **Catégories hiérarchiques** : Structure parent-enfant pour une organisation optimale
- **Système de commentaires** : Avis et notations sur les produits
- **Paniers d'achats** : Gestion complète du processus d'achat
- **Commandes** : Création et suivi des commandes
- **Gestion des médias** : Upload et organisation des images produits
- **Authentification et autorisation** : Sécurité basée sur les rôles et permissions
- **Pagination** : Résultats paginés pour toutes les listes
- **Validation** : Validation stricte des données entrantes
- **Relations chargées à la demande** : Optimisation des performances

## Structure du projet

```
app/
├── Http/
│   ├── Controllers/     # Contrôleurs API
│   ├── Requests/        # Classes de validation de requêtes
│   └── Resources/       # Transformateurs de ressources API
├── Models/              # Modèles Eloquent
└── Utils/              # Classes utilitaires
database/
├── migrations/          # Migrations de base de données
└── seeders/            # Données de test
routes/
└── api.php             # Définition des routes API
```

## Installation

1. Cloner le dépôt

```bash
git clone https://github.com/votre-nom/ecommerce-api.git
cd ecommerce-api
```

2. Installer les dépendances

```bash
composer install
```

3. Configurer l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

4. Configurer la base de données dans le fichier `.env`

5. Exécuter les migrations et les seeders

```bash
php artisan migrate --seed
```

6. Lancer le serveur

```bash
php artisan serve
```

## Configuration

### Configuration de l'environnement

Assurez-vous de configurer correctement les variables suivantes dans le fichier `.env` :

```
APP_NAME="E-Commerce API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=localhost,localhost:8000
SESSION_DOMAIN=localhost
```

### Configuration du stockage des médias

Ce projet utilise la bibliothèque Spatie Media Library pour la gestion des médias. Configurer le stockage des médias :

```bash
php artisan storage:link
```

## Base de données

### Diagramme de la base de données

La base de données comprend les tables suivantes :

- `users` - Utilisateurs du système
- `categories` - Catégories de produits (hiérarchiques)
- `products` - Produits disponibles à la vente
- `product_comments` - Commentaires et évaluations sur les produits
- `carts` - Paniers d'achat des utilisateurs
- `cart_items` - Articles dans les paniers
- `orders` - Commandes passées
- `order_items` - Articles dans les commandes
- `payments` - Informations de paiement

### Migrations

Toutes les migrations sont organisées avec des préfixes numériques pour contrôler l'ordre d'exécution. La principale migration créant les tables métier est `0001_01_01_000003_create_app_table.php`.

### Données de test

Des seeders sont fournis pour peupler la base de données avec des données de test. Le seeder principal `DatabaseSeeder` orchestre tous les autres seeders, notamment `CategorySeeder` qui crée une structure hiérarchique de catégories adaptée à une boutique de mode.

## Modèles et relations

Tous les modèles Eloquent sont définis avec leurs relations, garantissant une navigation fluide entre les entités liées.

### Exemples de relations

```php
// Dans le modèle Product
public function category()
{
    return $this->belongsTo(Category::class);
}

public function comments()
{
    return $this->hasMany(ProductComment::class);
}

// Dans le modèle Category
public function children()
{
    return $this->hasMany(Category::class, 'parent_id');
}

public function parent()
{
    return $this->belongsTo(Category::class, 'parent_id');
}
```

## API Endpoints

### Produits

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/products` | Liste des produits (avec filtres) |
| GET | `/api/products/{sku}` | Détails d'un produit |
| POST | `/api/admin/products` | Créer un produit |
| PUT | `/api/admin/products/{id}` | Mettre à jour un produit |
| DELETE | `/api/admin/products/{id}` | Supprimer un produit |
| GET | `/api/products/category/{slug}` | Produits par catégorie |
| PATCH | `/api/admin/products/{id}/stock` | Mettre à jour le stock |
| PATCH | `/api/admin/products/{id}/feature` | Mettre en avant un produit |

### Catégories

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/categories` | Liste des catégories |
| GET | `/api/categories/{slug}` | Détails d'une catégorie |
| POST | `/api/admin/categories` | Créer une catégorie |
| PUT | `/api/admin/categories/{id}` | Mettre à jour une catégorie |
| DELETE | `/api/admin/categories/{id}` | Supprimer une catégorie |
| PATCH | `/api/admin/categories/{id}/activate` | Activer une catégorie |
| PATCH | `/api/admin/categories/{id}/deactivate` | Désactiver une catégorie |

### Commentaires

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/products/{product}/comments` | Commentaires d'un produit |
| POST | `/api/comments` | Ajouter un commentaire |
| PUT | `/api/comments/{id}` | Modifier un commentaire |
| DELETE | `/api/comments/{id}` | Supprimer un commentaire |
| GET | `/api/admin/comments` | Admin: Liste des commentaires |
| GET | `/api/admin/comments/stats` | Admin: Statistiques des commentaires |

### Panier

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/cart` | Panier actuel de l'utilisateur |
| POST | `/api/cart/items` | Ajouter un article au panier |
| PUT | `/api/cart/items/{id}` | Modifier la quantité |
| DELETE | `/api/cart/items/{id}` | Retirer un article |
| DELETE | `/api/cart/clear` | Vider le panier |
| GET | `/api/admin/carts` | Admin: Liste des paniers |
| PATCH | `/api/admin/carts/{id}/mark-as-abandoned` | Admin: Marquer comme abandonné |

### Médias

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| DELETE | `/api/admin/media/{id}` | Supprimer un média |
| POST | `/api/admin/media/products/{id}/media/{media}/set-as-thumbnail` | Définir comme image principale |
| POST | `/api/admin/media/products/{id}/reorder` | Réorganiser les images |

## Authentification

L'API utilise Laravel Sanctum pour l'authentification. Les tokens sont utilisés pour toutes les routes protégées.

### Obtenir un token

```
POST /api/login
{
    "email": "utilisateur@exemple.com",
    "password": "mot_de_passe"
}
```

### Utiliser un token

Inclure le token dans l'en-tête `Authorization` pour toutes les requêtes authentifiées :

```
Authorization: Bearer {token}
```

## Gestion des médias

Le projet utilise Spatie Media Library pour gérer les médias associés aux produits. Les produits peuvent avoir :

- Une collection `product_images` pour toutes les images du produit
- Une collection `product_thumbnail` pour l'image principale

### Téléchargement d'images

```
POST /api/admin/products
Content-Type: multipart/form-data

{
    "name": "Produit Exemple",
    "price": 29.99,
    "category_id": "uuid-de-categorie",
    "images[]": [fichier1, fichier2],
    "thumbnail": fichier_principal
}
```

### Récupération d'images

Les URLs des images sont automatiquement incluses dans les réponses API via les ressources :

```json
{
    "id": "uuid-produit",
    "name": "Produit Exemple",
    "thumbnail_url": "http://localhost:8000/storage/media/product_thumbnail/...",
    "images": [
        {
            "id": "uuid-media",
            "url": "http://localhost:8000/storage/media/product_images/...",
            "thumb_url": "http://localhost:8000/storage/media/conversions/thumb/..."
        }
    ]
}
```

## Validation

Toutes les entrées sont validées à l'aide de classes dédiées dans le dossier `App\Http\Requests`. Exemples :

- `StoreProductRequest` - Validation à la création d'un produit
- `UpdateCartItemRequest` - Validation à la mise à jour d'un article du panier

Ces classes définissent les règles de validation, les messages d'erreur personnalisés et les autorisations.

## Autorisations

Le système utilise une combinaison de rôles et permissions. Les middlewares `role` et `permission` sont utilisés sur les routes sensibles.

Exemples de permissions :
- `product.create`
- `product.update`
- `category.delete`
- `cart.view`

## Exemples d'utilisation

### Récupérer tous les produits avec filtres

```
GET /api/products?category_id=uuid-categorie&min_price=20&max_price=50&in_stock=true&orderBy=price&orderDirection=asc&page=1&perPage=20
```

### Ajouter un produit au panier

```
POST /api/cart/items
{
    "product_id": "uuid-produit",
    "quantity": 2
}
```

### Mettre à jour le stock d'un produit

```
PATCH /api/admin/products/uuid-produit/stock
{
    "stock_quantity": 10,
    "operation": "add"  // "add", "subtract" ou "set"
}
```

## Tests

Des tests automatisés peuvent être exécutés avec PHPUnit :

```bash
php artisan test
```

Les tests couvrent :
- Les routes API
- La validation des entrées
- Les autorisations
- Les fonctionnalités métier

## Déploiement

Pour déployer l'API en production :

1. Configurer le fichier `.env` pour l'environnement de production
2. Optimiser l'application

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. Exécuter les migrations

```bash
php artisan migrate --force
```

4. Configurer un serveur web (Nginx/Apache) pour servir l'application

## Licence

Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.
