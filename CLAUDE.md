# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Start the dev server (requires Symfony CLI)
symfony server:start

# Clear cache
php bin/console cache:clear

# Run a migration
php bin/console doctrine:migrations:migrate

# Generate a new migration after entity changes
php bin/console doctrine:migrations:diff

# Create the database (first-time setup)
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Load fixtures (none exist yet)
php bin/console doctrine:fixtures:load

# Install dependencies
composer install
```

There is no test suite configured. There are no npm/frontend build steps — Bootstrap and Bootstrap Icons are loaded from CDN in `templates/base.html.twig`.

## Architecture

Symfony 7.4 application with SQLite (`var/data.db`). No authentication — all routes are public.

### Data model

```
Player
  └── Deck (many, owner)
       └── ColorIdentity (optional, ManyToOne)
            └── Color (many, via color_identity_color join table)

Game
  └── GamePlayer (participants, cascade remove)
       ├── Player (ManyToOne)
       └── Deck (optional ManyToOne)
```

`GamePlayer` is the join entity between `Game` and `Player`. It carries `placement` (int 1–20, nullable) and `winner` (bool). On game edit, all existing `GamePlayer` rows are deleted and rebuilt from the submitted form data.

`ColorIdentity` is a pre-seeded lookup table (e.g. "Izzet", "Temur"). `ColorIdentityRepository::findByColorNames()` matches by sorted color name arrays. Decks reference a `ColorIdentity` row; the `colors` field on `DeckType` is an unmapped checkbox group that resolves to a `ColorIdentity` in the controller.

`Deck::FORMATS` is the canonical list of formats and is referenced by both `Deck` and `Game` entities.

### Scryfall integration

`CommanderSearchController` exposes three JSON endpoints under `/api/commanders/`:

- `GET /api/commanders/search?q=` — fuzzy commander name search
- `GET /api/commanders/partners?q=&type=` — partner-filtered search (partner, friends_forever, choose_background, doctors_companion)
- `GET /api/commanders/info?name=` — exact card lookup

These call the Scryfall API directly (no API key needed). The `extractPartnerInfo()` private method detects all partner variants including DFCs by merging keywords across card faces. "Partner with X" is checked before generic "partner" to avoid false positives.

### Game form

The game new/edit forms use a custom participant section rendered in `templates/game/form.html.twig`. Participants are submitted as raw `participants[n][player_id|deck_id|placement|winner]` POST data — not a Symfony CollectionType. The controller reads `$request->request->all('participants')` directly.

### Routing conventions

Controllers use PHP 8 `#[Route]` attributes. Route names follow `{resource}_{action}` (e.g. `deck_show`, `game_new`). Delete actions are POST-only with CSRF token validation (`'delete' . $entity->getId()`).

### Templates

All templates extend `templates/base.html.twig`. Inline styles in `base.html.twig` define the dark theme (GitHub-dark palette, purple accents `#7c3aed`/`#c084fc`). MTG color pips use CSS classes `.mtg-pip-{Color}` where Color is one of White, Blue, Black, Red, Green, Colorless.
