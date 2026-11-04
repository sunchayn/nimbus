import {
    FILE_NAME_PATTERN,
    MIME_TYPE_PATTERN,
    MIME_TYPES,
} from '@/config/binaries/mimeTypes';
import type { GeneratorContext } from '@/interfaces/ui';
import { faker } from '@faker-js/faker/locale/en';
import { defineCategory } from '../utils/defineCategory';
import { withinLength } from '../utils/withinLength';

const COMMON_EXTENSIONS = ['png', 'jpg', 'pdf', 'txt', 'csv', 'json', 'zip'];

function randomBase64(context?: GeneratorContext): string {
    // Every 3 bytes become 4 base64 characters.
    const bytes = Math.min(
        20,
        Math.max(3, Math.floor((context?.maxLength ?? 28) / 4) * 3),
    );

    return withinLength(context, () => btoa(faker.string.alphanumeric(bytes)));
}

export const data = defineCategory({
    id: 'data',
    name: 'Data',
    icon: 'i-hugeicons-chart-bar-line',
    generators: [
        {
            id: 'json',
            name: 'JSON',
            description: 'Generate a random JSON object',
            icon: 'i-hugeicons-source-code',
            generate: context =>
                withinLength(context, () =>
                    JSON.stringify(
                        {
                            id: faker.number.int(),
                            name: faker.person.fullName(),
                            email: faker.internet.email().toLowerCase(),
                            createdAt: faker.date.anytime().toISOString(),
                        },
                        null,
                        2,
                    ),
                ),
        },
        {
            id: 'base64',
            name: 'Base64',
            description: 'Generate base64 encoded string',
            icon: 'i-hugeicons-binary-code',
            generate: context => randomBase64(context),
        },
        {
            id: 'hex',
            name: 'Hex',
            description: 'Generate a random hex string',
            icon: 'i-hugeicons-hash',
            generate: context =>
                withinLength(context, () => faker.string.hexadecimal(), {
                    truncate: true,
                }),
        },
        {
            id: 'dataUri',
            name: 'Data URI',
            description: 'Generate a random data uri',
            icon: 'i-hugeicons-image-01',
            generate: context => withinLength(context, () => btoa(faker.image.dataUri())),
            nameRules: [{ pattern: /^(.+[-_])?(image|file|blob)s?$/i }],
        },
        {
            id: 'boolean-as-string',
            name: 'Boolean (As string)',
            description: 'Generate a random boolean',
            icon: 'i-hugeicons-toggle-on',
            generate: context =>
                withinLength(context, () => String(faker.datatype.boolean())),
            // Case sensitive on purpose, so words like "issue" or "hash" do not match.
            nameRules: [{ pattern: /^(is|has|was)([-_].+|[A-Z].*)$/ }],
        },
        {
            id: 'base64-content',
            name: 'Base64 File Content',
            description: 'Generate the base64 content of a small file',
            icon: 'i-hugeicons-file-upload',
            generate: context => randomBase64(context),
            typeRules: [{ format: 'byte' }],
        },
        {
            id: 'mime-type',
            name: 'Mime Type',
            description: 'Generate a random mime type',
            icon: 'i-hugeicons-file-attachment',
            generate: context =>
                withinLength(context, () => faker.helpers.arrayElement(MIME_TYPES)),
            nameRules: [{ pattern: MIME_TYPE_PATTERN }],
        },
        {
            id: 'file-name',
            name: 'File Name',
            description: 'Generate a random file name',
            icon: 'i-hugeicons-document-attachment',
            generate: context =>
                withinLength(
                    context,
                    () =>
                        `${faker.lorem.word()}.${faker.helpers.arrayElement(COMMON_EXTENSIONS)}`,
                ),
            nameRules: [{ pattern: FILE_NAME_PATTERN }],
        },
    ],
});
