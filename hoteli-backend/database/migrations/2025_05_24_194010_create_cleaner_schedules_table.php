<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cleaner_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cleaner_id')->constrained('users')->onDelete('cascade'); // Lidhja me tabelën users
            $table->date('work_date');
            $table->time('shift_start');
            $table->time('shift_end');
            $table->string('status')->default('Planned'); // P.sh., Planned, Completed, Canceled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cleaner_schedules');
    }
};