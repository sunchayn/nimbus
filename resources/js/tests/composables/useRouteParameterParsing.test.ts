import { useRouteParameterParsing } from '@/composables/request/useRouteParameterParsing';
import { describe, expect, it } from 'vitest';
import { computed, ref } from 'vue';

describe('useRouteParameterParsing', () => {
    it('parses route parameters correctly', () => {
        const endpoint = ref('/api/users/{id}/posts/{postId}');
        const { parameters } = useRouteParameterParsing(endpoint);

        expect(parameters.value).toEqual(['id', 'postId']);
    });

    it('ignores environment variables with double braces', () => {
        const endpoint = ref('/api/{{collection}}/users/{id}');
        const { parameters } = useRouteParameterParsing(endpoint);

        expect(parameters.value).toEqual(['id']);
    });

    it('returns empty array when no parameters are present', () => {
        const endpoint = ref('/api/users/123');
        const { parameters } = useRouteParameterParsing(endpoint);

        expect(parameters.value).toEqual([]);
    });

    it('works with computed endpoint', () => {
        const raw = ref('/api/{resource}');
        const endpoint = computed(() => raw.value);
        const { parameters } = useRouteParameterParsing(endpoint);

        expect(parameters.value).toEqual(['resource']);
    });

    it('updates when endpoint changes', async () => {
        const endpoint = ref('/api/{old}');
        const { parameters } = useRouteParameterParsing(endpoint);

        expect(parameters.value).toEqual(['old']);

        endpoint.value = '/api/{new}';
        expect(parameters.value).toEqual(['new']);
    });
});
