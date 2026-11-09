/**
 * Switches the application by reloading the page with the application query parameter.
 * The backend reads the parameter to select the application.
 */
export const switchApplication = (applicationKey: string): void => {
    const url = new URL(window.location.href);
    url.searchParams.set('application', applicationKey);
    window.location.href = url.toString();
};
