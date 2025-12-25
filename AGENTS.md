# AGENTS.md

This file provides guidance for agentic coding assistants working in this Laravel 12 Secret Santa project.

## Development Commands

**IMPORTANT**: All commands must be run inside Docker containers.

```bash
# Start all services
docker-compose up -d

# Install dependencies (run in app container)
docker-compose exec app composer install
docker-compose exec app npm install

# Code formatting
docker-compose exec app ./vendor/bin/pint              # Format code
docker-compose exec app ./vendor/bin/pint --test       # Check without fixing

# Testing
docker-compose exec app php artisan test               # Run all tests
docker-compose exec app php artisan test --filter=TestName  # Run single test
docker-compose exec app php artisan test --testsuite=Feature # Run test suite
docker-compose exec app php artisan test --filter=GameControllerTest::test_store  # Specific method

# Database migrations
docker-compose exec app php artisan migrate
docker-compose exec app php artisan migrate:fresh
docker-compose exec app php artisan migrate:rollback

# Asset building
docker-compose exec app npm run dev      # Development build
docker-compose exec app npm run build    # Production build

# View logs
docker-compose logs -f           # All services
docker-compose logs -f app       # App container
docker-compose logs -f bot       # Bot container

# Restart bot after code changes
docker-compose restart bot
```

## Code Style Guidelines

### PHP General
- **PHP Version**: ^8.4
- **Type Hints**: Always use return types on methods (e.g., `: void`, `: Response`, `: HasMany`)
- **No Comments**: Do not add comments unless explicitly requested

### Imports and Namespaces
- Group imports: App\Models first, then Illuminate components, then others
- Use `use Illuminate\Support\Facades\*` for facades (DB, Hash, Cache, Http, etc.)
- Use `use Illuminate\Http\Request;` for request objects
- Example import order:
  ```php
  use App\Models\Game;
  use App\Models\Participant;
  use Illuminate\Http\Request;
  use Illuminate\Support\Facades\DB;
  use Illuminate\Support\Facades\Hash;
  ```

### Models
- Use `HasFactory` trait
- Define `$fillable` array for mass assignment
- Define relationships as public methods with return types:
  - `public function participants(): HasMany { ... }`
  - `public function game(): BelongsTo { ... }`
  - `public function assignmentAsSanta(): HasOne { ... }`

### Controllers
- Validate input at the start of controller methods
- Use `DB::transaction()` for multi-step database operations
- Return `back()->withErrors(['field' => 'message'])` for validation errors
- Return `redirect()->route('name')->with('status', 'message')` for success
- Use `abort(403)` for authorization failures
- Load eager relationships with `->load()` or `->with()`

### Migrations
- Standard structure with `up(): void` and `down(): void` methods
- Use `Schema::create()` and `Schema::dropIfExists()`
- Always include `$table->id()` and `$table->timestamps()`
- Use nullable for optional fields: `$table->string('title')->nullable();`

### Middleware
- Standard `handle(Request $request, Closure $next): Response` signature
- Use `abort(403, 'message')` for authorization failures
- Return `$next($request)` to continue

### Blade Templates
- Extend layouts: `@extends('layouts.app')`
- Use sections: `@section('content')` / `@endsection`
- Use translation keys: `__('key.name')`
- Use Tailwind CSS classes (e.g., `max-w-2xl mx-auto`, `text-3xl font-display`)
- Add CSRF tokens in forms: `@csrf`

### Telegram Bot Commands
- Define `$signature` and `$description` properties
- Use private methods for helper functions
- Use `Cache::put('key', value, ttl)` for state management (TTL typically 3600s)
- Use `Cache::forget('key')` to clean up state
- Use `Http::timeout()->get()` for API calls with long polling (30s)
- Escape underscores in Markdown with `str_replace('_', '\\_', $text)`
- Use `matchCommand($text, 'bot.key')` for multi-language command matching

### Localization
- Store translations in `lang/{locale}/` directories (uk, en)
- Use `__('key')` syntax for translations
- Resolve locale from: Cache → User model → Participant model → default 'uk'

### Error Handling
- Use `abort(403)` for authorization failures
- Use `back()->withErrors()` for validation errors
- Use `DB::transaction()` to ensure atomic operations
- Catch exceptions in bot loop: log errors, sleep(5), continue

### Database Transactions
- Always wrap multi-step operations in `DB::transaction(function() use (...) { ... })`
- This includes: creating game with participants, updating constraints, generating assignments

### Caching
- Use Cache for temporary state (bot conversations, locale caching, heartbeat)
- Typical TTL: 3600s for bot state, 86400s for user data, 60s for heartbeat
- Cache keys pattern: `bot_state_$chatId`, `bot_user_lang_$chatId`, `telegram_bot_last_seen`

### Security
- Never store plain text passwords or PINs long-term
- Use `Hash::make($pin)` for PIN hashing
- Store plain PINs only temporarily in session or cache
- Validate Telegram login data for web app authentication
- Never commit `.env` or sensitive data to repository

### Naming Conventions
- Models: PascalCase (e.g., `Game`, `Participant`)
- Controllers: PascalCase + "Controller" (e.g., `GameController`)
- Database tables: snake_case plural (e.g., `games`, `participants`)
- Columns: snake_case (e.g., `telegram_chat_id`, `shipping_address`)
- Routes: kebab-case (e.g., `game.store`, `reveal.show`)
- View files: kebab-case (e.g., `game/create.blade.php`)
- Methods: camelCase (e.g., `handleNewGame`, `processUpdate`)

### When Adding Features
- Check if global User model needs same fields as Participant (e.g., shipping_address, language)
- Add translations to both `lang/en` and `lang/uk` directories
- Run `docker-compose exec app ./vendor/bin/pint` to format code after changes
- Test with `docker-compose exec app php artisan test`
- If feature affects bot, restart bot with `docker-compose restart bot`
