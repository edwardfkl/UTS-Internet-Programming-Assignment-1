<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $u = $request->user();

        return response()->json([
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
            'shipping_recipient_name' => $u->shipping_recipient_name,
            'shipping_line1' => $u->shipping_line1,
            'shipping_line2' => $u->shipping_line2,
            'shipping_city' => $u->shipping_city,
            'shipping_state' => $u->shipping_state,
            'shipping_postcode' => $u->shipping_postcode,
            'shipping_country' => $u->shipping_country,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'shipping_recipient_name' => ['nullable', 'string', 'max:255'],
            'shipping_line1' => ['nullable', 'string', 'max:255'],
            'shipping_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['nullable', 'string', 'max:120'],
            'shipping_state' => ['nullable', 'string', 'max:80'],
            'shipping_postcode' => ['nullable', 'string', 'max:32'],
            'shipping_country' => ['nullable', 'string', 'max:120'],
        ]);

        $request->user()->fill($data);
        $request->user()->save();

        return $this->show($request);
    }
}
