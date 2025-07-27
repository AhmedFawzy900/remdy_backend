<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BodySystemController;
use App\Http\Controllers\RemedyTypeController;
use App\Http\Controllers\RemedyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\DiseaseController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public Auth Routes
Route::post('auth/request-otp', [AuthController::class, 'requestOtp']);
Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('auth/login/google', [AuthController::class, 'loginWithGoogle']);
Route::post('auth/login/apple', [AuthController::class, 'loginWithApple']);
Route::post('auth/admin/login', [AuthController::class, 'adminLogin']);
Route::post('auth/admin/refresh', [AuthController::class, 'refreshAdminToken']);
// Image Upload
Route::post('upload/image', [ImageUploadController::class, 'upload']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Place all routes that require authentication here
    Route::apiResource('admins', AdminController::class);
    Route::apiResource('body-systems', BodySystemController::class);
    Route::patch('body-systems/{id}/toggle-status', [BodySystemController::class, 'toggleStatus']);
    Route::apiResource('remedy-types', RemedyTypeController::class);
    Route::patch('remedy-types/{id}/toggle-status', [RemedyTypeController::class, 'toggleStatus']);
    Route::apiResource('remedies', RemedyController::class);
    Route::patch('remedies/{id}/toggle-status', [RemedyController::class, 'toggleStatus']);
    Route::apiResource('users', UserController::class);
    Route::patch('users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
    Route::apiResource('articles', ArticleController::class);
    Route::patch('articles/{id}/toggle-status', [ArticleController::class, 'toggleStatus']);
    Route::apiResource('courses', CourseController::class);
    Route::patch('courses/{id}/toggle-status', [CourseController::class, 'toggleStatus']);
    Route::apiResource('videos', VideoController::class);
    Route::patch('videos/{id}/toggle-status', [VideoController::class, 'toggleStatus']);
    Route::apiResource('diseases', DiseaseController::class);
    Route::patch('diseases/{id}/toggle-status', [DiseaseController::class, 'toggleStatus']);
    Route::apiResource('faqs', FaqController::class);
    Route::patch('faqs/{id}/toggle-status', [FaqController::class, 'toggleStatus']);
    Route::apiResource('policies', PolicyController::class);
    Route::patch('policies/{id}/toggle-status', [PolicyController::class, 'toggleStatus']);
    Route::apiResource('reviews', ReviewController::class);
    Route::patch('reviews/{id}/toggle-status', [ReviewController::class, 'toggleStatus']);
    Route::apiResource('contact-us', ContactUsController::class);
    Route::patch('contact-us/{id}/change-status', [ContactUsController::class, 'changeStatus']);
    Route::apiResource('notifications', NotificationController::class);
    Route::patch('notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::patch('notifications/{id}/archive', [NotificationController::class, 'archive']);
    Route::apiResource('plans', PlanController::class)->only(['index', 'show']);
    Route::apiResource('about', AboutController::class);
}); 