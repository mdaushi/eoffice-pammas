<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class LetterType extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasUuids;

    protected $table = "letter_type";
    protected $guarded = [];
}
