import InsightJobs from '@/components/domain/Client/Response/ResponseInsight/Jobs/InsightJobs.vue';
import type { InsightJobEntry } from '@/interfaces/http';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const render = (entries: InsightJobEntry[], dropped?: number) =>
    mount(InsightJobs, { props: { entries, dropped } });

const destinationOf = (entry: InsightJobEntry) =>
    render([entry]).find('[data-testid="insight-job-destination"]');

describe('InsightJobs', () => {
    it('lists each job', () => {
        const wrapper = render([
            { job: 'App\\Jobs\\Ship', queue: null, connection: null },
            { job: 'App\\Jobs\\Bill', queue: null, connection: null },
        ]);

        const jobs = wrapper.findAll('[data-testid="insight-job"]');

        expect(jobs).toHaveLength(2);
        expect(jobs[0].text()).toContain('App\\Jobs\\Ship');
        expect(jobs[1].text()).toContain('App\\Jobs\\Bill');
    });

    it('shows the connection and the queue together', () => {
        expect(
            destinationOf({ job: 'Job', queue: 'emails', connection: 'redis' }).text(),
        ).toBe('redis / emails');
    });

    it.each([
        [{ queue: 'emails', connection: null }, 'emails'],
        [{ queue: null, connection: 'redis' }, 'redis'],
    ])('shows only what is known (%j)', (partial, expected) => {
        expect(destinationOf({ job: 'Job', ...partial }).text()).toBe(expected);
    });

    it('shows no destination when neither is known', () => {
        expect(
            destinationOf({ job: 'Job', queue: null, connection: null }).exists(),
        ).toBe(false);
    });

    it('tells how many jobs were not shown', () => {
        const wrapper = render([{ job: 'Job', queue: null, connection: null }], 2);

        expect(wrapper.find('[data-testid="insight-dropped"]').text()).toContain('2');
    });
});
