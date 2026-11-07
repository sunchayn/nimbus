import {
    formDataSerializer,
    STORED_BODY_MAX_CHARS,
} from '@/utils/stores/formDataSerializer';
import { describe, expect, it } from 'vitest';

describe('formDataSerializer', () => {
    it('restores FormData values nested in the state', () => {
        // Arrange

        const formData = new FormData();
        formData.append('name', 'nimbus');
        formData.append('avatar', new File(['content'], 'me.png'));

        const state = {
            tabs: [{ body: { POST: { 'form-data': formData, json: '{}' } } }],
        };

        // Act

        const restored = formDataSerializer.deserialize(
            formDataSerializer.serialize(state),
        ) as typeof state;

        // Assert

        const body = restored.tabs[0].body.POST;

        expect(body['form-data']).toBeInstanceOf(FormData);
        expect(body['form-data'].get('name')).toBe('nimbus');
        expect((body['form-data'].get('avatar') as File).name).toBe('me.png');
        expect(body.json).toBe('{}');
    });

    it('keeps plain values untouched', () => {
        // Arrange

        const state = { a: 1, b: ['x'], c: null };

        // Act

        const restored = formDataSerializer.deserialize(
            formDataSerializer.serialize(state),
        );

        // Assert

        expect(restored).toEqual(state);
    });

    it('drops form data saved as a plain object by older versions', () => {
        // Arrange

        const raw = JSON.stringify({ body: { POST: { 'form-data': {}, json: '{}' } } });

        // Act

        const restored = formDataSerializer.deserialize(raw) as {
            body: { POST: Record<string, unknown> };
        };

        // Assert

        expect(restored.body.POST['form-data']).toBeNull();
        expect(restored.body.POST.json).toBe('{}');
    });

    it('returns an empty state when the saved value is not valid JSON', () => {
        // Act & Assert

        expect(formDataSerializer.deserialize('{broken')).toEqual({});
    });

    it('cuts a large response body and marks it', () => {
        // Arrange

        const state = {
            small: { statusCode: 200, body: 'ok' },
            large: { statusCode: 200, body: 'a'.repeat(STORED_BODY_MAX_CHARS + 5) },
        };

        // Act

        const restored = formDataSerializer.deserialize(
            formDataSerializer.serialize(state),
        ) as {
            small: { body: string; bodyTruncated?: boolean };
            large: { body: string; bodyTruncated?: boolean };
        };

        // Assert

        expect(restored.small).toEqual({ statusCode: 200, body: 'ok' });
        expect(restored.large.body).toHaveLength(STORED_BODY_MAX_CHARS);
        expect(restored.large.bodyTruncated).toBe(true);
    });
});
