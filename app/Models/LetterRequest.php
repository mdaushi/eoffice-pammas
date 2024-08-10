<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LetterRequest extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * Get the letter_type that owns the LetterRequest
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class);
    }
}
