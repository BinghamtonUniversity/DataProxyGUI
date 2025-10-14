<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SchedulersController extends BaseDjangoController{
    
    // ===========================================
    // Schedulers
    // ===========================================
    public function schedulersIndex(): JsonResponse
    {
        $result = $this->makeDjangoRequest('GET', 'schedulers');

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to fetch schedulers}",
            'status' => $result['status']
        ], $result['status']);
    }

    public function schedulersStore(Request $request): JsonResponse
    {
        $result = $this->makeDjangoRequest('POST', 'schedulers', $request->all());

        if ($result['success']) {
            return response()->json($result['data'], 201);
        }

        return response()->json([
            'error' => "Failed to create schedulers",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function schedulersUpdate(Request $request, $id): JsonResponse
    {
        $result = $this->makeDjangoRequest('PUT', "schedulers/{$id}", $request->all());

        if ($result['success']) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => "Failed to update schedulers",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }

    public function schedulersDestroy($id): JsonResponse
    {
        $result = $this->makeDjangoRequest('DELETE', "schedulers/{$id}");

        if ($result['success']) {
            return response()->json([
                'message' => ucfirst('schedulers') . ' deleted successfully'
            ]);
        }

        return response()->json([
            'error' => "Failed to delete schedulers",
            'details' => $result['data'],
            'status' => $result['status']
        ], $result['status']);
    }
}