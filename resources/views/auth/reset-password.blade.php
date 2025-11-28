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
        <span id="typing-text" style="display:inline-block; min-height:1em;"></span>
        </h1>
            <p class="mt-4 text-lg/7 text-black drop-shadow">
              You may change your password and log in to access the dashboard.
            </p>
      </div>
    </section>

    <!-- RIGHT: reset password card -->
    <section class="flex items-center justify-center py-12 lg:py-0">
      <div id="login-card"
           class="w-full max-w-md mx-6 bg-white/40 glass-card shadow-xl rounded-xl border border-white/80 backdrop-blur-md px-8 py-7">
        <div class="flex items-center justify-center lg:hidden mb-4">
          <img src="{{ asset('img/logo-treg3.png') }}" alt="Telkom Indonesia" class="w-40">
        </div>

        <h2 class="text-center text-xl font-bold mb-5">Dashboard Monitoring RLEGS</h2>

        <!-- status session??? -->
        <!--
        @if (session('success'))
          <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded shadow">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-green-700">{{ session('success') }}</p>
              </div>
            </div>
          </div>
        @endif

        <x-auth-session-status class="mb-4" :status="session('status')" />
        -->


        <form method="POST" action="{{ route('password.store') }}">
          @csrf

          <!-- Password Reset Token -->
          <input type="hidden" name="token" value="{{ $request->route('token') }}">

          <!-- Email -->
          <div class="mb-4">
            <label for="email" class="block font-medium text-sm text-gray-700 mb-1">Email</label>
            <input id="email" name="email" type="email" placeholder="Masukkan email" readonly
                   value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                   class="w-full border-gray-300 focus:border-red-600 focus:ring-red-600 rounded-md shadow-sm">
            @error('email')
              <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
          </div>

          <!-- Password -->
          <div class="mb-2">
            <label for="password" class="block font-medium text-sm text-gray-700 mb-1">Kata Sandi Baru</label>
            <div class="relative">
              <input id="password" name="password" type="password" placeholder="Masukkan kata sandi baru" required
                     autocomplete="new-password"
                     class="w-full border-gray-300 focus:border-red-600 focus:ring-red-600 rounded-md shadow-sm pr-10">
              <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center"
                      data-target="password" aria-label="Toggle password visibility">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
            </div>
            @error('password')
              <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
          </div>

          <!-- Password Confirmation -->
          <div class="mb-2">
            <label for="password_confirmation" class="block font-medium text-sm text-gray-700 mb-1">Konfirmasi Kata Sandi Baru</label>
            <div class="relative">
              <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Masukkan ulang kata sandi baru" required
                     autocomplete="new-password"
                     class="w-full border-gray-300 focus:border-red-600 focus:ring-red-600 rounded-md shadow-sm pr-10">
              <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center"
                      data-target="password_confirmation" aria-label="Toggle password visibility">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </button>
            </div>
            @error('password_confirmation')
              <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
          </div>

          <!-- Footer: sign up + submit -->
          <div class="flex items-center justify-between mt-6">
            <button type="submit"
                    class="px-4 py-2 bg-[#e30613] text-white rounded-md hover:bg-[#c70511] transition-colors shadow">
              Reset Password
            </button>
          </div>
        </form>
      </div>
    </section>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
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
