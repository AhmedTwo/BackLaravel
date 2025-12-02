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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            // $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            // $table->rememberToken();
            $table->string('role');
            $table->string('telephone');
            $table->string('ville');
            $table->string('code_postal');
            $table->string('cv_pdf');
            $table->string('qualification');
            $table->string('preference');
            $table->boolean('disponibilite')->default(false); // indispo
            $table->string('photo')->default('/public/assets/images/userDefault.jpeg');
            $table->timestamps();
            $table->foreignId('company_id')->nullable()->references('id')->on('company'); // clé etragere
            // $table->foreignId('vehicles_idnullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
