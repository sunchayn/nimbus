import { useAppToast } from '@/components/base/toast';
import {
    useConfigStore,
    useEnvironmentVariablesStore,
    useRequestsHistoryStore,
    useRequestStore,
} from '@/stores';
import { buildShareableUrl, encodeShareablePayload } from '@/utils/shareableLinks';
import { shallowRef, type ShallowRef } from 'vue';

/**
 * Builds the shareable link of the active request and controls its dialog state.
 */
export const useShareableLink = (): {
    showShareableLinkDialog: ShallowRef<boolean>;
    shareableLink: ShallowRef<string>;
    openShareableLinkDialog: () => void;
} => {
    const toast = useAppToast();
    const requestStore = useRequestStore();
    const configStore = useConfigStore();
    const historyStore = useRequestsHistoryStore();
    const environmentVariablesStore = useEnvironmentVariablesStore();

    const showShareableLinkDialog = shallowRef(false);
    const shareableLink = shallowRef('');

    const openShareableLinkDialog = () => {
        if (!requestStore.pendingRequestData) {
            return;
        }

        try {
            const lastLog = historyStore.lastLog;

            const encodedPayload = encodeShareablePayload(
                requestStore.pendingRequestData,
                environmentVariablesStore.resolve,
                lastLog?.response,
                lastLog ?? undefined,
                configStore.activeApplication ?? undefined,
            );

            shareableLink.value = buildShareableUrl(
                configStore.appBasePath,
                encodedPayload,
            );

            showShareableLinkDialog.value = true;
        } catch {
            toast.error('Failed to generate shareable link', {
                description: 'An unexpected error occurred.',
            });
        }
    };

    return { showShareableLinkDialog, shareableLink, openShareableLinkDialog };
};
