<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

trait RespondsWith
{
    protected function redirectSuccess(string $route, string $message): RedirectResponse
    {
        return redirect()->route($route)->with('flash.success', $message);
    }

    protected function redirectError(string $route, string $message): RedirectResponse
    {
        return redirect()->route($route)->with('flash.error', $message);
    }

    protected function backSuccess(string $message): RedirectResponse
    {
        return back()->with('flash.success', $message);
    }

    protected function backError(string $message): RedirectResponse
    {
        return back()->with('flash.error', $message);
    }

    protected function jsonSuccess(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function jsonError(string $message, int $status = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }
}
