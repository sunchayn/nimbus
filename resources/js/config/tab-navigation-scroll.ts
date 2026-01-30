/**
 * Tab navigation scroll configuration.
 *
 * This configuration defines scroll behavior settings for horizontal
 * tab navigation with gradient masks and smooth animations.
 */
export const tabNavigationScrollConfig = {
    /**
     * Mask width in pixels (Horizontal).
     *
     * Width of the gradient masks that fade content at scroll boundaries horizontally.
     * Provides visual indication of scrollable content beyond viewport.
     */
    MASK_WIDTH: 32,

    /**
     * Scroll threshold in pixels (1px).
     *
     * Minimum scroll distance to consider at start/end positions.
     * Prevents precision issues with fractional pixel values.
     */
    SCROLL_THRESHOLD: 1,

    /**
     * Scroll padding in pixels.
     *
     * Extra padding added when scrolling elements into view.
     * Ensures elements are fully visible with comfortable spacing.
     */
    SCROLL_PADDING: 8,

    /**
     * Animation duration in milliseconds.
     *
     * Duration of smooth scroll animations when navigating between tabs.
     */
    ANIMATION_DURATION: 300,

    /**
     * Debounce delay in milliseconds (16ms ≈ 60fps).
     *
     * Delay for scroll event handling to prevent excessive updates.
     * Optimizes performance during rapid scrolling.
     */
    DEBOUNCE_DELAY: 16,
} satisfies TabNavigationScrollConfig;

export interface TabNavigationScrollConfig {
    MASK_WIDTH: number;
    SCROLL_THRESHOLD: number;
    SCROLL_PADDING: number;
    ANIMATION_DURATION: number;
    DEBOUNCE_DELAY: number;
}
