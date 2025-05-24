<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReceptionistSchedulesTable extends Migration
{
    public function up()
    {
        Schema::create('receptionist_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receptionist_id')->constrained('users')->onDelete('cascade');
            $table->date('work_date');
            $table->time('shift_start');
            $table->time('shift_end');
            $table->string('status')->default('scheduled');
            $table->timestamp('check_in')->nullable();
            $table->timestamp('check_out')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('receptionist_schedules');
    }
}