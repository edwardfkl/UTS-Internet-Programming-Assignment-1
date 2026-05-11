<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * GET /api/products/{product}/reviews — public list of reviews.
     */
    public function index(Product $product): JsonResponse
    {
        $reviews = $product->reviews()
            ->with('user:id,name,avatar_url')
            ->orderByDesc('created_at')
            ->get(['id', 'user_id', 'rating', 'comment', 'created_at']);

        $rows = $reviews->map(fn (Review $review) => [
            'id' => $review->id,
            'rating' => (int) $review->rating,
            'comment' => $review->comment,
            'created_at' => $review->created_at?->toIso8601String(),
            'user' => $review->user ? [
                'id' => $review->user->id,
                'name' => $review->user->name,
                'avatar_url' => $review->user->avatar_url,
            ] : null,
        ]);

        $product->loadCount('reviews')->loadAvg('reviews', 'rating');

        return response()->json([
            'data' => $rows,
            'average_rating' => $product->reviews_avg_rating !== null
                ? round((float) $product->reviews_avg_rating, 2)
                : null,
            'review_count' => (int) ($product->reviews_count ?? 0),
        ]);
    }

    /**
     * POST /api/products/{product}/reviews — authenticated users create
     * or update their own review for the product.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        /** @var Review $review */
        $review = Review::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $product->id,
            ],
            [
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ],
        );

        $product->loadCount('reviews')->loadAvg('reviews', 'rating');

        return response()->json([
            'review' => [
                'id' => $review->id,
                'rating' => (int) $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at?->toIso8601String(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar_url' => $user->avatar_url,
                ],
            ],
            'average_rating' => $product->reviews_avg_rating !== null
                ? round((float) $product->reviews_avg_rating, 2)
                : null,
            'review_count' => (int) ($product->reviews_count ?? 0),
        ], 201);
    }
}
