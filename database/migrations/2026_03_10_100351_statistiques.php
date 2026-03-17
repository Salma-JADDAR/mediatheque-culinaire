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
     
            $table->unique('book_id');
        
            $table->index(['views', 'borrow_count']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('statistiques');
    }
};