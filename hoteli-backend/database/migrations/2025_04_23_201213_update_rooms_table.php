<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRoomsTable extends Migration
{
    public function up()
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Fshijmë vetëm kolonën që ekziston: description
            $table->dropColumn('description');
        });
    }

    public function down()
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('description')->nullable();
        });
    }
}
