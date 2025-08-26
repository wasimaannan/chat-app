<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
    
    /**
     * Show login form
     */
    public function showLogin()
    {
        return view('auth.login');
    }
    
    /**
     * Show registration form
     */
    public function showRegister()
    {
        return view('auth.register');
    }
    
    /**
     * Handle user registration
     */
    public function register(Request $request)
    {
        // Ensure SQLite database file exists (common cause of silent registration failure)
        $this->ensureSqliteDatabase();

        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => ['nullable','regex:/^\d{4}-\d{2}-\d{2}$/'],
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            // Check credential strength
            $credentialCheck = $this->credentialService->validateCredentialStrength(
                $request->email,
                $request->password
            );
            
            if (!$credentialCheck['valid']) {
                return back()->withErrors($credentialCheck['errors'])->withInput();
            }
            
            // Check if email already exists (need to decrypt and compare)
            if ($this->isEmailExists($request->email)) {
                return back()->withErrors(['email' => 'Email already registered'])->withInput();
            }
            
            // Hash password with salt
            $passwordData = $this->passwordService->hashPassword($request->password);
            
            // Normalize date_of_birth (HTML input might be d/m/Y in some browsers if locale set)
            $dobInput = $request->date_of_birth;
            if (!empty($dobInput) && preg_match('#^\d{2}/\d{2}/\d{4}$#', $dobInput)) {
                // Convert dd/mm/YYYY -> YYYY-mm-dd
                [$d,$m,$y] = explode('/', $dobInput);
                $dobInput = "$y-$m-$d";
            }

            DB::beginTransaction();
            try {
                // Create user with encrypted data
                $userData = [
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'date_of_birth' => $dobInput,
                ];
                
                // Encrypt user data
                $encryptedData = $this->encryptionService->encryptUserInfo($userData);
                
                // Create user (ensure email_hash populated via deterministic hash)
                $user = new User();
                $user->fill($encryptedData);
                if (!empty($request->email)) {
                    $user->email_hash = hash('sha256', strtolower(trim($request->email)));
                }
                $user->password_hash = $passwordData['hash'];
                $user->password_salt = $passwordData['salt'];
                $user->is_active = true;
                $user->save();

                DB::commit();
            } catch (\Throwable $inner) {
                DB::rollBack();
                \Log::error('Registration inner failure: '.$inner->getMessage(), [
                    'trace_top' => collect(explode("\n", $inner->getTraceAsString()))->take(5)->implode('|'),
                ]);
                throw $inner; // rethrow to outer catch
            }
            
            // Generate session token
            $sessionToken = $this->credentialService->generateSessionToken($user);
            
            // Set session
            session(['auth_token' => $sessionToken, 'user_id' => $user->id]);
            
            return redirect()->route('posts.index')->with('success', 'Registration successful!');
            
        } catch (\Exception $e) {
            \Log::error('Registration failed: ' . $e->getMessage(), ['class' => get_class($e)]);
            return back()->withErrors(['error' => 'Registration failed. Please try again.'])->withInput();
        }
    }
    
    /**
     * Handle user login
     */
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
    
    /**
     * Handle user logout
     */
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
    
    /**
     * Show user profile
     */
    public function profile()
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect()->route('login');
        }
        
        $decryptedData = $user->getDecryptedData();
        
        return view('auth.profile', compact('user', 'decryptedData'));
    }
    
    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            // Update user data with encryption
            $userData = [
                'name' => $request->name,
                'email' => $user->getDecryptedData()['email'], // Keep existing email
                'phone' => $request->phone,
                'address' => $request->address,
                'date_of_birth' => $request->date_of_birth,
            ];
            
            $user->setEncryptedData($userData);
            $user->save();
            
            return back()->with('success', 'Profile updated successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Profile update failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Profile update failed. Please try again.']);
        }
    }
    
    /**
     * Get current authenticated user
     */
    private function getCurrentUser(): ?User
    {
        $token = session('auth_token');
        if (!$token) {
            return null;
        }
        
        return $this->credentialService->validateSessionToken($token);
    }

    /**
     * Ensure sqlite database file exists when using sqlite.
     * Resolves relative path against base_path and auto-creates missing file.
     */
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
