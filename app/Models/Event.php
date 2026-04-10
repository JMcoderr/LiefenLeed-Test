<?php

namespace App\Models;

use App\Enums\EventCostStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';
    protected $fillable = [
        'title',
    ];

    protected $appends = [
        'current_cost'
    ];

    public function eventCosts(): HasMany
    {
        $orderRaw = DB::getDriverName() === 'sqlite'
            ? '(end_date IS NULL) DESC, end_date DESC'
            : 'ISNULL(end_date) DESC, end_date DESC';
        return $this->hasMany(EventCost::class)->orderByRaw($orderRaw);
    }

    public function activeEventCosts(): HasMany
    {
        return $this->hasMany(EventCost::class)->withStatus(EventCostStatus::Active);
    }

    /**
     * Get the current event cost.
     *
     * returns the earliest applicable cost where:
     * - start_date is in the past or now (<= current time)
     * - Ordered by the default order of the eventCosts relationship
     *
     * @return Attribute
     * @author Brighton van Rouendal
     */
    public function currentCost(): Attribute
    {
        return new Attribute(
            get: fn () => $this->eventCosts()->where('start_date', '<=', Carbon::now())->first()
        );
    }

    public function requests() : HasManyThrough
    {
        return $this->hasManyThrough(Request::class, EventCost::class);
    }
}
