<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Product;

class AdminDashboardController extends Controller
{
    public function stats()
    {
        $stats = [
            'products' => Product::whereIsActive(true)->count(),
            'categories' => Category::whereIsActive(true)->count(),
            'orders' => \App\Models\Order::count(),
            'users' => \App\Models\User::count()
        ];

        return $stats;
    }
}
