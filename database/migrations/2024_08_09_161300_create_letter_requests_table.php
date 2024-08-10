<?php

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('letter_requests', function (Blueprint $table) {
            $table->id();
            $table->string('unique_field')->unique()->default(Str::random(10));
            $table->string("birthplace_id")->nullable();
            $table->string("id_number")->nullable();
            $table->date("birth_date")->nullable();
            $table->string("gender")->nullable();
            $table->string("religion")->nullable();
            $table->string("work")->nullable();
            $table->text("address")->nullable();
            $table->string("created_by");
            $table->string("name")->nullable();
            $table->string("status");
            $table->integer("letter_type_id");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_requests');
    }
};
