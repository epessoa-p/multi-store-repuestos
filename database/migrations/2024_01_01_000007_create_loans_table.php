<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('loan_type_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');

            // Datos del cliente
            $table->string('client_name');
            $table->string('client_id_number', 30)->nullable(); // DUI, NIT, etc.
            $table->string('client_phone', 20)->nullable();
            $table->string('client_email')->nullable();
            $table->string('client_address')->nullable();

            // Datos del préstamo
            $table->decimal('amount', 15, 2);
            $table->decimal('interest_rate', 5, 2); // % mensual
            $table->integer('term_months');
            $table->decimal('monthly_payment', 15, 2)->nullable();
            $table->decimal('total_to_pay', 15, 2)->nullable();
            $table->decimal('total_paid', 15, 2)->default(0);

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('purpose')->nullable(); // propósito del préstamo
            $table->text('notes')->nullable();

            $table->enum('status', ['pending', 'approved', 'active', 'finished', 'cancelled', 'overdue'])
                  ->default('pending');

            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
