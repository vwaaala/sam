<?php

use Illuminate\Support\Facades\Route;
use App\Services\WassengerService;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/webhook/wassenger', function (Request $request, WassengerService $wassengerService) {
    return $wassengerService->handleIncomingMessage($request->all());
});
