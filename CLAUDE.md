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

There are no npm/frontend build steps — Bootstrap and Bootstrap Icons are loaded from CDN in `templates/base.html.twig`.

## Testing

- **Definition of Done:** You MUST NOT consider any feature addition, bug fix, or code modification complete until the corresponding PHPUnit tests have been written or updated.
- **Test Location:** Every class in `src/` must have a corresponding test in `tests/` mirroring the directory structure (e.g., `src/Service/Invoice.php` -> `tests/Service/InvoiceTest.php`).
- **Workflow:** 
  1. When adding or modifying code, immediately locate the corresponding test file.
  2. If the test file does not exist, you MUST create it.
  3. Write tests covering your new logic.
  4. Run the PHPUnit hook to verify the tests pass before reporting the task as finished.
- **Never Ask for Permission:** Do not ask "Would you like me to write tests for this?" Just do it automatically as part of the implementation step.


The test suite uses PHPUnit 12 via `symfony/test-pack`.

```bash
# Run the full test suite
php bin/phpunit

# Run only unit tests (no database required, fast)
php bin/phpunit --testsuite Unit

# Run only integration tests (repository tests, requires DB)
php bin/phpunit --testsuite Integration

# Run only functional tests (controller/form tests, requires DB)
php bin/phpunit --testsuite Functional

# Run a specific test file
php bin/phpunit tests/Unit/Entity/DeckTest.php

# Filter to a specific test method
php bin/phpunit --filter testFindOrCreateReturnsExistingCommander
```

The test database is `var/test.db` (SQLite, separate from `var/data.db`). Integration and Functional tests recreate the schema automatically via Doctrine's `SchemaTool`. Do not commit `var/test.db` to version control.

Scryfall API calls are mocked in all tests using `MockHttpClient`/`MockResponse` from `symfony/http-client`.

Symfony 7.2+ returns HTTP 422 (not 200) when a form re-renders with validation errors.

## Testing Quality Standards
- **No Magic Numbers/Strings:** NEVER hard-code the same value twice in a test. If a value is used to set up state (e.g., `$turnCount = 33`) and later used in an assertion, you MUST extract it to a local variable or class constant at the top of the test method.
- **Strict Layer Separation:** 
  - **Unit Tests:** Mock all external dependencies.
  - **Integration/Controller Tests:** Verify state changes by querying the database (via Doctrine EntityManager) or by inspecting structured data (JSON responses).
- **No Brittle UI Scraping:** NEVER use `$this->assertSelectorTextContains()` or test raw HTML body text unless explicitly asked to write an End-to-End browser test. Test the underlying data, not the Twig presentation.
- **Assertion Quality:** Always prefer strict assertions (`assertSame` over `assertEquals`) and assert the specific expected type.

## Static Analysis

PHPStan 2.x is configured at level 6 with the Symfony, Doctrine, and PHPUnit extensions.

```bash
# Run static analysis
php vendor/bin/phpstan analyse --memory-limit=256M

# Run with verbose output (shows progress per file)
php vendor/bin/phpstan analyse --memory-limit=256M -v
```

Config is in `phpstan.neon`. The Doctrine extension uses `tests/doctrine_object_manager.php` to load entity metadata; the Symfony extension reads the compiled dev container at `var/cache/dev/App_KernelDevDebugContainer.xml` — rebuild it with `php bin/console cache:warmup` if it goes stale.


## Validation Policy (Strict)
- **Zero-Bug Policy:** This project maintains a passing state for PHPUnit and PHPStan at all times.
- **Hook Failures:** If any hook (SessionStart or PostToolUse) returns a non-zero exit code, you must treat this as a BLOCKING error.
- **Ownership:** Fix all detected errors immediately, regardless of whether they were introduced by you or exist in the codebase from previous work. 

## Autonomous Behavior
- **Initial State:** Upon starting a session, if the SessionStart hooks indicate failures (PHPUnit or PHPStan), your first priority is to summarize these errors and ask to fix them, or fix them immediately if the user provides a broad instruction like "help me out" or "fix the build".
- **Silent Failures:** Never ignore a non-zero exit code from a hook. If a hook fails, you must investigate the output and propose a fix before continuing with other tasks.

## Development Notes
- **PHPStan Noise:** You may see a notice stating "Using configuration file ...phpstan.neon". This is **informational only** and does not indicate a failure. 
- **Validation Success:** As long as the command exit code is 0 and no specific error table is present, consider the check passed regardless of configuration notices.

## Git Guidelines
- **Commit Style:** Always use Conventional Commits (e.g., `feat:`, `fix:`, `refactor:`, `docs:`).
- **Format:** Use imperative mood in the subject line (e.g., "add service" not "added service"). Write commit messages are that as conscise as possible.
- **Scope:** Include a scope if applicable (e.g., `fix(controller): resolve null pointer`).
- **Justify:** When making more complex changes, try to justify the reason why the changes were made or explain the logic why certain changes were made the way they were.
- **Humanize:** Use simpler language, make messages friendly and warm. Add small, harmless jokes occasionally.
- **Concise:** 

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
