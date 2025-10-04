<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\User;
use App\Services\PasswordService;
use App\Services\EncryptionService;
use App\Services\CredentialCheckService;
use App\Services\MACService;

class AuthController extends Controller
{
    private $passwordService;
    private $encryptionService;
    
    // change password
    public function changePassword(Request $request)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect()->route('login');
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ], [
            'new_password.confirmed' => 'The new password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check current password
        $currentPassword = $request->input('current_password');
        $passwordService = $this->passwordService;
        $isValid = $passwordService->verifyPassword($currentPassword, $user->password_hash, $user->password_salt);
        if (!$isValid) {
            return back()->withErrors(['password_error' => 'Current password is incorrect.'])->withInput();
        }

        // Set new password
        $newPassword = $request->input('new_password');
        $passwordData = $passwordService->hashPassword($newPassword);
        $user->password_hash = $passwordData['hash'];
        $user->password_salt = $passwordData['salt'];
        $user->save();

        return back()->with('password_success', 'Password changed successfully!');
    }

    private $credentialService;
    private $macService;
    
    public function __construct(
        PasswordService $passwordService,
        EncryptionService $encryptionService,
        CredentialCheckService $credentialService,
        MACService $macService
    ) {
        $this->passwordService = $passwordService;
        $this->encryptionService = $encryptionService;
        $this->credentialService = $credentialService;
        $this->macService = $macService;
    }
    
    // Show login form
     
    public function showLogin()
    {
        return view('auth.login');
    }

    // Show registration form

    public function showRegister()
    {
        return view('auth.register');
    }
    
    // Handle user registration

    public function register(Request $request)
    {
        $this->ensureSqliteDatabase();

        \Log::info('REG: Starting registration', ['request' => $request->all()]);
        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => ['nullable','regex:/^\d{4}-\d{2}-\d{2}$/'],
            'password' => 'required|string|min:8|confirmed',
            'profile_picture' => 'nullable|file|image|mimes:jpeg,png|max:2048',
        ]);
        \Log::info('REG: After validation', ['fails' => $validator->fails(), 'errors' => $validator->errors()]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            \Log::info('REG: Before credential check');
            // Check credential strength
            $credentialCheck = $this->credentialService->validateCredentialStrength(
                $request->email,
                $request->password
            );
            \Log::info('REG: After credential check', ['result' => $credentialCheck]);
            if (!$credentialCheck['valid']) {
                return back()->withErrors($credentialCheck['errors'])->withInput();
            }
            \Log::info('REG: Before email exists check');
            if ($this->isEmailExists($request->email)) {
                \Log::info('REG: Email already exists', ['email' => $request->email]);
                return back()->withErrors(['email' => 'Email already registered'])->withInput();
            }
            \Log::info('REG: Before password hash');
            $passwordData = $this->passwordService->hashPassword($request->password);
            \Log::info('REG: After password hash');
            $dobInput = $request->date_of_birth;
            if (!empty($dobInput) && preg_match('#^\d{2}/\d{2}/\d{4}$#', $dobInput)) {
                [$d,$m,$y] = explode('/', $dobInput);
                $dobInput = "$y-$m-$d";
            }
            \Log::info('REG: Before DB transaction');
            DB::beginTransaction();
            try {
                \Log::info('REG: In DB transaction');
                $userData = [
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'date_of_birth' => $dobInput,
                ];
                \Log::info('REG: Before encrypt user info');
                $encryptedData = $this->encryptionService->encryptUserInfo($userData);
                \Log::info('REG: After encrypt user info');
                $newProfilePicEncrypted = null;
                if ($request->hasFile('profile_picture') && $request->file('profile_picture')->isValid()) {
                    \Log::info('REG: Handling profile picture upload');
                    $imageFile = $request->file('profile_picture');
                    $image = file_get_contents($imageFile->getRealPath());
                    $encryptionService = app(\App\Services\EncryptionService::class);
                    $newProfilePicEncrypted = $encryptionService->encrypt($image, 'profile_picture');
                    \Log::info('REG: After profile picture encrypt');
                }
                $user = new User();
                $user->fill($encryptedData);
                if (!empty($request->email)) {
                    $user->email_hash = hash('sha256', strtolower(trim($request->email)));
                }
                $user->password_hash = $passwordData['hash'];
                $user->password_salt = $passwordData['salt'];
                $user->is_active = true;
                if ($newProfilePicEncrypted !== null) {
                    $user->profile_picture = $newProfilePicEncrypted;
                }
                \Log::info('REG: Before user save');
                $user->save();
                \Log::info('REG: After user save', ['user_id' => $user->id]);
                DB::commit();
                \Log::info('REG: After DB commit');
            } catch (\Throwable $inner) {
                DB::rollBack();
                \Log::error('Registration inner failure: ' . $inner->getMessage(), [
                    'class' => get_class($inner),
                    'trace' => $inner->getTraceAsString(),
                    'file' => $inner->getFile(),
                    'line' => $inner->getLine(),
                    'request' => $request->all(),
                ]);
                throw $inner; // rethrow to outer catch
            }
            $sessionToken = $this->credentialService->generateSessionToken($user);
            session(['auth_token' => $sessionToken, 'user_id' => $user->id]);
            \Log::info('REG: Registration successful', ['user_id' => $user->id]);
            return redirect()->route('posts.index')->with('success', 'Registration successful!');
        } catch (\Exception $e) {
            \Log::error('Registration failed: ' . $e->getMessage(), [
                'class' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request->all(),
            ]);
            return back()->withErrors(['error' => 'Registration failed. Please try again.'])->withInput();
        }
    }

    // user login
    public function login(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            // Validate credentials
            $result = $this->credentialService->validateCredentials(
                $request->email,
                $request->password
            );
            
            if (!$result['success']) {
                return back()->withErrors(['error' => $result['message']])->withInput();
            }
            
            // Generate session token
            $sessionToken = $this->credentialService->generateSessionToken($result['user']);
            
            // Set session
            session(['auth_token' => $sessionToken, 'user_id' => $result['user']->id]);
            
            return redirect()->route('posts.index')->with('success', 'Login successful!');
            
        } catch (\Exception $e) {
            \Log::error('Login failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Login failed. Please try again.'])->withInput();
        }
    }
    
    // logout
    public function logout(Request $request)
    {
        session()->forget(['auth_token', 'user_id']);
        session()->flash('success', 'Logged out successfully!');
        
        return redirect()->route('login');
    }
    
    /**
     * Check if email exists (decrypt and compare)
     */
    private function isEmailExists(string $email): bool
    {
    $hash = hash('sha256', strtolower(trim($email)));
    return User::where('email_hash', $hash)->exists();
    }
    
    // show profile
    public function profile()
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect()->route('login');
        }
        $user = \App\Models\User::find($user->id);
        $decryptedData = $user->getDecryptedData();
        return view('auth.profile', compact('user', 'decryptedData'));
    }

    // update profile
    public function updateProfile(Request $request)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect()->route('login');
        }
    
    $user = \App\Models\User::find($user->id);
        
        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
            'bio' => 'nullable|string|max:1000',
            'profile_picture' => 'nullable|file|image|mimes:jpeg,png|max:2048',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            Log::info('Profile update: starting', [
                'user_id' => $user->id,
                'has_file' => $request->hasFile('profile_picture'),
                'file_valid' => $request->hasFile('profile_picture') ? $request->file('profile_picture')->isValid() : null,
                'request_files' => $request->allFiles(),
                'request_data' => $request->all(),
            ]);

            // Update user data 
            $userData = [
                'name' => $request->name,
                'email' => $user->getDecryptedData()['email'] ?? null, // Keep existing email
                'phone' => $request->phone,
                'address' => $request->address,
                'date_of_birth' => $request->date_of_birth,
                'bio' => $request->bio,
            ];

            // Handle profile picture upload 
            $newProfilePicEncrypted = null;
            $newProfilePicMime = null;
            if ($request->hasFile('profile_picture')) {
                Log::info('Profile update: file detected', [
                    'user_id' => $user->id,
                    'file_valid' => $request->file('profile_picture')->isValid(),
                    'file_info' => $request->file('profile_picture'),
                ]);
            }
            if ($request->hasFile('profile_picture') && $request->file('profile_picture')->isValid()) {
                $imageFile = $request->file('profile_picture');
                Log::info('Profile update: file upload details', [
                    'original_name' => $imageFile->getClientOriginalName(),
                    'mime' => $imageFile->getMimeType(),
                    'size' => $imageFile->getSize(),
                ]);
                $image = file_get_contents($imageFile->getRealPath());
                $mime = $imageFile->getMimeType();
                $encryptionService = app(\App\Services\EncryptionService::class);
                $newProfilePicEncrypted = $encryptionService->encrypt($image, 'profile_picture');
                $newProfilePicMime = $mime;
                Log::info('Profile update: picture uploaded', [
                    'user_id' => $user->id,
                    'mime' => $mime,
                    'encrypted_length' => strlen($newProfilePicEncrypted),
                ]);
            } else {
                Log::info('Profile update: no valid picture uploaded', [
                    'user_id' => $user->id
                ]);
            }

            // DB transaction to ensure atomic update
            DB::beginTransaction();
            try {
                $user->setEncryptedData($userData, $newProfilePicEncrypted, $newProfilePicMime);
                $saved = $user->save();

                Log::info('Profile update: after save attempt', [
                    'user_id' => $user->id,
                    'saved' => $saved,
                    'profile_picture_first_100' => $user->profile_picture ? substr($user->profile_picture,0,100) : null,
                    'profile_picture_mime' => $user->profile_picture_mime ?? null,
                ]);

                DB::commit();
            } catch (\Throwable $inner) {
                DB::rollBack();
                Log::error('Profile update transaction failed: ' . $inner->getMessage(), [
                    'trace' => $inner->getTraceAsString(),
                ]);
                throw $inner;
            }

            return back()->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            \Log::error('Profile update failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Profile update failed. Please try again.']);
        }
    }
    
    // get current user from session
    private function getCurrentUser(): ?User
    {
        $token = session('auth_token');
        if (!$token) {
            return null;
        }
        
        return $this->credentialService->validateSessionToken($token);
    }

    
    private function ensureSqliteDatabase(): void
    {
        try {
            if (config('database.default') !== 'sqlite') {
                return; // not sqlite
            }

            $configured = config('database.connections.sqlite.database');
            if (!$configured) {
                return;
            }

            $dbPath = $configured;
            if (!Str::contains($dbPath, ':') && !Str::startsWith($dbPath, ['/'])) {
                $dbPath = base_path($dbPath);
            }

            $dbPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $dbPath);

            if (!File::exists($dbPath)) {
                $dir = dirname($dbPath);
                if (!File::isDirectory($dir)) {
                    File::makeDirectory($dir, 0755, true);
                }
                File::put($dbPath, '');
                \Log::info('SQLite database file created automatically', ['path' => $dbPath]);
                try { DB::connection()->getPdo(); } catch (\Throwable $e) { /* ignore */ }
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to ensure sqlite database file', ['error' => $e->getMessage()]);
        }
    }
}
