<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->enum('type', ['RECLAMATION', 'DEMANDES', 'SUPPRESION', 'MODIFICATION']);
            $table->string('status')->default('en_cours');
            $table->timestamps();
            $table->foreignId('user_id')->references('id')->on('user'); // clé etragere
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requests');
    }
};
