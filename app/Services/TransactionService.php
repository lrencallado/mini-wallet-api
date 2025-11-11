<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    /**
     * Commission rate for transactions (1.5%)
     */
    const COMMISSION_RATE = 0.015;

    /**
     * Execute a money transfer between two users
     *
     * This method handles the complete transaction flow with proper locking
     * to prevent race conditions in high-concurrency scenarios.
     *
     * @param User $sender The user sending the money
     * @param int $receiverId The ID of the user receiving the money
     * @param float $amount The amount to transfer
     * @return Transaction The created transaction record
     * @throws \Exception If the transaction fails
     */
    public function executeTransfer(User $sender, int $receiverId, float $amount): Transaction
    {
        return DB::transaction(function () use ($sender, $receiverId, $amount) {
            // Lock both users for update to prevent race conditions
            // Lock in consistent order (by ID) to prevent deadlocks
            $userIds = [$sender->id, $receiverId];
            sort($userIds);

            $users = User::whereIn('id', $userIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Re-fetch sender and receiver with locks
            $lockedSender = $users[$sender->id];
            $lockedReceiver = $users[$receiverId];

            // Calculate commission
            $commission = round($amount * self::COMMISSION_RATE, 4);
            $totalDebit = $amount + $commission;

            // Verify sufficient balance (double-check after lock)
            if ($lockedSender->balance < $totalDebit) {
                throw new \Exception(
                    'Insufficient balance. Required: ' . number_format($totalDebit, 2) .
                    ' (including commission: ' . number_format($commission, 4) . '), Available: ' .
                    number_format($lockedSender->balance, 2)
                );
            }

            // Debit sender (amount + commission)
            $lockedSender->balance -= $totalDebit;
            $lockedSender->save();

            // Credit receiver (only the amount, not commission)
            $lockedReceiver->balance += $amount;
            $lockedReceiver->save();

            // Create transaction record
            $transaction = Transaction::create([
                'transaction_reference' => Str::uuid(),
                'sender_id' => $sender->id,
                'receiver_id' => $receiverId,
                'amount' => $amount,
                'commission_fee' => $commission,
                'status' => 'completed',
            ]);

            return $transaction;
        });
    }

    /**
     * Calculate the commission fee for a given amount
     *
     * @param float $amount
     * @return float
     */
    public function calculateCommission(float $amount): float
    {
        return round($amount * self::COMMISSION_RATE, 4);
    }

    /**
     * Calculate the total debit (amount + commission)
     *
     * @param float $amount
     * @return float
     */
    public function calculateTotalDebit(float $amount): float
    {
        return $amount + $this->calculateCommission($amount);
    }
}
