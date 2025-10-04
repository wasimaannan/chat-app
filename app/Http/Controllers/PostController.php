<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Post;
use App\Models\User;
use App\Services\EncryptionService;
use App\Services\CredentialCheckService;
use App\Services\MACService;

class PostController extends Controller
{
    private $encryptionService;
    private $credentialService;
    private $macService;
    
    public function __construct(
        EncryptionService $encryptionService,
        CredentialCheckService $credentialService,
        MACService $macService
    ) {
        $this->encryptionService = $encryptionService;
        $this->credentialService = $credentialService;
        $this->macService = $macService;
    }
    
    //all posts
    public function index()
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect()->route('login');
        }
        
        try {
            // Get all posts 
            $posts = Post::with(['user', 'comments'])->withCount('comments')->latest()->paginate(10);
            
            // Decrypt posts 
            $decryptedPosts = [];
            foreach ($posts as $post) {
                if (!$post->verifyIntegrity()) {
                    \Log::warning('Post integrity check failed', ['post_id' => $post->id]);
                    continue;
                }
                
                $decryptedData = $post->getDecryptedData();
                $postUser = $post->user;
                $decryptedUserData = $postUser->getDecryptedData();
                
                $decryptedPosts[] = [
                    'id' => $post->id,
                    'title' => $decryptedData['title'],
                    'content' => $decryptedData['content'],
                    'author' => $decryptedUserData['name'],
                    'created_at' => $post->created_at,
                    'comments_count' => $post->comments_count,
                ];
            }
            
            return view('posts.index', compact('decryptedPosts'));
            
        } catch (\Exception $e) {
            \Log::error('Failed to load posts: ' . $e->getMessage());
            return view('posts.index', ['decryptedPosts' => []]);
        }
    }

    // Creating a new post
    public function create()
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect()->route('login');
        }
        
        return view('posts.create');
    }

    // Storing a new post
    public function store(Request $request)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect()->route('login');
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            // Create new post
            $post = new Post();
            $post->user_id = $user->id;
            $post->is_published = true;
            $post->published_at = now();
            // Encrypt and set post data
            $post->setEncryptedData($request->title, $request->content);
            $post->save();
            
            return redirect()->route('posts.index')->with('success', 'Post created successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Post creation failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create post. Please try again.'])->withInput();
        }
    }

    // Display the specified post
    public function show($id)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect()->route('login');
        }
        
        try {
            $post = Post::with('user')->findOrFail($id);
            
            if (!$post->verifyIntegrity()) {
                \Log::warning('Post integrity check failed', ['post_id' => $post->id]);
                return redirect()->route('posts.index')->withErrors(['error' => 'Post data integrity error']);
            }
            
            // Decrypt post data
            $decryptedData = $post->getDecryptedData();
            $postUser = $post->user;
            $decryptedUserData = $postUser->getDecryptedData();
            
            $decryptedPost = [
                'id' => $post->id,
                'title' => $decryptedData['title'],
                'content' => $decryptedData['content'],
                'author' => $decryptedUserData['name'],
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
                'can_edit' => $post->user_id === $user->id
            ];
            $comments = $post->comments()->with('user')->latest()->get();
            return view('posts.show', compact('decryptedPost', 'comments'));
            
        } catch (\Exception $e) {
            \Log::error('Failed to load post: ' . $e->getMessage());
            return redirect()->route('posts.index')->withErrors(['error' => 'Post not found or access denied']);
        }
    }

    // Editing a post
    public function edit($id)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect()->route('login');
        }
        
        try {
            $post = Post::findOrFail($id);
            
            // Check if user owns the post
            if ($post->user_id !== $user->id) {
                return redirect()->route('posts.index')->withErrors(['error' => 'Unauthorized access']);
            }
            
            // Verify data integrity
            if (!$post->verifyIntegrity()) {
                \Log::warning('Post integrity check failed', ['post_id' => $post->id]);
                return redirect()->route('posts.index')->withErrors(['error' => 'Post data integrity error']);
            }
            
            // Decrypt post data for editing
            $decryptedData = $post->getDecryptedData();
            
            return view('posts.edit', [
                'post' => $post,
                'title' => $decryptedData['title'],
                'content' => $decryptedData['content']
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to load post for editing: ' . $e->getMessage());
            return redirect()->route('posts.index')->withErrors(['error' => 'Post not found']);
        }
    }

    // Updating a post
    public function update(Request $request, $id)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Validate input
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'is_published' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            $post = Post::findOrFail($id);
            
            // Check if user owns the post
            if ($post->user_id !== $user->id) {
                return redirect()->route('posts.index')->withErrors(['error' => 'Unauthorized access']);
            }
            
            // Update publication status
            $post->is_published = $request->has('is_published');
            if ($post->is_published && !$post->published_at) {
                $post->published_at = now();
            }
            
            // Encrypt and update post data
            $post->setEncryptedData($request->title, $request->content);
            $post->save();
            
            return redirect()->route('posts.show', $post->id)->with('success', 'Post updated successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Post update failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update post. Please try again.'])->withInput();
        }
    }

    // Deleting a post
    public function destroy($id)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect()->route('login');
        }
        
        try {
            $post = Post::findOrFail($id);
            
            // Check if user owns the post
            if ($post->user_id !== $user->id) {
                return redirect()->route('posts.index')->withErrors(['error' => 'Unauthorized access']);
            }
            
            $post->delete();
            
            return redirect()->route('posts.index')->with('success', 'Post deleted successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Post deletion failed: ' . $e->getMessage());
            return redirect()->route('posts.index')->withErrors(['error' => 'Failed to delete post']);
        }
    }
    // Display posts of the authenticated user
    public function myPosts()
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return redirect()->route('login');
        }
        
        try {
            // Get user's posts
            $posts = Post::byUser($user->id)->latest()->paginate(10);
            
            // Decrypt posts for display
            $decryptedPosts = [];
            foreach ($posts as $post) {
                if (!$post->verifyIntegrity()) {
                    \Log::warning('Post integrity check failed', ['post_id' => $post->id]);
                    continue;
                }
                
                $decryptedData = $post->getDecryptedData();
                
                $decryptedPosts[] = [
                    'id' => $post->id,
                    'title' => $decryptedData['title'],
                    'content' => substr($decryptedData['content'], 0, 200) . '...',
                    'created_at' => $post->created_at,
                    'is_published' => $post->is_published
                ];
            }
            
            return view('posts.my-posts', compact('decryptedPosts'));
            
        } catch (\Exception $e) {
            \Log::error('Failed to load user posts: ' . $e->getMessage());
            return view('posts.my-posts', ['decryptedPosts' => []]);
        }
    }

    // Get current authenticated user
    private function getCurrentUser(): ?User
    {
        $token = session('auth_token');
        if (!$token) {
            return null;
        }
        
        return $this->credentialService->validateSessionToken($token);
    }
}
