<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAlertStatusRequest;
use App\Http\Resources\AlertResource;
use App\Models\Alert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * The dashboard polls this list while it is open.
     */
    public function index(Request $request): JsonResponse
    {
        $alerts = Alert::query()
            ->when($request->filled('facility'),
                fn ($query) => $query->where('facility', $request->string('facility')))
            ->latest()
            ->limit(100)
            ->get();

        return response()->json([
            'data' => $alerts,
            'stats' => $this->stats(),
        ]);
    }

    public function updateStatus(UpdateAlertStatusRequest $request, Alert $alert): JsonResponse
    {
        $status = $request->string('status');

        if (! $alert->transitionTo($status)) {
            return response()->json([
                'message' => "Cannot move alert from {$alert->status} to {$status}.",
            ], 409);
        }

        $alert->save();

        return AlertResource::make($alert->fresh())->response();
    }

    /**
     * @return array<string, int|null>
     */
    private function stats(): array
    {
        return [
            'active' => Alert::query()->where('status', Alert::STATUS_ACTIVE)->count(),
            'responding' => Alert::query()->where('status', Alert::STATUS_RESPONDING)->count(),
            'acknowledged' => Alert::query()->where('status', Alert::STATUS_ACKNOWLEDGED)->count(),
            'resolved_today' => Alert::query()
                ->where('status', Alert::STATUS_RESOLVED)
                ->whereDate('resolved_at', today())
                ->count(),
            'open' => Alert::open()->count(),
            'avg_response_seconds' => $this->averageResponseSeconds(),
        ];
    }

    /**
     * Mean seconds from creation to acknowledgement, across alerts that have
     * been acknowledged. Null when there is nothing to average yet.
     */
    private function averageResponseSeconds(): ?int
    {
        $times = Alert::query()
            ->whereNotNull('acknowledged_at')
            ->get(['created_at', 'acknowledged_at'])
            ->map(fn (Alert $alert) => $alert->responseSeconds())
            ->filter(fn (?int $seconds) => $seconds !== null);

        return $times->isEmpty() ? null : (int) round($times->avg());
    }
}
