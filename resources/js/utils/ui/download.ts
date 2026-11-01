/**
 * Decodes a base64 string into a Blob of the given mime type.
 */
export function base64ToBlob(content: string, mimeType: string): Blob {
    const bytes = Uint8Array.from(atob(content), char => char.charCodeAt(0));

    return new Blob([bytes], { type: mimeType });
}

/**
 * Utility to trigger browser file download for a given Blob.
 */
export function triggerDownload(blob: Blob, filename: string): void {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = filename;
    link.click();

    URL.revokeObjectURL(url);
}
