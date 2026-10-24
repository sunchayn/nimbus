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
   - `components/base`: thin `App` wrappers over Nuxt UI, exported from `index.ts`. Only this folder imports `@nuxt/ui`.
   - `components/common`: reusable, domain-agnostic.
   - `components/domain`: feature components, grouped by feature.
   - `components/layout`: panels and navigation.
   - `pages`: route views that compose components.
   - `composables`, `stores`: reactive logic and Pinia state. API access and business logic go here or in domain components.
3. Use `<script setup lang="ts">`, then `<template>`, then `<style>` if needed.
4. Keep state minimal, derive with `computed`, split components with more than one responsibility.
5. Type everything. No `any`. Use `import type`. Import with `@/` (js) and `~/` (css).
6. Backend types come from `@/interfaces/generated`. Never edit it. Run `php bin/intellisense` after backend changes.
7. Tailwind v4 with Nuxt UI. Visual decisions live in `tools/ui/theme.js` and `resources/css/themes/base.css`, never in a wrapper. The preset reads tokens only, run `npm run check:theme` after editing it. Use `gap` for sibling spacing and existing utilities like `h-toolbar` and `p-panel`.
   - Use semantic utilities such as `bg-default`, `bg-muted`, `text-muted`, `text-highlighted`, `border-default`, and the named token utilities such as `h-control-md`, `gap-base`, `p-card`, `shadow-overlay` and `z-overlay`.
   - Never write a variable class such as `gap-(--gap-base)`. If a token has no utility, register it in the `@theme inline` block of `base.css`, or add an `@utility` in `app.css` when Tailwind has no namespace for it. Then add the name to `twMergeConfig` in `tools/ui/theme.js`.
   - No raw palette classes such as `zinc` or `white`, and no arbitrary values for size, radius, shadow, or z-index. The semantic utilities already follow dark mode.
   - Icons use full literal names such as `i-hugeicons-cancel-01`, through `AppIcon` or an `icon` prop. Never build a name from parts.
8. Run `npm run style:fix` instead of formatting by hand.
9. Add `data-testid` to elements tests need.
10. Apply `write-comments`.

## Testing

1. Vitest tests go in `resources/js/tests`, mirroring the source path. Start from `resources/js/tests/_templates/component.test.template.ts`.
2. Test behavior, never class names. Select by `data-testid` or role. Mock API calls and stores. Use `createPinia()` and `setActivePinia()` for stores.
3. Nuxt UI is installed globally in `resources/js/tests/setup.ts`. Overlays portal to `document.body`, so assert on `document.body` or `screen`.
4. Cross-page flows get a Playwright spec in `tests/E2E/tests`, using `tests/E2E/core/fixtures.ts` and `tests/E2E/pages`.
5. Iterate with `npm run test:run -- <file>`.

## Anti-Patterns

- Business logic or API calls in pages or base components.
- Margins between siblings where `gap` works.
- No dark mode support.
- Importing `@nuxt/ui` outside `components/base`.
- Raw palette classes, arbitrary values, or class name assertions in tests.
- Editing `interfaces/generated`.
- `any` or non-null assertions to silence the type checker.

## Related

- [write-comments](../write-comments/SKILL.md)
- [task-finalization](../task-finalization/SKILL.md)
