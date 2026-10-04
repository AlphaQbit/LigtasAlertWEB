<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAlertRequest;
use App\Http\Resources\AlertResource;
use App\Models\Alert;
use Illuminate\Http\JsonResponse;

class AlertController extends Controller
{
    /**
     * Receive an alert from the rescuee app. Unauthenticated on purpose - see
     * StoreAlertRequest::authorize().
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
     */
    public function show(Alert $alert): AlertResource
    {
        return AlertResource::make($alert);
    }
}
