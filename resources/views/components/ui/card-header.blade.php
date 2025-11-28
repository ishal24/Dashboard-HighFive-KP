@props(['class' => ''])
<div {{ $attributes->merge(['class' => "px-4 py-3 border-b $class"]) }}>
  {{ $slot }}
</div>
