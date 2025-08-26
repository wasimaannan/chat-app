<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date',
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
            
            // Create user with encrypted data
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'date_of_birth' => $request->date_of_birth,
            ];
            
            // Encrypt user data
            $encryptedData = $this->encryptionService->encryptUserInfo($userData);
            
            // Create user
            $user = new User();
            $user->fill($encryptedData);
            $user->password_hash = $passwordData['hash'];
            $user->password_salt = $passwordData['salt'];
            $user->is_active = true;
            $user->save();
            
            // Generate session token
            $sessionToken = $this->credentialService->generateSessionToken($user);
            
            // Set session
            session(['auth_token' => $sessionToken, 'user_id' => $user->id]);
            
            return redirect()->route('dashboard')->with('success', 'Registration successful!');
            
        } catch (\Exception $e) {
            \Log::error('Registration failed: ' . $e->getMessage());
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
            
            return redirect()->route('dashboard')->with('success', 'Login successful!');
            
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
        $users = User::all();
        
        foreach ($users as $user) {
            try {
                $decryptedEmail = $this->encryptionService->decrypt($user->email, 'user_info_email');
                if (strtolower($decryptedEmail) === strtolower($email)) {
                    return true;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        return false;
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
}
