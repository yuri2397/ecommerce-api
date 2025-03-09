<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;

class ShopController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $shop = Shop::create($request->all());

        if ($request->hasFile('logo')) {
            $shop->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        return $shop;
    }

    /**
     * Display the specified resource.
     */
    public function show(Shop $shop)
    {
        return $shop;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shop $shop)
    {
        $shop->update($request->all());
        return $shop;
    }
}
