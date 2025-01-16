<?php

namespace App\Models;

use App\Enums\Status;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Letter extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * Get the letter_request that owns the Letter
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function letter_request(): BelongsTo
    {
        return $this->belongsTo(LetterRequest::class);
    }


    public function sign_letter(){

        DB::beginTransaction();
        try {

            $this->letter_request->status = Status::SELESAI;
            $this->letter_request->save();

            $this->sign_at = Carbon::now();
            $this->save();

            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
        }
    }

}
