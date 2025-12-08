<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// Route::get('/reverse-geocode', function (\Illuminate\Http\Request $request) {
//     $lat = $request->lat;
//     $lon = $request->lon;

//     $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lon}";

//     $response = \Illuminate\Support\Facades\Http::withHeaders([
//         'User-Agent' => 'absensi-app/1.0 (admin@yourdomain.com)'
//     ])->get($url);

//     return $response->json();
// });
