import { useEnvVariablesAwareString } from '@/composables/request/useEnvVariablesAwareString';
import { ParameterType } from '@/interfaces/ui';
import { useEnvironmentVariablesStore } from '@/stores/core/useEnvironmentVariablesStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { nextTick, ref } from 'vue';

describe('useEnvVariablesAwareString', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('updates resolved value when environment variables update (reactivity)', async () => {
        // Arrange

        const environmentVariablesStore = useEnvironmentVariablesStore();
        environmentVariablesStore.addCollection();
        environmentVariablesStore.updateVariables([
            {
                id: 1,
                type: ParameterType.Text,
                key: 'host',
                value: 'localhost',
                enabled: true,
            },
        ]);

        const source = ref('{{host}}');

        const { raw, resolved } = useEnvVariablesAwareString(source);

        // Assert (pre-condition)
        expect(raw.value).toBe('{{host}}');
        expect(resolved.value).toBe('localhost');

        // Act

        // Update environment variable
        environmentVariablesStore.updateVariables([
            {
                id: 1,
                type: ParameterType.Text,
                key: 'host',
                value: 'production.nimbus',
                enabled: true,
            },
        ]);

        // Wait for watchers
        await nextTick();

        // Assert

        // Assert: The internal resolved value should be updated
        expect(resolved.value).toBe('production.nimbus');
        expect(source.value).toBe('{{host}}');
    });

    it('syncs internal raw state when parent source changes externally', async () => {
        // Arrange

        const environmentVariablesStore = useEnvironmentVariablesStore();
        environmentVariablesStore.addCollection();
        environmentVariablesStore.updateVariables([
            {
                id: 1,
                type: ParameterType.Text,
                key: 'host',
                value: 'localhost',
                enabled: true,
            },
        ]);

        const source = ref('');

        const { raw, resolved } = useEnvVariablesAwareString(source);

        // Assert (pre-condition)

        expect(raw.value).toBe('');
        expect(resolved.value).toBe('');

        // Act

        // Simulate a component directly updating the bound string
        source.value = '{{host}}/api';

        await nextTick();

        // Assert

        expect(raw.value).toBe('{{host}}/api');
        expect(resolved.value).toBe('localhost/api');
    });
});
