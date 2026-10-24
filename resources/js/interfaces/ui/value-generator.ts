export enum ValueGeneratorCommandOpenMethod {
    CLICK = 'click',
    SHIFT_SHIFT = 'shift-shift',
}

export interface ValueGenerator {
    id: string;
    name: string;
    description: string;
    category: GeneratorCategory;
    generate: (config?: object) => string | number | bigint;
    shortcut?: string;
    icon?: string;
}

export interface GeneratorCategory {
    id: string;
    name: string;
    icon?: string;
}

export interface GeneratorCommandState {
    searchQuery: string;
    selectedCategory: string | null;
    recentGenerators: string[];
}
