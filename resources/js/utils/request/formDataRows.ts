import type { ParameterContract } from '@/interfaces/ui';

/**
 * Remembers the editor rows of each FormData the body editor emitted.
 *
 * FormData cannot hold the enabled flag, so a remounted editor reads the rows back from here.
 */
export const rowsByFormData = new WeakMap<FormData, ParameterContract[]>();
