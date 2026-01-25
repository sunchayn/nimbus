import { GeneratorType } from '@/interfaces/http';
import type { useValueGeneratorStore } from '@/stores';
import { generateValueFromType } from '@/utils/value-generator/generateValueFromType';
import { describe, expect, it, vi } from 'vitest';

describe('generateValueFromType', () => {
    it('should generate UUID when type is Uuid', () => {
        // Arrange

        const mockValueGeneratorStore = {
            generateValue: vi
                .fn()
                .mockReturnValue('550e8400-e29b-41d4-a716-446655440000'),
        } as unknown as ReturnType<typeof useValueGeneratorStore>;

        // Act

        const result = generateValueFromType(GeneratorType.Uuid, mockValueGeneratorStore);

        // Assert

        expect(mockValueGeneratorStore.generateValue).toHaveBeenCalledWith('uuid');
        expect(result).toBe('550e8400-e29b-41d4-a716-446655440000');
    });

    it('should generate email when type is Email', () => {
        // Arrange

        const mockValueGeneratorStore = {
            generateValue: vi.fn().mockReturnValue('test@example.com'),
        } as unknown as ReturnType<typeof useValueGeneratorStore>;

        // Act

        const result = generateValueFromType(
            GeneratorType.Email,
            mockValueGeneratorStore,
        );

        // Assert

        expect(mockValueGeneratorStore.generateValue).toHaveBeenCalledWith('email');
        expect(result).toBe('test@example.com');
    });

    it('should generate word when type is String', () => {
        // Arrange

        const mockValueGeneratorStore = {
            generateValue: vi.fn().mockReturnValue('randomWord'),
        } as unknown as ReturnType<typeof useValueGeneratorStore>;

        // Act

        const result = generateValueFromType(
            GeneratorType.String,
            mockValueGeneratorStore,
        );

        // Assert

        expect(mockValueGeneratorStore.generateValue).toHaveBeenCalledWith('word');
        expect(result).toBe('randomWord');
    });

    it('should generate word for unknown generator type', () => {
        // Arrange

        const mockValueGeneratorStore = {
            generateValue: vi.fn().mockReturnValue('defaultWord'),
        } as unknown as ReturnType<typeof useValueGeneratorStore>;

        const unknownType = 999 as unknown as GeneratorType;

        // Act

        const result = generateValueFromType(unknownType, mockValueGeneratorStore);

        // Assert

        expect(mockValueGeneratorStore.generateValue).toHaveBeenCalledWith('word');
        expect(result).toBe('defaultWord');
    });

    it('should return string type', () => {
        // Arrange

        const mockValueGeneratorStore = {
            generateValue: vi.fn().mockReturnValue('testValue'),
        } as unknown as ReturnType<typeof useValueGeneratorStore>;

        // Act

        const result = generateValueFromType(GeneratorType.Uuid, mockValueGeneratorStore);

        // Assert

        expect(typeof result).toBe('string');
    });
});
