<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\AdminListRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $q = AdminListRequest::search($request);

        $rawStatus = $request->query('status');
        $statusFilter = null;
        if (is_string($rawStatus) && $rawStatus !== '' && in_array($rawStatus, Product::STATUSES, true)) {
            $statusFilter = $rawStatus;
        }

        [$sort, $dir] = AdminListRequest::sort(
            $request,
            ['id', 'name', 'price', 'stock', 'status', 'created_at', 'updated_at'],
            'id',
            'desc',
        );

        $query = Product::query();
        if ($q !== null) {
            $like = '%'.$q.'%';
            $query->where(function ($sub) use ($like) {
                $sub->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }
        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }
        $query->orderBy($sort, $dir);

        $products = $query->paginate(20)->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'sort' => $sort,
            'dir' => $dir,
            'q' => $q,
            'statusFilter' => $statusFilter,
            'statuses' => Product::STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'statuses' => Product::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedProduct($request);
        Product::query()->create($data);

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', [
            'product' => $product,
            'statuses' => Product::STATUSES,
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->fill($this->validatedProduct($request));
        $product->save();

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $locked = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($q) => $q->where('status', '!=', Order::STATUS_CART))
            ->exists();

        if ($locked) {
            return back()->withErrors([
                'delete' => 'Cannot delete a product that appears on placed or pending orders.',
            ]);
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    /**
     * Bulk delete or status update for the products list.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['delete', 'set_status'])],
            'status' => ['required_if:action,set_status', Rule::in(Product::STATUSES)],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:products,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['ids'])));
        $rows = Product::query()->whereIn('id', $ids)->get();
        $skipped = [];
        $applied = 0;

        if ($data['action'] === 'delete') {
            $lockedIds = OrderItem::query()
                ->whereIn('product_id', $ids)
                ->whereHas('order', fn ($q) => $q->where('status', '!=', Order::STATUS_CART))
                ->pluck('product_id')
                ->unique()
                ->all();

            foreach ($rows as $product) {
                if (in_array($product->id, $lockedIds, true)) {
                    $skipped[] = $product->name.' (on placed orders)';

                    continue;
                }
                $product->delete();
                $applied++;
            }

            return redirect()
                ->route('admin.products.index', $request->only(['q', 'status', 'sort', 'dir', 'page']))
                ->with('success', __('admin.products.bulk.deleted', ['count' => $applied]).(empty($skipped) ? '' : ' '.__('admin.products.bulk.skipped', ['list' => implode(', ', $skipped)])));
        }

        $status = $data['status'];
        foreach ($rows as $product) {
            $product->status = $status;
            $product->save();
            $applied++;
        }

        return redirect()
            ->route('admin.products.index', $request->only(['q', 'status', 'sort', 'dir', 'page']))
            ->with('success', __('admin.products.bulk.status_updated', ['count' => $applied, 'status' => $status]));
    }

    /**
     * @return array{name: string, description: string|null, price: string, image_url: string|null, stock: int, status: string}
     */
    private function validatedProduct(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'stock' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'status' => ['required', Rule::in(Product::STATUSES)],
        ]);

        $validated['description'] = $validated['description'] ?: null;
        $validated['image_url'] = $validated['image_url'] ?: null;

        return $validated;
    }
}
