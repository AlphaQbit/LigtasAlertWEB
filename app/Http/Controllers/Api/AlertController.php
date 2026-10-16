<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAlertRequest;
use App\Http\Resources\AlertResource;
use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * Receive an alert from the rescuee app. Authenticated so the alert is tied
     * to the rescuee's account (see StoreAlertRequest::authorize()).
     */
    public function store(StoreAlertRequest $request): JsonResponse
    {
        $alert = Alert::create($request->validated());

        return AlertResource::make($alert->fresh())
            ->response()
            ->setStatusCode(201);
    }

    /**
     * The rescuee app polls this to learn its alert's current status.
     * Rescuees can only read their own alerts.
     */
    public function show(Request $request, Alert $alert): AlertResource
    {
        $requestUser = $request->user();
        $cookies = $request->cookies->all();
        $sessionId = null;
        try {
            $sessionId = $request->session()->getId();
        } catch (\RuntimeException) {
            // No session on API routes
        }

        // Diagnostics only. Never log the Authorization header - even a
        // truncated bearer token is credential material that belongs out of
        // the application log. has_auth_header is a boolean, not a value.
        \Log::info('Alert show check', [
            'alert_user_id' => $alert->user_id,
            'request_user_id' => $requestUser?->id,
            'alert_id' => $alert->id,
            'has_auth_header' => $request->header('Authorization') !== null,
            'session_id' => $sessionId,
            'cookies' => array_keys($cookies),
        ]);

        if ($alert->user_id !== $requestUser->id) {
            abort(403, 'This alert belongs to another rescuee.');
        }

        return AlertResource::make($alert);
    }
}
