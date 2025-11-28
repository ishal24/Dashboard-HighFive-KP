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
    <link rel="stylesheet" href="{{ asset('css/register.css') }}">

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
  <div class="register-bg"></div>

  <!-- Main split layout -->
  <div class="relative min-h-screen grid grid-cols-1 lg:grid-cols-2">
    <!-- LEFT: welcome panel -->
    <section class="hidden lg:flex flex-col justify-center px-12 xl:px-40 text-red-600">
      <div class="max-w-xl">
        <img src="{{ asset('img/logo-treg3.png') }}" alt="Telkom Indonesia" class="w-60 drop-shadow-lh mb-8">
        <h1 class="text-6xl font-extrabold tracking-tight">
        <span id="typing-text" style="display:inline-block; min-height:1em;"></span>
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

    <!-- RIGHT: register card -->
    <section class="flex items-center justify-center py-12 lg:py-0">
      <div id="register-card"
           class="w-full max-w-md mx-6 bg-white/40 glass-card shadow-xl rounded-xl border border-white/80 backdrop-blur-md px-8 py-7">
        <div class="flex items-center justify-center lg:hidden mb-4">
          <img src="{{ asset('img/logo-treg3.png') }}" alt="Telkom Indonesia" class="w-36">
        </div>

        <h2 class="text-center text-xl font-bold mb-5">Dashboard Monitoring RLEGS</h2>

        <form id="registration-form" method="POST" action="{{ route('register') }}" enctype="multipart/form-data" data-recaptcha-action="register">
          @csrf

          <!-- Role Selection Dropdown -->
          <div class="mb-4">
            <label for="role" class="block font-medium text-sm text-gray-700 mb-1">Tipe Akun</label>
            <select id="role" name="role"
                    class="w-full border-gray-300 focus:border-red-600 focus:ring-red-600 rounded-md shadow-sm">
              <option value="account_manager">Account Manager</option>
              <option value="witel">Support Witel</option>
              <option value="admin">Admin</option>
            </select>
            @error('role')
              <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
          </div>

          <!-- NOTE: Account Manager Fields -->
          <div id="account_manager_fields">
            <div class="mb-4">
              <label for="account_manager_search" class="block font-medium text-sm text-gray-700 mb-1">Nama Account Manager</label>
              <div class="relative">
                <input id="account_manager_search" type="text" placeholder="Cari Account Manager..."
                       class="w-full border-gray-300 focus:border-red-600 focus:ring-red-600 rounded-md shadow-sm" required>
                <div id="account_manager_suggestions"
                     class="absolute z-10 w-full bg-white shadow-md rounded-lg mt-1 hidden"></div>
              </div>
              <input type="hidden" id="account_manager_id" name="account_manager_id" required>
              <p class="text-red-500 text-xs mt-1 error-msg" id="error_am"></p>
              @error('account_manager_id')
                <p class="text-red-500 text-xs mt-1" id="error_am_id" >{{ $message }}</p>
              @enderror
            </div>

            <div class="mb-4">
              <label for="nik" class="block font-medium text-sm text-gray-700 mb-1">NIK Account Manager</label>
              <div class="relative">
                <input id="nik" name="nik" type="text"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')" value="{{ old('nik') }}"
                       placeholder="Masukkan NIK Account Manager"
                       class="w-full border-gray-300 focus:border-red-600 focus:ring-red-600 rounded-md shadow-sm"
                required>
              </div>
              @error('account_manager_nik')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
              @enderror
            </div>
          </div>

          <!-- Witel Fields -->
          <div id="witel_fields" class="hidden">
            <div class="mb-4">
              <label for="witel_id" class="block font-medium text-sm text-gray-700 mb-1">Pilih Witel</label>
              <select id="witel_id" name="witel_id"
                      class="w-full border-gray-300 focus:border-red-600 focus:ring-red-600 rounded-md shadow-sm">
                <option value="">Pilih Witel</option>
                @foreach($witels as $witel)
                  <option value="{{ $witel->id }}">{{ $witel->nama }}</option>
                @endforeach
              </select>
              @error('witel_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
              @enderror
            </div>

            {{-- FIX: change to witel code and add backend handler --}}
            <div class="mb-4">
              <label for="witel_code" class="block font-medium text-sm text-gray-700 mb-1">Kode Witel</label>
              <div class="relative">
                <input id="witel_code" name="witel_code" type="password" placeholder="Masukkan kode witel"
                       class="w-full border-gray-300 focus:border-red-600 focus:ring-red-600 rounded-md shadow-sm pr-10">
                <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center"
                        data-target="witel_code" aria-label="Toggle witel code visibility">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
              </div>
              <p class="text-sm text-gray-500 mt-1">Masukkan kode witel untuk verifikasi.</p>
              @error('witel_code')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
              @enderror
            </div>
          </div>

          <!-- Admin Fields -->
          <div id="admin_fields" class="hidden">
            <div class="mb-4">
              <label for="admin_name" class="block font-medium text-sm text-gray-700 mb-1">Nama Admin</label>
              <input id="admin_name" name="name" type="text" placeholder="Masukkan nama admin"
                     class="w-full border-gray-300 focus:border-red-600 focus:ring-red-600 rounded-md shadow-sm">
              @error('name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
              @enderror
            </div>

            <div class="mb-4">
              <label for="admin_code" class="block font-medium text-sm text-gray-700 mb-1">Kode Admin</label>
              <div class="relative">
                <input id="admin_code" name="admin_code" type="password" placeholder="Masukkan kode admin"
                       class="w-full border-gray-300 focus:border-red-600 focus:ring-red-600 rounded-md shadow-sm pr-10">
                <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center"
                        data-target="admin_code" aria-label="Toggle admin code visibility">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
              </div>
              <p class="text-sm text-gray-500 mt-1">Masukkan kode admin untuk verifikasi.</p>
              @error('admin_code')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
              @enderror
            </div>
          </div>

          <!-- Email -->
          <div class="mb-4">
            <label for="email" class="block font-medium text-sm text-gray-700 mb-1">Email</label>
            <input id="email" name="email" type="email" placeholder="Masukkan email"
                   value="{{ old('email') }}" required
                   class="w-full border-gray-300 focus:border-red-600 focus:ring-red-600 rounded-md shadow-sm">
            @error('email')
              <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
          </div>

          <!-- Password and Confirmation -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="mb-4">
              <label for="password" class="block font-medium text-sm text-gray-700 mb-1">Kata Sandi</label>
              <div class="relative">
                <input id="password" name="password" type="password" placeholder="Masukkan kata sandi" required
                       class="w-full border-gray-300 focus:border-red-600 focus:ring-red-600 rounded-md shadow-sm pr-10">
                <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center"
                        data-target="password" aria-label="Toggle password visibility">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
              </div>
              @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
              @enderror
            </div>

            <div class="mb-4">
              <label for="password_confirmation" class="block font-medium text-sm text-gray-700 mb-1">Konfirmasi Kata Sandi</label>
              <div class="relative">
                <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Konfirmasi kata sandi" required
                       class="w-full border-gray-300 focus:border-red-600 focus:ring-red-600 rounded-md shadow-sm pr-10">
                <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center"
                        data-target="password_confirmation" aria-label="Toggle confirm visibility">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </button>
              </div>
              @error('password_confirmation')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
              @enderror
            </div>
          </div>

          <!-- Foto Profil -->
          <div class="mb-6">
            <label for="profile_image" class="block font-medium text-sm text-gray-700 mb-1">Foto Profil</label>
            <div class="mt-1 flex items-center">
              <label for="profile_image"
                     class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-600 cursor-pointer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 -ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Pilih Foto
              </label>
              <span id="file-name" class="ml-3 text-sm text-gray-500">Belum ada file yang dipilih</span>
              <input id="profile_image" class="hidden" type="file" name="profile_image" accept="image/*">
            </div>
            @error('profile_image')
              <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
          </div>

          <x-recaptcha-v3 />

          <!-- Footer: login link + submit -->
          <div class="flex items-center justify-between mt-6">
            <div>
              <p class="text-sm text-gray-600">Sudah punya akun?</p>
              <a href="{{ route('login') }}" class="text-sm text-blue-700 hover:underline">Login Sekarang</a>
            </div>

            <button type="submit"
                    class="px-4 py-2 bg-[#e30613] text-white rounded-md hover:bg-[#c70511] transition-colors shadow">
              Daftar
            </button>
          </div>
        </form>
      </div>

      <!-- CAPTCHA Failure Modal -->
      <div id="captchaModal" class="fixed inset-0 z-50 hidden">
        <!-- backdrop -->
        <div class="absolute inset-0 bg-black/50" aria-hidden="true"></div>

        <!-- modal panel -->
        <div class="relative mx-auto mt-24 w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
          <h2 class="text-lg font-semibold text-gray-900">ReCAPTCHA Verification Failed</h2>
          <p class="mt-2 text-sm text-gray-600" id="captchaModalMessage">
            Captcha verification failed. Please try again.
          </p>

          <div class="mt-6 flex justify-end gap-2">
            <button type="button"
                    class="rounded-md border px-4 py-2 text-sm"
                    onclick="document.getElementById('captchaModal').classList.add('hidden')">
              Close
            </button>
            <button type="button"
                    class="rounded-md bg-red-600 px-4 py-2 text-sm text-white"
                    onclick="location.reload()">
              Try again
            </button>
          </div>
        </div>
    </section>
  </div>

  <script>
    // CSRF Token refresh
    (async function () {
      async function refreshCsrf() {
        try {
          const res = await fetch('{{ route('csrf.token') }}', { credentials: 'same-origin' });
          if (!res.ok) return;
          const { token } = await res.json();
          // update meta
          const meta = document.querySelector('meta[name="csrf-token"]');
          if (meta) meta.setAttribute('content', token);
          // update all hidden _token inputs
          document.querySelectorAll('input[name="_token"]').forEach(i => { i.value = token; });
        } catch (_) {}
      }

      // Refresh when the tab regains focus (common 419 trigger)
      document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refreshCsrf();
      });

      // Also refresh on a timer (every 5 minutes)
      setInterval(refreshCsrf, 5 * 60 * 1000);
    })();

    document.addEventListener('DOMContentLoaded', function () {
      // cacptcha check
      const forms = document.querySelectorAll('form[data-recaptcha-action]');
      forms.forEach(function (form) {
          form.addEventListener('submit', function (e) {
              if (typeof grecaptcha === 'undefined') {
                  e.preventDefault();
                  document.getElementById('captchaModalMessage').textContent =
                      'Captcha script failed to load. Please disable blockers and try again.';
                  document.getElementById('captchaModal').classList.remove('hidden');
              }
          });
      });



      // typing animation
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

      // Toggle password visibility (works for all toggle buttons)
      document.querySelectorAll('.toggle-password').forEach(function (button) {
        button.addEventListener('click', function () {
          const targetId = this.getAttribute('data-target');
          const input = document.getElementById(targetId);
          const icon = this.querySelector('svg');

          if (input.type === 'password') {
            input.type = 'text';
            icon.innerHTML =
              '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
          } else {
            input.type = 'password';
            icon.innerHTML =
              '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
          }
        });
      });

      // File name label
      const profileImageInput = document.getElementById('profile_image');
      const fileNameSpan = document.getElementById('file-name');
      profileImageInput.addEventListener('change', function () {
        fileNameSpan.textContent = this.files.length ? this.files[0].name : 'Belum ada file yang dipilih';
      });

      // Role switching
      const roleSelect = document.getElementById('role');
      const amFields = document.getElementById('account_manager_fields');
      const witelFields = document.getElementById('witel_fields');
      const adminFields = document.getElementById('admin_fields');

      roleSelect.addEventListener('change', function () {
        amFields.classList.add('hidden');
        witelFields.classList.add('hidden');
        adminFields.classList.add('hidden');

        // remove requireds first (if elements exist)
        const amSearch = document.getElementById('account_manager_search');
        const amId = document.getElementById('account_manager_id');
        const amNik = document.getElementById('nik');
        const witelId = document.getElementById('witel_id');
        const witelCode = document.getElementById('witel_code');
        const adminName = document.getElementById('admin_name');
        const adminCode = document.getElementById('admin_code');

        if (amSearch) amSearch.required = false;
        if (amId) amId.required = false;
        if (amNik) amNik.required = false;
        if (witelId) witelId.required = false;
        if (witelCode) witelCode.required = false;
        if (adminName) adminName.required = false;
        if (adminCode) adminCode.required = false;

        if (this.value === 'account_manager') {
          amFields.classList.remove('hidden');
          adminName.value = '';
          adminCode.value = '';
          witelId.value = '';
          witelCode.value = '';
          if (amSearch) amSearch.required = true;
          if (amId) amId.required = true;
          if (amNik) amNik.required = true;
        } else if (this.value === 'witel') {
          witelFields.classList.remove('hidden');
          amSearch.value = '';
          amId.value = '';
          amNik.value = '';
          adminName.value = '';
          adminCode.value = '';
          if (witelId) witelId.required = true;
          if (witelCode) witelCode.required = true;
        } else if (this.value === 'admin') {
          adminFields.classList.remove('hidden');
          amSearch.value = '';
          amId.value = '';
          amNik.value = '';
          witelId.value = '';
          witelCode.value = '';
          if (adminName) adminName.required = true;
          if (adminCode) adminCode.required = true;
        }
      });

      // AM search and check account availability
      const registForm = document.getElementById('registration-form');
      const searchInput = document.getElementById('account_manager_search');
      const suggestionsContainer = document.getElementById('account_manager_suggestions');
      const idInput = document.getElementById('account_manager_id');
      const errorContainer = document.getElementById('error_am');
      // NOTE: might be redundant, use querySelectorAll instead? but who cares though
      const amIdErrorContainer = document.getElementById('error_am_id');
      const submitBtn = registForm?.querySelector('[type="submit"]');
      let debounceTimer;

      // AM Account Exists
      function showInlineError(msg) {
        if (!errorContainer) return;
        errorContainer.textContent = msg || 'Terjadi kesalahan';
        errorContainer.classList.add('visible');

        // shake animation
        errorContainer.style.animation = 'none';
        requestAnimationFrame(() => { errorContainer.style.animation = 'shake .3s'; });
      }

      function clearInlineError() {
        if (!errorContainer || !amIdErrorContainer) return;
        errorContainer.textContent = '';
        amIdErrorContainer.textContent = '';
        errorContainer.classList.remove('visible');
        amIdErrorContainer.classList.remove('visible');
      }

      function setSubmitEnabled(on) {
        submitBtn.disabled = !on;
        submitBtn.classList.toggle('disabled', !on);
      }

      // setSubmitEnabled(false);

      async function handleSelectSuggestionClick(am) {
        searchInput.value = am.nama;
        try {
          const res = await fetch(`/am/check-account-available?account_manager_id=${encodeURIComponent(am.id)}`, {
            headers: { 'Accept': 'application/json' }
          });

          if (res.status === 409) {
            const j = await res.json().catch(() => ({}));
            // TODO: if AM already has an account, assign idInput as 'invalid' or something so backend can return->withErrors()
            idInput.value = null;
            showInlineError(j.message || 'Nama ini telah terdaftar pada akun lain.');
            searchInput.focus();
            return;
          }
          if (!res.ok) {
            showInlineError('Gagal memeriksa ketersediaan akun. Silahkan coba lagi.');
            return;
          }

          clearInlineError();
          idInput.value = am.id;
          // setSubmitEnabled(true);
          // ermm maybe not needed
          // suggestionsBox.classList.add('hidden');
        }
        catch {
          showInlineError('Terjadi kesalahan jaringan.');
        }
      }

      registForm.addEventListener('submit', (e) => {
        // user typed a name but never selected (am_id empty)
        if (!idInput.value) {
          if (searchInput.value.trim().length > 0) {
            e.preventDefault();
            setError('Pilih nama AM dari daftar agar valid.');
            searchInput.focus();
          } else {
            e.preventDefault();
            setError('Kolom AM wajib diisi.');
            searchInput.focus();
          }
          return;
        }

        // Optional: ultra-safe recheck right before submit (cheap GET)
        /*
        e.preventDefault();
        fetch(`/am/check-registered?am_id=${encodeURIComponent(idInput.value)}`, { headers:{Accept:'application/json'} })
          .then(r => (r.status === 409 ? r.json().then(j=>{ throw new Error(j.message||'AM sudah terdaftar.'); }) : (r.ok?r.json():Promise.reject())))
          .then(() => { clearError(); setSubmitEnabled(true); form.submit(); })
          .catch(err => { setError(err.message); });
        */
      });

      // AM Search functionality
      if (searchInput) {
        searchInput.addEventListener('input', function () {
          clearTimeout(debounceTimer);
          idInput.value = null;

          clearInlineError();
          // setSubmitEnabled(false);

          const query = this.value.trim();
          if (query.length < 3) { suggestionsContainer.classList.add('hidden'); return; }

          debounceTimer = setTimeout(function () {
            suggestionsContainer.innerHTML =
              '<div class="p-2 text-center text-gray-500"><svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg><span class="mt-1 block">Mencari...</span></div>';
            suggestionsContainer.classList.remove('hidden');

            fetch(`/search-account-managers?search=${query}`)
              .then(r => r.json())
              .then(data => {
                suggestionsContainer.innerHTML = '';
                if (!data.length) {
                    suggestionsContainer.innerHTML = '<div class="p-2 text-center text-gray-500">Tidak ditemukan</div>';
                    return;
                }
                data.forEach(function (am) {
                  const div = document.createElement('div');
                  div.className = 'p-2 hover:bg-gray-100 cursor-pointer';
                  div.textContent = `${am.nama}`;
                  div.addEventListener('click', () => handleSelectSuggestionClick(am));

                  suggestionsContainer.appendChild(div);
                });
              })
              .catch(() => {
                suggestionsContainer.innerHTML = '<div class="p-2 text-center text-gray-500">Terjadi kesalahan</div>';
              });
          }, 300);
        });

        document.addEventListener('click', function (e) {
          if (!searchInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
            suggestionsContainer.classList.add('hidden');
          }
        });
      }

      // init default
      roleSelect.dispatchEvent(new Event('change'));
    });
  </script>

  @if ($errors->has('recaptcha_token'))
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('captchaModal');
    // Optional: show server error text inside the modal
    const msg = @json($errors->first('recaptcha_token'));
    const msgEl = document.getElementById('captchaModalMessage');
    if (msgEl && typeof msg === 'string' && msg.trim().length) {
      msgEl.textContent = msg;
    }
    modal.classList.remove('hidden');
  });
  </script>
  @endif

</body>
</html>
