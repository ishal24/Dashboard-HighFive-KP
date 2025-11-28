@props(['class' => ''])
<h3 {{ $attributes->merge(['class' => "text-base font-semibold leading-6 $class"]) }}>
  {{ $slot }}
</h3>
