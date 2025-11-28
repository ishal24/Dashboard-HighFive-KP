<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'RLEGS') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    <style>
      /* Blinking cursor */
      #typing-text::after {
        content: '|';
        display: inline-block;
        width: 0.2ch;
        margin-left: 2px;
        color: currentColor;
        animation: typing-caret-blink 1s steps(1) infinite;
      }
      @keyframes typing-caret-blink {
        0%, 49% { opacity: 1; }
        50%, 100% { opacity: 0; }
      }
    </style>
</head>
<body class="font-sans text-gray-900 antialiased">
  <!-- Fullscreen blurred background image -->
  <div class="login-bg"></div>

  <!-- Main split layout -->
  <div class="relative min-h-screen grid grid-cols-1 lg:grid-cols-2">
    <!-- LEFT: welcome panel -->
    <section class="hidden lg:flex flex-col justify-center px-20 xl:px-36 text-red-600">
      <div class="max-w-xl">
        <img src="{{ asset('img/logo-treg3.png') }}" alt="Telkom Indonesia" class="w-60 drop-shadow-lg mb-8">
        <h1 class="text-6xl font-extrabold tracking-tight">
            <span id="typing-text" style="display:inline-block; min-height:1em;">Welcome Back!</span>
        </h1>
        <p class="mt-4 text-lg/7 text-black drop-shadow">
          To access the dashboard please log in with your credentials.
        </p>

        {{-- <a href="{{url('login')}}"
           class="inline-block text-white mt-8 w-36 text-center rounded-md bg-red-600/90 hover:bg-red-600 px-4 py-2 font-medium shadow-lg">
          SIGN IN
        </a> --}}
      </div>
    </section>

    <!-- RIGHT: forgot password card -->
    <section class="flex items-center justify-center py-12 lg:py-0">
      <div id="forgot-card"
           class="w-full max-w-md mx-6 bg-white/40 glass-card shadow-xl rounded-xl border border-white/80 backdrop-blur-md px-8 py-7">
        <div class="flex items-center justify-center lg:hidden mb-4">
          <img src="{{ asset('img/logo-treg3.png') }}" alt="Telkom Indonesia" class="w-40">
        </div>
            <h2 class="text-center text-xl font-bold mb-5">Lupa Kata Sandi</h2>

            <div class="flex flex-col mb-4 text-sm text-gray-700 text-center">
                <p>Masukkan alamat email yang terdaftar untuk <br> memulihkan akses ke akun Anda.</p>
            </div>

            <!-- Session Status -->
            @if (session('status'))
                <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded shadow">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">{{ session('status') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <!-- Email Address -->
                <div class="mb-4">
                    <label for="email" class="block font-medium text-sm text-gray-700 mb-1">Alamat Email</label>
                    <input id="email" name="email" type="email" placeholder="sintia@gmail.com" 
                           value="{{ old('email') }}" required autofocus
                           class="w-full border-gray-300 focus:border-red-600 focus:ring-red-600 rounded-md shadow-sm">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between mt-6">
                    <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:underline">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Halaman Login
                    </a>

                    <button type="submit" 
                            class="px-4 py-2 bg-[#e30613] text-white rounded-md hover:bg-[#c70511] transition-colors shadow">
                        Kirim Tautan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Typing animation for Welcome Back!
      const text = 'Welcome Back!';
      const typingText = document.getElementById('typing-text');
      let i = 1;
      let isDeleting = false;
      function typeLoop() {
        if (!isDeleting && i <= text.length) {
          typingText.textContent = text.substring(0, i);
          i++;
          if (i > text.length) {
            isDeleting = true;
            setTimeout(typeLoop, 2500); // pause before deleting
          } else {
            setTimeout(typeLoop, 120);
          }
        } else if (isDeleting && i >= 1) {
          typingText.textContent = text.substring(0, i);
          i--;
          if (i < 1) {
            isDeleting = false;
            setTimeout(typeLoop, 0); // pause before typing again
          } else {
            setTimeout(typeLoop, 50);
          }
        }
      }
      typeLoop();

      // Password toggle
      const togglePasswordButtons = document.querySelectorAll('.toggle-password');
      togglePasswordButtons.forEach(function (button) {
        button.addEventListener('click', function () {
          const targetId = this.getAttribute('data-target');
          const passwordInput = document.getElementById(targetId);
          const icon = this.querySelector('svg');

          if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.innerHTML =
              '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268-2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
          } else {
            passwordInput.type = 'password';
            icon.innerHTML =
              '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268-2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
          }
        });
      });
    });
  </script>
</body>
</html>

