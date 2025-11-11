<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Fake events to prevent broadcasting during tests
        Event::fake();
    }

    #[Test]
    public function user_can_transfer_money_successfully(): void
    {
        // Arrange
        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create(['balance' => 500.00]);

        // Act
        $response = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/transactions', [
                'receiver_id' => $receiver->id,
                'amount' => 100.00,
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'transaction' => [
                    'id',
                    'transaction_reference',
                    'sender_id',
                    'receiver_id',
                    'amount',
                    'commission_fee',
                    'status',
                ],
                'new_balance',
            ]);

        // Verify balances
        $sender->refresh();
        $receiver->refresh();

        $expectedCommission = 100.00 * 0.015; // 1.50
        $expectedSenderBalance = 1000.00 - 100.00 - $expectedCommission; // 898.50
        $expectedReceiverBalance = 500.00 + 100.00; // 600.00

        $this->assertEquals($expectedSenderBalance, (float) $sender->balance);
        $this->assertEquals($expectedReceiverBalance, (float) $receiver->balance);

        // Verify transaction record
        $this->assertDatabaseHas('transactions', [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'amount' => 100.00,
            'status' => 'completed',
        ]);
    }

    #[Test]
    public function transfer_fails_with_insufficient_balance(): void
    {
        // Arrange
        $sender = User::factory()->create(['balance' => 50.00]);
        $receiver = User::factory()->create(['balance' => 100.00]);

        // Act
        $response = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/transactions', [
                'receiver_id' => $receiver->id,
                'amount' => 100.00, // Needs 101.50 with commission
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);

        // Verify balances unchanged
        $this->assertEquals(50.00, (float) $sender->fresh()->balance);
        $this->assertEquals(100.00, (float) $receiver->fresh()->balance);

        // Verify no transaction created
        $this->assertDatabaseCount('transactions', 0);
    }

    #[Test]
    public function transfer_fails_with_invalid_receiver(): void
    {
        // Arrange
        $sender = User::factory()->create(['balance' => 1000.00]);

        // Act
        $response = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/transactions', [
                'receiver_id' => 99999, // Non-existent user
                'amount' => 100.00,
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['receiver_id']);

        // Verify balance unchanged
        $this->assertEquals(1000.00, (float) $sender->fresh()->balance);
    }

    #[Test]
    public function user_cannot_send_money_to_themselves(): void
    {
        // Arrange
        $sender = User::factory()->create(['balance' => 1000.00]);

        // Act
        $response = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/transactions', [
                'receiver_id' => $sender->id,
                'amount' => 100.00,
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['receiver_id']);
    }

    #[Test]
    public function transfer_fails_with_negative_amount(): void
    {
        // Arrange
        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create(['balance' => 500.00]);

        // Act
        $response = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/transactions', [
                'receiver_id' => $receiver->id,
                'amount' => -50.00,
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function commission_is_calculated_correctly(): void
    {
        // Arrange
        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create(['balance' => 0.00]);

        // Act
        $response = $this->actingAs($sender, 'sanctum')
            ->postJson('/api/transactions', [
                'receiver_id' => $receiver->id,
                'amount' => 100.00,
            ]);

        // Assert
        $response->assertStatus(201);

        $transaction = Transaction::first();
        $expectedCommission = 1.5000; // 1.5% of 100

        $this->assertEquals($expectedCommission, (float) $transaction->commission_fee);
    }

    #[Test]
    public function user_can_view_transaction_history(): void
    {
        // Arrange
        $user = User::factory()->create(['balance' => 1000.00]);
        $otherUser = User::factory()->create(['balance' => 500.00]);

        // Create some transactions
        Transaction::create([
            'transaction_reference' => Str::uuid(),
            'sender_id' => $user->id,
            'receiver_id' => $otherUser->id,
            'amount' => 50.00,
            'commission_fee' => 0.75,
            'status' => 'completed',
        ]);

        Transaction::create([
            'transaction_reference' => Str::uuid(),
            'sender_id' => $otherUser->id,
            'receiver_id' => $user->id,
            'amount' => 25.00,
            'commission_fee' => 0.375,
            'status' => 'completed',
        ]);

        // Act
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/transactions');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'balance',
                'transactions' => [
                    'data' => [
                        '*' => [
                            'id',
                            'sender_id',
                            'receiver_id',
                            'amount',
                            'commission_fee',
                            'status',
                            'created_at',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('balance', '1000.00');

        // Verify both transactions are returned
        $this->assertCount(2, $response->json('transactions.data'));
    }

    #[Test]
    public function unauthenticated_user_cannot_access_transactions(): void
    {
        // Create a user to use as receiver
        $receiver = User::factory()->create(['balance' => 500.00]);

        // Act & Assert
        $this->getJson('/api/transactions')
            ->assertStatus(401);

        $this->postJson('/api/transactions', [
            'receiver_id' => $receiver->id,
            'amount' => 100,
        ])->assertStatus(401);
    }
}
