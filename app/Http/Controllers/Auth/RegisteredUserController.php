<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AccountManager;
use App\Models\Witel;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use App\Rules\RecaptchaV3;
use Exception;

use App\Http\Requests\Auth\LoginRequest;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        try {
            // Mengambil data Account Manager untuk dropdown/autocomplete
            $accountManagers = AccountManager::select('id', 'nama', 'nik')->get();

            // Mengambil data Witel untuk dropdown
            $witels = Witel::select('id', 'nama')->get();
            Log::info("Registrate", ['witels' => $witels]);

            // Periksa jika tidak ada Account Manager/Witel, tampilkan pesan
            $noAccountManagers = $accountManagers->isEmpty();
            $noWitels = $witels->isEmpty();

            return view('auth.register', compact('accountManagers', 'witels', 'noAccountManagers', 'noWitels'));
        } catch (Exception $e) {
            Log::error('Error loading registration page: ', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Fall back to basic registration page if data loading fails
            return view('auth.register', [
                'accountManagers' => collect([]),
                'witels' => collect([]),
                'noAccountManagers' => true,
                'noWitels' => true,
                'error' => 'Terjadi kesalahan saat memuat data. Silakan coba lagi nanti.'
            ]);
        }
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            Log::info('Registration attempt:', [
                'email' => $request->email,
                'role' => $request->role
            ]);

            Log::info($_REQUEST, []);

            // Validasi dasar untuk semua role
            $commonRules = [
                'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
                'role' => ['required', 'string', 'in:admin,account_manager,witel'],
                'recaptcha_token'  => ['required', new RecaptchaV3('register', config('services.recaptcha.v3.threshold'))],
            ];

            // Validasi khusus berdasarkan role
            $roleSpecificRules = [];

            if ($request->role === 'admin') {
                $roleSpecificRules = [
                    'name' => ['required', 'string', 'max:255'],
                    'admin_code' => ['required', 'string']
                ];
            } elseif ($request->role === 'account_manager') {
                // NOTE: maybe temp
                if ($request->account_manager_id === null) {
                    Log::warning('Registration failed: Selected AM already has an account');
                    return back()->withErrors([
                        'account_manager_id' => 'Nama ini telah terdaftar pada akun lain.'
                    ])->withInput();
                }

                // Periksa apakah ada account manager di database
                $accountManagersExist = AccountManager::count() > 0;

                if ($accountManagersExist) {
                    $roleSpecificRules = [
                        'account_manager_id' => ['required', 'exists:account_managers,id'],
                        'nik' => ['required', 'string']
                    ];
                } else {
                    Log::warning('Registration failed: No account managers available');
                    return back()->withErrors([
                        'account_manager_id' => 'Belum ada data Account Manager. Silakan hubungi administrator untuk menambahkan Anda dalam data Account Manager.'
                    ])->withInput();
                }
            } elseif ($request->role === 'witel') {
                // Periksa apakah ada witel di database
                $witelsExist = Witel::count() > 0;

                if ($witelsExist) {
                    $roleSpecificRules = [
                        'witel_id' => ['required', 'exists:witel,id'],
                        'witel_code' => ['required', 'string']
                    ];
                } else {
                    Log::warning('Registration failed: No witel available');
                    return back()->withErrors([
                        'witel_id' => 'Belum ada data Witel. Silakan hubungi administrator untuk menambahkan data Witel.'
                    ])->withInput();
                }
            }

            // TODO: Make a validator function to check if selected AM already has an account
            $rules = array_merge($commonRules, $roleSpecificRules);
            $validator = Validator::make($request->all(), $rules);

            // Validasi khusus untuk kode admin
            if ($request->role === 'admin') {
                $validator->after(function ($validator) use ($request) {
                    $hash = config('auth.admin_code_hash');
                    if (!Hash::check($request->admin_code, $hash)) {
                        $validator->errors()->add('admin_code', 'Kode admin tidak valid.');
                    }
                });
            }

            // Validasi khusus untuk komparasi NIK AM
            elseif ($request->role === 'account_manager') {
                $validator->after(function ($validator) use ($request) {
                    $accountManager = AccountManager::findOrFail($request->account_manager_id);
                    if ($request->nik !== $accountManager->nik) {
                        $validator->errors()->add('account_manager_nik', 'NIK tidak sesuai dengan Account Manager yang dipilih');
                    }
                });
            }

            // Validasi khusus untuk kode witel
            else {
                $validator->after(function ($validator) use ($request) {
                    // NOTE: the order of this array is important to keep it as is
                    $witel_codes = ["bali", "jatim_barat", "jatim_timur", "nusa_tenggara", "semarang_jateng_utara", "solo_jateng_timur", "suramadu", "yogya_jateng_selatan"];

                    $hash = config("auth.witel_{$witel_codes[$request->witel_id - 1]}_code_hash");

                    if (!Hash::check($request->witel_code, $hash)) {
                        $validator->errors()->add('witel_code', 'Kode witel tidak valid.');
                    }
                });
            }

            if ($validator->fails()) {
                Log::warning('Registration validation failed', [
                    'errors' => $validator->errors()->toArray()
                ]);
                return back()->withErrors($validator)->withInput();
            }

            // Set data berdasarkan role
            $userData = [
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'account_manager_id' => null,
                //'account_manager_nik' => null,
                'witel_id' => null,
                'admin_code' => null,
                'witel_code' => null,
            ];

            // Proses untuk admin
            if ($request->role === 'admin') {
                $userData['name'] = $request->name;
                $userData['admin_code'] = $request->admin_code;
            }
            // Proses untuk account manager
            elseif ($request->role === 'account_manager') {
                try {
                    $accountManager = AccountManager::findOrFail($request->account_manager_id);
                    $userData['name'] = $accountManager->nama;
                    $userData['account_manager_id'] = $accountManager->id;
                } catch (Exception $e) {
                    Log::error('Account Manager not found', [
                        'account_manager_id' => $request->account_manager_id,
                        'error' => $e->getMessage()
                    ]);
                    return back()->withErrors(['account_manager_id' => 'Account Manager tidak ditemukan.'])->withInput();
                }
            }
            // Proses untuk witel
            elseif ($request->role === 'witel') {
                try {
                    $witel = Witel::findOrFail($request->witel_id);
                    $userData['name'] = "Support Witel " . $witel->nama;
                    $userData['witel_id'] = $witel->id;
                    // NOTE: wut??
                    $userData['witel_code'] = $request->witel_code;
                } catch (Exception $e) {
                    Log::error('Witel not found', [
                        'witel_id' => $request->witel_id,
                        'error' => $e->getMessage()
                    ]);
                    return back()->withErrors(['witel_id' => 'Witel tidak ditemukan.'])->withInput();
                }
            }

            // Upload profile image jika ada
            if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
                try {
                    $path = $request->file('profile_image')->store('profile-images', 'public');
                    $userData['profile_image'] = $path;
                    Log::info('Profile image uploaded successfully', ['path' => $path]);
                } catch (Exception $e) {
                    Log::error('Failed to upload profile image', [
                        'error' => $e->getMessage()
                    ]);
                    // Continue registration even if image upload fails
                }
            }

            Log::info('Creating user with data:', [
                'name' => $userData['name'] ?? 'Not provided',
                'email' => $userData['email'],
                'role' => $userData['role']
            ]);

            $user = User::create($userData);

            // automatically verify admin email upon creation
            if ($request->role === 'admin' || $request->role === 'witel') {
                $user->markEmailAsVerified();
            }

            Log::info('User created successfully', [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]);

            event(new Registered($user));

            Log::info('$user in RegUseCont', [$user]);
            Auth::login($user);

            if (Auth::check()) {
                //request()->session()->regenerate();
                $request->session()->regenerate();

                Log::info('Manually logged in user.', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                return redirect()->route('verification.notice');
            }

            Log::error('Failed to log in user manually.', ['email' => $user->email]);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')
                ->withErrors(['email' => 'Gagal mengautentikasi, silahkan log in ulang']);

            //return redirect(route('login', absolute: false))->with('success', 'Pendaftaran berhasil! Silakan login dengan akun yang telah Anda buat.');
        } catch (Exception $e) {
            Log::error('Registration failed with exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->except(['password', 'password_confirmation'])
            ]);

            return back()->withErrors([
                'general' => 'Terjadi kesalahan saat mendaftar: ' . $e->getMessage()
            ])->withInput($request->except(['password', 'password_confirmation']));
        }
    }

    /**
     * Search for account managers (for AJAX requests)
     */
    public function searchAccountManagers(Request $request)
    {
        try {
            $search = $request->input('search', '');

            $accountManagers = AccountManager::where('nama', 'LIKE', "%{$search}%")
                ->limit(10)
                ->get(['id', 'nama']);

            return response()->json($accountManagers);
        } catch (Exception $e) {
            Log::error('Error searching account managers', [
                'search' => $request->input('search'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan saat mencari data Account Manager'
            ], 500);
        }
    }

    // TODO: Fetch users table to see if account manager id has a user record, if so append a disclaimer saying this AM already has an account
    public function checkAccountAvailable(Request $request)
    {
        try {
            $selectedAm = (int) $request->query('account_manager_id');

            if (!$selectedAm) {
                return response()->json(['error' => 'account_manager_id_missing'], 422);
            }

            $userExists = User::where('account_manager_id', $selectedAm)->exists();

            if ($userExists) {
                return response()->json([
                    'registered' => true,
                    'message' => 'Nama ini telah terdaftar pada akun lain.',
                ], 409);
            }

            return response()->json(['registered' => false], 200);
        } catch (Exception $e) {
            // NOTE: don't know about this
            return back()->withErrors(['account_manager_id' => $e])->withInput();
        }
    }
}
