<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admin extends Model
{
    /** @use HasFactory<\Database\Factories\AdminFactory> */
    use HasFactory;

    protected $table = 'admins';
    protected $fillable = [
        'employee',
        'super'
    ];
    protected $casts = [
        'super' => 'boolean'
    ];

    public function setSuperAttribute($value)
    {
        $this->attributes['super'] = $value ? Carbon::now() : null;
    }
}
