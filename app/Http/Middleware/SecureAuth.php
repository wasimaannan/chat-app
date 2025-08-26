<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\CredentialCheckService;
use Illuminate\Support\Facades\Auth; // ensure Auth::user() works in controllers

class SecureAuth
{
    private $credentialService;
    
    public function __construct(CredentialCheckService $credentialService)
    {
        $this->credentialService = $credentialService;
    }
    
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = session('auth_token');
        
        if (!$token) {
            return redirect()->route('login')->withErrors(['error' => 'Please login to continue']);
        }
        
        $user = $this->credentialService->validateSessionToken($token);
        
        if (!$user) {
            // Clear invalid session
            session()->forget(['auth_token', 'user_id']);
            return redirect()->route('login')->withErrors(['error' => 'Session expired. Please login again']);
        }
        
        // Verify user data integrity
        if (!$user->verifyIntegrity()) {
            \Log::error('User data integrity check failed', ['user_id' => $user->id]);
            session()->forget(['auth_token', 'user_id']);
            return redirect()->route('login')->withErrors(['error' => 'Account security error. Please contact administrator']);
        }
        
        // Check if user is active
        if (!$user->is_active) {
            session()->forget(['auth_token', 'user_id']);
            return redirect()->route('login')->withErrors(['error' => 'Account is deactivated']);
        }
        
        // Add user to request for easy access
        $request->attributes->set('authenticated_user', $user);

        // ALSO inject into Laravel's Auth facade so legacy Auth::user()/Auth::id() calls work.
        // We aren't using guards/password column, but setting the user instance is enough for retrieval.
        if (!Auth::check()) {
            Auth::setUser($user);
        }
        
        return $next($request);
    }
}
