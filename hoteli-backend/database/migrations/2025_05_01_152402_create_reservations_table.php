<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationsTable extends Migration
{
    
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
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reservations');
    }
}
