<?php

namespace App\Http\Controllers\Api;

use App\Events\TransactionCompleted;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * Get transaction history and current balance for authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get all transactions (sent and received) with related users
        $transactions = $user->allTransactions()
            ->with(['sender:id,name,email', 'receiver:id,name,email'])
            ->paginate(50);

        return response()->json([
            'balance' => $user->balance,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Execute a new money transfer
     *
     * @param StoreTransactionRequest $request
     * @return JsonResponse
     */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $user = $request->user();

        $transaction = $this->transactionService->executeTransfer(
            sender: $user,
            receiverId: $request->input('receiver_id'),
            amount: $request->input('amount')
        );

        // Load relationships for response
        $transaction->load(['sender:id,name,email,balance', 'receiver:id,name,email,balance']);

        // Broadcast event to both sender and receiver
        broadcast(new TransactionCompleted($transaction))->toOthers();

        return response()->json([
            'message' => 'Transfer completed successfully',
            'transaction' => $transaction,
            'new_balance' => $user->fresh()->balance,
        ], 201);
    }
}
