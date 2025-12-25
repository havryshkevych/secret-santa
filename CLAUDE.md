# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code), Codex, Gemini, and other AI tools when working with code in this repository.

## Project Overview

Secret Santa is a Laravel 12 web application with a Telegram bot integration for organizing Secret Santa gift exchanges. The application supports:
- Creating and managing Secret Santa games with multiple participants
- Setting gift-giving constraints (who cannot give to whom)
- Automatic assignment generation with constraint validation
- PIN-protected reveal system for participants to discover their recipients
- Telegram bot interface for participant interactions
- Multi-language support (Ukrainian and English)
- Telegram Mini App integration for web views

## Development Commands

**This project uses Docker for development.** All PHP/Artisan commands must be run inside Docker containers.

### Initial Setup
```bash
# Start all services (app, nginx, db, bot)
docker-compose up -d

# Install dependencies and setup application
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app npm install
docker-compose exec app npm run build
```

### Running Development Environment
```bash
# Start all services (includes web server on port 8000 and Telegram bot)
docker-compose up -d

# View logs
docker-compose logs -f           # All services
docker-compose logs -f app       # App container only
docker-compose logs -f bot       # Bot container only

# Stop all services
docker-compose down

# Rebuild containers after Dockerfile changes
docker-compose up -d --build
```

