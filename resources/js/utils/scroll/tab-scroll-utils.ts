/**
 * Scroll bounds information for tab navigation containers
 */
export interface ScrollBounds {
    current: number;
    max: number;
    isAtStart: boolean;
    isAtEnd: boolean;
}

/**
 * Element visibility information within scroll containers
 */
export interface ElementVisibility {
    isFullyVisible: boolean;
    isCutOffLeft: boolean;
    isCutOffRight: boolean;
    visibleAreaLeft: number;
    visibleAreaRight: number;
}

/**
 * Gets scroll bounds information for a container.
 *
 * Determines current scroll position, maximum scroll distance,
 * and whether the container is at the start or end of scroll.
 */
export const getScrollBounds = (
    container: HTMLElement,
    scrollThreshold: number,
): ScrollBounds => {
    const { scrollLeft, scrollWidth, clientWidth } = container;

    const maxScroll = scrollWidth - clientWidth;

    return {
        current: scrollLeft,
        max: maxScroll,
        isAtStart: scrollLeft <= scrollThreshold,
        isAtEnd: scrollLeft >= maxScroll - scrollThreshold,
    };
};

/**
 * Gets element visibility within scroll container.
 *
 * Determines if an element is fully visible, partially cut off,
 * and calculates the visible area boundaries.
 */
export const getElementVisibility = (
    element: HTMLElement,
    container: HTMLElement,
    maskWidth: number,
): ElementVisibility => {
    const { elementOffsetLeft, elementRight, visibleAreaLeft, visibleAreaRight } =
        calculateElementAndVisibleArea(element, container, maskWidth);

    // Check visibility states
    const isFullyVisible =
        elementOffsetLeft >= visibleAreaLeft && elementRight <= visibleAreaRight;
    const isCutOffLeft = elementOffsetLeft < visibleAreaLeft;
    const isCutOffRight = elementRight > visibleAreaRight;

    return {
        isFullyVisible,
        isCutOffLeft,
        isCutOffRight,
        visibleAreaLeft,
        visibleAreaRight,
    };
};

interface ElementAndVisibleArea {
    elementOffsetLeft: number;
    elementWidth: number;
    elementRight: number;
    visibleAreaLeft: number;
    visibleAreaRight: number;
    containerWidth: number;
    currentScrollLeft: number;
}

/**
 * Calculates element and visible area positions.
 *
 * Extracts common calculations used by both visibility and scroll position functions.
 */
function calculateElementAndVisibleArea(
    element: HTMLElement,
    container: HTMLElement,
    maskWidth: number,
): ElementAndVisibleArea {
    const containerWidth = container.clientWidth;
    const currentScrollLeft = container.scrollLeft;

    const elementOffsetLeft = element.offsetLeft;
    const elementWidth = element.offsetWidth;
    const elementRight = elementOffsetLeft + elementWidth;

    // Calculate visible area accounting for gradient masks
    const visibleAreaLeft = currentScrollLeft + maskWidth;
    const visibleAreaRight = currentScrollLeft + containerWidth - maskWidth;

    return {
        elementOffsetLeft,
        elementWidth,
        elementRight,
        visibleAreaLeft,
        visibleAreaRight,
        containerWidth,
        currentScrollLeft,
    };
}

/**
 * Calculates target scroll position to bring element into view.
 *
 * Determines the optimal scroll position to make an element fully visible,
 * accounting for gradient masks and scroll padding.
 */
export const calculateScrollToElement = (
    element: HTMLElement,
    container: HTMLElement,
    maskWidth: number,
    scrollPadding: number,
): number => {
    const maxScrollLeft = container.scrollWidth - container.clientWidth;

    const {
        elementOffsetLeft,
        elementRight,
        visibleAreaLeft,
        visibleAreaRight,
        containerWidth,
        currentScrollLeft,
    } = calculateElementAndVisibleArea(element, container, maskWidth);

    let targetScrollLeft = currentScrollLeft;

    // If element is cut off on the left side, scroll left
    if (elementOffsetLeft < visibleAreaLeft) {
        targetScrollLeft = Math.max(0, elementOffsetLeft - maskWidth - scrollPadding);
    }
    // If element is cut off on the right side, scroll right
    else if (elementRight > visibleAreaRight) {
        targetScrollLeft = elementRight - containerWidth + maskWidth + scrollPadding;
    }

    // Ensure we don't scroll beyond container bounds
    return Math.min(targetScrollLeft, maxScrollLeft);
};

/**
 * Gets mask visibility based on scroll bounds.
 *
 * Determines when to show left/right gradient masks based on
 * whether the container can scroll in those directions.
 */
export const getMaskVisibility = (
    bounds: ScrollBounds,
): { showLeftMask: boolean; showRightMask: boolean } => {
    return {
        showLeftMask: !bounds.isAtStart,
        showRightMask: !bounds.isAtEnd,
    };
};
