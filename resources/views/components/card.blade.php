@props(['class' => ''])
<div {{ $attributes->merge(['class' => "rounded-xl border bg-white $class"]) }}>
  {{ $slot }}
</div>
