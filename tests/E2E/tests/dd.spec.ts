import { test, expect } from "@playwright/test";

test("Dump and Die visualization sanity checklist", async ({ page }) => {
    // Note: Generated with Playwright codegen.

    await page.goto("http://127.0.0.1:8000/demo/");
    await page.getByRole("button", { name: "dd" }).click();
    await page.getByRole("button", { name: "GET /" }).click();
    await page.getByRole("tab", { name: "Headers" }).click();
    await page.getByTestId("kv-key").nth(3).click();
    await page.getByTestId("kv-key").nth(3).fill("x-index");
    await page.getByTestId("kv-value").nth(3).click();
    await page.getByTestId("kv-value").nth(3).fill("0");
    await page.getByTestId("endpoint-input").click();
    await page.waitForTimeout(300); // <- Wait for next tick.
    await page.getByRole("button", { name: "Send ( )" }).click();

    await expect(page.getByTestId("response-status-text")).toContainText(
        "Dump & Die",
    );

    const hasCorrectClass = await page
        .getByTestId("response-status-indicator")
        .evaluate((element) => element.classList.contains("text-violet-600"));
    expect(hasCorrectClass).toBe(true);

    await expect(page.getByLabel("Response")).toMatchAriaSnapshot(`
    - 'button "array: 4 items" [expanded]':
      - img
    - 'button "\\"users\\": array: 2 items"':
      - img
    - 'button "\\"pagination\\": <runtime object>: 4 properties"':
      - img
    - text: "\\"nullValue\\": null"
    - 'button "\\"callback\\": Closure()"':
      - img
    `);
    await page.getByTestId("kv-value").nth(3).click();
    await page.getByTestId("kv-value").nth(3).fill("1");
    await page.getByTestId("endpoint-input").click();
    await page.waitForTimeout(300); // <- Wait for next tick.
    await page.getByRole("button", { name: "Send ( )" }).click();
    await expect(page.getByLabel("Response")).toMatchAriaSnapshot(`
  - 'button "Illuminate\\\\Http\\\\Request: 31 properties" [expanded]':
    - img
    - text: "Illuminate\\\\Http\\\\Request: 31 properties"
  - 'button "+attributes: Symfony\\\\Component\\\\HttpFoundation\\\\ParameterBag: 1 property"':
    - text: "+attributes:"
    - img
    - text: "Symfony\\\\Component\\\\HttpFoundation\\\\ParameterBag: 1 property"
  - 'button "+request: Symfony\\\\Component\\\\HttpFoundation\\\\InputBag: 1 property"':
    - text: "+request:"
    - img
    - text: "Symfony\\\\Component\\\\HttpFoundation\\\\InputBag: 1 property"
  - 'button "+query: Symfony\\\\Component\\\\HttpFoundation\\\\InputBag: 1 property"':
    - text: "+query:"
    - img
    - text: "Symfony\\\\Component\\\\HttpFoundation\\\\InputBag: 1 property"
  - 'button "+server: Symfony\\\\Component\\\\HttpFoundation\\\\ServerBag: 1 property"':
    - text: "+server:"
    - img
    - text: "Symfony\\\\Component\\\\HttpFoundation\\\\ServerBag: 1 property"
  - 'button "+files: Symfony\\\\Component\\\\HttpFoundation\\\\FileBag: 1 property"':
    - text: "+files:"
    - img
    - text: "Symfony\\\\Component\\\\HttpFoundation\\\\FileBag: 1 property"
  - 'button "+cookies: Symfony\\\\Component\\\\HttpFoundation\\\\InputBag: 1 property"':
    - text: "+cookies:"
    - img
    - text: "Symfony\\\\Component\\\\HttpFoundation\\\\InputBag: 1 property"
  - 'button "+headers: Symfony\\\\Component\\\\HttpFoundation\\\\HeaderBag: 2 properties"':
    - text: "+headers:"
    - img
    - text: "Symfony\\\\Component\\\\HttpFoundation\\\\HeaderBag: 2 properties"
  - text: "#content: null #languages: null #charsets: null #encodings: null #acceptableContentTypes: null #pathInfo: \\"/_demo/dd\\" (9) #requestUri: \\"/_demo/dd\\" (9) #baseUrl: \\"\\" (0) #basePath: null #method: \\"GET\\" (3) #format: null #session: null #locale: null #defaultLocale: \\"en\\" (2) -preferredFormat: null -isHostValid: true -isForwardedValid: true -isSafeContentPreferred: ? bool (Uninitialized Prop)"
  - 'button "-trustedValuesCache: []" [disabled]':
    - text: "-trustedValuesCache:"
    - img
    - text: "[]"
  - text: "-isIisRewrite: false #json: null #convertedFiles: null"
  - 'button "#userResolver: Closure($guard = null)"':
    - text: "#userResolver:"
    - img
    - text: Closure($guard = null)
  - 'button "#routeResolver: Closure()"':
    - text: "#routeResolver:"
    - img
    - text: Closure()
    `);
    await page.getByRole("button", { name: "+attributes: Symfony\\" }).click();
    await expect(page.locator("#reka-collapsible-content-v-69"))
        .toMatchAriaSnapshot(`
    - 'button "#parameters: []" [disabled]':
      - img
    `);
    await page.getByTestId("kv-value").nth(3).click();
    await page.getByTestId("kv-value").nth(3).fill("2");
    await page.getByTestId("endpoint-input").click();
    await page.waitForTimeout(300); // <- Wait for next tick.
    await page.getByTestId("endpoint-input").click();
    await page.getByRole("button", { name: "Send ( )" }).click();
    await expect(page.getByTestId("dump-value-content")).toMatchAriaSnapshot(`
    - 'button /Illuminate\\\\Foundation\\\\Application: \\d+ properties/ [expanded]':
      - img
    - 'button /#resolved: array: \\d+ items/':
      - img
    - 'button /#bindings: array: \\d+ items/':
      - img
    - 'button "#methodBindings: []" [disabled]':
      - img
    - 'button /#instances: array: \\d+ items/':
      - img
    - 'button "#scopedInstances: array: 2 items"':
      - img
    - 'button /#aliases: array: \\d+ items/':
      - img
    - 'button /#abstractAliases: array: \\d+ items/':
      - img
    - 'button "#extenders: array: 1 item"':
      - img
    - 'button "#tags: []" [disabled]':
      - img
    - 'button "#buildStack: []" [disabled]':
      - img
    - 'button "#with: []" [disabled]':
      - img
    - 'button "+contextual: []" [disabled]':
      - img
    - 'button "+contextualAttributes: []" [disabled]':
      - img
    - 'button /#checkedForAttributeBindings: array: \\d+ items/':
      - img
    - 'button /#checkedForSingletonOrScopedAttributes: array: \\d+ items/':
      - img
    - 'button "#reboundCallbacks: array: 2 items"':
      - img
    - 'button "#globalBeforeResolvingCallbacks: []" [disabled]':
      - img
    - 'button "#globalResolvingCallbacks: []" [disabled]':
      - img
    - 'button "#globalAfterResolvingCallbacks: []" [disabled]':
      - img
    - 'button "#beforeResolvingCallbacks: array: 1 item"':
      - img
    - 'button "#resolvingCallbacks: array: 2 items"':
      - img
    - 'button "#afterResolvingCallbacks: array: 6 items"':
      - img
    - 'button "#afterResolvingAttributeCallbacks: []" [disabled]':
      - img
    - 'button "#environmentResolver: Illuminate\\\\Foundation\\\\Application::environment(...$environments)"':
      - img
    - text: "/#basePath: \\"\\\\/Volumes\\\\/Dev\\\\/nimbus-dev\\" \\\\(\\\\d+\\\\)/"
    - 'button "#registeredCallbacks: []" [disabled]':
      - img
    - text: "#hasBeenBootstrapped: true #booted: true"
    - 'button "#bootingCallbacks: array: 3 items"':
      - img
    - 'button "#bootedCallbacks: array: 2 items"':
      - img
    - 'button "#terminatingCallbacks: array: 1 item"':
      - img
    - 'button /#serviceProviders: array: \\d+ items/':
      - img
    - 'button /#loadedProviders: array: \\d+ items/':
      - img
    - 'button /#deferredServices: array: \\d+ items/':
      - img
    - text: "/#bootstrapPath: \\"\\\\/Volumes\\\\/Dev\\\\/nimbus-dev\\\\/bootstrap\\" \\\\(\\\\d+\\\\) #appPath: null #configPath: null #databasePath: null #langPath: \\"\\\\/Volumes\\\\/Dev\\\\/nimbus-dev\\\\/lang\\" \\\\(\\\\d+\\\\) #publicPath: null #storagePath: null #environmentPath: null #environmentFile: \\"\\\\.env\\" \\\\(4\\\\) #isRunningInConsole: false #namespace: null #mergeFrameworkConfiguration: true/"
    - 'button "#absoluteCachePathPrefixes: array: 2 items"':
      - img
    `);
    await page
        .getByRole("button", { name: "#absoluteCachePathPrefixes:" })
        .click();
    await expect(
        page.locator("#reka-collapsible-content-v-113"),
    ).toMatchAriaSnapshot(`- text: "0: \\"/\\" (1) 1: \\"\\\\\\" (1)"`);
    await page.getByTestId("kv-value").nth(3).click();
    await page.getByTestId("kv-value").nth(3).fill("3");
    await page.getByTestId("endpoint-input").click();
    await page.waitForTimeout(300); // <- Wait for next tick.
    await page.getByRole("button", { name: "Send ( )" }).click();
    await expect(page.getByLabel("Response")).toMatchAriaSnapshot(`
    - 'button "<runtime object>: 6 properties" [expanded]':
      - img
    - text: "/\\\\+id: \\\\d+ \\\\+name: \\"Laravel Framework Book\\" \\\\(\\\\d+\\\\) \\\\+price: \\\\d+\\\\.\\\\d+ \\\\+inStock: true/"
    - 'button "+tags: array: 4 items"':
      - img
    - 'button "+reviews: array: 1 item"':
      - img
    `);

    await page.getByTestId("kv-value").nth(3).click();
    await page.getByTestId("kv-value").nth(3).fill("4");
    await page.getByTestId("endpoint-input").click();
    await page.waitForTimeout(300); // <- Wait for next tick.
    await page.getByRole("button", { name: "Send ( )" }).click();
    await expect(page.getByLabel("Response")).toMatchAriaSnapshot(`
    - 'button /App\\\\Models\\\\User: \\d+ properties/ [expanded]':
      - img
    - text: "#connection: null #table: null #primaryKey: \\"id\\" (2) #keyType: \\"int\\" (3) +incrementing: true"
    - 'button "#with: []" [disabled]':
      - img
    - 'button "#withCount: []" [disabled]':
      - img
    - text: "/\\\\+preventsLazyLoading: false #perPage: \\\\d+ \\\\+exists: false \\\\+wasRecentlyCreated: false #escapeWhenCastingToString: false/"
    - 'button "#attributes: array: 9 items"':
      - img
    - 'button "#original: []" [disabled]':
      - img
    - 'button "#changes: []" [disabled]':
      - img
    - 'button "#previous: []" [disabled]':
      - img
    - 'button "#casts: array: 2 items"':
      - img
    - 'button "#classCastCache: []" [disabled]':
      - img
    - 'button "#attributeCastCache: []" [disabled]':
      - img
    - text: "#dateFormat: null"
    - 'button "#appends: []" [disabled]':
      - img
    - 'button "#dispatchesEvents: []" [disabled]':
      - img
    - 'button "#observables: []" [disabled]':
      - img
    - 'button "#relations: []" [disabled]':
      - img
    - 'button "#touches: []" [disabled]':
      - img
    - text: "#relationAutoloadCallback: null #relationAutoloadContext: null +timestamps: true +usesUniqueIds: false"
    - 'button "#hidden: array: 2 items"':
      - img
    - 'button "#visible: []" [disabled]':
      - img
    - 'button "#fillable: array: 3 items"':
      - img
    - 'button "#guarded: array: 1 item"':
      - img
    - text: "/#authPasswordName: \\"password\\" \\\\(8\\\\) #rememberTokenName: \\"remember_token\\" \\\\(\\\\d+\\\\)/"
    `);
    await page
        .getByRole("button", { name: "#attributes: array: 9 items" })
        .click();
    await page
        .getByRole("button", { name: '"two_factor_confirmed_at":' })
        .click();

    await expect(
        page.locator("#reka-collapsible-content-v-122"),
    ).toMatchAriaSnapshot(
        `- text: "\\"name\\": \\"Dr. Major Willms Sr.\\" (20) \\"email\\": \\"farrell.ryley@example.com\\" (25) \\"email_verified_at\\": \\"2026-01-03 22:54:39\\" (19) \\"password\\": \\"$2y$12$oW6OxRs//G14H8mPrL.2/eyeDNAOOLSp4vQ7bJ.LkA83Zw.MuuCVq\\" (60) \\"remember_token\\": \\"Euycju51eF\\" (10) \\"two_factor_secret\\": \\"zTSvlt8tdp\\" (10) \\"two_factor_recovery_codes\\": \\"KqosMgNT1s\\" (10)"
- 'button "\\"two_factor_confirmed_at\\": Illuminate\\\\Support\\\\Carbon: 17 properties" [expanded]':
  - text: "\\"two_factor_confirmed_at\\":"
  - img
  - text: "Illuminate\\\\Support\\\\Carbon: 17 properties"
- text: "#endOfTime: false #startOfTime: false #constructedObjectId: \\"00000000000001e10000000000000000\\" (32) -clock: null #localMonthsOverflow: null #localYearsOverflow: null #localStrictModeEnabled: null #localHumanDiffOptions: null #localToStringFormat: null #localSerializer: null #localMacros: null #localGenericMacros: null #localFormatFunction: null #localTranslator: null"
- 'button "#dumpProperties: array: 3 items"':
  - text: "#dumpProperties:"
  - img
  - text: "array: 3 items"
- text: "#dumpLocale: null #dumpDateProperties: null"
- 'button "\\"emptyObject\\": {}" [disabled]':
  - text: "\\"emptyObject\\":"
  - img
  - text: "{}"`,
    );

    await page.getByTestId("kv-value").nth(3).click();
    await page.getByTestId("kv-value").nth(3).fill("5");
    await page.getByTestId("endpoint-input").click();
    await page.waitForTimeout(300); // <- Wait for next tick.
    await page.getByRole("button", { name: "Send ( )" }).click();

    await expect(
        page.getByTestId("dump-value-0").getByTestId("dump-value-title"),
    ).toContainText("Dump #1");
    await expect(
        page.getByTestId("dump-value-0").getByTestId("dump-value-content"),
    ).toContainText(/ ".+" \(\d+\)/);

    await expect(
        page.getByTestId("dump-value-1").getByTestId("dump-value-title"),
    ).toContainText("Dump #2");
    await expect(
        page.getByTestId("dump-value-1").getByTestId("dump-value-content"),
    ).toContainText(/\d+/);


    await expect(
        page.getByTestId("dump-value-2").getByTestId("dump-value-title"),
    ).toContainText("Dump #3");
    await expect(
        page.getByTestId("dump-value-2").getByTestId("dump-value-content"),
    ).toContainText('null');

    await expect(
        page.getByTestId("dump-value-3").getByTestId("dump-value-title"),
    ).toContainText("Dump #4");
    await expect(
        page.getByTestId("dump-value-3").getByTestId("dump-value-content"),
    ).toContainText('true');

    await expect(
        page.getByTestId("dump-value-4").getByTestId("dump-value-title"),
    ).toContainText("Dump #5");
    await expect(
        page.getByTestId("dump-value-4").getByTestId("dump-value-content"),
    ).toContainText('false');

    await page.getByTestId("next-dump-button").click();
    await expect(page.getByLabel("Response")).toMatchAriaSnapshot(`
    - img
    - text: "/App\\\\\\\\Models\\\\\\\\User: \\\\d+ properties/"
    `);
    await page.getByTestId("next-dump-button").click();
    await expect(page.getByLabel("Response")).toMatchAriaSnapshot(`
    - img
    - text: "<runtime object>: 6 properties"
    `);
    await page.getByTestId("next-dump-button").click();
    await expect(page.getByLabel("Response")).toMatchAriaSnapshot(`
    - img
    - text: "/Illuminate\\\\\\\\Foundation\\\\\\\\Application: \\\\d+ properties/"
    `);
    await page.getByTestId("next-dump-button").click();
    await expect(page.getByLabel("Response")).toMatchAriaSnapshot(`
    - img
    - text: "/Illuminate\\\\\\\\Http\\\\\\\\Request: \\\\d+ properties/"
    `);
    await page.getByTestId("next-dump-button").click();
    await expect(page.getByLabel("Response")).toMatchAriaSnapshot(`
    - img
    - text: "array: 4 items"
    `);
    await page.getByTestId("previous-dump-button").click();
    await page.getByTestId("previous-dump-button").click();
    await page.getByTestId("delete-dump-button").click();
    await page.getByTestId("delete-dump-button").click();
    await expect(page.getByLabel("Response")).toMatchAriaSnapshot(
        `- text: 4 / 5`,
    );
    await expect(page.getByLabel("Response")).toMatchAriaSnapshot(`
    - img
    - text: "/Illuminate\\\\\\\\Http\\\\\\\\Request: \\\\d+ properties/"
    `);
    await page.getByTestId("next-dump-button").click();
    await expect(page.getByLabel("Response")).toMatchAriaSnapshot(`
    - img
    - text: "array: 4 items"
    `);
    await page.getByTestId("previous-dump-button").click();
    await page.getByTestId("previous-dump-button").click();
    await expect(page.getByLabel("Response")).toMatchAriaSnapshot(`
    - img
    - text: "<runtime object>: 6 properties"
    `);
});
