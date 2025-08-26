<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use App\Services\CredentialCheckService;

class DashboardController extends Controller
{
    private $credentialService;
    
    public function __construct(CredentialCheckService $credentialService)
    {
        $this->credentialService = $credentialService;
    }
    
    /**
     * Display the dashboard
     */
    public function index()
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect()->route('login');
        }
        
        try {
            // Get user's decrypted data
            $userData = $user->getDecryptedData();
            
            // Get dashboard statistics
            $stats = [
                'total_posts' => Post::byUser($user->id)->count(),
                'published_posts' => Post::byUser($user->id)->published()->count(),
                'total_users' => User::count(),
                'recent_posts_count' => Post::byUser($user->id)->where('created_at', '>=', now()->subDays(7))->count()
            ];
            
            // Get recent posts
            $recentPosts = Post::byUser($user->id)->latest()->limit(5)->get();
            $decryptedRecentPosts = [];
            
            foreach ($recentPosts as $post) {
                if ($post->verifyIntegrity()) {
                    $decryptedData = $post->getDecryptedData();
                    $decryptedRecentPosts[] = [
                        'id' => $post->id,
                        'title' => $decryptedData['title'],
                        'content' => substr($decryptedData['content'], 0, 100) . '...',
                        'created_at' => $post->created_at,
                        'is_published' => $post->is_published
                    ];
                }
            }
            
            return view('dashboard', compact('userData', 'stats', 'decryptedRecentPosts'));
            
        } catch (\Exception $e) {
            \Log::error('Dashboard load failed: ' . $e->getMessage());
            return view('dashboard', [
                'userData' => ['name' => 'User'],
                'stats' => ['total_posts' => 0, 'published_posts' => 0, 'total_users' => 0, 'recent_posts_count' => 0],
                'decryptedRecentPosts' => []
            ]);
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
