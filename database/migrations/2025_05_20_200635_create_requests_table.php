<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
//            $table->integer('employee_requester')->unsigned(); // Reinstate when API is made available
            $table->string('employee_requester');
            $table->integer('employee_recipient')->unsigned();
            $table->foreignId('event_cost_id')->constrained('event_costs');
            $table->string('iban')->nullable();
            $table->string('account_name')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'paid', 'exported'])->default('pending');
            $table->text('reason')->nullable();
            $table->timestamp('paid_at')->nullable(); // Remove 'paid' status because when this field is filled that means the request has been paid.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
