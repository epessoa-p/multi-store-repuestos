<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->foreignId('registered_by')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->decimal('capital', 15, 2)->nullable();
            $table->decimal('interest', 15, 2)->nullable();
            $table->date('payment_date');
            $table->string('payment_method')->default('efectivo');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};
