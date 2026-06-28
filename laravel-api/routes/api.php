<?php

use App\Http\Controllers\Api\StudentApiController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [StudentApiController::class, 'health']);
Route::get('/ping', [StudentApiController::class, 'ping']);
Route::post('/login', [StudentApiController::class, 'login']);
Route::post('/register', [StudentApiController::class, 'register']);
Route::post('/contact', [StudentApiController::class, 'contact']);

Route::get('/home', [StudentApiController::class, 'home']);
Route::get('/dashboard', [StudentApiController::class, 'home']);
Route::get('/company', [StudentApiController::class, 'company']);
Route::get('/classes', [StudentApiController::class, 'classes']);
Route::get('/exams', [StudentApiController::class, 'exams']);
Route::get('/mock-questions', [StudentApiController::class, 'mockQuestions']);
Route::get('/question-meta', [StudentApiController::class, 'questionMeta']);
Route::get('/questions', [StudentApiController::class, 'questions']);
Route::post('/questions/answer', [StudentApiController::class, 'submitQuestionAnswer']);
Route::get('/{resource}', [StudentApiController::class, 'listTable'])
    ->whereIn('resource', ['blogs', 'categories', 'courses', 'events', 'gallery', 'news', 'pdfs', 'services', 'sliders', 'staff', 'subcategories', 'teachers', 'testimonials']);
Route::get('/{resource}/{id}', [StudentApiController::class, 'showTable'])
    ->whereIn('resource', ['blogs', 'categories', 'courses', 'events', 'gallery', 'news', 'pdfs', 'services', 'sliders', 'staff', 'subcategories', 'teachers', 'testimonials'])
    ->whereNumber('id');

Route::get('/me', [StudentApiController::class, 'profile']);
Route::patch('/me', [StudentApiController::class, 'updateProfile']);
Route::post('/me/photo', [StudentApiController::class, 'uploadProfilePhoto']);
Route::post('/change-password', [StudentApiController::class, 'changePassword']);
Route::get('/notices', [StudentApiController::class, 'notices']);
Route::patch('/notices/{id}/seen', [StudentApiController::class, 'markNoticeSeen'])->whereNumber('id');
Route::get('/materials', [StudentApiController::class, 'materials']);
Route::get('/materials/{id}', [StudentApiController::class, 'material'])->whereNumber('id');
Route::patch('/materials/{id}/favorite', [StudentApiController::class, 'toggleFavorite'])->whereNumber('id');
Route::get('/progress', [StudentApiController::class, 'progress']);
Route::get('/fees', [StudentApiController::class, 'fees']);
Route::get('/reminders', [StudentApiController::class, 'reminders']);
Route::get('/admission', [StudentApiController::class, 'admission']);
Route::get('/polls', [StudentApiController::class, 'polls']);
Route::post('/polls/{id}/vote', [StudentApiController::class, 'votePoll'])->whereNumber('id');
Route::post('/mock-questions/answer', [StudentApiController::class, 'submitMockAnswer']);
Route::get('/attendance', [StudentApiController::class, 'attendance']);
Route::get('/homework', [StudentApiController::class, 'homework']);
Route::post('/homework/{id}/submit', [StudentApiController::class, 'submitHomework'])->whereNumber('id');
Route::get('/enquiries', [StudentApiController::class, 'enquiries']);
Route::post('/enquiries', [StudentApiController::class, 'createEnquiry']);
Route::get('/payment-methods', [StudentApiController::class, 'paymentMethods']);
Route::post('/payment-methods', [StudentApiController::class, 'addPaymentMethod']);
