use App\Http\Controllers\EdukasiController;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
// ... existing routes ...

Route::resource('edukasi', EdukasiController::class);
});