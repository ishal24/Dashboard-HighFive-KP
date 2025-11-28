{{-- resources/views/profile/index.blade.php --}}
@extends('layouts.main')

@section('title', 'Profile')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endsection

@section('content')
<div class="main-content">
  <div class="header-dashboard">
    <div class="header-content">
      <div class="header-text">
        <h1 class="header-title">Profile Pengguna</h1>
        <p class="header-subtitle">Kelola informasi akun, foto profil, dan kata sandi Anda</p>
      </div>
    </div>
  </div>

  <div class="row g-4 row-stretch">
    {{-- Kolom kiri: Foto + Info Akun --}}
    <div class="col-lg-7">
      <div class="dashboard-card">
        <div class="card-header">
          <div class="card-header-content">
            <h5 class="card-title">Informasi Akun</h5>
            <p class="text-muted mb-0">Nama, email, dan foto profil</p>
          </div>
        </div>

        <div class="card-body">
          <form class="profile-form" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="profile-grid">
              {{-- Foto profil --}}
              <section class="profile-image-section">
                <div class="profile-image-container">
                  <div class="profile-image-wrapper" id="profileImageWrapper">
                    @if(auth()->user()->profile_image)
                      <img
                        src="{{ asset('storage/' . auth()->user()->profile_image) }}"
                        alt="Profile Image"
                        class="profile-image"
                        id="currentProfileImage">
                    @else
                      <div class="profile-image-placeholder" id="profilePlaceholder">
                        <span class="initial">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                      </div>
                    @endif

                    <label for="profile_image" class="edit-icon-wrapper" title="Ganti foto">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="edit-icon" aria-hidden="true">
                        <path d="M12 20h9"/>
                        <path d="M16.5 3.5l4 4L7 21l-4 1 1-4L16.5 3.5z"/>
                      </svg>
                    </label>
                  </div>

                  <input id="profile_image" name="profile_image" class="d-none" type="file" accept="image/jpeg,image/jpg,image/png,image/gif" />
                  @error('profile_image') <p class="error-text mt-2">{{ $message }}</p> @enderror
                  <p class="error-text mt-2" id="imgErr" style="display:none;"></p>

                  @if(auth()->user()->profile_image)
                    <button type="button" id="btnRemovePhoto" class="btn-outline mt-2 small">Hapus Foto</button>
                  @endif
                </div>
              </section>

              {{-- Form nama & email --}}
              <section class="profile-info-section">
                <div class="form-group">
                  <label for="name" class="form-label">Nama</label>
                  <input id="name" name="name" type="text" class="form-input"
                         value="{{ old('name', auth()->user()->name) }}"
                         autocomplete="name" required>
                  @error('name') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                  <label for="email" class="form-label">Email</label>
                  <input id="email" name="email" type="email" class="form-input"
                         value="{{ old('email', auth()->user()->email) }}"
                         autocomplete="email" required>
                  @error('email') <p class="error-text">{{ $message }}</p> @enderror

                  @if(!auth()->user()->hasVerifiedEmail())
                    <div class="verification-notice mt-2">
                      <p class="text-verify">
                        Alamat email Anda belum diverifikasi.
                        <button type="submit" class="verify-link" form="send-verification">
                          Klik di sini untuk mengirim ulang email verifikasi.
                        </button>
                      </p>
                    </div>
                  @endif

                  @if(session('verification-link-sent'))
                    <p class="success-text mt-2">
                      Link verifikasi baru telah dikirim ke alamat email Anda.
                    </p>
                  @endif
                </div>

                <div class="form-actions">
                  <button type="submit" class="save-button">Simpan Perubahan</button>
                  @if(session('status') === 'profile-updated')
                    <span class="success-text">Tersimpan.</span>
                  @endif
                </div>
              </section>
            </div>
          </form>

          {{-- Form terpisah untuk hapus foto --}}
          <form id="removePhotoForm" action="{{ route('profile.remove-photo') }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
          </form>

          {{-- Form terpisah untuk kirim ulang verifikasi (di luar form profil) --}}
          <form id="send-verification" action="{{ route('verification.send') }}" method="POST" class="d-none">
            @csrf
          </form>
        </div>
      </div>
    </div>

    {{-- Kolom kanan: Reset Password --}}
    <div class="col-lg-5">
      <div class="dashboard-card">
        <div class="card-header">
          <div class="card-header-content">
            <h5 class="card-title">Reset Password</h5>
            <p class="text-muted mb-0">Ganti kata sandi Anda</p>
          </div>
        </div>

        <div class="card-body">
          <form id="passwordForm" action="{{ route('profile.password') }}" method="POST" class="password-form">
            @csrf
            @method('PUT')

            <div class="form-group">
              <label for="current_password" class="form-label">Password Saat Ini</label>
              <div class="password-field">
                <input id="current_password" name="current_password" type="password" class="form-input" autocomplete="current-password" required>
                <button class="toggle-eye" type="button" data-target="current_password" aria-label="Tampilkan/Sembunyikan">👁</button>
              </div>
              @error('current_password') <p class="error-text">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
              <label for="password" class="form-label">Password Baru</label>
              <div class="password-field">
                <input id="password" name="password" type="password" class="form-input" autocomplete="new-password" required>
                <button class="toggle-eye" type="button" data-target="password" aria-label="Tampilkan/Sembunyikan">👁</button>
              </div>
              @error('password') <p class="error-text">{{ $message }}</p> @enderror
            </div>

            <div class="form-group">
              <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
              <div class="password-field">
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" autocomplete="new-password" required>
                <button class="toggle-eye" type="button" data-target="password_confirmation" aria-label="Tampilkan/Sembunyikan">👁</button>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="save-button">Perbarui Password</button>
              @if(session('status') === 'password-updated')
                <span class="success-text">Password diperbarui.</span>
              @endif
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  // Preview foto
  const inputPhoto = document.getElementById('profile_image');
  const imgErr = document.getElementById('imgErr');
  const removeBtn = document.getElementById('btnRemovePhoto');
  const wrapper = document.getElementById('profileImageWrapper');

  inputPhoto?.addEventListener('change', (e) => {
    imgErr.style.display = 'none';
    const file = inputPhoto.files?.[0];
    if (!file) return;

    const maxBytes = 5 * 1024 * 1024; // 5MB
    if (file.size > maxBytes) {
      imgErr.textContent = 'Ukuran file terlalu besar. Maksimal 5MB.';
      imgErr.style.display = 'block';
      inputPhoto.value = '';
      return;
    }

    const valid = ['image/jpeg','image/jpg','image/png','image/gif'];
    if (!valid.includes(file.type)) {
      imgErr.textContent = 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF.';
      imgErr.style.display = 'block';
      inputPhoto.value = '';
      return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
      // Hapus placeholder jika ada
      const placeholder = wrapper.querySelector('.profile-image-placeholder');
      if (placeholder) {
        placeholder.remove();
      }

      // Cek apakah sudah ada img element
      let img = wrapper.querySelector('img.profile-image');
      if (img) {
        // Update src yang sudah ada
        img.src = e.target.result;
      } else {
        // Buat img element baru
        img = document.createElement('img');
        img.className = 'profile-image';
        img.alt = 'Profile Image';
        img.id = 'currentProfileImage';
        img.src = e.target.result;
        
        // Insert sebelum edit icon
        const editIcon = wrapper.querySelector('.edit-icon-wrapper');
        wrapper.insertBefore(img, editIcon);
      }
    };
    reader.readAsDataURL(file);
  });

  // Hapus foto
  removeBtn?.addEventListener('click', () => {
    if (confirm('Yakin ingin menghapus foto profil?')) {
      document.getElementById('removePhotoForm').submit();
    }
  });

  // Toggle eye
  document.querySelectorAll('.toggle-eye').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-target');
      const input = document.getElementById(id);
      input.type = input.type === 'password' ? 'text' : 'password';
    });
  });
</script>
@endsection