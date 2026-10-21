---
name: write-frontend-code
description: "Use when writing, reviewing, or testing Vue 3, TypeScript, and Tailwind v4 code under resources/js and resources/css in Nimbus."
license: MIT
metadata:
  author: sunchayn
---

# Write Frontend Code

Put code in the right folder, type it strictly, style it with the existing Tailwind setup, and test behavior.

## Workflow

1. Read the target folder's `README.md` first.
2. Place by role.
   - `components/base`: style-only primitives, mostly shadcn-vue wrappers, `App` prefix, exported from `index.ts`.
   - `components/common`: reusable, domain-agnostic.
   - `components/domain`: feature components, grouped by feature.
   - `components/layout`: panels and navigation.
   - `pages`: route views that compose components.
   - `composables`, `stores`: reactive logic and Pinia state. API access and business logic go here or in domain components.
3. Use `<script setup lang="ts">`, then `<template>`, then `<style>` if needed.
4. Keep state minimal, derive with `computed`, split components with more than one responsibility.
5. Type everything. No `any`. Use `import type`. Import with `@/` (js) and `~/` (css).
6. Backend types come from `@/interfaces/generated`. Never edit it. Run `php bin/intellisense` after backend changes.
7. Tailwind v4. Config is `tools/tailwind/tailwind.config.js`, loaded from `resources/css/app.css`. Use `gap` for sibling spacing, `dark:` for dark mode, and existing utilities like `.h-toolbar` and `.p-panel`.
8. Run `npm run style:fix` instead of formatting by hand.
9. Add `data-testid` to elements tests need.
10. Apply `write-comments`.

## Testing

1. Vitest tests go in `resources/js/tests`, mirroring the source path. Start from `resources/js/tests/_templates/component.test.template.ts`.
2. Test interactions and outcomes. Select by `data-testid`. Mock API calls and stores. Use `createPinia()` and `setActivePinia()` for stores.
3. Cross-page flows get a Playwright spec in `tests/E2E/tests`, using `tests/E2E/core/fixtures.ts` and `tests/E2E/pages`.
4. Iterate with `npm run test:run -- <file>`.

## Anti-Patterns

- Business logic or API calls in pages or base components.
- Margins between siblings where `gap` works.
- No dark mode support.
- Editing `interfaces/generated`.
- `any` or non-null assertions to silence the type checker.

## Related

- [write-comments](../write-comments/SKILL.md)
- [task-finalization](../task-finalization/SKILL.md)
