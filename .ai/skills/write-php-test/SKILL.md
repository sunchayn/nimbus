---
name: write-php-test
description: "Use when writing, fixing, or reviewing PHPUnit tests in Nimbus with Orchestra Testbench. Covers test kinds, placement, naming, block structure, and mocking."
license: MIT
metadata:
  author: sunchayn
---

# Write PHP Test

Every test has the same shape so a reader sees setup, exercise, and assertion at a glance.

## Workflow

1. Write the smallest failing test first, then the smallest passing change. Cover happy, failure, and edge cases.
2. Pick the kind.
   - Unit: no framework. Value objects, DTOs, helpers. Extends `PHPUnit\Framework\TestCase`. File ends `UnitTest.php`.
   - Functional: needs container, config, or routes. Actions, Services, commands, provider. Extends `Sunchayn\Nimbus\Tests\TestCase`. File ends `FunctionalTest.php`.
   - Integration: HTTP layer. Extends `Sunchayn\Nimbus\Tests\TestCase`. File ends `Test.php`, in `tests/Integration`.
3. Mirror `src/` by path under `tests/App` or `tests/Integration`. Add `#[CoversClass(...)]`.
4. One endpoint per Integration file. Add `test_it_integrates(): void` only when other tests in the file mock the endpoint's actions.
5. Name methods `test_it_does_the_thing(): void`. Describe behavior, not implementation.
6. Use `// Arrange`, `// Act`, `// Assert`, and `// Anticipate` only for mock expectations. One blank line after each marker and between independent statements. `// Act & Assert` only for one-line checks.
7. Resolve the subject with `resolve()`. Use `new` only in dependency-free Unit tests.
8. Prefer `$this->spy()` over `$this->mock()`. Create it before resolving the consumer. Assert with `shouldHaveReceived()`, and for arguments use a typed `withArgs()` callback that asserts and returns `true`.
9. Data providers return `Generator` with string keys and named values, one array item per line.
10. `Http::preventStrayRequests()` is on. Fake every HTTP call.
11. Iterate with `composer test -- --filter <name>`, finish with `composer test:parallel`.

## Example

```php
public function test_it_ignores_routes_when_applicable(): void
{
    // Arrange

    $ignoredRoutesServiceSpy = $this->spy(IgnoredRoutesService::class);

    $action = resolve(IgnoreRouteErrorAction::class);

    // Act

    $action->execute('api/users|["GET"]');

    // Assert

    $ignoredRoutesServiceSpy
        ->shouldHaveReceived('add')
        ->once()
        ->withArgs(function (string $uri, array $methods): bool {
            $this->assertEquals('api/users', $uri);

            return true;
        });
}
```

## Anti-Patterns

- Code outside the labeled blocks.
- Testing a collaborator's effects in the caller's test.
- `new` where the container resolves.
- Provider without `Generator` or string keys.
- Deleting or weakening a real test.
- Real network calls.

## Related

- [write-php-code](../write-php-code/SKILL.md)
- [task-finalization](../task-finalization/SKILL.md)
