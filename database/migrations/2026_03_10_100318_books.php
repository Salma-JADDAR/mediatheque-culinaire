<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->text('description');
            $table->string('slug')->unique();
            $table->boolean('isDamaged')->default(false);
            $table->enum('condition', ['neuf', 'bon', 'moyen', 'degrade'])->default('neuf');
            $table->integer('views')->default(0);
            $table->integer('total_copies')->default(1);
            $table->integer('available_copies')->default(1);
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->timestamps(); 
            $table->index(['title', 'author']);
            $table->index('views');
        });
    }

    public function down()
    {
        Schema::dropIfExists('books');
    }
};