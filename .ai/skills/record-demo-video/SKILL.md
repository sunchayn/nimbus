---
name: record-demo-video
description: "Use after a UI change to re-record the Nimbus demo video with Playwright and replace art/nimbus-demo-video.gif. Describes the storyline to perform and how to adjust the recording script."
license: MIT
metadata:
  author: sunchayn
---

# Record Demo Video

The demo GIF at `art/nimbus-demo-video.gif` is embedded in the root `README.md`. It is produced by `record.mjs`, which sits next to this file. The script records the demo app in Chromium, then converts the video to a GIF with `ffmpeg`. The script is part of this skill, so keep it in sync with the UI and the storyline.

## Prerequisites

1. The demo app answers on `http://127.0.0.1:8000/demo`. Check with `curl -sI http://127.0.0.1:8000/demo`. If it is down, ask the user to start it, for example with `php tests/E2E/.scripts/bin launch`.
2. The Vite dev server runs (`npm run dev`), so the page loads the current source. Never run `npm run build` and never touch `resources/dist`.
3. `ffmpeg` is on the PATH and Playwright browsers are installed (`npx playwright install chromium`).

## Storyline

The video shows the whole loop in about 12 seconds. A grey dot follows the mouse and shrinks on click. Each beat ends with a short pause so the viewer can read it.

1. **Start** on the empty client with the Route Explorer on the left.
2. **Open a resource group.** Click `shapes`.
3. **Open a route.** Click `POST /nested-object`. The body is prefilled from the validation rules and the invalid placeholder values are flagged in the gutter.
4. **Auto Fill.** Click Auto Fill. The placeholders become realistic values.
5. **Send.** Click Send. A `201 - Created` response appears with its JSON body.
6. **Headers.** Click the Headers tab of the response panel.
7. **Cookies.** Click the Cookies tab of the response panel.

Keep the story short and linear. Do not add steps unless the README promises a headline feature the video does not show.

## Workflow

1. Compare the storyline with the current UI. Use `git diff --name-only` and open the app to confirm every beat still exists and reads well.
2. Adjust the script when needed, see [Adjusting the Script](#adjusting-the-script).
3. Record a draft without touching the repo with `node .ai/skills/record-demo-video/record.mjs --out <tmp>/draft.gif`.
4. Inspect the draft. Extract frames with `ffmpeg -i draft.gif -vf "fps=1.3,scale=640:-1,tile=4x4" -frames:v 1 contact.png` and open the image with the Read tool. Check that every beat is visible, nothing is clipped, no error banner shows, and the clip ends on the Cookies tab.
5. Check the size. The target is under 6 MB and about 10 to 14 seconds. Lower `FPS` or shorten pauses in the script when it is larger.
6. Record the final GIF with `node .ai/skills/record-demo-video/record.mjs`. It overwrites `art/nimbus-demo-video.gif`.
7. Run `git status` and confirm only the GIF changed, plus the script or this file when they were adjusted.
8. Update the storyline above when it changed. Update the `README.md` text near the GIF only when the story no longer matches it.

## Adjusting the Script

Treat `record.mjs` as code that follows the UI. Read the diff of the UI change, then update the script before recording.

1. A renamed or moved button, tab, or route breaks a selector. Update every use in the script.
2. A changed flow, such as an extra click to reach a menu, needs the steps updated so the story still reaches the same state.
3. A new headline feature may deserve a new beat. Add a numbered block inside `play`, with a short pause after it, and add the beat to the storyline above.
4. A removed feature needs its beat removed from both the script and the storyline.
5. Always go through the `click` helper so the cursor glides to the target and the dot shows the press. Do not use `locator.click()`.
6. The two tab lists are told apart by position, the response panel is `getByRole('tablist').nth(1)`. Update this when the layout changes.
7. Prefer `getByRole` and `data-testid` selectors, the same ones the E2E specs use.
8. Follow `write-comments` for any comment in the script.
9. After any edit, record a draft and inspect it before replacing the GIF.

## Tuning

- `VIEWPORT` sets the GIF width. The README displays it at its natural size, so keep the width near 1280.
- `FPS` and the pauses inside `play` drive file size and duration.
- `--keep-video` also saves the raw `.webm` next to the GIF for debugging.
- The first seconds of page load are cut automatically, so the GIF starts on a ready page.

## Anti-Patterns

- Replacing the GIF without watching the draft frames.
- Recording against a stale dev server or a built `resources/dist`.
- Adding many beats. A longer GIF is a bigger file and fewer viewers reach the end.
- Committing the raw `.webm`.

## Related

- [update-doc-screenshots](../update-doc-screenshots/SKILL.md)
- [write-comments](../write-comments/SKILL.md)
