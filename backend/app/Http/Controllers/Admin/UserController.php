<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminListRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function create(): View
    {
        return view('admin.users.create', [
            'statuses' => User::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->input('avatar_url') === '') {
            $request->merge(['avatar_url' => null]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(User::STATUSES)],
            'avatar_url' => ['nullable', 'string', 'max:2048', 'url'],
            'phone' => ['nullable', 'string', 'max:64'],
            'shipping_recipient_name' => ['nullable', 'string', 'max:255'],
            'shipping_line1' => ['nullable', 'string', 'max:255'],
            'shipping_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['nullable', 'string', 'max:120'],
            'shipping_state' => ['nullable', 'string', 'max:80'],
            'shipping_postcode' => ['nullable', 'string', 'max:32'],
            'shipping_country' => ['nullable', 'string', 'max:120'],
        ]);

        $data = $this->normaliseOptionalUserFields($data);
        $data['password'] = Hash::make($data['password']);
        $data['is_admin'] = $request->boolean('is_admin');

        User::query()->create($data);

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function index(Request $request): View
    {
        $q = AdminListRequest::search($request);

        $rawStatus = $request->query('status');
        $statusFilter = null;
        if (is_string($rawStatus) && $rawStatus !== '' && in_array($rawStatus, User::STATUSES, true)) {
            $statusFilter = $rawStatus;
        }

        [$sort, $dir] = AdminListRequest::sort(
            $request,
            ['id', 'name', 'email', 'is_admin', 'status', 'created_at', 'updated_at'],
            'id',
            'desc',
        );

        $query = User::query();
        if ($q !== null) {
            $like = '%'.$q.'%';
            $query->where(function ($sub) use ($like) {
                $sub->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }
        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }
        $query->orderBy($sort, $dir);

        $users = $query->paginate(20)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'sort' => $sort,
            'dir' => $dir,
            'q' => $q,
            'statusFilter' => $statusFilter,
            'statuses' => User::STATUSES,
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'statuses' => User::STATUSES,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($request->input('avatar_url') === '') {
            $request->merge(['avatar_url' => null]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'is_admin' => ['sometimes', 'boolean'],
            'status' => ['required', Rule::in(User::STATUSES)],
            'avatar_url' => ['nullable', 'string', 'max:2048', 'url'],
            'phone' => ['nullable', 'string', 'max:64'],
            'shipping_recipient_name' => ['nullable', 'string', 'max:255'],
            'shipping_line1' => ['nullable', 'string', 'max:255'],
            'shipping_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['nullable', 'string', 'max:120'],
            'shipping_state' => ['nullable', 'string', 'max:80'],
            'shipping_postcode' => ['nullable', 'string', 'max:32'],
            'shipping_country' => ['nullable', 'string', 'max:120'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $data = $this->normaliseOptionalUserFields($data);

        $wasAdmin = $user->is_admin;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->is_admin = $request->boolean('is_admin');
        $user->status = $data['status'];
        $user->avatar_url = $data['avatar_url'] ?? null;
        $user->phone = $data['phone'];
        $user->shipping_recipient_name = $data['shipping_recipient_name'];
        $user->shipping_line1 = $data['shipping_line1'];
        $user->shipping_line2 = $data['shipping_line2'];
        $user->shipping_city = $data['shipping_city'];
        $user->shipping_state = $data['shipping_state'];
        $user->shipping_postcode = $data['shipping_postcode'];
        $user->shipping_country = $data['shipping_country'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($wasAdmin && ! $user->is_admin && User::query()->where('is_admin', true)->whereKeyNot($user->id)->doesntExist()) {
            return back()->withErrors(['is_admin' => 'Cannot remove the last admin user.'])->withInput();
        }

        if (
            $request->user()->is($user)
            && $data['status'] !== User::STATUS_ACTIVE
        ) {
            return back()->withErrors(['status' => 'You cannot disable your own account.'])->withInput();
        }

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['delete' => 'You cannot delete your own account while logged in.']);
        }

        if ($user->is_admin && User::query()->where('is_admin', true)->count() <= 1) {
            return back()->withErrors(['delete' => 'Cannot delete the last admin user.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    /**
     * Bulk delete or status update for the user list.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['delete', 'set_status'])],
            'status' => ['required_if:action,set_status', Rule::in(User::STATUSES)],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $self = $request->user();
        $ids = array_values(array_unique(array_map('intval', $data['ids'])));
        $usersInScope = User::query()->whereIn('id', $ids)->get();

        $skipped = [];
        $applied = 0;

        if ($data['action'] === 'delete') {
            $remainingAdminCount = User::query()
                ->where('is_admin', true)
                ->whereNotIn('id', $ids)
                ->count();

            foreach ($usersInScope as $user) {
                if ($self->is($user)) {
                    $skipped[] = $user->email.' (self)';

                    continue;
                }
                if ($user->is_admin && $remainingAdminCount < 1) {
                    $skipped[] = $user->email.' (last admin)';

                    continue;
                }
                $user->delete();
                $applied++;
            }

            return redirect()
                ->route('admin.users.index', $request->only(['q', 'status', 'sort', 'dir', 'page']))
                ->with('success', __('admin.users.bulk.deleted', ['count' => $applied]).(empty($skipped) ? '' : ' '.__('admin.users.bulk.skipped', ['list' => implode(', ', $skipped)])));
        }

        $status = $data['status'];
        foreach ($usersInScope as $user) {
            if ($self->is($user) && $status !== User::STATUS_ACTIVE) {
                $skipped[] = $user->email.' (self)';

                continue;
            }
            $user->status = $status;
            $user->save();
            $applied++;
        }

        return redirect()
            ->route('admin.users.index', $request->only(['q', 'status', 'sort', 'dir', 'page']))
            ->with('success', __('admin.users.bulk.status_updated', ['count' => $applied, 'status' => $status]).(empty($skipped) ? '' : ' '.__('admin.users.bulk.skipped', ['list' => implode(', ', $skipped)])));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normaliseOptionalUserFields(array $data): array
    {
        foreach (
            [
                'phone',
                'shipping_recipient_name',
                'shipping_line1',
                'shipping_line2',
                'shipping_city',
                'shipping_state',
                'shipping_postcode',
                'shipping_country',
            ] as $key
        ) {
            if (($data[$key] ?? '') === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }
}
