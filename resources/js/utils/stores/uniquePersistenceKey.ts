const keys: string[] = [];

const render = (key: string): string => `nimbus:${key}`;

export const uniquePersistenceKey = (key: string): string => {
    if (keys.includes(key)) {
        const newKey = key + '-duplicate';

        console.warn(`Key ${key} must be unique. '${newKey}' will be used instead.`);

        return uniquePersistenceKey(newKey);
    }

    keys.push(key);

    return render(key);
};

export const singletonPersistenceKey = (key: string): string => {
    if (!keys.includes(key)) {
        keys.push(key);
    }

    return render(key);
};

export const clearPersistentKeys = (): void => {
    keys.forEach((key: string) => {
        window.localStorage.removeItem(render(key));
    });
};
