<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'RLEGS') }} - Verifikasi Email</title>

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
          Mohon verifikasi email Anda untuk melanjutkan ke dashboard.
        </p>
      </div>
    </section>

    <!-- RIGHT: verification card -->
    <section class="flex items-center justify-center py-12 lg:py-0">
      <div id="verification-card"
           class="w-full max-w-md mx-6 bg-white/40 glass-card shadow-xl rounded-xl border border-white/80 backdrop-blur-md px-8 py-7">
        <div class="flex items-center justify-center lg:hidden mb-4">
          <img src="{{ asset('img/logo-treg3.png') }}" alt="Telkom Indonesia" class="w-40">
        </div>

        <h2 class="text-center text-xl font-bold mb-5">Verifikasi Email</h2>

        <!-- Success message -->
        @if (session('status') == 'verification-link-sent')
          <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded shadow">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-green-700">Link verifikasi telah dikirim ke alamat email anda.</p>
              </div>
            </div>
          </div>
        @endif

        <!-- Verification message -->
        <div class="mb-6 text-sm text-gray-700 text-center">
          Mohon untuk memverifikasi akun anda melalui email yang kami kirim sebelum mengakses Dashboard. Jika anda tidak menerima email verifikasi, tekan tombol di bawah untuk mengirim ulang email verifikasi.
        </div>

        <!-- Resend verification form -->
        <form method="POST" action="{{ route('verification.send') }}">
          @csrf
          
          <div class="mb-4">
            <button type="submit"
                    class="w-full px-4 py-3 bg-[#e30613] text-white rounded-md hover:bg-[#c70511] transition-colors shadow font-medium">
              Kirim Email Verifikasi
            </button>
          </div>
        </form>

        <!-- Logout link -->
        <div class="text-center">
          <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" 
                    class="text-sm text-blue-700 hover:underline">
              Log Out
            </button>
          </form>
        </div>
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
    });
  </script>

</body>
</html>