@extends('layouts.main')

@section('title', 'Edit Profile')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/editprofile.css') }}">
@endsection

@section('content')
<div id="main-content" class="content">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Header Section -->
    <div class="header-section mb-8">
      <h1 class="page-title">Pengaturan Profil</h1>
      <p class="page-subtitle">Kelola informasi Akun  Anda</p>
    </div>

    <!-- Main Container -->
    <div class="profile-container">
      
      <!-- Left Section: Profile Information -->
      <div class="profile-section">
        <div class="section-card">
          <div class="section-header">
            <h2 class="section-title">Informasi Profil</h2>
            <p class="section-description">Perbarui foto dan data pribadi Anda</p>
          </div>

          <form class="profile-form" enctype="multipart/form-data" onsubmit="return false;">
            
            <!-- Profile Photo Section -->
            <div class="photo-upload-section">
              <div class="photo-container">
                <div class="photo-wrapper">
                  <div class="photo-placeholder" id="photoPlaceholder">
                    <span class="placeholder-initial">A</span>
                  </div>
                  <img id="photoPreview" class="photo-image" alt="Profile Photo" style="display: none;">
                  
                  <label for="profile_image" class="photo-edit-btn" title="Ganti foto profil">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M12 20h9"></path>
                      <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                  </label>
                </div>
                <input id="profile_image" type="file" class="hidden" accept="image/*" />
              </div>

              <div class="photo-info">
                <p class="photo-info-label">Foto Profil</p>
                <p class="photo-info-hint" id="photoFileName">Belum ada foto dipilih</p>
                <p class="photo-info-size">Format: JPG, PNG | Ukuran maksimal: 5MB</p>
              </div>
            </div>

            <!-- Form Fields -->
            <div class="form-fields">
              <div class="form-group">
                <label for="name" class="form-label">Nama Lengkap</label>
                <input id="name" type="text" class="form-input" placeholder="Masukkan nama lengkap Anda" />
              </div>

              <div class="form-group">
                <label for="email" class="form-label">Alamat Email</label>
                <input id="email" type="email" class="form-input" placeholder="nama@email.com" />
              </div>

              <div class="form-group">
                <label for="phone" class="form-label">Nomor Telepon</label>
                <input id="phone" type="tel" class="form-input" placeholder="+62 8XX XXXX XXXX" />
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="form-actions">
              <button type="button" class="btn btn-primary" disabled>
                <span>Simpan Perubahan</span>
              </button>
              <button type="button" class="btn btn-secondary">
                <span>Batal</span>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Right Section: Password Settings -->
      <div class="profile-section">
        <div class="section-card">
          <div class="section-header">
            <h2 class="section-title">Kata Sandi</h2>
            <p class="section-description">Ubah kata sandi akun Anda</p>
          </div>

          <form class="password-form" onsubmit="return false;">
            
            <div class="form-group">
              <label for="current_password" class="form-label">Kata Sandi Saat Ini</label>
              <div class="password-input-wrapper">
                <input id="current_password" type="password" class="form-input" autocomplete="off" placeholder="Masukkan kata sandi saat ini" />
                <button type="button" class="password-toggle" data-target="current_password" aria-label="Tampilkan/sembunyikan kata sandi">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </button>
              </div>
            </div>

            <div class="divider"></div>

            <div class="form-group">
              <label for="new_password" class="form-label">Kata Sandi Baru</label>
              <div class="password-input-wrapper">
                <input id="new_password" type="password" class="form-input" autocomplete="off" placeholder="Masukkan kata sandi baru" />
                <button type="button" class="password-toggle" data-target="new_password" aria-label="Tampilkan/sembunyikan kata sandi">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </button>
              </div>
              <p class="password-hint">Minimal 8 karakter, kombinasi huruf besar, kecil, dan angka</p>
            </div>

            <div class="form-group">
              <label for="confirm_password" class="form-label">Konfirmasi Kata Sandi</label>
              <div class="password-input-wrapper">
                <input id="confirm_password" type="password" class="form-input" autocomplete="off" placeholder="Konfirmasi kata sandi baru" />
                <button type="button" class="password-toggle" data-target="confirm_password" aria-label="Tampilkan/sembunyikan kata sandi">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="form-actions">
              <button type="button" class="btn btn-primary" disabled>
                <span>Perbarui Kata Sandi</span>
              </button>
              <button type="button" class="btn btn-secondary">
                <span>Batalkan</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Security Info Card -->
        <div class="section-card info-card mt-6">
          <div class="info-content">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="info-icon">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
              <p class="info-title">Tips Keamanan</p>
              <p class="info-text">Gunakan kata sandi yang kuat dan unik untuk menjaga keamanan akun Anda. Jangan bagikan kata sandi dengan siapapun.</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Handle profile image upload
    const imageInput = document.getElementById('profile_image');
    const photoPlaceholder = document.getElementById('photoPlaceholder');
    const photoPreview = document.getElementById('photoPreview');
    const photoFileName = document.getElementById('photoFileName');

    if (imageInput) {
      imageInput.addEventListener('change', function() {
        const file = this.files?.[0];
        if (!file) return;

        photoFileName.textContent = file.name;

        const reader = new FileReader();
        reader.onload = (e) => {
          photoPreview.src = e.target.result;
          photoPreview.style.display = 'block';
          photoPlaceholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
      });
    }

    // Handle password visibility toggle
    document.querySelectorAll('.password-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (input) {
          input.type = input.type === 'password' ? 'text' : 'password';
        }
      });
    });
  });
</script>

@endsection