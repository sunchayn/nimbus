---
name: create-dto-action
description: "Use when adding a single-purpose operation to a Nimbus module. Pairs an Action with the DTO that feeds it. Use with scaffold-module."
license: MIT
metadata:
  author: sunchayn
---

# Create DTO and Action

One operation, one Action, one `execute()`. Add a DTO only when input has several values or needs resolving.

## Workflow

1. Name it `VerbNounAction` in `src/Modules/<Domain>/Actions/`.
2. One public method `execute()` with one parameter. A single scalar stays a scalar, no DTO.
3. For several inputs, create `<Name>Data` in `DataTransferObjects/` as a `readonly class` with constructor promotion.
4. Add a static factory like `fromRequest()` that resolves and casts input, so `execute()` gets ready values.
5. Return a DTO, value object, or primitive. Do not leak module internals.
6. Throw a domain exception from `Exceptions/` for expected failures. The controller converts it to a response.
7. Add a `/** @final */` docblock, not the `final` keyword, so tests can spy.
8. Inject into the controller and call with a named argument, `$action->execute(data: $data)`.
9. Test with `write-php-test`. Assert the Action's own result.

## Examples

- `IgnoreRouteErrorAction::execute(IgnoreRouteErrorData $data)`, DTO built from the request.
- `BuildCurrentUserAction::execute()`, no input, no DTO.

## Anti-Patterns

- More than one public method or parameter.
- Reading the request or global state inside the Action.
- Mutable DTO or DTO with setters.
- DTO wrapping one scalar.
- An Action that only forwards to a Service.

## Related

- [scaffold-module](../scaffold-module/SKILL.md)
- [write-php-code](../write-php-code/SKILL.md)
- [write-php-test](../write-php-test/SKILL.md)
