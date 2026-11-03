import { convertPayloadToFormData } from '@/utils/http';
import { describe, expect, it } from 'vitest';

describe('convertPayloadToFormData', () => {
    it('passes a file through', () => {
        // Arrange

        const file = new File(['content'], 'me.png');

        // Act

        const result = convertPayloadToFormData({ body: { avatar: file } });

        // Assert

        expect(result.get('body[avatar]')).toBe(file);
    });

    it('nests the keys of a nested form data', () => {
        // Arrange

        const nested = new FormData();

        nested.append('name', 'Jane');

        // Act

        const result = convertPayloadToFormData({ body: nested });

        // Assert

        expect(result.get('body[name]')).toBe('Jane');
    });

    it('keeps repeated array keys of a nested form data as separate entries', () => {
        // Arrange

        const first = new File(['a'], 'a.png');
        const second = new File(['b'], 'b.png');
        const nested = new FormData();

        nested.append('photos[]', first);
        nested.append('photos[]', second);
        nested.append('tags[0]', 'x');
        nested.append('plain', 'y');

        // Act

        const result = convertPayloadToFormData({ body: nested });

        // Assert

        expect(result.getAll('body[photos][]')).toEqual([first, second]);
        expect(result.get('body[tags][0]')).toBe('x');
        expect(result.get('body[plain]')).toBe('y');
    });

    it('skips an empty file restored from storage', () => {
        // Arrange

        const nested = new FormData();

        nested.append('avatar', new File([], 'me.png'));

        // Act

        const result = convertPayloadToFormData({ body: nested });

        // Assert

        expect(result.has('body[avatar]')).toBe(false);
    });

    it('keeps an empty file when placeholders are asked to be kept', () => {
        // Arrange

        const placeholder = new File([], '');

        // Act

        const result = convertPayloadToFormData({ avatar: placeholder }, true);

        // Assert

        expect(result.get('avatar')).toBeInstanceOf(File);
    });
});
