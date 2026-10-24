import { GeneratorType } from '@/interfaces/http';
import { generateValueFromType } from '@/utils/value-generator/generateValueFromType';
import { describe, expect, it, vi } from 'vitest';

describe('generateValueFromType', () => {
    it('should generate UUID when type is Uuid', () => {
        // Arrange

        const generate = vi.fn().mockReturnValue('550e8400-e29b-41d4-a716-446655440000');

        // Act

        const result = generateValueFromType(GeneratorType.Uuid, generate);

        // Assert

        expect(generate).toHaveBeenCalledWith('uuid');
        expect(result).toBe('550e8400-e29b-41d4-a716-446655440000');
    });

    it('should generate email when type is Email', () => {
        // Arrange

        const generate = vi.fn().mockReturnValue('test@example.com');

        // Act

        const result = generateValueFromType(GeneratorType.Email, generate);

        // Assert

        expect(generate).toHaveBeenCalledWith('email');
        expect(result).toBe('test@example.com');
    });

    it('should generate word when type is String', () => {
        // Arrange

        const generate = vi.fn().mockReturnValue('randomWord');

        // Act

        const result = generateValueFromType(GeneratorType.String, generate);

        // Assert

        expect(generate).toHaveBeenCalledWith('word');
        expect(result).toBe('randomWord');
    });

    it('should generate word for unknown generator type', () => {
        // Arrange

        const generate = vi.fn().mockReturnValue('defaultWord');

        const unknownType = 999 as unknown as GeneratorType;

        // Act

        const result = generateValueFromType(unknownType, generate);

        // Assert

        expect(generate).toHaveBeenCalledWith('word');
        expect(result).toBe('defaultWord');
    });

    it('should return string type', () => {
        // Arrange

        const generate = vi.fn().mockReturnValue('testValue');

        // Act

        const result = generateValueFromType(GeneratorType.Uuid, generate);

        // Assert

        expect(typeof result).toBe('string');
    });
});
