<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Support\AdminListRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PromoCodeController extends Controller
{
    public function index(Request $request): View
    {
        $q = AdminListRequest::search($request);

        $rawStatus = $request->query('status');
        $statusFilter = null;
        if (is_string($rawStatus) && in_array($rawStatus, ['active', 'inactive'], true)) {
            $statusFilter = $rawStatus;
        }

        [$sort, $dir] = AdminListRequest::sort(
            $request,
            ['id', 'code', 'type', 'amount', 'is_active', 'created_at', 'updated_at'],
            'id',
            'desc',
        );

        $query = PromoCode::query();
        if ($q !== null) {
            $like = '%'.$q.'%';
            $query->where(function ($sub) use ($like) {
                $sub->where('code', 'like', $like)
                    ->orWhere('label', 'like', $like);
            });
        }
        if ($statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }
        $query->orderBy($sort, $dir);

        $codes = $query->paginate(20)->withQueryString();

        return view('admin.promo_codes.index', [
            'codes' => $codes,
            'sort' => $sort,
            'dir' => $dir,
            'q' => $q,
            'statusFilter' => $statusFilter,
            'types' => PromoCode::TYPES,
        ]);
    }

    public function create(): View
    {
        return view('admin.promo_codes.create', [
            'types' => PromoCode::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        PromoCode::query()->create($data);

        return redirect()->route('admin.promo-codes.index')->with('success', 'Promo code created.');
    }

    public function edit(PromoCode $promoCode): View
    {
        return view('admin.promo_codes.edit', [
            'promo' => $promoCode,
            'types' => PromoCode::TYPES,
        ]);
    }

    public function update(Request $request, PromoCode $promoCode): RedirectResponse
    {
        $promoCode->fill($this->validated($request, $promoCode->id));
        $promoCode->save();

        return redirect()->route('admin.promo-codes.index')->with('success', 'Promo code updated.');
    }

    public function destroy(PromoCode $promoCode): RedirectResponse
    {
        $promoCode->delete();

        return redirect()->route('admin.promo-codes.index')->with('success', 'Promo code deleted.');
    }

    /**
     * Bulk delete or activation toggle for the promo code list.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['delete', 'set_status'])],
            'status' => ['required_if:action,set_status', Rule::in(['active', 'inactive'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:promo_codes,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['ids'])));
        $rows = PromoCode::query()->whereIn('id', $ids)->get();
        $applied = 0;

        if ($data['action'] === 'delete') {
            foreach ($rows as $row) {
                $row->delete();
                $applied++;
            }

            return redirect()
                ->route('admin.promo-codes.index', $request->only(['q', 'status', 'sort', 'dir', 'page']))
                ->with('success', __('admin.promo_codes.bulk.deleted', ['count' => $applied]));
        }

        $isActive = $data['status'] === 'active';
        foreach ($rows as $row) {
            $row->is_active = $isActive;
            $row->save();
            $applied++;
        }

        return redirect()
            ->route('admin.promo-codes.index', $request->only(['q', 'status', 'sort', 'dir', 'page']))
            ->with('success', __('admin.promo_codes.bulk.status_updated', [
                'count' => $applied,
                'status' => $data['status'],
            ]));
    }

    /**
     * @return array{code: string, label: ?string, type: string, amount: string, min_subtotal: ?string, starts_at: ?string, ends_at: ?string, is_active: bool}
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        if ($request->input('label') === '') {
            $request->merge(['label' => null]);
        }
        if ($request->input('min_subtotal') === '') {
            $request->merge(['min_subtotal' => null]);
        }
        if ($request->input('starts_at') === '') {
            $request->merge(['starts_at' => null]);
        }
        if ($request->input('ends_at') === '') {
            $request->merge(['ends_at' => null]);
        }
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:32',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('promo_codes', 'code')->ignore($ignoreId),
            ],
            'label' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(PromoCode::TYPES)],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        if ($validated['type'] === PromoCode::TYPE_PERCENT && (float) $validated['amount'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amount' => ['Percent discount cannot exceed 100.'],
            ]);
        }

        return $validated;
    }
}
