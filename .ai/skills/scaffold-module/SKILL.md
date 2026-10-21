---
name: scaffold-module
description: "Use when adding a capability or domain module to Nimbus. Covers where files go under src/Modules, src/Http, src/Console and how they are wired."
license: MIT
metadata:
  author: sunchayn
---

# Scaffold Module

Add exactly the files the capability needs now, wired with explicit Laravel APIs.

## Workflow

1. Read the closest existing domain, `NimbusServiceProvider`, and the route files. Decide on subagent delegation first, per GUIDELINES.md.
2. Prefer an existing module. Add a new PascalCase module only when no domain owns the behavior.
3. Create only the subfolder the first class needs.
4. Add an HTTP layer only if reachable over HTTP. Controller, request, and resource go in `src/Http/Api/<Domain>`, a browser page controller in `src/Http/Web/Controllers`. Register the route in `routes/api.php` or `routes/web.php`.
5. Controller takes a Form Request, calls one Action with a DTO, returns a resource. No logic in it.
6. Register through `NimbusServiceProvider`. Guard `commands()` with `runningInConsole()`. No `env()` outside `config/nimbus.php`.
7. Test with `write-php-test`, update the wiki if behavior or architecture changed, finish with `task-finalization`.

## Module subfolders

- `Actions/`, `DataTransferObjects/`: see `create-dto-action`.
- `Services/`: reusable logic or stateful helpers, never a full workflow. Strategy families live in `Services/<Area>/Strategies` with `Contracts`.
- `ValueObjects/`: immutable values.
- `Contracts/`: cross-module interfaces.
- `Queries/`: read-only AST helpers like `ClassQuery`. Never Eloquent.
- `Collections/`, `Factories/`, `Enums/`, `Exceptions/`: only when one exists.

One discrete operation returning a result is an Action. Reusable logic or state is a Service.

## Examples

- Command: class in `src/Console`, registered in the provider inside `runningInConsole()`.
- Endpoint for `Export`: `src/Http/Api/Export/*`, route in `routes/api.php`, logic in `src/Modules/Export/Actions`.
- New extraction approach: add a class in `Extractor/Services/Request/Strategies` implementing the strategy contract.

## Anti-Patterns

- Creating empty subfolders up front.
- HTTP classes for logic never exposed over HTTP.
- Logic in controllers or the provider.
- Using another module's internals.
- Running host code during extraction.
- A factory for something the container resolves.

## Related

- [create-dto-action](../create-dto-action/SKILL.md)
- [write-php-code](../write-php-code/SKILL.md)
- [write-php-test](../write-php-test/SKILL.md)
- [task-finalization](../task-finalization/SKILL.md)
