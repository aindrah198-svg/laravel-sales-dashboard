<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $t) {
            $t->id();
            $t->string('reference')->unique();
            $t->string('customer');
            $t->string('product');
            $t->string('channel');
            $t->string('status');
            $t->date('ordered_at')->index();
            $t->unsignedBigInteger('amount_cents');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
