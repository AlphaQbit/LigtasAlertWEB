<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Read-only incident reporting for the rescuer dashboard.
 *
 * Every report is derived from the live alerts table - no denormalized
 * copies, no sample data. All routes sit behind the session auth middleware,
 * so a responder only ever sees incidents they can review.
 */
class ReportController extends Controller
{
    /**
     * Summary + breakdowns for the reports tab. The facility and date
     * filters narrow the summary, type, and response-time breakdowns; the
     * facility breakdown itself stays unfiltered so the UI can populate
     * its filter dropdown.
     *
     * @return array<string, mixed>
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'facility' => ['nullable', 'string', 'max:255'],
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;
        $facility = $validated['facility'] ?? null;

        return response()->json([
            'summary' => $this->summary($facility, $from, $to),
            'by_status' => $this->groupBy('status', $facility, $from, $to),
            'by_type' => $this->groupBy('type', $facility, $from, $to),
            'by_facility' => $this->groupBy('facility', null, $from, $to),
            'response_times' => $this->responseTimes($facility, $from, $to),
            'recent' => $this->recent($facility, $from, $to),
        ]);
    }

    /**
     * CSV export of incidents within the requested window.
     */
    public function csv(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'facility' => ['nullable', 'string', 'max:255'],
        ]);

        $alerts = $this->query($validated['facility'] ?? null, $validated['from'] ?? null, $validated['to'] ?? null)
            ->latest()
            ->get();

        // ponytail: buffer into memory (php://temp) instead of streaming so the
        // response is unit-testable. Alert volumes are small (hundreds/day);
        // switch to response()->stream() if the table grows to tens of thousands.
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, [
            'ID', 'Facility', 'Type', 'Location', 'Status',
            'Acknowledged At', 'Resolved At', 'Response Seconds',
            'Created At', 'Details',
        ]);
        foreach ($alerts as $alert) {
            fputcsv($csv, [
                $alert->id,
                $alert->facility,
                $alert->type,
                $alert->location,
                $alert->status,
                $alert->acknowledged_at?->toDateTimeString(),
                $alert->resolved_at?->toDateTimeString(),
                $alert->responseSeconds(),
                $alert->created_at?->toDateTimeString(),
                $alert->details,
            ]);
        }
        rewind($csv);

        return response(stream_get_contents($csv), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ligtas-alerts-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function summary(?string $facility, ?string $from, ?string $to): array
    {
        $base = fn () => $this->query($facility, $from, $to);

        return [
            'total' => $base()->count(),
            'active' => $base()->where('status', Alert::STATUS_ACTIVE)->count(),
            'responding' => $base()->where('status', Alert::STATUS_RESPONDING)->count(),
            'acknowledged' => $base()->where('status', Alert::STATUS_ACKNOWLEDGED)->count(),
            'resolved' => $base()->where('status', Alert::STATUS_RESOLVED)->count(),
            'open' => $base()->open()->count(),
        ];
    }

    /**
     * @return array<int, array{key: string, count: int}>
     */
    private function groupBy(string $column, ?string $facility, ?string $from, ?string $to): array
    {
        return $this->query($facility, $from, $to)
            ->select($column, DB::raw('COUNT(*) as count'))
            ->groupBy($column)
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => ['key' => (string) $row->{$column}, 'count' => (int) $row->count])
            ->values()
            ->all();
    }

    /**
     * @return array{count: int, min: ?int, max: ?int, avg: ?int}
     */
    private function responseTimes(?string $facility, ?string $from, ?string $to): array
    {
        $seconds = $this->query($facility, $from, $to)
            ->whereNotNull('acknowledged_at')
            ->get(['created_at', 'acknowledged_at'])
            ->map(fn (Alert $alert) => $alert->responseSeconds())
            ->filter(fn (?int $s) => $s !== null);

        return [
            'count' => $seconds->count(),
            'min' => $seconds->isEmpty() ? null : $seconds->min(),
            'max' => $seconds->isEmpty() ? null : $seconds->max(),
            'avg' => $seconds->isEmpty() ? null : (int) round($seconds->avg()),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recent(?string $facility, ?string $from, ?string $to, int $limit = 25): array
    {
        return $this->query($facility, $from, $to)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Alert $alert) => [
                'id' => $alert->id,
                'facility' => $alert->facility,
                'type' => $alert->type,
                'location' => $alert->location,
                'status' => $alert->status,
                'acknowledged_at' => $alert->acknowledged_at?->toIso8601String(),
                'resolved_at' => $alert->resolved_at?->toIso8601String(),
                'response_seconds' => $alert->responseSeconds(),
                'created_at' => $alert->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * Base query: all alerts, optionally narrowed by facility and date range.
     */
    private function query(?string $facility, ?string $from, ?string $to): Builder
    {
        return Alert::query()
            ->when($facility, fn ($query) => $query->where('facility', $facility))
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to));
    }
}
