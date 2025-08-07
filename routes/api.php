use App\Http\Controllers\Settings\MessageController;
use App\Http\Controllers\DepartmentController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/settings/message', [MessageController::class, 'index']);
    Route::get('/settings/message/{conversation}', [MessageController::class, 'show']);
    Route::post('/settings/message', [MessageController::class, 'store']);
    Route::post('/settings/message/{conversation}/send', [MessageController::class, 'sendMessage']);
    Route::put('/settings/message/{message}', [MessageController::class, 'update']);
    Route::delete('/settings/message/{message}', [MessageController::class, 'destroy']);
    Route::delete('/settings/message/{conversation}', [MessageController::class, 'deleteConversation']);
    Route::get('/settings/message/available-contacts', [MessageController::class, 'availableContacts']);

    // Departments API
    Route::get('/departments', [DepartmentController::class, 'apiIndex']);
});
