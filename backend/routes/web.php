<?php

use Illuminate\Support\Facades\Route;

// Frontend je odvojena Vue SPA aplikacija (videti /frontend), servirana preko
// Vite dev servera u razvoju ili preko statičkog hostinga u produkciji.
// Backend u ovom projektu izlaže isključivo REST API definisan u routes/api.php.
Route::get('/', function () {
    return response()->json([
        'message' => 'API servera za rezervacije restorana. Pogledajte /api/* rute.',
    ]);
});
