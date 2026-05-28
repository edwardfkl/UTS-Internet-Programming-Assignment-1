@props(['amount'])

<span {{ $attributes->merge(['class' => 'tabular-nums']) }}>{{ \App\Support\Money::formatAud($amount) }}</span>
