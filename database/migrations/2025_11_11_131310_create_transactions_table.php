<?php

use App\Models\User;
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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_reference')->unique();
            $table->foreignIdFor(User::class, 'sender_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignIdFor(User::class, 'receiver_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('commission_fee', 12, 4)->default(0);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamps();

            // Indexes for performance with millions of rows
            $table->index('sender_id');
            $table->index('receiver_id');
            $table->index('created_at');
            $table->index(['sender_id', 'created_at']);
            $table->index(['receiver_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
