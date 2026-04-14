@props([
    'label',
    'column',
    'sort',
    'dir',
    'route',
    'preserveQuery' => ['q'],
])
@php
    $query = [];
    foreach ((array) $preserveQuery as $key) {
        $v = request($key);
        if ($v !== null && $v !== '') {
            $query[$key] = $v;
        }
    }
    if ($sort === $column) {
        $nextDir = $dir === 'asc' ? 'desc' : 'asc';
    } else {
        $nextDir = in_array($column, ['id', 'created_at', 'updated_at', 'placed_at', 'is_admin'], true) ? 'desc' : 'asc';
    }
    $url = route($route, array_merge($query, ['sort' => $column, 'dir' => $nextDir]));
    $arrow = $sort === $column ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
@endphp
<th {{ $attributes->merge(['class' => 'px-4 py-3']) }}>
    <a href="{{ $url }}" class="text-inherit underline-offset-2 hover:text-amber-900 hover:underline">{{ $label }}{!! $arrow !!}</a>
</th>
