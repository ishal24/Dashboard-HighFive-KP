<button {{ $attributes->merge(['type' => 'submit', 'class' => 'bg-[#0e223e] text-black px-4 py-2 rounded-md font-semibold hover:bg-[#1b3a5f]']) }}>
    {{ $slot }}
</button>
