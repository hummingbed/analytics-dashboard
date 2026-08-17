# Pulse Analytics

Pulse is a near-real-time analytics dashboard built with Laravel 12, Vue 3, Apache Kafka, and SQLite. The HTTP API publishes events to Kafka; a separate Laravel consumer stores them; the Vue dashboard refreshes its metrics every five seconds.

## Architecture

```text
Client / event producer
        |
        | POST /api/events
        v
Laravel API (Kafka producer)
        |
        v
analytics-events topic (3 partitions)
        |
        v
Laravel consumer
        |
        v
SQLite --> GET /api/dashboard --> Vue dashboard
```

The API returns `202 Accepted` when Kafka accepts a message. The event will appear on the dashboard after the consumer processes and persists it.

## Features

- Kafka-based event ingestion with an idempotent event UUID
- A straightforward consumer command that persists messages directly
- Sales, traffic, click, financial, and operations metrics
- Vue 3 component-based dashboard with loading and reconnect states
- Five-second dashboard refresh
- Apache Kafka in KRaft mode—no ZooKeeper required
- SQLite for simple local persistence
- Docker Compose setup for the complete stack
- Feature tests for the API, Kafka publisher, persistence, and dashboard

## Requirements

### Docker setup—recommended

- Docker Engine 24 or newer
- Docker Compose v2
- At least 3 GB of available memory
- Ports `8000` and `29092` available

Docker provides PHP 8.2, the `rdkafka` extension, Node.js, Kafka, and the consumer runtime.

### Native setup—without Docker

- PHP 8.2 or newer
- Composer 2
- Node.js 20 or newer and npm
- SQLite and the PHP SQLite extension
- Java 17 or newer
- Apache Kafka 4.1.1 or a compatible Kafka broker
- `librdkafka` and the PHP `rdkafka` extension

Confirm the important PHP modules:

```bash
php -m | grep -E 'pdo_sqlite|rdkafka'
```

## Setup with Docker

Copy the environment file:

```bash
cp .env.example .env
```

Generate a key with Docker:

```bash
docker run --rm php:8.2-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Copy the generated value into the `APP_KEY=` line in `.env`. If the project dependencies and PHP are already installed locally, you can instead run:

```bash
php artisan key:generate
```

Ensure the SQLite file exists:

```bash
touch database/database.sqlite
```

Build and start the entire stack:

```bash
docker compose up --build -d
```

Open the dashboard at [http://localhost:8000](http://localhost:8000).

Check the services:

```bash
docker compose ps
```

The expected long-running services are:

- `app`: Laravel API and compiled Vue frontend
- `kafka`: Apache Kafka broker
- `consumer`: Laravel Kafka consumer

`kafka-init` is a one-time container that creates the topic and then exits successfully.

Follow logs:

```bash
docker compose logs -f app
docker compose logs -f consumer
docker compose logs -f kafka
```

Stop the stack:

```bash
docker compose down
```

The SQLite database is bind-mounted from `database/database.sqlite`, so `docker compose down` does not delete application data. Kafka's log is container-local in this development configuration and is reset when the broker container is removed and recreated.

## Setup without Docker

### 1. Install PHP dependencies

On Ubuntu or Debian, install `librdkafka` and build tools before installing the PHP extension:

```bash
sudo apt update
sudo apt install librdkafka-dev php-dev php-pear php-sqlite3
sudo pecl install rdkafka
```

Enable the extension in the relevant `php.ini`:

```ini
extension=rdkafka.so
```

On macOS with Homebrew:

```bash
brew install librdkafka
pecl install rdkafka
```

Then install the project dependencies:

```bash
composer install
npm install
```

### 2. Configure Laravel

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

For a Kafka broker running directly on the host, update `.env`:

```dotenv
KAFKA_BROKERS=localhost:9092
KAFKA_TOPIC=analytics-events
KAFKA_CONSUMER_GROUP=pulse-dashboard
```

### 3. Start Kafka

Install Apache Kafka 4.1.1, then initialize a single-node KRaft broker once from the extracted Kafka directory:

```bash
KAFKA_CLUSTER_ID="$(bin/kafka-storage.sh random-uuid)"
bin/kafka-storage.sh format --standalone -t "$KAFKA_CLUSTER_ID" -c config/server.properties
```

Start the broker:

```bash
bin/kafka-server-start.sh config/server.properties
```

In a second terminal, create the topic:

```bash
bin/kafka-topics.sh \
  --bootstrap-server localhost:9092 \
  --create \
  --if-not-exists \
  --topic analytics-events \
  --partitions 3 \
  --replication-factor 1
