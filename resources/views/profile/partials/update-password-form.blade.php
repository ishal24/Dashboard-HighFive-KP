<section>
    <form method="post" action="{{ route('password.update') }}" class="password-form">
        @csrf
        @method('put')

        <div class="form-group">
            <label for="current_password" class="form-label">{{ __('Kata Sandi Saat Ini') }}</label>
            <div class="password-input-wrapper">
                <input id="current_password" name="current_password" type="password" class="form-input" autocomplete="current-password" />
                <button type="button" class="password-toggle" data-target="current_password">
                    <svg xmlns="http://www.w3.org/2000/svg" class="eye-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            @error('current_password', 'updatePassword')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="password" class="form-label">{{ __('Kata Sandi Baru') }}</label>
            <div class="password-input-wrapper">
                <input id="password" name="password" type="password" class="form-input" autocomplete="new-password" />
                <button type="button" class="password-toggle" data-target="password">
                    <svg xmlns="http://www.w3.org/2000/svg" class="eye-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            @error('password', 'updatePassword')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation" class="form-label">{{ __('Konfirmasi Kata Sandi') }}</label>
            <div class="password-input-wrapper">
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" autocomplete="new-password" />
                <button type="button" class="password-toggle" data-target="password_confirmation">
                    <svg xmlns="http://www.w3.org/2000/svg" class="eye-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </button>
            </div>
            @error('password_confirmation', 'updatePassword')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="save-button">{{ __('Simpan') }}</button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="success-text"
                >{{ __('Tersimpan.') }}</p>
            @endif
        </div>
    </form>

    <script>
        // Toggle password visibility
        document.querySelectorAll('.password-toggle').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const inputField = document.getElementById(targetId);
                
                if (inputField.type === 'password') {
                    inputField.type = 'text';
                    this.querySelector('.eye-icon').innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    `;
                } else {
                    inputField.type = 'password';
                    this.querySelector('.eye-icon').innerHTML = `
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    `;
                }
            });
        });
    </script>

    <style>
        /* Password form styles */
        .password-form {
            width: 100%;
            max-width: 100%;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.625rem 0.75rem;
            font-size: 0.95rem;
            line-height: 1.5;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-input:focus {
            border-color: #133057;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(19, 48, 87, 0.15);
        }

        /* Password input styling */
        .password-input-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 0.25rem;
        }

        .password-toggle:hover {
            color: #4b5563;
        }

        .password-toggle:focus {
            outline: none;
            color: #133057;
        }

        .eye-icon {
            width: 1.25rem;
            height: 1.25rem;
        }

        /* Button and actions styling */
        .form-actions {
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .save-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.25rem;
            font-size: 0.95rem;
            font-weight: 500;
            color: #ffffff;
            background-color: #133057;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.15s ease-in-out;
        }

        .save-button:hover {
            background-color: #0e223e;
        }

        .save-button:focus {
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(19, 48, 87, 0.25);
        }

        .save-button:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }

        /* Status message styling */
        .error-text {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.375rem;
        }

        .success-text {
            color: #10b981;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Form layout styling for different screens */
        @media (max-width: 640px) {
            .form-actions {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .save-button {
                width: 100%;
            }
        }
    </style>
</section>