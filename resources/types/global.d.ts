import { AxiosInstance } from 'axios';

interface NimbusConfig {
    basePath: string;
    routes: string | null;
    headers: string | null;
    apiBaseUrl: string;
    isVersioned: boolean;
    routeExtractorException: string | null;
    currentUser: string | null;
    applications: string | null;
    activeApplication: string | null;
}

declare global {
    interface Window {
        axios: AxiosInstance;
        Nimbus: NimbusConfig;
    }
}
