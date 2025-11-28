@props(['class' => ''])
<div {{ $attributes->merge(['class' => "rounded-2xl border bg-white shadow-sm $class"]) }}>
  {{ $slot }}
</div>
