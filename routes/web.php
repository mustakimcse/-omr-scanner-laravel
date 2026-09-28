<?php

use App\Http\Controllers\Web\ExamController;
use Illuminate\Support\Facades\Route;


Route::get('/', [ExamController::class, 'index'])->name('home'); 
Route::get('/omr/scan', [ExamController::class, 'scanForm'])->name('omr.scan');
Route::post('/omr/scan', [ExamController::class, 'scanUpload'])->name('omr.scan.upload');
Route::get('/exam/create', [ExamController::class, 'create'])->name('exam.create');
Route::post('/exam', [ExamController::class, 'store'])->name('exam.store');
Route::delete('/exam/{id}', [ExamController::class, 'destroyExam'])->name('exam.delete');
Route::patch('/exam/result/{id}', [ExamController::class, 'correctResult'])->name('exam.result.correct');
Route::delete('/exam/result/{id}', [ExamController::class, 'deleteResult'])->name('exam.result.delete');
Route::get('/exam/result/{id}/view', [ExamController::class, 'singleResult'])->name('exam.result.view');
Route::get('/exam/{id}/scan-results', [ExamController::class, 'scanResults'])->name('exam.scan_results');
Route::get('/exam/{id}/results',[ExamController::class,'showResults'])->name('exam.results');