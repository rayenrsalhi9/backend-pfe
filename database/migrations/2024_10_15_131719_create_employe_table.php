<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('employe')) {
            Schema::create('employe', function (Blueprint $table) {
                $table->id(); // Auto-incrémenté
                $table->string('matricule', 50); // Limite à 50 caractères
                $table->string('direction', 100)->nullable();
                $table->string('firstName', 100)->nullable();
                $table->string('lastName', 100)->nullable();
                $table->string('email', 100)->nullable();
                $table->boolean('isDeleted')->default(0); // Ajouté si nécessaire
                // Si tu as besoin de soft deletes ou timestamps, ajoute-les ici
                $table->timestamps(); // Ajoute created_at et updated_at
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('employe');
    }
}
