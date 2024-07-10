<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    use HasFactory;

    protected $fillable = [
        'common_name',
        'watering_general_benchmark',
        'watering',
        'watering_period',
        'depth_water_requirement',
        'volume_water_requirement'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('trust', 'to_water_at', 'checked_at');
    }
}
