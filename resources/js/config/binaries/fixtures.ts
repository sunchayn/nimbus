import type { BinaryFixture } from '@/interfaces/ui';

/**
 * Tiny valid files for binary properties.
 *
 * The images are 1x1 pixels, the pdf has one blank page, and the zip is empty.
 * The text based files are encoded once when the module loads.
 */
export const BINARY_FIXTURES: readonly BinaryFixture[] = [
    {
        id: 'png',
        extension: 'png',
        mimeType: 'image/png',
        base64: 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGPwnvPhPwAF5ALXglNtSwAAAABJRU5ErkJggg==',
    },
    {
        id: 'gif',
        extension: 'gif',
        mimeType: 'image/gif',
        base64: 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
    },
    {
        id: 'jpeg',
        extension: 'jpg',
        mimeType: 'image/jpeg',
        base64: '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=',
    },
    {
        id: 'webp',
        extension: 'webp',
        mimeType: 'image/webp',
        base64: 'UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==',
    },
    {
        id: 'pdf',
        extension: 'pdf',
        mimeType: 'application/pdf',
        base64: 'JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK',
    },
    {
        id: 'zip',
        extension: 'zip',
        mimeType: 'application/zip',
        base64: 'UEsFBgAAAAAAAAAAAAAAAAAAAAAAAA==',
    },
    {
        id: 'txt',
        extension: 'txt',
        mimeType: 'text/plain',
        base64: btoa('Hello from Nimbus.\n'),
    },
    {
        id: 'json',
        extension: 'json',
        mimeType: 'application/json',
        base64: btoa('{"message":"Hello from Nimbus"}\n'),
    },
    {
        id: 'csv',
        extension: 'csv',
        mimeType: 'text/csv',
        base64: btoa('id,name\n1,Nimbus\n'),
    },
    {
        id: 'svg',
        extension: 'svg',
        mimeType: 'image/svg+xml',
        base64: btoa(
            '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"><rect width="1" height="1"/></svg>',
        ),
    },
];
