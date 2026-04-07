<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'price', 'image_url', 'stock']);

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json(
            $product->only(['id', 'name', 'description', 'price', 'image_url', 'stock'])
        );
    }
}
