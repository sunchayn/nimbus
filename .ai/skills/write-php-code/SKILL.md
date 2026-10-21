---
name: write-php-code
description: "Use when writing or refactoring PHP in Nimbus. Covers strict typing, constructor promotion, error handling, and when an abstraction is justified. For file placement use scaffold-module."
license: MIT
metadata:
  author: sunchayn
---

# Write PHP Code

Plain Laravel package code. Strictly typed, explicit, no more abstract than needed.

## Workflow

1. Start new files with `declare(strict_types=1);`.
2. Use constructor promotion. No empty `__construct()`.
3. Type every parameter and return. Use PHPDoc shapes like `array<string, mixed>` where needed. Code must pass PHPStan level 8.
4. Always use braces for control structures.
5. Inject dependencies and let the container resolve them. Use `resolve()` only in static factories.
6. Prefer `Arr`, `Str`, and `Collection` over hand-rolled code.
7. Use `match` over nested ternaries or long `if`/`elseif`. Enum cases are TitleCase.
8. DTOs and value objects are `readonly`. Split a class that gains a second reason to change.
9. Throw domain exceptions for expected failures, not `null`.
10. Add a Strategy class for interchangeable behavior families like extraction, not a new branch.
11. Never run host code during extraction. Use the AST modules.
12. Apply `write-comments`. Run `composer style:fix` instead of formatting by hand.

## Anti-Patterns

- A class, interface, or option for one current use case.
- `final` on a class tests spy on. Use `/** @final */` on Actions.
- Ignoring a PHPStan error when a type fix works.
- `env()` outside `config/nimbus.php`.
- Using another module's internals.

## Related

- [write-comments](../write-comments/SKILL.md)
- [scaffold-module](../scaffold-module/SKILL.md)
- [create-dto-action](../create-dto-action/SKILL.md)
- [write-php-test](../write-php-test/SKILL.md)
- [task-finalization](../task-finalization/SKILL.md)
