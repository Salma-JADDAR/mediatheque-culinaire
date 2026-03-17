// database/migrations/2024_01_01_000003_create_statistiques_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('statistiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('borrow_count')->default(0);
            $table->integer('views')->default(0);
            $table->timestamps();
            
            // Un livre ne peut avoir qu'une seule statistique
            $table->unique('book_id');
            
            // Index pour les recherches de popularité
            $table->index(['views', 'borrow_count']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('statistiques');
    }
};