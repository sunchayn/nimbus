import { generatePlaceholderPayload } from '@/utils/payload/payloadGenerator';
import { describe, expect, it } from 'vitest';

describe('payloadGenerator', () => {
    it('marks a binary property as an empty file', () => {
        // Arrange

        const schema = {
            type: 'object',
            properties: {
                name: { type: 'string' },
                avatar: { type: 'string', format: 'binary' },
            },
        } as const;

        // Act

        const payload = generatePlaceholderPayload(schema);

        // Assert

        expect(payload.name).toBe('<placeholder>');
        expect(payload.avatar).toBeInstanceOf(File);
        expect((payload.avatar as File).size).toBe(0);
    });
});