### Docker Services
- **app**: PHP-FPM application container
- **nginx**: Web server (accessible at http://localhost:8000)
- **db**: PostgreSQL database
- **bot**: Telegram bot (runs `php artisan telegram:run`)

### Artisan Commands
All artisan commands must be executed inside the `app` container:
```bash
docker-compose exec app php artisan migrate
docker-compose exec app php artisan migrate:fresh
docker-compose exec app php artisan migrate:rollback
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan tinker
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
```

### Testing
```bash
docker-compose exec app php artisan test
docker-compose exec app php artisan test --filter=TestName
```

### Composer Commands
```bash
docker-compose exec app composer install
docker-compose exec app composer update
docker-compose exec app composer require package/name
```

### Code Quality
```bash
docker-compose exec app ./vendor/bin/pint              # Format code using Laravel Pint
docker-compose exec app ./vendor/bin/pint --test       # Check without fixing
```

### NPM/Asset Building
```bash
docker-compose exec app npm install
docker-compose exec app npm run dev      # Development build
docker-compose exec app npm run build    # Production build
```

### Database Access
```bash
# Access PostgreSQL directly
docker-compose exec db psql -U user -d secretsanta

# Or use Artisan tinker
docker-compose exec app php artisan tinker
```

### Telegram Bot Management
The bot runs automatically in the `bot` container when you run `docker-compose up -d`.

```bash
# Restart bot after code changes
docker-compose restart bot

# View bot logs
docker-compose logs -f bot

# Manually run bot (if stopped)
docker-compose exec bot php artisan telegram:run
```

### Production Deployment
The Procfile defines two processes for non-Docker deployments:
- `web`: Runs migrations and starts web server
- `bot`: Runs Telegram bot command

## Architecture

### Core Domain Models

**Game → Participant → Assignment**

- **Game**: Top-level entity representing a Secret Santa game
  - Has many Participants
  - Has many Constraints (exclusion rules)
  - Has many Assignments (Santa-Recipient pairs)
  - Tracks `organizer_chat_id` (Telegram chat ID of game creator)
  - Has optional `title` and `description`

- **Participant**: Individual in a game
  - Belongs to a Game
  - Has optional `telegram_username` and `telegram_chat_id` (linked when bot is started)
  - Stores `pin_hash` (for reveal authentication), `reveal_token` (UUID for web access)
  - Plain `pin` field temporarily stores PIN after generation (not meant for long-term storage)
  - Can store `wishlist_text` and `shipping_address`
  - Has `language` preference ('uk' or 'en')
  - Has relationships: `assignmentAsSanta` (who they give to), `assignmentAsRecipient` (who gives to them)

- **Assignment**: Santa-Recipient pair
  - Belongs to a Game
  - Links `santa_id` → `recipient_id` (both reference Participant.id)

- **Constraint**: Gift-giving exclusion rule
  - Represents "participant X cannot give to participant Y"
  - Field `cannot_receive_from_participant_id` is the Santa who is forbidden
  - Field `participant_id` is the Recipient they cannot give to

- **User**: Authenticated web users (via Telegram login)
  - Has `telegram_id` (Telegram chat ID) and optional `telegram_username`
  - Stores global `shipping_address` and `language` preference
  - Used for admin panel access and web authentication

### Key Controllers

**GameController**: Main game lifecycle
- `create/store`: Create new game with participants (parses "Name @username" format)
- `constraints/storeConstraints`: Set exclusion rules before assignment
- `assign`: Generate Santa-Recipient pairs using constraint-aware shuffling algorithm (max 1000 attempts)
- `result`: Display generated assignments and PINs (PINs flashed via session)
- `myGames`: Dashboard for authenticated users showing organized/participating games

**RevealController**: Participant reveal flow
- `show`: Display reveal page with PIN entry form
- `reveal`: Verify PIN and show assigned recipient
- `updateWishlist`: Update participant's wishlist
- `resendNotification`: Resend Telegram notification to participant

**AuthController**: Telegram authentication
- `telegramLogin`: Handle Telegram Login Widget authentication
- `webAppLogin`: Handle Telegram Mini App authentication (via initData validation)
- `logout`: Clear session

**AdminController**: Admin panel (middleware: 'auth', 'admin')
- `index`: List all games with management options
- `destroyGame`: Delete a game and related data

### Telegram Bot Architecture

**Command: `docker-compose exec app php artisan telegram:run`** (runs automatically in `bot` container)
Located in: `app/Console/Commands/TelegramBotCommand.php`

The bot uses long-polling (getUpdates with 30s timeout) and maintains conversation state via Cache.

**State Machine Pattern**:
- States stored as `bot_state_{$chatId}` in cache
- State transitions: `waiting_for_title` → `waiting_for_description` → `waiting_for_participants` → game created
- Other states: `waiting_for_wishlist`, `waiting_for_game_selection`, `waiting_for_settings_action`, etc.

**Key Bot Features**:
1. **Game Creation Flow**: Collects title, description, participant list
2. **Localization**: Resolves user language from User/Participant models, falls back to 'uk'
   - Uses `matchCommand()` helper to match buttons in multiple languages
3. **Username Syncing**: Automatically syncs `telegram_chat_id` for participants with matching `telegram_username`
4. **Notification System**: Sends game invites with Telegram Mini App buttons (WebApp links)
5. **Settings Panel**: Manage games, change language, notify specific players, broadcast messages
6. **Heartbeat**: Updates `telegram_bot_last_seen` cache key every loop iteration for `/health/bot` endpoint

**Bot Menu Structure**:
- New Game / My Games
- Who (reveal recipient) / Wishlist
- Address (shipping info) / Settings

**Message Sending**:
- All messages use Markdown parse mode
- Supports both keyboard buttons (reply markup) and inline buttons (web_app links)
- Menu buttons are persistent and localized

### Authentication & Authorization

**Telegram Login**:
- Uses Telegram Login Widget for web authentication
- Validates Telegram initData for Mini App authentication
- Creates/updates User records with `telegram_id` and `telegram_username`

**Admin Middleware**:
- Checks if `telegram_username` is in `ADMIN_TELEGRAM_USERNAMES` (comma-separated env var)
- Admin users can access `/admin/*` routes

**Reveal Authentication**:
- Two methods: PIN-based (4-digit hash) or reveal_token (UUID in URL)
- PINs are hashed and stored in `pin_hash`
- Plain PINs (`pin` field) only shown once after generation

### Assignment Generation Algorithm

Located in: `GameController::assign()`

1. Load all constraints into a forbidden map: `forbidden[santa_id] = [recipient_ids]`
2. Shuffle participants randomly (max 1000 attempts)
3. For each shuffle, pair participants by index: `santa[i] → recipient[shuffled[i]]`
4. Validate:
   - Santa cannot give to themselves
   - Santa cannot give to forbidden recipients (from constraints)
5. If valid, create Assignment records and regenerate PINs
6. Return error if no valid assignment found after 1000 attempts

### Localization

**Supported Languages**: Ukrainian ('uk'), English ('en')

**Translation Files**: `lang/{locale}/`
- Bot messages use `__('bot.key')` syntax
- Locale resolution order: Cache → User model → Participant model → default 'uk'
- Language can be changed via bot settings (callback query handlers)

### Front-end Architecture

**Blade Templates**:
- `layouts/app.blade.php`: Main layout with Telegram Mini App initialization
- Game views: `game/create.blade.php`, `game/constraints.blade.php`, `game/edit.blade.php`, `game/my_games.blade.php`
- Reveal views: `reveal/show.blade.php` (PIN entry), `reveal/result.blade.php` (recipient display)
- Admin: `admin/index.blade.php`

**Vite + Tailwind CSS**:
- Entry point: `resources/js/app.js` (imports CSS)
- Tailwind config in `tailwind.config.js`
- Build: `npm run build` for production

**Telegram WebApp Integration**:
- Uses Telegram.WebApp SDK for Mini App features
- Expands app viewport, enables closing confirmation
- Theme-aware styling via Telegram color scheme

### Database

**Driver**: PostgreSQL (via Docker container)
- Default credentials: `user` / `password` / database: `secretsanta`
- Data persisted in Docker volume `db_data`
- Can be configured for SQLite or MySQL if needed

**Key Tables**:
- `users`: Telegram-authenticated web users
- `games`: Secret Santa games
- `participants`: Game participants (linked to users via telegram_username/chat_id)
- `assignments`: Santa-Recipient pairs
- `constraints`: Gift-giving exclusion rules
- `cache`: Used for session, queue, and bot state storage
- `jobs`: Background job queue

**Important**: When adding new Participant or Game features, check if global User model should also be updated (e.g., `shipping_address` exists on both).

### Environment Variables

**Required**:
- `APP_KEY`: Laravel encryption key (generate via `docker-compose exec app php artisan key:generate`)
- `TELEGRAM_BOT_TOKEN`: Telegram bot API token
- `APP_URL`: Full application URL (used in bot messages and Mini App links)
- `DB_*`: Database credentials (set in docker-compose.yml for PostgreSQL)

**Optional**:
- `TELEGRAM_BOT_USERNAME`: Bot username (default: 'SecretSantaBot')
- `ADMIN_TELEGRAM_USERNAMES`: Comma-separated list of admin usernames (lowercase, no @)
- `DB_CONNECTION`: Database driver (default: pgsql for Docker setup)

### Deployment Considerations

**HTTPS Requirement**:
- Telegram Mini Apps require HTTPS
- `bootstrap/app.php` forces HTTPS URL scheme when `APP_ENV != 'local'`

**Proxy Trust**:
- Application trusts all proxies (for services like Heroku, Railway)

**Health Check**:
- `/health/bot`: Returns 200 if bot was seen in last 5 minutes, 503 otherwise

**Multi-Process Setup**:
- **Docker Development**: Separate containers for app, nginx, db, and bot (all managed by docker-compose)
- **Production (Procfile)**: Web server and bot process run as separate processes
- Bot process runs `php artisan telegram:run` (long-polling mode)

### Common Patterns

**Creating Games**:
- Participants can be specified as "Name @username" (one per line)
- Username is extracted via regex and stored lowercased
- If username exists in previous games, shipping address is auto-populated

**Bot State Management**:
- Always store state with TTL (usually 3600 seconds)
- Clean up state on cancel/completion using `Cache::forget()`
- Multiple related cache keys per flow (e.g., `bot_state_`, `bot_game_title_`, `bot_game_description_`)

**Telegram Chat ID Syncing**:
- When user starts bot, update all Participant records with matching `telegram_username`
- This links web-created participants to bot users

**Wishlist & Address Updates**:
- Updates apply to all participant records for that user (across all games)
- Also updates User model if exists
