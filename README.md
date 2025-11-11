# Mini Wallet API

A high-performance digital wallet application built with Laravel that enables users to transfer money to each other with real-time updates via Pusher.

## Technology Stack

- **Backend**: Laravel 12
- **Database**: PostgreSQL
- **Authentication**: Laravel Sanctum
- **Real-time**: Pusher
- **Testing**: PHPUnit

## Features

- User authentication with Sanctum
- Money transfers between users
- Automatic commission calculation (1.5% per transaction)
- Real-time transaction notifications via Pusher
- Transaction history with pagination
- High-concurrency support with pessimistic locking
- Comprehensive validation and error handling

## Prerequisites

- PHP 8.2 or higher
- Composer
- PostgreSQL
- A Pusher account (free tier available at [pusher.com](https://pusher.com))

## Installation & Setup

### 1. Clone the Repository

```bash
git clone <repository-url>
cd mini-wallet-api
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Configuration

Copy the example environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

### 4. Database Configuration

Update your `.env` file with your PostgreSQL credentials:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=mini_wallet_api
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Run migrations:

```bash
php artisan migrate
```

### 5. Pusher Configuration

Update your `.env` file with your Pusher credentials:

```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1  # or your cluster
```

To get Pusher credentials:
1. Sign up at [pusher.com](https://pusher.com)
2. Create a new Channels app
3. Copy the app credentials to your `.env` file

### 6. Seed the Database

Create test users with balances:

```bash
php artisan db:seed
```

This creates 5 test users:
- alice@example.com (balance: $1,000.00)
- bob@example.com (balance: $500.00)
- charlie@example.com (balance: $750.00)
- diana@example.com (balance: $250.00)
- eve@example.com (balance: $1,500.00)

All test users have the password: `password`

### 7. Start the Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## API Endpoints

### Authentication

First, obtain an API token using Sanctum:

```bash
POST /api/login
Content-Type: application/json

{
  "email": "alice@example.com",
  "password": "password"
}
```

Use the returned token in the `Authorization` header for subsequent requests.

### Get Transaction History

```bash
GET /api/transactions
Authorization: Bearer {your-token}
```

**Response:**
```json
{
  "balance": "1000.00",
  "transactions": {
    "data": [
      {
        "id": 1,
        "transaction_reference": "uuid",
        "sender_id": 1,
        "receiver_id": 2,
        "amount": "100.00",
        "commission_fee": "1.5000",
        "status": "completed",
        "created_at": "2025-11-11T10:30:00.000000Z",
        "sender": {...},
        "receiver": {...}
      }
    ],
    "links": {...},
    "meta": {...}
  }
}
```

### Create Transaction (Transfer Money)

```bash
POST /api/transactions
Authorization: Bearer {your-token}
Content-Type: application/json

{
  "receiver_id": 2,
  "amount": 100.00
}
```

**Response:**
```json
{
  "message": "Transfer completed successfully",
  "transaction": {
    "id": 1,
    "transaction_reference": "uuid",
    "sender_id": 1,
    "receiver_id": 2,
    "amount": "100.00",
    "commission_fee": "1.5000",
    "status": "completed",
    "created_at": "2025-11-11T10:30:00.000000Z"
  },
  "new_balance": "898.50"
}
```

**Note:** A 1.5% commission is automatically calculated and deducted from the sender. For a $100 transfer:
- Sender is debited: $101.50 (amount + commission)
- Receiver is credited: $100.00

## Running Tests

Run the test suite:

```bash
php artisan test
```

Or with coverage:

```bash
php artisan test --coverage
```

The test suite includes:
- Successful transfer scenarios
- Insufficient balance validation
- Invalid receiver validation
- Self-transfer prevention
- Negative amount validation
- Commission calculation accuracy
- Transaction history retrieval
- Authentication requirements

## Technical Design Decisions

### Balance Storage: Decimal vs Integer (Centavos)

**Assignment Requirement:** The specification requires using `decimal` type for balance, amount, and commission_fee.

**Implementation:** This application uses `decimal(12,2)` for balance and amount, and `decimal(12,4)` for commission_fee as specified.

**Industry Best Practice Note:** In production financial systems, storing monetary values as integers (cents/centavos) is generally preferred because:
- Eliminates floating-point precision errors
- Enables atomic database operations
- Better performance for high-concurrency scenarios
- Used by Stripe, PayPal, and most payment processors

However, for this assignment, we follow the specified decimal approach while maintaining precision through proper rounding and Laravel's decimal casting.

### Concurrency & Race Condition Prevention

**Challenge:** Handle hundreds of transfers per second without balance inconsistencies.

**Solution:** Pessimistic locking with deadlock prevention
```php
// Lock users in consistent order (by ID) to prevent deadlocks
$userIds = [$senderId, $receiverId];
sort($userIds);
$users = User::whereIn('id', $userIds)->lockForUpdate()->get();
```

This ensures:
- Only one transaction can modify user balances at a time
- Consistent lock ordering prevents deadlocks
- Database-level guarantees for data integrity

### Transaction Atomicity

All transfer operations are wrapped in database transactions:
```php
DB::transaction(function () {
    // 1. Lock users
    // 2. Verify balance
    // 3. Debit sender
    // 4. Credit receiver
    // 5. Create transaction record
});
```

If any step fails, all changes are rolled back automatically.

### Scalability for Millions of Transactions

**Approach:** Direct balance storage on users table
- User balance is stored directly, not calculated from transactions
- Transactions table has indexes on sender_id, receiver_id, created_at
- Composite indexes for efficient filtering: (sender_id, created_at), (receiver_id, created_at)
- Status index for filtering by transaction status

**Performance:** O(1) balance lookups, efficient paginated history queries

### Real-time Updates

Uses Pusher with private channels:
- Each user has a private channel: `users.{userId}`
- Only authenticated users can subscribe to their own channel
- Both sender and receiver receive instant updates after transfer completion

## Project Structure

```
app/
├── Events/
│   └── TransactionCompleted.php    # Pusher broadcast event
├── Http/
│   ├── Controllers/Api/
│   │   └── TransactionController.php
│   └── Requests/
│       └── StoreTransactionRequest.php  # Validation rules
├── Models/
│   ├── Transaction.php
│   └── User.php
└── Services/
    └── TransactionService.php      # Business logic

database/
├── migrations/
│   ├── create_users_table.php
│   └── create_transactions_table.php
└── seeders/
    └── DatabaseSeeder.php

tests/
└── Feature/
    └── TransactionTest.php

routes/
├── api.php                         # API endpoints
└── channels.php                    # Pusher channel authorization
```

## Security Considerations

- All API endpoints protected with Sanctum authentication
- Request validation prevents invalid data
- User can only access their own transaction history
- Users cannot send money to themselves
- Commission and balance checks prevent overdrafts
- Database transactions ensure data consistency
- Private Pusher channels ensure users only receive their own updates

## Database Schema

### Users Table
- `id`: Primary key
- `name`: User full name
- `email`: Unique email address
- `password`: Hashed password
- `balance`: decimal(12,2) - Current balance
- `timestamps`

### Transactions Table
- `id`: Primary key
- `transaction_reference`: UUID for tracking
- `sender_id`: Foreign key to users (restrict on delete)
- `receiver_id`: Foreign key to users (restrict on delete)
- `amount`: decimal(12,2) - Transfer amount
- `commission_fee`: decimal(12,4) - Calculated commission
- `status`: enum('pending', 'completed', 'failed')
- `timestamps`
- Indexes: sender_id, receiver_id, created_at, status, composites

## License

This project is created as a technical assignment for Pimono Software Design LLC.
