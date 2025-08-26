<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\MessageController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes (require authentication)
Route::middleware(['secure.auth'])->group(function () {
    // Redirect old dashboard path to posts
    Route::get('/dashboard', function(){ return redirect()->route('posts.index'); });
    
    // Profile management
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::post('/profile', [AuthController::class, 'updateProfile']);
    
    // Posts management
    Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('/posts/create', [PostController::class, 'create'])->name('posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
    Route::get('/posts/{id}', [PostController::class, 'show'])->name('posts.show');
    Route::get('/posts/{id}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/posts/{id}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{id}', [PostController::class, 'destroy'])->name('posts.destroy');
    Route::get('/my-posts', [PostController::class, 'myPosts'])->name('posts.my-posts');

    // Messaging
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.inbox');
    Route::get('/messages/sent', [MessageController::class, 'sent'])->name('messages.sent');
    Route::get('/messages/with/{userId}', [MessageController::class, 'conversation'])->name('messages.conversation');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/messages/{message}/read', [MessageController::class, 'read'])->name('messages.read');

    // New chatty_cat modern UI
    Route::get('/chat', [MessageController::class, 'chat'])->name('chat.index');
    Route::get('/chat/users', [MessageController::class, 'users'])->name('chat.users');
    Route::get('/chat/conversation/{userId}', [MessageController::class, 'conversationJson'])->name('chat.conversation.json');
    Route::post('/chat/message', [MessageController::class, 'storeJson'])->name('chat.message.store');
});
