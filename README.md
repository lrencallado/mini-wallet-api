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

## Technical Design Decisions

### Balance, Amount, and Commission fee: Decimal vs Integer (Centavos)

**Assignment Requirement:** The specification requires using `decimal` type for balance, amount, and commission_fee.

**Implementation:** This application uses `decimal(12,2)` for balance and amount, and `decimal(12,4)` for commission_fee as specified.

**Industry Best Practice Note:** In production financial systems, storing monetary values as integers (cents/centavos) is generally preferred because:
- Eliminates floating-point precision errors
- Enables atomic database operations
- Better performance for high-concurrency scenarios
- Used by Stripe, PayPal, and most payment processors

However, for this assignment, I follow the specified decimal approach while maintaining precision through proper rounding and Laravel's decimal casting.

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

### 5. Session & CORS Configuration

Configure session domain and Sanctum stateful domains for SPA authentication:

```env
SESSION_DOMAIN=localhost
SANCTUM_STATEFUL_DOMAINS=localhost:5173
```

**Note:** If you're running the frontend on a different port or domain, update `SANCTUM_STATEFUL_DOMAINS` accordingly. This setting tells Sanctum which domains can use cookie-based authentication.

### 6. Pusher Configuration

Update your `.env` file with your Pusher credentials:

```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_app_cluster
```

### 7. Seed the Database

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

### 8. Start the Development Server, this will run queue worker as well

```bash
composer run dev
```

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
