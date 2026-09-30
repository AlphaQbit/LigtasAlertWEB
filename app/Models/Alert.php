<?php

namespace App\Models;

use Database\Factories\AlertFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['facility', 'type', 'location', 'details', 'status'])]
class Alert extends Model
{
    /** @use HasFactory<AlertFactory> */
    use HasFactory;

    const STATUS_ACTIVE = 'active';

    const STATUS_ACKNOWLEDGED = 'acknowledged';

    const STATUS_RESOLVED = 'resolved';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Alert>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_ACKNOWLEDGED]);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_ACKNOWLEDGED], true);
    }

    public function responseSeconds(): ?int
    {
        if (! $this->acknowledged_at) {
            return null;
        }

        return $this->created_at->diffInSeconds($this->acknowledged_at);
    }
}