```

You may also use any managed Kafka-compatible broker and place its connection address in `KAFKA_BROKERS`.

### 4. Start the application processes

Build the Vue frontend:

```bash
npm run build
```

Start Laravel:

```bash
php artisan serve
```

Start the Kafka consumer in another terminal:

```bash
php artisan kafka:consume-analytics
```

Both processes must remain running. In production, run the consumer under Supervisor, systemd, or another process manager.

### Hybrid local setup

You can run only Kafka in Docker while running Laravel and Vue natively:

```bash
docker compose up -d kafka kafka-init
```

Set the host application broker address to:

```dotenv
KAFKA_BROKERS=localhost:29092
```

Then run `php artisan serve`, `php artisan kafka:consume-analytics`, and `npm run dev` in separate terminals.

## API

### Publish an event

```http
POST /api/events
Content-Type: application/json
Accept: application/json
```

Example body:

```json
{
  "event_id": "b0b6b3e7-4a88-4a8e-91ec-e34ea66e85a9",
  "type": "sale",
  "value": 79.90,
  "source": "checkout",
  "metadata": {
    "currency": "USD",
    "order_id": "ORD-1042"
  },
  "occurred_at": "2026-08-17T12:00:00Z"
}
```

Supported event types:

- `sale`
- `page_view`
- `click`
- `operation`
- `financial`

Required fields are `event_id`, `type`, and `occurred_at`. `value` defaults to `1`, `source` defaults to `web`, and `metadata` is optional. Always use a new UUID for a new event.

Example request:

```bash
curl -X POST http://localhost:8000/api/events \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    "event_id":"b0b6b3e7-4a88-4a8e-91ec-e34ea66e85a9",
    "type":"sale",
    "value":79.90,
    "source":"checkout",
    "metadata":{"currency":"USD","order_id":"ORD-1042"},
    "occurred_at":"2026-08-17T12:00:00Z"
  }'
```

Successful response:

```json
{
  "event_id": "b0b6b3e7-4a88-4a8e-91ec-e34ea66e85a9",
  "status": "queued",
  "topic": "analytics-events"
}
```

### Dashboard data

```http
GET /api/dashboard
Accept: application/json
```

This returns metric cards, hourly series, source totals, recent events, and the snapshot timestamp.

## Vue development

The Vue entry point is `resources/js/App.vue`; reusable components live in `resources/js/components`.

For hot module replacement while Laravel is running locally:

```bash
npm run dev
```

Build production assets:

```bash
npm run build
```

The Dockerfile performs this production build automatically with a Node.js build stage.

## Database and demo data

The SQLite database is located at `database/database.sqlite`.

Create a clean schema with generated demo metrics:

```bash
php artisan migrate:fresh --seed
```

With Docker:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Inspect recent records using Tinker:

```bash
php artisan tinker --execute="dump(App\Models\MetricEvent::latest('occurred_at')->take(20)->get()->toArray());"
```

Or use the SQLite CLI:

```bash
sqlite3 database/database.sqlite
```

```sql
.tables
.schema metric_events
SELECT event_id, type, value, source, occurred_at
FROM metric_events
ORDER BY occurred_at DESC
LIMIT 20;
```

Seeded data is written directly to SQLite for development convenience. Events submitted through the API always travel through Kafka.

## Testing and formatting

Run the PHP test suite:

```bash
php artisan test
```

Format PHP code:

```bash
vendor/bin/pint app config database routes tests
```

Validate the frontend production build:

```bash
npm run build
```

## Kafka operations

Describe the topic:

```bash
docker compose exec kafka /opt/kafka/bin/kafka-topics.sh \
  --bootstrap-server kafka:9092 \
  --describe \
  --topic analytics-events
```

Check consumer lag:

```bash
docker compose exec kafka /opt/kafka/bin/kafka-consumer-groups.sh \
  --bootstrap-server kafka:9092 \
  --describe \
  --group pulse-dashboard
```

A healthy consumer normally shows `LAG` as `0` after it catches up.

## Environment variables

| Variable | Default | Purpose |
|---|---|---|
| `APP_URL` | `http://localhost` | Public Laravel URL |
| `DB_CONNECTION` | `sqlite` | Database driver |
| `KAFKA_BROKERS` | `kafka:9092` | Comma-separated Kafka brokers |
| `KAFKA_TOPIC` | `analytics-events` | Analytics event topic |
| `KAFKA_CONSUMER_GROUP` | `pulse-dashboard` | Consumer group ID used by the app |

Do not commit `.env`. Use real secrets and set `APP_DEBUG=false` in production.

## Troubleshooting

### Dashboard loads but does not update

Check that the consumer is running:

```bash
docker compose ps
docker compose logs --tail=100 consumer
```

The API only queues the event. The consumer must be running to write it to SQLite.

### API returns a Kafka connection error

- Inside Docker, use `KAFKA_BROKERS=kafka:9092`.
- From the host, use `KAFKA_BROKERS=localhost:29092` for the Compose broker.
- For a native Kafka installation, usually use `localhost:9092`.

### `Class "RdKafka\\Conf" not found`

The PHP `rdkafka` extension is missing or not enabled. Run `php -m | grep rdkafka` and confirm you edited the `php.ini` used by the CLI.

### Kafka consumer repeatedly restarts

Inspect `docker compose logs consumer`. Confirm `config/kafka.php` exists, the broker is healthy, the topic exists, and the database file is writable.

### Port already in use

Change the host side of the relevant mapping in `compose.yaml`, for example `8080:8000` for the dashboard or `39092:29092` for Kafka.

### Reset local Kafka data

```bash
docker compose down
docker compose up --build -d
```

This recreates Kafka with an empty log. It does not remove the bind-mounted SQLite file.
