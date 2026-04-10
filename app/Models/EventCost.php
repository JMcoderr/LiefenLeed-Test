<?php

namespace App\Models;

use App\Enums\EventCostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class EventCost extends Model
{
    use HasFactory;

    protected $table = 'event_costs';
    protected $fillable = [
        'event_id',
        'amount',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    protected $appends = [
        'status'
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Determines the event cost status through the start_date and end_date
     *
     * A cost is considered passed if the end_date is in the past
     * A cost is considered upcoming if the start_date is the future
     * And a cost is considered active if the start_date is before now and the end_date is in the future or null
     *
     * @return Attribute
     * @author Brighton van Rouendal
     */
    public function status(): Attribute
    {
        return Attribute::get(function () {
            $now = Carbon::now();

            if ($this->start_date <= $now && ($this->end_date == null || $this->end_date > $now)) return EventCostStatus::Active;
            elseif ($this->start_date > $now) return EventCostStatus::Upcoming;
            elseif ($this->end_date <= $now) return EventCostStatus::Passed;
            else return EventCostStatus::Unknown;
        });
    }

    /**
     * @param Builder $query
     * @param EventCostStatus $status
     * @return Builder
     * @author Brighton van Rouendal
     */
    public function scopeWithStatus(Builder $query, EventCostStatus $status): Builder
    {
        $now = Carbon::now();

        return match ($status) {
            EventCostStatus::Active => $query
                ->where('start_date', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_date')->orWhere('end_date', '>', $now);
                }),

            EventCostStatus::Upcoming => $query
                ->where('start_date', '>', $now),

            EventCostStatus::Passed => $query
                ->whereNotNull('end_date')
                ->where('end_date', '<=', $now),

            default => $query->whereRaw('1=0'), // no match for Unknown status
        };
    }

    public function requests() : HasMany
    {
        return $this->hasMany(Request::class);
    }
}
