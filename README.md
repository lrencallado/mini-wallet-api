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
git clone git@github.com:lrencallado/mini-wallet-api.git
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
composer run dev 
```
or 
```bash
php artisan serve
```

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

The test suite includes:
- Successful transfer scenarios
- Insufficient balance validation
- Invalid receiver validation
- Self-transfer prevention
- Negative amount validation
- Commission calculation accuracy
- Transaction history retrieval
- Authentication requirements

## License

This project is created as a technical assignment for Pimono Software Design LLC.
