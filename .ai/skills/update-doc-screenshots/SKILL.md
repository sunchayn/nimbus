---
name: update-doc-screenshots
description: "Use after a UI change to re-capture the screenshots in wiki/user-guide with Playwright and fix any wording that no longer matches the interface. Works for all features or a chosen few."
license: MIT
metadata:
  author: sunchayn
---

# Update Doc Screenshots

Screenshots in `wiki/user-guide/assets` are produced by `capture.mjs`, which sits next to this file. It drives the demo app (the `workbench/` app) with Playwright, one feature at a time. The script is part of this skill, so keep it in sync with the UI.

## Prerequisites

1. The workbench answers on `http://127.0.0.1:8000/demo`. Check with `curl -sI http://127.0.0.1:8000/demo`. If it is down, ask the user to start it with `composer dev`.
2. `composer dev` also runs the Vite dev server, so the page loads the current source. Never run `npm run build` and never touch `resources/dist`.
3. When the workbench runs on another port, pass it with `--base`, for example `--base http://127.0.0.1:8110/demo`.
4. Playwright browsers are installed (`npx playwright install chromium`).

## Workflow

1. Pick the features to refresh from the table below. Use `git diff --name-only` to see which components changed. Refresh everything only when the layout or theme changed.
2. Adjust the script first when the change needs it, see [Adjusting the Script](#adjusting-the-script).
3. Run `node .ai/skills/update-doc-screenshots/capture.mjs --only <feature,feature>`. Omit `--only` to refresh all features. Add `--out <dir>` to write somewhere else first.
4. Every shot prints `ok` or `FAIL`. Fix a failing shot by updating the script, then rerun only that feature.
5. Open every changed image with the Read tool and check it. Look for a clipped menu or popover, the wrong tab, an error banner, leftover state, or a crop that cuts content.
6. Adjust the clip rectangle in the script when a crop is off, then rerun that feature. Clips are `[x, y, width, height]` in CSS pixels on a 1440x900 viewport.
7. Compare each affected README section with the new image. Update wording that names a button, menu item, location, or step that no longer matches. Keep sentences short and plain.
8. Run `git status` and confirm only the intended assets and README text changed.

## Features

| Feature | Images | Source folders that affect it |
|---|---|---|
| `layout` | `empty-client-sections`, `request-builder`, `applications-switcher`, `routes` | `components/layout`, `components/domain/RoutesExplorer`, `components/domain/Client/ClientWorkspace.vue` |
| `schema` | `schema-validation`, `auto-fill`, `auth-options` | `Client/Request/RequestBody`, `Client/Request/RequestAuthorization` |
| `generator` | `value-generator` | `components/common/ValueGenerator`, `GeneratorInput.vue` |
| `response` | `response`, `cookies`, `decrypted-cookies`, `response-download` | `Client/Response` |
| `dd` | `dd-viewer`, `dd-viewer-pagination` | `Client/Response/ResponseBody` |
| `history` | `history-viewer` | `Client/Response` history components |
| `environments` | `env-page`, `env-variables-client-different-states`, `env-variables-resolved-value` | `EnvironmentAwareInput.vue`, environment page, `AppTopBar.vue` |
| `request-options` | `transaction-mode`, `export-to-curl`, `shareable-link`, `curl-command-export`, `shareable-link-2` | `Client/Request/RequestBuilder` |
| `openapi` | `open-api-support` | `components/domain/RoutesExplorer` |

## Adjusting the Script

Treat `capture.mjs` as code that follows the UI. Read the diff of the UI change, then update the script before running it.

1. A renamed or moved button, tab, menu item, or test id breaks a selector. Update every use in the script, not only the one that failed.
2. A new user-facing feature or a new documented screen needs a new shot. Add the steps to the matching feature function, or add a new feature and register it in `FEATURES`.
3. A removed feature needs its shot, its feature function, and its README image link removed. Delete the asset file too.
4. A changed flow, such as an extra click to reach a menu, needs the steps updated so the shot still reaches the same state.
5. A changed layout may need new clip rectangles or new overlay boxes.
6. Prefer `getByRole` and `data-testid` selectors, the same ones the E2E specs use.
7. Name each file after the image in the README so no link changes.
8. Wrap each shot in `step`, so one failure never blocks the others.
9. Follow `write-comments` for any comment in the script.
10. After any edit, update the feature table in this file, then run the touched feature and open its images.

## Known Limits

- `env-variables-resolved-value` needs a hover tooltip that did not open under headless Chromium. Always check the image. If the tooltip is missing, keep the old file and tell the user.
- A fresh browser context has no logged-in user, so the auth menu image would show a "Please log in first" notice.
  The script avoids it with `open(browser, '', { login: true })`, which visits `/dev/login-as-new-user` first.
  Use `/dev/login-as/{id}` instead to log in as a seeded user. Use `login: true` for any shot that needs a logged-in user.
- The images contain generated data such as names and UUIDs. Do not diff them for content, only for layout.

## Anti-Patterns

- Refreshing every image for a change that touches one feature.
- Trusting the `ok` output without opening the image.
- Editing `wiki/` for anything except screenshots and the wording they require. Larger guide changes need an explicit request.
- Committing a `resources/dist` change or a build made for the screenshots.

## Related (when adjusting the script)

- [write-comments](../write-comments/SKILL.md)
- [write-frontend-code](../write-frontend-code/SKILL.md)
- [task-finalization](../task-finalization/SKILL.md)
