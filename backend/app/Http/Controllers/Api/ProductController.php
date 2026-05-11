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

        $query = Product::query()
            ->listed()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        if ($term !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(function ($sub) use ($like): void {
                $sub->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        $products = $query
            ->orderBy('name')
            ->get();

        $rows = $products->map(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'image_url' => $product->image_url,
            'stock' => $product->stock,
            'average_rating' => $product->reviews_avg_rating !== null
                ? round((float) $product->reviews_avg_rating, 2)
                : null,
            'review_count' => (int) ($product->reviews_count ?? 0),
        ]);

        return response()->json($rows);
    }

    public function show(Product $product): JsonResponse
    {
        if (! $product->isActive()) {
            abort(404);
        }

        $product->loadCount('reviews')->loadAvg('reviews', 'rating');

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'image_url' => $product->image_url,
            'stock' => $product->stock,
            'average_rating' => $product->reviews_avg_rating !== null
                ? round((float) $product->reviews_avg_rating, 2)
                : null,
            'review_count' => (int) ($product->reviews_count ?? 0),
        ]);
    }
}
