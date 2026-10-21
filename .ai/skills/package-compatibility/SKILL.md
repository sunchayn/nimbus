---
name: package-compatibility
description: "Use when reviewing Nimbus code, dependencies, or CI against the supported PHP and Laravel matrix."
license: MIT
metadata:
  author: sunchayn
---

# Package Compatibility

Keep code and dependencies compatible with `composer.json` and the matrix in `.github/workflows/php-tests.yml`.

## Workflow

1. Read the PHP, `illuminate/contracts`, and Testbench constraints and the `switch:l10` to `switch:l13` scripts in `composer.json`.
2. Treat the `php-tests.yml` matrix and its `exclude` list as the source of truth.
3. Check changes against the oldest and newest supported versions. No syntax or API missing from the oldest one.
4. `nesbot/carbon` spans two majors. `spatie/laravel-data` and `devizzent/cebe-php-openapi` are optional, so code using them must degrade when absent.
5. Confirm dependency changes still allow every Laravel major in the matrix. No new dependency without approval.
6. Reproduce one cell with `composer switch:lNN && composer test`. CI covers the full matrix.
7. Restore dependencies afterward and do not commit an accidental `composer.lock` change.

## Anti-Patterns

- Treating the local version as the whole matrix.
- Raising a constraint to avoid a compatibility branch.
- Removing a matrix cell or `switch:lNN` script for speed.

## Related

- [task-finalization](../task-finalization/SKILL.md)
