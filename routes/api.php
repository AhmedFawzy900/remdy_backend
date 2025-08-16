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
use App\Http\Controllers\AdController;
use App\Http\Controllers\DeviceTokensController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\OutNotificationController;

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

// Mobile Authentication Routes
Route::prefix('mobile/auth')->group(function () {
Route::post('register', [App\Http\Controllers\Mobile\AuthController::class, 'register']);
Route::post('login', [App\Http\Controllers\Mobile\AuthController::class, 'login']);
Route::post('login/google', [App\Http\Controllers\Mobile\AuthController::class, 'loginWithGoogle']);
Route::post('login/apple', [App\Http\Controllers\Mobile\AuthController::class, 'loginWithApple']);
Route::get('oauth/urls', [App\Http\Controllers\Mobile\AuthController::class, 'getOAuthUrls']);
Route::post('oauth/google/callback', [App\Http\Controllers\Mobile\AuthController::class, 'googleCallback']);
Route::post('oauth/apple/callback', [App\Http\Controllers\Mobile\AuthController::class, 'appleCallback']);
Route::post('send-email', [App\Http\Controllers\Mobile\AuthController::class, 'sendEmail']);
Route::post('check-code', [App\Http\Controllers\Mobile\AuthController::class, 'checkCode']);
Route::post('forget-password', [App\Http\Controllers\Mobile\AuthController::class, 'forgetPassword']);
    
    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [App\Http\Controllers\Mobile\AuthController::class, 'userProfile']);
        Route::post('profile', [App\Http\Controllers\Mobile\AuthController::class, 'updateProfile']);
        Route::post('password', [App\Http\Controllers\Mobile\AuthController::class, 'updatePassword']);
        Route::post('logout', [App\Http\Controllers\Mobile\AuthController::class, 'logout']);
        Route::delete('account', [App\Http\Controllers\Mobile\AuthController::class, 'deleteAccount']);
    });
});

// Image Upload
Route::post('upload/image', [ImageUploadController::class, 'upload']);


// Protected Mobile Routes (Require Authentication)
Route::prefix('mobile')->middleware('auth:sanctum')->group(function () {
    // Favorites management
    Route::post('favorites/add', [App\Http\Controllers\Mobile\FavoriteController::class, 'addToFavorites']);
    Route::post('favorites/remove', [App\Http\Controllers\Mobile\FavoriteController::class, 'removeFromFavorites']);
    Route::post('favorites/clear', [App\Http\Controllers\Mobile\FavoriteController::class, 'clearFavorites']);
    Route::get('favorites', [App\Http\Controllers\Mobile\FavoriteController::class, 'getFavorites']);
    Route::post('favorites/check', [App\Http\Controllers\Mobile\FavoriteController::class, 'checkFavorite']);
    
    // Review reactions (like/dislike)
    Route::post('reviews/{id}/like', [ReviewController::class, 'likeReview']);
    Route::post('reviews/{id}/dislike', [ReviewController::class, 'dislikeReview']);
    Route::get('reviews/{id}/user-reaction', [ReviewController::class, 'getUserReaction']);
    
    // Reminders management
    Route::post('reminders', [App\Http\Controllers\Mobile\ReminderController::class, 'create']);
    Route::get('reminders', [App\Http\Controllers\Mobile\ReminderController::class, 'index']);
    Route::put('reminders/{id}', [App\Http\Controllers\Mobile\ReminderController::class, 'update']);
    Route::delete('reminders/{id}', [App\Http\Controllers\Mobile\ReminderController::class, 'destroy']);
    Route::delete('reminders', [App\Http\Controllers\Mobile\ReminderController::class, 'deleteAll']);
    Route::patch('reminders/{id}/toggle', [App\Http\Controllers\Mobile\ReminderController::class, 'toggleStatus']);
    
    // Mobile Course Management
    Route::post('purchase-course', [App\Http\Controllers\Mobile\CourseController::class, 'purchaseCourse']);
    Route::get('my-courses', [App\Http\Controllers\Mobile\CourseController::class, 'myCourses']);
    Route::prefix('courses')->group(function () {
        Route::get('available', [App\Http\Controllers\Mobile\CourseController::class, 'availableCourses']);
        // Route::get('{courseId}', [App\Http\Controllers\Mobile\CourseController::class, 'showCourse']);
        Route::post('{courseId}/start', [App\Http\Controllers\Mobile\CourseController::class, 'startCourse']);
        Route::get('{courseId}/lessons', [App\Http\Controllers\Mobile\CourseController::class, 'getLessonsByCourse']);
        Route::get('{courseId}/progress', [App\Http\Controllers\Mobile\CourseController::class, 'getLessonProgressSummary']);
        Route::get('{courseId}/lessons/{lessonId}', [App\Http\Controllers\Mobile\CourseController::class, 'showLesson']);
        Route::post('{courseId}/lessons/{lessonId}/start', [App\Http\Controllers\Mobile\CourseController::class, 'startLesson']);
        Route::get('{courseId}/lessons/{lessonId}/next', [App\Http\Controllers\Mobile\CourseController::class, 'getNextLesson']);
        Route::post('lessons/complete', [App\Http\Controllers\Mobile\CourseController::class, 'completeLesson']);
    });
});

