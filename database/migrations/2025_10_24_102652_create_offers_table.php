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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('mission');
            $table->string('location');
            $table->string('category');
            $table->foreignId('employment_type_id')->references('id')->on('employment_type');
            $table->text('technologies_used');
            $table->text('benefits')->nullable();
            $table->integer('participants_count')->default(0);
            $table->string('image_url')->default('/public/assets/images/offreDefault.jpeg');
            $table->timestamps();
            $table->foreignId('id_company')->references('id')->on('company');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('offers');
    }
};
