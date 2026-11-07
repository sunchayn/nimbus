import RateLimitMeter from '@/components/domain/Client/Response/ResponseStatus/RateLimitMeter.vue';
import { useConfigStore } from '@/stores';
import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';

let mounted: VueWrapper | null = null;

describe('RateLimitMeter', () => {
    beforeEach(() => {
        window.Nimbus = {} as typeof window.Nimbus;
        setActivePinia(createPinia());
    });

    afterEach(async () => {
        await flushPromises();
        mounted?.unmount();
        mounted = null;
    });

    it('shows the remaining attempts', () => {
        // Arrange & Act

        mounted = mount(RateLimitMeter, {
            props: { rateLimit: { limit: 60, remaining: 58, retryAfter: null } },
            attachTo: document.body,
        });

        // Assert

        expect(mounted.get('[data-testid="rate-limit-count"]').text()).toBe('58/60');
    });

    it('emits reset from the popover action', async () => {
        // Arrange

        mounted = mount(RateLimitMeter, {
            props: { rateLimit: { limit: 5, remaining: 0, retryAfter: 12 } },
            attachTo: document.body,
        });

        // Act

        await mounted.get('[data-testid="rate-limit-meter"]').trigger('click');
        await flushPromises();
        document.querySelector<HTMLElement>('[data-testid="rate-limit-reset"]')?.click();

        // Assert

        expect(mounted.emitted('reset')).toHaveLength(1);
        expect(document.body.textContent).toContain('Try again in 12 seconds.');
    });

    it('warns instead of showing a count when the limiter cache is ephemeral', async () => {
        // Arrange

        window.Nimbus = { ephemeralRateLimiter: true } as typeof window.Nimbus;
        setActivePinia(createPinia());

        mounted = mount(RateLimitMeter, {
            props: { rateLimit: { limit: 5, remaining: 4, retryAfter: null } },
            attachTo: document.body,
        });

        // Act

        await mounted.get('[data-testid="rate-limit-meter"]').trigger('click');
        await flushPromises();

        // Assert

        expect(useConfigStore().isRateLimiterEphemeral).toBe(true);
        expect(mounted.find('[data-testid="rate-limit-count"]').exists()).toBe(false);
        expect(mounted.get('[data-testid="rate-limit-meter"]').text()).toBe(
            'No rate limit',
        );
        expect(
            document.querySelector('[data-testid="rate-limit-ephemeral-message"]'),
        ).not.toBeNull();
        expect(document.querySelector('[data-testid="rate-limit-reset"]')).toBeNull();
    });
});
