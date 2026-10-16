# Laravel hybrid search

This application indexes text with two search signals:

- PostgreSQL full-text search over a generated, weighted `tsvector` column.
- pgvector cosine-distance search over 512-dimension OpenAI embeddings.

The API merges both ranked lists with Reciprocal Rank Fusion (RRF) using rank constant `60`.

## Requirements

- PHP 8.3 or newer with `pdo_pgsql`
- Composer
- PostgreSQL with the pgvector extension available
- An OpenAI API key

## Setup

Install the application dependencies and create the local environment file:

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
```

Set `OPENAI_API_KEY` in `.env`. Do not commit that file or put the key in source code.

Configure `.env` for the PostgreSQL server already running on the machine. Fill in the database name and credentials for that server:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

The PostgreSQL installation must provide pgvector. The database user must be allowed to run `CREATE EXTENSION IF NOT EXISTS vector`, or a database administrator must enable the extension before migration.

Run the migrations against the configured database:

```bash
php artisan migrate
```

The document migration enables the `vector` extension and creates:

- `search_vector tsvector`, generated from weighted title and content text
- a GIN index on `search_vector`
- `embedding vector(512)`
- an HNSW index with `vector_cosine_ops`

Start Laravel:

```bash
php artisan serve
```

## Index a document

`POST /api/documents` validates the title and content, requests a 512-dimension embedding from OpenAI, and stores both the text and embedding.

```bash
curl --request POST http://127.0.0.1:8000/api/documents \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data '{
    "title": "Laravel queues",
    "content": "Laravel queues move slow work into background workers."
  }'
```

You can index three sample documents with the seeder. It uses the configured OpenAI API key and makes one embeddings request per document:

```bash
php artisan db:seed --class=DocumentSeeder
```

## Search

`GET /api/search` accepts `q` and an optional `limit` from 1 to 50. The default limit is 10.

```bash
curl --get http://127.0.0.1:8000/api/search \
  --header 'Accept: application/json' \
  --data-urlencode 'q=background jobs' \
  --data-urlencode 'limit=10'
```

Each result includes its combined RRF score plus its keyword and semantic ranks. A rank is `null` when the document did not appear in that source list.

## Tests

The standard suite uses an in-memory SQLite database and fakes every OpenAI response, so it does not require an API key or network access:

```bash
php artisan test
```

Verify the configured PostgreSQL connection and migration state with:

```bash
php artisan migrate:status
```
