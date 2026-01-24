import type { MockInstance } from '@vitest/spy';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

describe('uniquePersistenceKey', () => {
    let uniquePersistenceKey: (key: string) => string;
    let consoleWarnSpy: MockInstance;

    beforeEach(async () => {
        // Reset modules to clear the internal keys array
        vi.resetModules();

        // Re-import the module to get a fresh instance
        const module = await import('@/utils/stores');
        uniquePersistenceKey = module.uniquePersistenceKey;

        vi.clearAllMocks();
        consoleWarnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
    });

    afterEach(() => {
        consoleWarnSpy.mockRestore();
    });

    it('should return a key with nimbus prefix', () => {
        const result = uniquePersistenceKey('testKey');
        expect(result).toBe('nimbus:testKey');
    });

    it('should allow multiple different keys', () => {
        const result1 = uniquePersistenceKey('key1');
        const result2 = uniquePersistenceKey('key2');

        expect(result1).toBe('nimbus:key1');
        expect(result2).toBe('nimbus:key2');
    });

    it('should handle duplicate keys by appending -duplicate', () => {
        const result1 = uniquePersistenceKey('duplicate');
        const result2 = uniquePersistenceKey('duplicate');

        expect(result1).toBe('nimbus:duplicate');
        expect(result2).toBe('nimbus:duplicate-duplicate');
        expect(consoleWarnSpy).toHaveBeenCalledWith(
            "Key duplicate must be unique. 'duplicate-duplicate' will be used instead.",
        );
    });

    it('should handle multiple levels of duplication', () => {
        const result1 = uniquePersistenceKey('test');
        const result2 = uniquePersistenceKey('test');
        const result3 = uniquePersistenceKey('test');

        expect(result1).toBe('nimbus:test');
        expect(result2).toBe('nimbus:test-duplicate');
        expect(result3).toBe('nimbus:test-duplicate-duplicate');
    });

    it('should warn when duplicate key is detected', () => {
        uniquePersistenceKey('myKey');
        uniquePersistenceKey('myKey');

        expect(consoleWarnSpy).toHaveBeenCalledTimes(1);
    });
});

describe('clearPersistentKeys', () => {
    let uniquePersistenceKey: (key: string) => string;
    let clearPersistentKeys: () => void;
    let localStorageRemoveSpy: MockInstance;

    beforeEach(async () => {
        // Reset modules to clear the internal keys array
        vi.resetModules();

        // Re-import the module to get a fresh instance
        const module = await import('@/utils/stores');
        uniquePersistenceKey = module.uniquePersistenceKey;
        clearPersistentKeys = module.clearPersistentKeys;

        localStorageRemoveSpy = vi.fn();

        global.localStorage = {
            // @ts-expect-error it is a mock.
            removeItem: localStorageRemoveSpy,
            setItem: vi.fn(),
            getItem: vi.fn(),
            clear: vi.fn(),
            key: vi.fn(),
            length: 0,
        };
    });

    it('should remove all registered keys from localStorage', () => {
        uniquePersistenceKey('key1');
        uniquePersistenceKey('key2');
        uniquePersistenceKey('key3');

        clearPersistentKeys();

        expect(localStorageRemoveSpy).toHaveBeenCalledWith('nimbus:key1');
        expect(localStorageRemoveSpy).toHaveBeenCalledWith('nimbus:key2');
        expect(localStorageRemoveSpy).toHaveBeenCalledWith('nimbus:key3');
        expect(localStorageRemoveSpy).toHaveBeenCalledTimes(3);
    });

    it('should not attempt to remove keys if none were registered', () => {
        clearPersistentKeys();

        expect(localStorageRemoveSpy).not.toHaveBeenCalled();
    });

    it('should remove duplicate keys with their modified names', () => {
        const consoleWarnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

        uniquePersistenceKey('duplicate');
        uniquePersistenceKey('duplicate');

        clearPersistentKeys();

        expect(localStorageRemoveSpy).toHaveBeenCalledWith('nimbus:duplicate');
        expect(localStorageRemoveSpy).toHaveBeenCalledWith('nimbus:duplicate-duplicate');

        consoleWarnSpy.mockRestore();
    });
});
