# Nimbus

A Laravel package (`sunchayn/nimbus`). An in-browser API client that extracts validation rules from host routes, builds JSON schemas, and relays requests from a Vue SPA.
PHP 8.2+, Laravel 10 to 13. Frontend is Vue 3, TypeScript, Tailwind v4.
No database, no Eloquent, no migrations.

## Conventions

- Use Laravel-native APIs and the existing `NimbusServiceProvider` before adding abstractions.
- Add only what the change needs. No new dependency without approval.
- Never execute host application code during extraction. Read source through the AST modules.
- Support the matrix in `.github/workflows/php-tests.yml`. See `package-compatibility`.

## Structure

- `src/Modules/<Domain>`: logic by domain (`Ast`, `Config`, `Export`, `Extractor`, `Relay`, `Routes`, `Schemas`). Create a subfolder only when a class needs it.
- `src/Http/Api/<Domain>`, `src/Http/Web/Controllers`: HTTP layer. Routes in `routes/api.php` and `routes/web.php`.
- `src/Console`: Artisan commands.
- `resources/js`: see each folder's `README.md`. Never edit `interfaces/generated`, run `php bin/intellisense`.
- `tests/App` and `tests/Integration` mirror `src`. `tests/E2E` is Playwright. `resources/js/tests` is Vitest.
- Modules are isolated. Cross them through Actions, DTOs, or `Contracts`, and add a contract only when a second module needs it.

## Commands

- `composer test`, `composer test:parallel`, `composer phpstan`, `composer rector`, `composer style:fix`
- `composer switch:l10` to `l13`
- `npm run style:check`, `npm run type:check`, `npm run test:run`, `npm run test:e2e`
- `php bin/intellisense`

Prefer a named script over a vendor binary.

## Commits

- Conventional Commits, `type(scope): description`.
- release-please owns `CHANGELOG.md` and versions. Do not edit them.
- Never build or commit `resources/dist` files in a PR. The release workflow builds and commits them. Do not run `npm run build` or `npm run build:dev` to produce a change for a PR.
- Update `wiki/user-guide` for user-facing changes and `wiki/contribution-guide` for architecture changes.

## Agent Behavior

- A question is not a command. Answer it and wait for instruction before editing.
- No emojis or em dashes.
- No documentation files unless asked. Write plans and audits to `.ai/artifacts/` with a dated name.
- Do not edit `wiki/` unless asked.

## Subagents

Delegate non-trivial, multi-file, or refactoring work to a subagent. Review its diff against `write-php-code`, `write-comments`, `write-php-test`, and `write-frontend-code`, then run `task-finalization`. Skip the split for small low-risk changes.

## Skills

- `scaffold-module`: where files go and how they are wired.
- `create-dto-action`: Action and DTO pairs.
- `write-php-code`: PHP conventions.
- `write-comments`: comment rules for PHP, TypeScript, Vue.
- `write-php-test`: PHPUnit conventions.
- `write-frontend-code`: Vue, TypeScript, Tailwind, and their tests.
- `task-finalization`: checks before marking work done.
- `package-compatibility`: PHP and Laravel matrix.
