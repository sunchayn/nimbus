import { test, expect } from '../core/fixtures';
import * as fs from 'fs';

test.describe('Response Shape & Download', () => {
    test.beforeEach(async ({ page, basePage }) => {
        await basePage.goto();
        await expect(page.getByTestId('app-tabs-container')).toBeInViewport();
    });

    test('downloads raw response body file', async ({ page, basePage }) => {
        await basePage.sendRequest('responses', 'GET /raw-payload');
        await expect(page.getByTestId('response-status-text')).toContainText('Success');
        await expect(page.getByTestId('response-status-badge')).toContainText('200 - OK');

        await page.getByTestId('download-dropdown-trigger').click();
        await expect(page.getByTestId('download-raw-option')).toBeVisible();

        const downloadPromise = page.waitForEvent('download');
        await page.getByTestId('download-raw-option').click();
        const download = await downloadPromise;

        expect(download.suggestedFilename()).toMatch(/\.json$/);

        const filePath = await download.path();
        if (filePath) {
            const content = JSON.parse(fs.readFileSync(filePath, 'utf-8'));
            expect(content).toEqual({
                message: 'raw payload array',
                active: true,
                count: 42,
            });
        }
    });

    test('downloads and asserts JSON shape from JsonResource response', async ({ page, basePage }) => {
        await basePage.sendRequest('responses', 'GET /json-resource');
        await expect(page.getByTestId('response-status-badge')).toContainText('200 - OK');

        await page.getByTestId('download-dropdown-trigger').click();
        await expect(page.getByTestId('download-shape-option')).toBeEnabled();

        const downloadPromise = page.waitForEvent('download');
        await page.getByTestId('download-shape-option').click();
        const download = await downloadPromise;

        expect(download.suggestedFilename()).toMatch(/\.schema\.json$/);

        const filePath = await download.path();
        if (filePath) {
            const shapeSchema = JSON.parse(fs.readFileSync(filePath, 'utf-8'));

            expect(shapeSchema).toMatchObject({
                $schema: 'https://json-schema.org/draft/2020-12/schema',
                type: 'object',
                properties: {
                    id: { type: 'integer' },
                    name: { type: 'string' },
                    email: { type: 'string' },
                    is_active: { type: 'boolean' },
                },
            });
        }
    });

    test('downloads and asserts JSON shape from nested JSON response', async ({ page, basePage }) => {
        await basePage.sendRequest('responses', 'GET /nested-json');
        await expect(page.getByTestId('response-status-badge')).toContainText('200 - OK');

        await page.getByTestId('download-dropdown-trigger').click();
        await expect(page.getByTestId('download-shape-option')).toBeEnabled();

        const downloadPromise = page.waitForEvent('download');
        await page.getByTestId('download-shape-option').click();
        const download = await downloadPromise;

        expect(download.suggestedFilename()).toMatch(/\.schema\.json$/);

        const filePath = await download.path();
        if (filePath) {
            const shapeSchema = JSON.parse(fs.readFileSync(filePath, 'utf-8'));

            expect(shapeSchema).toMatchObject({
                $schema: 'https://json-schema.org/draft/2020-12/schema',
                type: 'object',
                properties: {
                    id: { type: 'integer' },
                    user: {
                        type: 'object',
                        properties: {
                            name: { type: 'string' },
                            role: { type: 'string' },
                            preferences: {
                                type: 'object',
                                properties: {
                                    theme: { type: 'string' },
                                    notifications: { type: 'boolean' },
                                },
                            },
                        },
                    },
                },
            });

            expect(shapeSchema.properties.tags).toEqual({
                type: 'array',
                items: { type: 'string' },
            });
        }
    });

    test('downloads and asserts JSON shape from Spatie Data response', async ({ page, basePage }) => {
        await basePage.sendRequest('responses', 'GET /spatie-data');
        await expect(page.getByTestId('response-status-badge')).toContainText('200 - OK');

        await page.getByTestId('download-dropdown-trigger').click();
        await expect(page.getByTestId('download-shape-option')).toBeEnabled();

        const downloadPromise = page.waitForEvent('download');
        await page.getByTestId('download-shape-option').click();
        const download = await downloadPromise;

        expect(download.suggestedFilename()).toMatch(/\.schema\.json$/);

        const filePath = await download.path();
        if (filePath) {
            const shapeSchema = JSON.parse(fs.readFileSync(filePath, 'utf-8'));

            expect(shapeSchema).toMatchObject({
                $schema: 'https://json-schema.org/draft/2020-12/schema',
                type: 'object',
                properties: {
                    title: { type: 'string' },
                    artist: { type: 'string' },
                },
            });
        }
    });

    test('downloads and asserts JSON shape from inline response()->json() response', async ({ page, basePage }) => {
        await basePage.sendRequest('responses', 'GET /inline-json');
        await expect(page.getByTestId('response-status-badge')).toContainText('200 - OK');

        await page.getByTestId('download-dropdown-trigger').click();
        await expect(page.getByTestId('download-shape-option')).toBeEnabled();

        const downloadPromise = page.waitForEvent('download');
        await page.getByTestId('download-shape-option').click();
        const download = await downloadPromise;

        expect(download.suggestedFilename()).toMatch(/\.schema\.json$/);

        const filePath = await download.path();
        if (filePath) {
            const shapeSchema = JSON.parse(fs.readFileSync(filePath, 'utf-8'));

            expect(shapeSchema).toMatchObject({
                $schema: 'https://json-schema.org/draft/2020-12/schema',
                type: 'object',
                properties: {
                    status: { type: 'string' },
                    code: { type: 'integer' },
                    data: {
                        type: 'object',
                        properties: {
                            item_id: { type: 'string' },
                            title: { type: 'string' },
                        },
                    },
                },
            });
        }
    });

    test('downloads and asserts JSON shape from raw payload array response', async ({ page, basePage }) => {
        await basePage.sendRequest('responses', 'GET /raw-payload');
        await expect(page.getByTestId('response-status-badge')).toContainText('200 - OK');

        await page.getByTestId('download-dropdown-trigger').click();
        await expect(page.getByTestId('download-shape-option')).toBeEnabled();

        const downloadPromise = page.waitForEvent('download');
        await page.getByTestId('download-shape-option').click();
        const download = await downloadPromise;

        expect(download.suggestedFilename()).toMatch(/\.schema\.json$/);

        const filePath = await download.path();
        if (filePath) {
            const shapeSchema = JSON.parse(fs.readFileSync(filePath, 'utf-8'));

            expect(shapeSchema).toMatchObject({
                $schema: 'https://json-schema.org/draft/2020-12/schema',
                type: 'object',
                properties: {
                    message: { type: 'string' },
                    active: { type: 'boolean' },
                    count: { type: 'integer' },
                },
            });
        }
    });
});
