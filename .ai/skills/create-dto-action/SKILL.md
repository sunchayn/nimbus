---
name: create-dto-action
description: "Use when adding a single-purpose operation to a Nimbus module. Covers action inputs, plain typed parameters, and when a DTO is worth it. Use with scaffold-module."
license: MIT
metadata:
  author: sunchayn
---

# Create DTO and Action

One operation, one Action, one `execute()`. Pass plain typed parameters when the input is a few values. Add a DTO only when the input has many fields, is reused, or needs resolving.

## Workflow

1. Name it `VerbNounAction` in `src/Modules/<Domain>/Actions/`.
2. One public method `execute()`. Take plain typed parameters for up to about four values, and let the controller read them from the request. Do not wrap them in a DTO.
3. For many fields, or an input several callers build, create `<Name>Data` in `DataTransferObjects/` as a class with `public readonly` promoted properties. Use `readonly class` only for a DTO that no Action or Service returns, because tests mock those and Mockery cannot subclass a readonly class.
4. Add a static factory like `fromRequest()` that resolves and casts input, so `execute()` gets ready values.
5. Return a DTO, value object, or primitive. Do not leak module internals.
6. Throw a domain exception from `Exceptions/` for expected failures. The controller converts it to a response.
7. Add a `/** @final */` docblock, not the `final` keyword, so tests can spy.
8. Inject into the controller and call with named arguments, one per line, like `$action->execute(folder: $folder, newName: $newName)`.
9. Test with `write-php-test`. Assert the Action's own result.

## Examples

- `RenameWorkspaceFolderAction::execute(FolderPath $folder, FolderPath $newName)`, plain inputs.
- `SaveRequestFileAction::execute(SaveRequestFileData $data)`, a DTO because it carries many fields.
- `BuildCurrentUserAction::execute()`, no input, no DTO.

## Anti-Patterns

- More than one public method.
- A DTO that only renames two or three parameters.
- Reading the request or global state inside the Action.
- Mutable DTO or DTO with setters.
- DTO wrapping one scalar.
- An Action that only forwards to a Service.

## Related

- [scaffold-module](../scaffold-module/SKILL.md)
- [write-php-code](../write-php-code/SKILL.md)
- [write-php-test](../write-php-test/SKILL.md)
