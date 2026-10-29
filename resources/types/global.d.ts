import { SharedState } from '../js/interfaces/share';

interface NimbusConfig {
    basePath: string;
    headers: string | null;
    apiBaseUrl: string;
    isVersioned: boolean;
    currentUser: string | null;
    applications: string | null;
    activeApplication: string | null;
    sharedState: SharedState | null;
    primaryProcessorName: string | null;
    showOperationId: boolean | null;
    globalException: string | null;
}

declare global {
    interface Window {
        axios: AxiosInstance;
        Nimbus: NimbusConfig;
    }
}
