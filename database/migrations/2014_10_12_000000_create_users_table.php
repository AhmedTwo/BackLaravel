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
            $table->string('password');
            $table->string('role');
            $table->string('telephone')->default('Non Renseignée');
            $table->string('ville')->default('Non Renseignée');
            $table->string('code_postal')->default('Non Renseignée');
            $table->string('cv_pdf')->default('Non Renseignée');
            $table->string('qualification')->default('Non Renseignée');
            $table->string('preference')->nullable();
            $table->boolean('disponibilite')->default(false);
            $table->string('photo')->default('/public/assets/images/userDefault.jpeg');
            $table->foreignId('company_id')->nullable()->constrained('companys')->nullOnDelete();
            $table->timestamp('email_verified_at')->nullable();
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