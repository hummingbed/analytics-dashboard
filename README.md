# Transact Dashboard

A near-real-time user transaction dashboard built with Laravel 12, Vue 3, Apache Kafka, and SQLite. The API publishes transactions to Kafka, a Laravel consumer persists them, and Vue refreshes the dashboard every five seconds.

## Data flow

```text
POST /api/transactions
        |
        v
Laravel Kafka producer
        |
        v
user-transactions topic
        |
        v
Laravel consumer --> SQLite --> GET /api/dashboard --> Vue
```

The transaction endpoint returns `202 Accepted` after the message is queued. The consumer must be running before the transaction can appear on the dashboard.

## Requirements

For the recommended Docker setup:

- Docker Engine 24+
- Docker Compose v2
- 3 GB available memory
- Ports `8000` and `29092` available

For a native setup:

- PHP 8.2+, Composer 2, and PHP SQLite
- Node.js 20+ and npm
- Apache Kafka and Java 17+
- `librdkafka` and the PHP `rdkafka` extension

Check the required PHP extensions:

```bash
php -m | grep -E 'pdo_sqlite|rdkafka'
```

## Run with Docker

```bash
cp .env.example .env
touch database/database.sqlite
docker compose up --build -d
```

If `APP_KEY` is empty, generate one locally with `php artisan key:generate` before starting the containers. Open [http://localhost:8000](http://localhost:8000).

Useful commands:

```bash
docker compose ps
docker compose logs -f consumer
docker compose down
```

Create a clean database with sample transactions:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

This command deletes all existing SQLite records.

## Run without Docker

Install PHP and frontend dependencies:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm run build
```

For Kafka running on your machine, use:

```dotenv
KAFKA_BROKERS=localhost:9092
KAFKA_TOPIC=user-transactions
KAFKA_CONSUMER_GROUP=transaction-dashboard
```

Create the topic:

```bash
bin/kafka-topics.sh \
  --bootstrap-server localhost:9092 \
  --create \
  --if-not-exists \
  --topic user-transactions \
  --partitions 3 \
  --replication-factor 1
```

Run these processes in separate terminals:

```bash
php artisan serve
php artisan kafka:consume-transactions
npm run dev
```

## Transaction API

```http
POST /api/transactions
Content-Type: application/json
Accept: application/json
```

Request body:

```json
{
  "transaction_id": "fc47e688-53ad-4db5-a0e2-23bb973fd640",
  "user_name": "Michael",
  "amount": 2500,
  "type": "credit",
  "status": "successful",
  "description": "Wallet deposit",
  "transacted_at": "2026-08-17T15:30:00+01:00"
}
```

Allowed values:

- `type`: `credit` or `debit`
- `status`: `pending`, `successful`, or `failed`

`description` is optional. All other fields are required, and each new transaction needs a unique UUID.

Example request:

```bash
curl -X POST http://localhost:8000/api/transactions \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "transaction_id":"fc47e688-53ad-4db5-a0e2-23bb973fd640",
    "user_name":"Michael",
    "amount":2500,
    "type":"credit",
    "status":"successful",
    "description":"Wallet deposit",
    "transacted_at":"2026-08-17T15:30:00+01:00"
  }'
```

Response:

```json
{
  "transaction_id": "fc47e688-53ad-4db5-a0e2-23bb973fd640",
  "status": "queued",
  "topic": "user-transactions"
}
```

Dashboard data is available from `GET /api/dashboard`.

## Database

The SQLite file is `database/database.sqlite`. Inspect it with:

```bash
sqlite3 database/database.sqlite
```

```sql
.tables
.schema transactions
SELECT transaction_id, user_name, amount, type, status, transacted_at
FROM transactions
ORDER BY transacted_at DESC
LIMIT 20;
```

Or use Laravel Tinker:

```bash
php artisan tinker --execute="dump(App\Models\Transaction::latest('transacted_at')->take(20)->get()->toArray());"
```

## Tests and frontend

```bash
php artisan test
vendor/bin/pint app config database routes tests
npm run build
```

The Vue frontend polls `GET /api/dashboard` every five seconds. For development hot reload, use `npm run dev`.

## Troubleshooting

If the API accepts a transaction but the dashboard does not update, verify the consumer:

```bash
docker compose ps
docker compose logs --tail=100 consumer
```

Inside Docker, Kafka is `kafka:9092`. From the host, the Compose broker is `localhost:29092`. If PHP reports `Class "RdKafka\\Conf" not found`, install and enable the `rdkafka` extension.
