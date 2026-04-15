<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = ['name', 'date_of_birth', 'mrn'];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}