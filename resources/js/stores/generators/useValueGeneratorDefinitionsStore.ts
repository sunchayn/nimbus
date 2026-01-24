import { allValueGenerators, generatorCategories } from '@/config/generators';
import type { ValueGenerator } from '@/interfaces/ui';
import { defineStore } from 'pinia';
import { ref } from 'vue';

/**
 * Store for managing value generator definitions and categories.
 *
 * Provides access to all available generators and categories,
 * with computed properties for easy data access.
 */
export const useValueGeneratorDefinitionsStore = defineStore(
    '_valueGeneratorDefinitions',
    () => {
        /*
         * State.
         */

        const generators = ref<ValueGenerator[]>(allValueGenerators);
        const categories = ref(generatorCategories);

        /*
         * Computed.
         */

        const getGeneratorById = (id: string) => {
            return generators.value.find(generator => generator.id === id);
        };

        const getGeneratorsByCategory = (categoryId: string) => {
            return generators.value.filter(
                generator => generator.category.id === categoryId,
            );
        };

        const getAllCategories = () => categories.value;

        return {
            // State
            generators,
            categories,

            // Computed
            getGeneratorById,
            getGeneratorsByCategory,
            getAllCategories,
        };
    },
);
