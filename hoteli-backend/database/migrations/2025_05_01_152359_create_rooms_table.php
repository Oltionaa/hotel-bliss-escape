<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id(); 
            $table->string('room_number')->nullable();
            $table->string('name')->nullable();
            $table->string('image')->nullable();
            $table->integer('capacity')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('is_reserved')->default(false);
            $table->enum('status', ['clean', 'dirty'])->default('clean');
            $table->text("description")->nullable();
            $table->string("size")->nullable();

            // Shto user_id si foreign key
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
