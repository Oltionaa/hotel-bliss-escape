<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

<<<<<<< HEAD
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
=======
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
>>>>>>> 7939a173dd73ea95795fb154841479ed00e5f408
