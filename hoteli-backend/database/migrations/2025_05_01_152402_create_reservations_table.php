<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationsTable extends Migration
{
<<<<<<< HEAD
    
    public function up()
    {
        Schema::create("reservations", function (Blueprint $table) {
            $table->id();
            $table->string("customer_name");
            $table->date("check_in");
            $table->date("check_out");
            $table->foreignId("room_id")->constrained("rooms")->onDelete("cascade");
            $table->foreignId("user_id")
                ->nullable()
                ->constrained("users")
                ->onDelete("set null");
            $table->enum("status", ["pending", "confirmed", "cancelled"])->default(
                "pending"
            );
=======
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->date('check_in');
            $table->date('check_out');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Lidhja me përdoruesin
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending'); // Statusi i rezervimit
>>>>>>> 7939a173dd73ea95795fb154841479ed00e5f408
            $table->timestamps();
        });
    }

<<<<<<< HEAD
=======
    /**
     * Reverse the migrations.
     *
     * @return void
     */
>>>>>>> 7939a173dd73ea95795fb154841479ed00e5f408
    public function down()
    {
        Schema::dropIfExists('reservations');
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> 7939a173dd73ea95795fb154841479ed00e5f408