// Protected Routes (Admin Dashboard)
Route::middleware('auth:sanctum')->group(function () {
    // Place all routes that require authentication here
    Route::apiResource('admins', AdminController::class);
    Route::apiResource('body-systems', BodySystemController::class);
    Route::patch('body-systems/{id}/toggle-status', [BodySystemController::class, 'toggleStatus']);
    Route::apiResource('remedy-types', RemedyTypeController::class);
    Route::patch('remedy-types/{id}/toggle-status', [RemedyTypeController::class, 'toggleStatus']);
    Route::apiResource('remedies', RemedyController::class);
    Route::patch('remedies/{id}/toggle-status', [RemedyController::class, 'toggleStatus']);
    Route::get('remedies/{id}/dashboard', [RemedyController::class, 'showForDashboard']);
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
    Route::apiResource('reviews', ReviewController::class)->only(['index', 'show', 'store','update','destroy']);
    Route::patch('reviews/{id}/toggle-status', [ReviewController::class, 'toggleStatus']);
    Route::get('reviews/{type}/{elementId}', [ReviewController::class, 'getReviewsByTypeAndElement']);
   
    Route::apiResource('contact-us', ContactUsController::class);
    Route::patch('contact-us/{id}/change-status', [ContactUsController::class, 'changeStatus']);
    Route::apiResource('notifications', NotificationController::class);
    Route::patch('notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::patch('notifications/{id}/archive', [NotificationController::class, 'archive']);
    Route::apiResource('plans', PlanController::class)->only(['index', 'show']);
    Route::apiResource('about', AboutController::class);
    Route::apiResource('ads', AdController::class);
    Route::patch('ads/{id}/toggle-status', [AdController::class, 'toggleStatus']);
    Route::apiResource('instructors', InstructorController::class);
    Route::patch('instructors/{id}/toggle-status', [InstructorController::class, 'toggleStatus']);
    Route::get('instructors/for-selection', [InstructorController::class, 'forSelection']);
    Route::apiResource('lessons', LessonController::class);
    Route::patch('lessons/{id}/toggle-status', [LessonController::class, 'toggleStatus']);
    Route::get('courses/{courseId}/lessons', [LessonController::class, 'byCourse']);
    Route::post('send-notification', [OutNotificationController::class, 'sendNotification']);
    // Lesson Content Blocks Routes
    Route::prefix('lessons/{lessonId}/content-blocks')->group(function () {
        Route::get('/', [App\Http\Controllers\LessonContentBlockController::class, 'index']);
        Route::post('/', [App\Http\Controllers\LessonContentBlockController::class, 'store']);
        Route::get('/types', [App\Http\Controllers\LessonContentBlockController::class, 'getTypes']);
        Route::patch('/reorder', [App\Http\Controllers\LessonContentBlockController::class, 'reorder']);
        Route::get('/{blockId}', [App\Http\Controllers\LessonContentBlockController::class, 'show']);
        Route::put('/{blockId}', [App\Http\Controllers\LessonContentBlockController::class, 'update']);
        Route::delete('/{blockId}', [App\Http\Controllers\LessonContentBlockController::class, 'destroy']);
        Route::patch('/{blockId}/toggle-status', [App\Http\Controllers\LessonContentBlockController::class, 'toggleStatus']);
    });
}); 

// Public Mobile App Routes (Guest Mode)
Route::prefix('mobile')->group(function () {
    // Public read-only endpoints for mobile app
    Route::get('remedies', [RemedyController::class, 'index']);
    Route::get('remedies/{id}', [RemedyController::class, 'show']);
  
    Route::get('body-systems', [BodySystemController::class, 'index']);
    Route::get('body-systems/{id}', [BodySystemController::class, 'show']);
 
    
    Route::get('articles', [ArticleController::class, 'index']);
    Route::get('articles/{id}', [ArticleController::class, 'show']);
   
    Route::get('courses', [CourseController::class, 'index']);
    Route::get('courses/{id}', [App\Http\Controllers\Mobile\CourseController::class, 'showCourse']);
   
    Route::get('videos', [VideoController::class, 'index']);
    Route::get('videos/{id}', [VideoController::class, 'show']);
   
    Route::get('faqs', [FaqController::class, 'index']);
    Route::get('faqs/{id}', [FaqController::class, 'show']);
    
    Route::get('about', [AboutController::class, 'index']);
    Route::get('about/{id}', [AboutController::class, 'show']);
    
    Route::get('policies', [PolicyController::class, 'index']);
    Route::get('policies/{id}', [PolicyController::class, 'show']);
    
    Route::get('plans', [PlanController::class, 'index']);
    Route::get('plans/{id}', [PlanController::class, 'show']);
    
    Route::get('reviews', [ReviewController::class, 'index']);
    Route::get('reviews/{id}', [ReviewController::class, 'show']);
    Route::get('reviews/{type}/{elementId}', [ReviewController::class, 'getReviewsByTypeAndElement']);
    
    Route::get('diseases', [DiseaseController::class, 'index']);
    Route::get('diseases/{id}', [DiseaseController::class, 'show']);
    
    Route::get('remedy-types', [RemedyTypeController::class, 'index']);
    Route::get('remedy-types/{id}', [RemedyTypeController::class, 'show']);
    
    Route::get('ads', [AdController::class, 'active']);
    Route::get('home', [App\Http\Controllers\Mobile\HomeController::class, 'index']);
    Route::get('learn', [App\Http\Controllers\Mobile\LearnController::class, 'index']);
    
    // Contact Us - public submission endpoint
    Route::post('contact-us', [ContactUsController::class, 'store']);
    Route::get('contact-us', [ContactUsController::class, 'index']);
    Route::get('contact-us/{id}', [ContactUsController::class, 'show']);
    Route::post('update-token', [DeviceTokensController::class, 'updateToken']);

    Route::get('notifications', [App\Http\Controllers\Mobile\OutNotificationController::class, 'index']);
    Route::get('notifications/{id}', [App\Http\Controllers\Mobile\OutNotificationController::class, 'show']);
    Route::get('notifications/guest', [App\Http\Controllers\Mobile\OutNotificationController::class, 'getNotificationForGuest']);

});
