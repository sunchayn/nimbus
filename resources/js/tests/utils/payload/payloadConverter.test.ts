import { RequestBodyTypeEnum } from '@/interfaces/http';
import { serializeSchemaPayload } from '@/utils/payload';
import { describe, expect, it } from 'vitest';

describe('serializeSchemaPayload', () => {
    it('keeps an empty file in form data so the field stays an upload', () => {
        // Arrange

        const payload = { avatar: new File([], '') };

        // Act

        const formData = serializeSchemaPayload(
            payload,
            RequestBodyTypeEnum.FORM_DATA,
        ) as FormData;

        // Assert

        expect(formData.get('avatar')).toBeInstanceOf(File);
    });

    it('leaves a file empty in a JSON body', () => {
        // Arrange

        const payload = { avatar: new File([], '') };

        // Act

        const json = serializeSchemaPayload(payload, RequestBodyTypeEnum.JSON) as string;

        // Assert

        expect(JSON.parse(json).avatar).toBe('');
    });

    it('leaves a file empty in a plain text body', () => {
        // Arrange

        const payload = { avatar: new File([], ''), name: 'Jane' };

        // Act

        const text = serializeSchemaPayload(payload, RequestBodyTypeEnum.PLAIN_TEXT);

        // Assert

        expect(text).toBe('avatar=;\nname=Jane;');
    });
});
