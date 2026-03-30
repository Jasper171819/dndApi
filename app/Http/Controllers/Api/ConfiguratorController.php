<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ConfiguratorController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(config('dnd'));
    }
}
