# Transact Dashboard

A near-real-time transaction dashboard built with Laravel 12, Vue 3, Kafka, and SQLite.

```text
POST /api/transactions → Laravel → Kafka → Consumer → SQLite
                                                    ↓
Vue dashboard ← GET /api/dashboard ←───────────────┘
```

The API returns `202 Accepted` when Kafka queues a transaction. Vue refreshes the dashboard every five seconds.

## Requirements

Docker setup:

- Docker Engine 24+
- Docker Compose v2
- Ports `8000` and `29092`

Native setup:

- PHP 8.2+, Composer 2, and PHP SQLite
- Node.js 20+ and npm
- Kafka, Java 17+, `librdkafka`, and PHP `rdkafka`

## Docker setup

```bash
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
docker compose up --build -d
```

Open [http://localhost:8000](http://localhost:8000).

```bash
docker compose ps
docker compose logs -f consumer
docker compose down
```

Reset the database and add sample transactions:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

> `migrate:fresh` deletes existing database records.

## Native setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

When Kafka runs locally, set:

```dotenv
KAFKA_BROKERS=localhost:9092
KAFKA_TOPIC=user-transactions
KAFKA_CONSUMER_GROUP=transaction-dashboard
```

Create the Kafka topic:

```bash
bin/kafka-topics.sh --bootstrap-server localhost:9092 --create \
  --if-not-exists --topic user-transactions --partitions 3 \
  --replication-factor 1
```

Run in separate terminals:

```bash
php artisan serve
php artisan kafka:consume-transactions
npm run dev
```

## API

Send a transaction:

```bash
curl -X POST http://localhost:8000/api/transactions \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
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

- `type`: `credit` or `debit`
- `status`: `pending`, `successful`, or `failed`
- `description` is optional; all other fields are required.
- Use a unique UUID for each transaction.

Dashboard JSON is available at `GET /api/dashboard`.

## Send 100 requests

This Bash command requires `uuidgen`:

```bash
for i in $(seq 1 100); do
  curl -sS -X POST http://localhost:8000/api/transactions \
    -H 'Accept: application/json' \
    -H 'Content-Type: application/json' \
    -d "{
      \"transaction_id\":\"$(uuidgen)\",
      \"user_name\":\"User $i\",
      \"amount\":$((RANDOM % 5000 + 1)),
      \"type\":\"$([ $((i % 2)) -eq 0 ] && echo credit || echo debit)\",
      \"status\":\"successful\",
      \"description\":\"Load test transaction $i\",
      \"transacted_at\":\"$(date --iso-8601=seconds)\"
    }"
  echo
done
```

## Database

The database is [database/database.sqlite](database/database.sqlite).

```bash
sqlite3 database/database.sqlite
```

```sql
.tables
.schema transactions
SELECT * FROM transactions ORDER BY transacted_at DESC LIMIT 20;
```

## Verify

```bash
php artisan test
vendor/bin/pint app config database routes tests
npm run build
```

If accepted transactions do not appear, check the consumer with `docker compose logs --tail=100 consumer`.
