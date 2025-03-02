<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LetterRequest extends Model
{
    use HasFactory, HasUuids;
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

    public function disposisi_action()
    {
        $this->status = Status::DISPOSISI;
        $this->save();
    }

    public function create_reply()
    {
        $letter_request_id = $this->id;

        $max_number = Letter::max('number');

        // Jika tidak ada nilai maksimum (misalnya data pertama), set $max_number ke 1
        $current_number = $max_number ? $max_number + 1 : 1;

        Letter::create([
            'letter_request_id' => $letter_request_id,
            'number' => $current_number
        ]);
    }
}
