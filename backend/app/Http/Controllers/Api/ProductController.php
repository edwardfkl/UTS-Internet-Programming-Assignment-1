<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $term = trim((string) ($validated['q'] ?? ''));

        $query = Product::query();

        if ($term !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(function ($sub) use ($like): void {
                $sub->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        $products = $query
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
