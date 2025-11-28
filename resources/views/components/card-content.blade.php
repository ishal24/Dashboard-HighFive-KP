@props(['class' => ''])
<div {{ $attributes->merge(['class' => "p-4 $class"]) }}>
  {{ $slot }}
</div>
