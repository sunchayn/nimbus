---
name: task-finalization
description: "Use before marking any Nimbus change complete. Runs the package's quality tools scoped to what changed and sets the review step for subagent work."
license: MIT
metadata:
  author: sunchayn
---

# Task Finalization

Do not call a change done until the checks that catch regressions have run.

## Workflow

1. List changed files with `git status` and `git diff --name-only`.
2. Skip checks for questions, investigations, and documentation-only changes.
3. PHP changed under `src/`, `tests/`, `config/`, `routes/`, or `bin/`: run `composer style:fix`, `composer phpstan`, `composer rector`, `composer test:parallel` in that order. Use `composer test -- --filter <name>` while iterating.
4. `resources/js` or `resources/css` changed: run `npm run style:check`, `npm run type:check`, `npm run test:run`.
5. UI flow changed: run the matching spec with `npm run test:e2e`.
6. Backend shape behind a TypeScript interface changed: run `php bin/intellisense` and check `resources/js/interfaces/generated`.
7. Version-sensitive change: follow `package-compatibility`.
8. Subagent work: review its diff against `write-php-code`, `write-comments`, `write-php-test`, and `write-frontend-code` first.
9. Do not run the frontend build or leave changes in `resources/dist`. The release workflow produces them. Revert any `resources/dist` change before finishing.
10. Report which commands ran and which failed. Never claim a check passed that did not run.

## Anti-Patterns

- Running every suite for a docs change.
- Skipping `composer test:parallel` because PHPStan passed.
- Accepting a subagent diff unreviewed.
- Leaving unrelated Rector or Pint changes without saying so.

## Related

- [write-php-code](../write-php-code/SKILL.md)
- [write-php-test](../write-php-test/SKILL.md)
- [write-frontend-code](../write-frontend-code/SKILL.md)
- [package-compatibility](../package-compatibility/SKILL.md)
