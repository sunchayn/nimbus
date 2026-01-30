import { test, expect, Page } from "@playwright/test";

test("Dump and Die visualization sanity checklist", async ({ page }) => {
    await page.goto("http://127.0.0.1:8000/demo/");

    //  helpers

    const sendRequestWithIndex = async (index: string) => {
        const value = page.getByTestId("kv-value").nth(3);
        await value.fill(index);

        await page.getByTestId("endpoint-input").click();
        await page.waitForTimeout(300); // <- Wait for next tick.

        await page.getByRole("button", { name: "Send ( )" }).click();

        await expect(page.getByTestId("response-status-text")).toContainText(
            "Dump & Die",
        );
    };

    const response = () => page.getByLabel("Response");

    const expectResponseHeader = async (pattern: RegExp | string) => {
        await expect(response()).toContainText(pattern);
    };

    const expectDumpValue = async (
        dumpIndex: number,
        expected: RegExp | string,
    ) => {
        const dump = page
            .getByTestId(`dump-value-${dumpIndex}`)
            .getByTestId("dump-value-content");

        await expect(dump).toContainText(expected);
    };

    const expectExpandableInDump = (
        dumpIndex: number,
        label: RegExp | string,
    ) => {
        const expectExpandableInDump = async (
            dumpIndex: number,
            label: string | RegExp,
        ) => {
            // Narrow scope by first selecting the dump container
            const dumpContainer = page.getByTestId(`dump-value-${dumpIndex}`);

            // Use getByRole within container and match exact text if possible
            const button = dumpContainer.getByRole("button", {
                name: label,
                exact: true,
            });

            await expect(button).toBeVisible();
        };
    };

    //  initial setup

    await page.getByRole("button", { name: "dd" }).click();
    await page.getByRole("button", { name: "GET /" }).click();
    await page.getByRole("tab", { name: "Headers" }).click();
    await page.getByRole("button", { name: "Add" }).click();
    const headerKey = page.getByTestId("kv-key").nth(3);
    await headerKey.fill("x-index");

    await sendRequestWithIndex("0");

    //  status assertions

    await expect(page.getByTestId("response-status-indicator")).toHaveClass(
        /text-violet-600/,
    );

    //  dump #0 (array root)

    await expectExpandableInDump(0, "array: 4 items");
    await expectExpandableInDump(0, /"users": array: \d+ items/);
    await expectExpandableInDump(0, /"pagination": <runtime object>/);
    await expect(response()).toContainText('"nullValue": null');
    await expectExpandableInDump(0, /"callback": Closure/);

    //  dump #1 (request object)

    await sendRequestWithIndex("1");

    await expectExpandableInDump(0, /Illuminate\\Http\\Request:/);
    await expectExpandableInDump(0, /\+attributes:/);
    await expectExpandableInDump(0, /\+headers:/);
    await expectResponseHeader(/#method:\s*"GET"/);

    // Expand attributes and verify content exists
    await page
        .getByTestId("dump-value-0")
        .getByRole("button", { name: /\+attributes:/ })
        .click();
    await expect(response()).toContainText("#parameters:");

    //  dump #2 (application container)

    await sendRequestWithIndex("2");

    await expectExpandableInDump(0, /Illuminate\\Foundation\\Application:/);
    await expectExpandableInDump(0, /#bindings: array:/);
    await expectExpandableInDump(0, /#instances: array:/);
    await expectExpandableInDump(0, /#serviceProviders: array:/);

    // Expand a leaf
    await page
        .getByTestId("dump-value-0")
        .getByRole("button", { name: "#absoluteCachePathPrefixes:" })
        .click();

    await expect(
        page.locator("#reka-collapsible-content-v-146"),
    ).toMatchAriaSnapshot(`- text: "0: \\"/\\" (1) 1: \\"\\\\\\" (1)"`);

    //  dump #3 (runtime object)

    await sendRequestWithIndex("3");

    await expectExpandableInDump(0, "<runtime object>: 6 properties");
    await expect(response()).toContainText(/\+id:\s*\d+/);
    await expect(response()).toContainText(
        /\+name:\s*"Laravel Framework Book"/,
    );
    await expect(response()).toContainText(/\+price:\s*\d+\.\d+/);
    await expect(response()).toContainText(/\+inStock:\s*true/);

    //  dump #4 (eloquent model)

    await sendRequestWithIndex("4");

    await expectExpandableInDump(0, /App\\Models\\User:/);
    await expectExpandableInDump(0, /#attributes: array: \d+ items/);
    await expectExpandableInDump(0, /#casts: array:/);
    await expect(response()).toContainText(/#primaryKey:\s*"id"/);

    // Expand attributes and carbon value
    await page
        .getByTestId("dump-value-0")
        .getByRole("button", { name: /#attributes:/ })
        .click();
    await page
        .getByTestId("dump-value-0")
        .getByRole("button", { name: '"two_factor_confirmed_at":' })
        .click();

    await expect(response()).toContainText(/Illuminate\\Support\\Carbon/);
    await expect(response()).toContainText(/#endOfTime:\s*false/);

    //  dump #5 (scalar dumps list)

    await sendRequestWithIndex("5");

    await expectDumpValue(0, /".+"\s+\(\d+\)/);
    await expectDumpValue(1, /^\d+$/);
    await expectDumpValue(2, "null");
    await expectDumpValue(3, "true");
    await expectDumpValue(4, "false");

    //  navigation

    await page.getByTestId("next-dump-button").click();
    await expectResponseHeader(/App\\Models\\User:/);

    await page.getByTestId("next-dump-button").click();
    await expectResponseHeader("<runtime object>: 6 properties");

    await page.getByTestId("next-dump-button").click();
    await expectResponseHeader(/Illuminate\\Foundation\\Application:/);

    //  deletion

    await page.getByTestId("delete-dump-button").click();
    await page.getByTestId("delete-dump-button").click();

    await expect(page.getByLabel("Response")).toMatchAriaSnapshot(
        `- text: 4 / 5`,
    );

    await expectResponseHeader(/Illuminate\\Http\\Request:/);

    await page.getByTestId("next-dump-button").click();
    await expectResponseHeader("array: 4 items");
    await page.getByTestId("previous-dump-button").click();
    await page.getByTestId("previous-dump-button").click();
    await expectResponseHeader("<runtime object>: 6 properties");
});
