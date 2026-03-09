# SAF-T PT Invoice Verifier

A Laravel application for verifying Portuguese SAF-T (Standard Audit File for Tax) invoices.

## Requirements

- PHP 8.2+
- Composer
- Node.js & npm

## Installation

### Quick setup (recommended)

```bash
composer run setup
```

This single command will:
1. Install PHP dependencies
2. Create the `.env` file from `.env.example`
3. Generate an application key
4. Create the SQLite database file
5. Run database migrations
6. Install Node.js dependencies and build assets

### Manual setup

If you prefer to set up manually:

```bash
# Install PHP dependencies
composer install

# Copy the environment file
cp .env.example .env

# Generate an application key
php artisan key:generate

# Create the SQLite database file
touch database/database.sqlite

# Run database migrations
php artisan migrate

# Install Node.js dependencies and build assets
npm install
npm run build
```

## Running the application

```bash
composer run dev
```

Or start the development server independently:

```bash
php artisan serve
```

## Troubleshooting

### `Database file at path [.../database/database.sqlite] does not exist`

This error means the SQLite database file has not been created yet. Run one of the following commands to create it:

```bash
# Option 1: Re-run the full setup
composer run setup

# Option 2: Create only the database file and run migrations
touch database/database.sqlite
php artisan migrate
```

On Windows (Command Prompt or PowerShell):

```cmd
type nul > database\database.sqlite
php artisan migrate
```

## Testing

```bash
composer run test
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

