import { test, expect } from "@playwright/test";

test("State Persistence sanity checklist", async ({ page }) => {
    // Note: Generated with Playwright codegen.

    await page.goto("http://127.0.0.1:8000/demo/");

    await page.getByRole("button", { name: "inline-validation" }).click();
    await page.getByRole("button", { name: "shapes" }).click();
    await page.getByRole("button", { name: "POST /simple" }).click();
    await page.getByText('{ "name": "<placeholder>", "').click();
    await page.getByRole("button", { name: "Auto Fill" }).click();
    await page.getByRole("tab", { name: "Parameters" }).click();
    await page.getByRole("textbox", { name: "Key" }).click();
    await page.getByRole("textbox", { name: "Key" }).fill("key");
    await page.getByRole("textbox", { name: "Key" }).press("Tab");
    await page.getByRole("textbox", { name: "Value" }).fill("value-example");
    await page.getByRole("tab", { name: "Authorization" }).click();
    await page.getByRole("tab", { name: "Headers" }).click();
    await page.getByRole("button", { name: "Add" }).click();
    await page.getByTestId("kv-key").nth(3).click();
    await page.getByTestId("kv-key").nth(3).fill("x-new");
    await page.getByTestId("kv-key").nth(3).press("Tab");
    await page.getByTestId("kv-value").nth(3).fill("x-value");
    await page.getByRole("button", { name: "Send ( )" }).click();

    await page
        .getByTestId("response-content")
        .getByRole("tab", { name: "Headers" })
        .click();
    await page.getByRole("tab", { name: "Cookies" }).click();

    await page.reload();

    await expect(page.getByTestId("response-content").getByRole("tablist"))
        .toMatchAriaSnapshot(`
    - tablist:
      - tab "Response"
      - tab "Headers"
      - tab "Cookies" [selected]
    `);

    await expect(page.getByTestId("app-tabs-container")).toMatchAriaSnapshot(`
    - tablist:
      - tab "Parameters"
      - tab "Body"
      - tab "Authorization"
      - tab "Headers" [selected]
    `);

    await expect(page.getByTestId("kv-value").nth(3)).toHaveValue("x-value");

    await expect(page.locator("#reka-splitter-panel-v-6")).toMatchAriaSnapshot(`
    - list:
      - listitem:
        - button "authentication":
          - img
          - img
      - listitem:
        - button "dd":
          - img
          - img
      - listitem:
        - button "inline-validation" [expanded]:
          - img
          - img
        - list:
          - button "POST /complex"
          - button "POST /conditional"
          - button "POST /enum"
          - button "POST /simple"
      - listitem:
        - button "request-parameters":
          - img
          - img
      - listitem:
        - button "responses":
          - img
          - img
      - listitem:
        - button "segments":
          - img
          - img
      - listitem:
        - button "shapes" [expanded]:
          - img
          - img
        - list:
          - button "POST /array-of-primitives"
          - button "POST /nested-object"
      - listitem:
        - button "spatie-data":
          - img
          - img
      - listitem:
        - button "verbs":
          - img
          - img
    `);

    await page.getByRole("tab", { name: "Authorization" }).click();

    await page
        .getByTestId("response-content")
        .getByRole("tab", { name: "Headers" })
        .click();

    await page.getByRole("tab", { name: "Response" }).click();

    await page
        .getByTestId("response-content")
        .getByRole("tab", { name: "Headers" })
        .click();

    await page.getByRole("textbox", { name: "Type to search..." }).click();

    await page.getByRole("textbox", { name: "Type to search..." }).fill("user");

    await page.getByRole("button", { name: "authentication" }).click();

    await page.reload();

    await expect(page.locator("#reka-splitter-panel-v-6")).toMatchAriaSnapshot(`
    - text: Routes
    - list:
      - listitem:
        - button "authentication" [expanded]:
          - img
          - img
        - list:
          - button "GET /show-logged-in-user"
    `);

    await expect(page.getByTestId("response-content")).toMatchAriaSnapshot(`
    - tablist:
      - tab "Response"
      - tab "Headers" [selected]
      - tab "Cookies"
    `);

    await expect(page.getByTestId("app-tabs-container")).toMatchAriaSnapshot(`
    - tablist:
      - tab "Parameters"
      - tab "Body"
      - tab "Authorization" [selected]
      - tab "Headers"
    `);

    await expect(
        page.getByRole("textbox", { name: "Type to search..." }),
    ).toHaveValue("user");
});
