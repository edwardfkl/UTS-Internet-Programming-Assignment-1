{{--
    Reusable bulk-action toolbar.

    Required variables in the calling view:
      - $bulkRoute       : route name (e.g. 'admin.users.bulk')
      - $bulkStatuses    : list<string> of statuses for the "set status" select
      - $bulkResourceKey : translation prefix (e.g. 'admin.users' or 'admin.products')

    The toolbar relies on checkboxes inside a wrapping <form> with name="ids[]".
    Pair it with `admin/partials/bulk-script.blade.php` to enable the
    "select all" checkbox and the disabled state on the submit buttons.
--}}
<div class="mt-6 flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50 p-3"
     data-bulk-toolbar>
    <span class="inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-medium text-zinc-700 ring-1 ring-zinc-200">
        <span class="tabular-nums" data-bulk-count>0</span>
        <span class="text-zinc-500">{{ __('admin.bulk.selected') }}</span>
    </span>
    <div class="flex flex-wrap items-center gap-2">
        <select name="status"
                aria-label="{{ __('admin.bulk.set_status') }}"
                class="rounded-lg border border-zinc-300 bg-white px-2 py-2 text-xs shadow-sm">
            @foreach ($bulkStatuses as $statusOption)
                <option value="{{ $statusOption }}">
                    {{ __('admin.bulk.set_status') }}:
                    {{ \Illuminate\Support\Facades\Lang::has($bulkResourceKey.'.status_labels.'.$statusOption)
                        ? __($bulkResourceKey.'.status_labels.'.$statusOption)
                        : $statusOption }}
                </option>
            @endforeach
        </select>
        <button type="submit"
                name="action"
                value="set_status"
                data-bulk-submit
                class="rounded-lg bg-zinc-900 px-3 py-2 text-xs font-medium text-white hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-50">
            {{ __('admin.bulk.apply_status') }}
        </button>
        <button type="submit"
                name="action"
                value="delete"
                data-bulk-submit
                onclick="return confirm({{ json_encode(__('admin.bulk.confirm_delete')) }});"
                class="rounded-lg border border-red-300 bg-white px-3 py-2 text-xs font-medium text-red-900 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50">
            {{ __('admin.bulk.delete_selected') }}
        </button>
    </div>
</div>
