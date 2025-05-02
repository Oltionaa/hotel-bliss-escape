<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create("payments", function (Blueprint $table) {
            $table->id();
            $table->foreignId("reservation_id")->constrained("reservations")->onDelete("cascade");
            $table->string("cardholder");
            $table->string("bank_name");
            $table->string("card_number");
            $table->string("card_type");
            $table->string("cvv");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists("payments");
    }
}
