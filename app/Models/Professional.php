<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Professional extends Model
{
    protected $fillable = ['name', 'specialty'];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
