/*
 * Nuxt UI component theme preset.
 * Every visual value reads a CSS variable from resources/css/themes, so a theme never edits this file.
 * Tailwind finds classes by scanning source text, so every class is written out in full.
 */

/*
 * Token utility names read from resources/css/themes/base.css, taught to Tailwind merge.
 * Without them it reads an unknown name such as shadow-overlay as a color and keeps the default class.
 * A new token in the CSS is picked up here, so nothing is listed twice.
 * The caller passes the CSS text, so this file runs in Node and in the browser bundle.
 */
const tokens = (css, ...prefixes) => [
    ...new Set(
        prefixes.flatMap((prefix) =>
            [
                ...css.matchAll(
                    new RegExp(`^\\s*--${prefix}-([a-z0-9-]+):`, "gm"),
                ),
            ]
                .map((match) => match[1])
                .filter((name) => !name.includes("--")),
        ),
    ),
];

export const createTwMergeConfig = (css) => ({
    extend: {
        theme: {
            // The control sizes come from the utilities in app.css, not from a theme variable.
            spacing: [
                ...tokens(css, "spacing"),
                ...["xs", "sm", "md", "lg"].map((size) => `control-${size}`),
            ],
            container: tokens(css, "min-width", "max-width"),
            text: tokens(css, "text"),
            shadow: tokens(css, "shadow"),
            animate: tokens(css, "animate"),
        },
        classGroups: {
            z: [{ z: tokens(css, "z-index") }],
            duration: [{ duration: tokens(css, "transition-duration") }],
            opacity: [{ opacity: tokens(css, "opacity") }],
            "ring-w": [{ ring: tokens(css, "ring-width") }],
            "border-w-b": [{ "border-b": ["line"] }],
            "border-w-s": [{ "border-s": ["line"] }],
            "border-w-e": [{ "border-e": ["line"] }],
        },
    },
});

/*
 * Shared class strings.
 */

const fadeOverlay =
    "data-[state=open]:animate-fade-in-fast data-[state=closed]:animate-fade-out-fast";

const fadeDialog =
    "data-[state=open]:animate-fade-in-base data-[state=closed]:animate-fade-out-base";

const overlaySurface =
    "bg-overlay rounded-md ring-line ring-default shadow-overlay";

const dialogSurface =
    "bg-overlay rounded-lg ring-line ring-default shadow-modal";

const overlaySeparator = "-mx-menu my-menu bg-separator";

// Menus also divide their groups with borders on the viewport.
const overlayDivide = "divide-separator";

const itemMd =
    "p-0 h-item-md items-center px-control-sm py-0 gap-control-md text-sm";

const itemSm =
    "p-0 h-item-sm items-center px-control-sm py-0 gap-control-sm text-xs";

const itemBase =
    "before:rounded-sm data-disabled:cursor-default data-disabled:opacity-disabled";

const itemHighlight =
    "text-default data-highlighted:text-highlighted data-[state=open]:text-highlighted data-highlighted:before:bg-overlay-hover data-[state=open]:before:bg-overlay-hover";

/*
 * Group headers in menus and selects use small muted text that lines up with the item labels.
 * The viewport divides groups, so every group after the first also gets a line above.
 */
const labelRow =
    "h-auto items-center px-control-sm py-tight gap-tight text-menu-label leading-none font-semibold text-muted";

const emptyRow = "p-base text-sm";

const controlBase =
    "shadow-control disabled:cursor-default disabled:opacity-disabled duration-base";

// Outline and subtle controls draw their border as an inset ring.
const controlBorder = {
    outline: "ring-line",
    subtle: "ring-line",
};

const control = {
    xs: "h-control-xs px-control-xs py-0 gap-control-xs text-control-xs",
    sm: "h-control-sm px-control-sm py-0 gap-control-sm text-control-sm",
    md: "h-control-md px-control-md py-0 gap-control-md text-control-md",
    lg: "h-control-lg px-control-lg py-0 gap-control-lg text-control-lg",
};

const icon = {
    xs: "size-icon-xs",
    sm: "size-icon-sm",
    md: "size-icon-md",
    lg: "size-icon-lg",
};

// Icons placed in a button's default slot get the same size as its icon props.
const slottedIcon = {
    xs: "[&>.iconify]:size-icon-xs",
    sm: "[&>.iconify]:size-icon-sm",
    md: "[&>.iconify]:size-icon-md",
    lg: "[&>.iconify]:size-icon-lg",
};

const leading = {
    xs: "ps-control-xs",
    sm: "ps-control-sm",
    md: "ps-control-md",
    lg: "ps-control-lg",
};

const trailing = {
    xs: "pe-control-xs",
    sm: "pe-control-sm",
    md: "pe-control-md",
    lg: "pe-control-lg",
};

// Icons inside controls and menu rows use the body text color, because the thin stroke fades in muted tones.
const fieldIcon = "text-default";

const itemIcon =
    "text-default group-data-highlighted:not-group-data-disabled:text-highlighted";

const fieldSize = (size) => ({
    base: control[size],
    leading: leading[size],
    trailing: trailing[size],
    leadingIcon: `${icon[size]} ${fieldIcon}`,
    trailingIcon: `${icon[size]} ${fieldIcon}`,
});

/*
 * Menus and selects use small rows up to sm and regular rows above.
 */
const menuSm = {
    label: labelRow,
    item: itemSm,
    empty: emptyRow,
    itemLeadingIcon: `${icon.sm} ${itemIcon}`,
    itemTrailingIcon: icon.sm,
};

const menuMd = {
    label: labelRow,
    item: itemMd,
    empty: emptyRow,
    itemLeadingIcon: `${icon.md} ${itemIcon}`,
    itemTrailingIcon: icon.md,
};

const paletteInput = "[&>input]:h-control-lg";

const paletteSm = {
    ...menuSm,
    input: paletteInput,
    itemTrailingHighlightedIcon: icon.sm,
};

const paletteMd = {
    ...menuMd,
    input: paletteInput,
    itemTrailingHighlightedIcon: icon.md,
};

const menuSizes = {
    xs: menuSm,
    sm: menuSm,
    md: menuMd,
    lg: menuMd,
    xl: menuMd,
};

const select = {
    slots: {
        base: `rounded-md ${controlBase}`,
        // The list grows to its longest item, so labels never truncate.
        content: `w-max min-w-(--reka-select-trigger-width) max-w-(--reka-select-content-available-width) ${overlaySurface}`,
        viewport: overlayDivide,
        group: "p-menu",
        separator: overlaySeparator,
        label: labelRow,
        item: `${itemBase} data-highlighted:not-data-disabled:before:bg-overlay-hover`,
    },
    variants: {
        variant: controlBorder,
        position: {
            popper: { content: fadeOverlay },
        },
        size: {
            xs: { ...fieldSize("xs"), ...menuSm },
            sm: { ...fieldSize("sm"), ...menuSm },
            md: { ...fieldSize("md"), ...menuMd },
            lg: { ...fieldSize("lg"), ...menuMd },
            xl: { ...fieldSize("lg"), ...menuMd },
        },
    },
    defaultVariants: {
        color: "neutral",
        variant: "outline",
        size: "md",
    },
};

/*
 * The active tab is a raised surface on the trigger itself, for every variant.
 * The sliding indicator stays hidden so pill and link tabs share one look.
 */
const activeTab =
    "data-[state=active]:bg-tab-active-bg data-[state=active]:text-tab-active-text data-[state=active]:shadow-tab-active data-[state=active]:ring-line data-[state=active]:ring-tab-active-border";

/*
 * Dialogs pad the whole surface once and space their parts with one gap, like native sheets.
 * The header, body and footer add no padding and no dividers of their own.
 */
const dialogSlots = {
    overlay: "bg-scrim",
    content: "p-dialog gap-dialog-gap divide-y-0",
    header: "p-0 sm:p-0 min-h-0 shrink-0 items-start gap-base",
    wrapper: "flex-1 min-w-0",
    // The body is a column, so a code box inside can shrink and scroll on its own.
    body: "p-0 sm:p-0 flex flex-col min-h-0",
    footer: "p-0 sm:p-0 shrink-0 justify-end gap-base",
    title: "flex items-center gap-base text-sm",
    description: "mt-tight text-xs",
    // The close button sits in the header row, and the negative margin centers it on the title line.
    // It uses the small control size, which the close prop of the component cannot set from the theme.
    close: `static -my-tight -me-tight h-control-sm w-control-sm p-0 ${slottedIcon.sm}`,
};

const cardSlots = {
    header: "p-card sm:p-card",
    body: "p-card sm:p-card",
    footer: "p-card sm:p-card",
    description: "mt-tight",
};

const disabledToggle = {
    true: {
        root: "opacity-disabled",
        base: "cursor-default",
        label: "cursor-default",
        description: "cursor-default",
    },
};

/*
 * Components, in alphabetical order.
 */

export default {
    alert: {
        slots: {
            root: "shrink-0 rounded-md py-base px-control-md gap-base",
            title: "text-xs",
            // Full opacity keeps tinted alerts above 4.5 to 1.
            description: "text-xs opacity-100",
            icon: `${icon.sm} mt-line`,
            actions: "gap-base",
        },
        defaultVariants: {
            color: "neutral",
            variant: "outline",
        },
    },

    badge: {
        variants: {
            size: {
                xs: {
                    base: "h-chip-sm px-tight py-0 gap-tight text-xxs rounded-sm",
                    leadingIcon: icon.xs,
                    trailingIcon: icon.xs,
                },
                sm: {
                    base: "h-chip-sm px-control-xs py-0 gap-tight text-xxs rounded-sm",
                    leadingIcon: icon.xs,
                    trailingIcon: icon.xs,
                },
                md: {
                    base: "h-control-xs px-control-xs py-0 gap-tight text-xs rounded-sm",
                    leadingIcon: icon.xs,
                    trailingIcon: icon.xs,
                },
                lg: {
                    base: "h-control-sm px-control-sm py-0 gap-control-sm text-xs rounded-sm",
                    leadingIcon: icon.sm,
                    trailingIcon: icon.sm,
                },
                xl: {
                    base: "h-control-sm px-control-sm py-0 gap-control-sm text-xs rounded-sm",
                    leadingIcon: icon.sm,
                    trailingIcon: icon.sm,
                },
            },
        },
        defaultVariants: {
            color: "neutral",
            variant: "solid",
            size: "md",
        },
    },

    button: {
        slots: {
            base: `rounded-sm aria-disabled:cursor-default aria-disabled:opacity-disabled press-feedback ${controlBase}`,
        },
        variants: {
            size: {
                xs: {
                    base: `h-control-sm px-control-sm py-0 gap-control-xs text-control-xs ${slottedIcon.sm}`,
                    leadingIcon: icon.sm,
                    trailingIcon: icon.sm,
                },
                sm: {
                    base: `${control.sm} ${slottedIcon.sm}`,
                    leadingIcon: icon.sm,
                    trailingIcon: icon.sm,
                },
                md: {
                    base: `${control.md} ${slottedIcon.md}`,
                    leadingIcon: icon.md,
                    trailingIcon: icon.md,
                },
                lg: {
                    base: `${control.lg} ${slottedIcon.lg}`,
                    leadingIcon: icon.lg,
                    trailingIcon: icon.lg,
                },
                xl: {
                    base: `${control.lg} ${slottedIcon.lg}`,
                    leadingIcon: icon.lg,
                    trailingIcon: icon.lg,
                },
            },
        },
        compoundVariants: [
            { variant: ["outline", "subtle"], class: "ring-line" },
            {
                size: "xs",
                square: true,
                class: "w-control-sm p-0 justify-center",
            },
            {
                size: "sm",
                square: true,
                class: "w-control-sm p-0 justify-center",
            },
            {
                size: "md",
                square: true,
                class: "w-control-md p-0 justify-center",
            },
            {
                size: "lg",
                square: true,
                class: "w-control-lg p-0 justify-center",
            },
            {
                size: "xl",
                square: true,
                class: "w-control-lg p-0 justify-center",
            },
        ],
        defaultVariants: {
            color: "neutral",
            variant: "solid",
            size: "md",
        },
    },

    card: {
        slots: {
            root: "rounded-md",
            ...cardSlots,
        },
        variants: {
            variant: {
                outline: { root: "ring-line" },
                subtle: { root: "ring-line" },
            },
        },
    },

    checkbox: {
        variants: {
            disabled: disabledToggle,
        },
        defaultVariants: {
            color: "neutral",
            size: "md",
        },
    },

    collapsible: {
        slots: {
            content:
                "data-[state=open]:animate-collapsible-down data-[state=closed]:animate-collapsible-up",
        },
    },

    commandPalette: {
        slots: {
            // AppCommand renders its header and footer inside the footer slot.
            // The order classes then place the header between the input and the list.
            root: "bg-default rounded-lg overflow-hidden *:data-[slot=content]:order-1 **:data-[slot=command-footer]:order-2",
            group: "p-menu",
            footer: "contents",
            item: `${itemBase} items-center`,
        },
        variants: {
            size: {
                xs: paletteSm,
                sm: paletteSm,
                md: paletteMd,
                lg: paletteMd,
                xl: paletteMd,
            },
            active: {
                true: { item: "before:bg-overlay-hover" },
                false: {
                    item: "data-highlighted:not-data-disabled:before:bg-overlay-hover",
                },
            },
        },
        defaultVariants: {
            size: "md",
        },
    },

    dropdownMenu: {
        slots: {
            content: `min-w-overlay ${overlaySurface} ${fadeOverlay}`,
            viewport: overlayDivide,
            group: "p-menu",
            separator: overlaySeparator,
            item: itemBase,
        },
        variants: {
            active: {
                true: { item: "before:bg-overlay-hover" },
                false: { item: itemHighlight },
            },
            size: menuSizes,
        },
        defaultVariants: {
            size: "md",
        },
    },

    formField: {
        slots: {
            container: "mt-tight",
            error: "mt-tight",
            help: "mt-tight",
        },
        variants: {
            orientation: {
                vertical: { container: "mt-tight" },
            },
        },
    },

    input: {
        slots: {
            base: `rounded-md ${controlBase}`,
        },
        variants: {
            variant: {
                ...controlBorder,
                none: "bg-transparent ring-0 border-0 shadow-none focus:outline-none focus-visible:outline-none",
            },
            size: {
                xs: fieldSize("xs"),
                sm: fieldSize("sm"),
                md: fieldSize("md"),
                lg: fieldSize("lg"),
                xl: fieldSize("lg"),
            },
        },
        defaultVariants: {
            color: "neutral",
            variant: "outline",
            size: "md",
        },
    },

    kbd: {
        variants: {
            size: {
                sm: "h-chip-sm min-w-chip-sm text-xxs",
                md: "h-control-xs min-w-control-xs text-xxs",
                lg: "h-control-sm min-w-control-sm text-xs",
            },
        },
    },

    modal: {
        slots: {
            ...dialogSlots,
            content: `bg-overlay ${dialogSlots.content}`,
        },
        variants: {
            transition: {
                true: { overlay: fadeDialog, content: fadeDialog },
            },
            fullscreen: {
                false: { content: `max-w-dialog ${dialogSurface}` },
            },
            overlay: {
                true: { overlay: "bg-scrim" },
            },
        },
        compoundVariants: [
            {
                scrollable: false,
                fullscreen: false,
                class: { content: "max-h-dialog sm:max-h-dialog" },
            },
        ],
    },

    navigationMenu: {
        slots: {
            list: "flex flex-col gap-nav-gap",
            link: "before:rounded-sm",
            childLink: "before:rounded-sm",
            linkLeadingIcon: "size-nav-icon",
        },
        compoundVariants: [
            {
                variant: "pill",
                active: true,
                highlight: false,
                class: { link: "before:bg-sidebar-item-active" },
            },
        ],
    },

    popover: {
        slots: {
            // The box grows to its content up to a viewport based cap, and children never exceed it.
            content: `w-max max-w-popover *:max-w-full wrap-break-word p-popover ${overlaySurface} ${fadeOverlay}`,
        },
    },

    scrollArea: {
        slots: {
            root: "overscroll-none",
            // A full height viewport lets children such as the code editor fill the panel.
            // Children keep their size so taller content still overflows and scrolls.
            viewport: "h-full *:shrink-0",
        },
    },

    select,

    selectMenu: {
        ...select,
        slots: {
            ...select.slots,
            content: `w-max min-w-(--reka-combobox-trigger-width) max-w-(--reka-combobox-content-available-width) ${overlaySurface}`,
            input: "border-b-line border-default",
        },
        variants: {
            ...select.variants,
            virtualize: {
                false: { viewport: overlayDivide },
            },
        },
    },

    separator: {
        slots: {
            label: "text-xs text-muted",
        },
        defaultVariants: {
            color: "neutral",
            size: "xs",
            type: "solid",
        },
    },

    sidebar: {
        slots: {
            root: "[--sidebar-width:var(--sidebar-expanded-width)] [--sidebar-width-icon:var(--sidebar-collapsed-width)]",
            container: "z-sticky bg-muted border-default",
            header: "px-panel min-h-toolbar",
            body: "p-panel gap-base border-b-0",
            footer: "p-panel",
            actions: "gap-tight",
            rail: "z-raised",
        },
        variants: {
            side: {
                left: { container: "border-e-line" },
                right: { container: "border-s-line" },
            },
            transition: {
                true: {
                    gap: "duration-base",
                    container: "duration-base",
                },
            },
        },
    },

    skeleton: {
        base: "bg-accented/50",
    },

    slideover: {
        slots: {
            ...dialogSlots,
            content: `bg-overlay sm:shadow-modal ${dialogSlots.content}`,
        },
        variants: {
            inset: {
                true: { content: "rounded-lg" },
            },
            transition: {
                true: { overlay: fadeDialog },
            },
        },
        compoundVariants: ["top", "right", "bottom", "left"].map((side) => ({
            transition: true,
            side,
            class: { content: fadeDialog },
        })),
    },

    // The handle is a thin line. A wider invisible hit area sits on the after pseudo element.
    splitter: {
        slots: {
            panel: "block",
            handle: "bg-border hover:bg-accented data-[resize-handle-state=hover]:bg-accented data-[resize-handle-state=drag]:bg-accented transition-colors after:absolute",
        },
        variants: {
            orientation: {
                horizontal: {
                    handle: "w-line after:inset-y-0 after:left-1/2 after:w-hit-sm after:-translate-x-1/2",
                },
                vertical: {
                    handle: "h-line after:inset-x-0 after:top-1/2 after:h-hit-sm after:-translate-y-1/2",
                },
            },
        },
    },

    switch: {
        slots: {
            base: "group/switch duration-base",
            thumb: "shadow-control duration-spring ease-elastic! group-active/switch:scale-x-125 motion-reduce:group-active/switch:scale-x-100 motion-reduce:transition-none data-[state=checked]:origin-right data-[state=unchecked]:origin-left",
        },
        variants: {
            disabled: disabledToggle,
        },
        defaultVariants: {
            color: "neutral",
            size: "md",
        },
    },

    // Tables fit their container. Long values wrap, and rows have no grid lines unless a column adds them.
    table: {
        slots: {
            base: "w-full table-fixed",
            tbody: "divide-y-0",
            th: "p-0 px-control-sm py-tight text-xs font-medium text-muted",
            td: "p-0 px-control-sm py-tight text-xs text-default align-top whitespace-normal wrap-anywhere",
            empty: "py-loose text-xs",
            loading: "py-loose",
        },
    },

    tabs: {
        slots: {
            list: "p-tabs gap-tabs-gap overflow-x-auto no-scrollbar",
            indicator: "hidden",
            content: "rounded-none",
            trigger: `shrink-0 grow-0 rounded-sm disabled:cursor-default disabled:opacity-disabled duration-base ${activeTab}`,
        },
        variants: {
            variant: {
                pill: { list: "rounded-md" },
            },
            // Triggers have a fixed height, so a taller list must center them.
            orientation: {
                horizontal: { list: "items-center" },
            },
            size: {
                xs: {
                    trigger:
                        "h-control-xs px-control-xs py-0 gap-control-xs text-control-xs",
                    leadingIcon: icon.xs,
                },
                sm: {
                    trigger:
                        "h-control-sm px-control-sm py-0 gap-control-sm text-control-sm",
                    leadingIcon: icon.sm,
                },
                md: {
                    trigger:
                        "h-control-md px-control-md py-0 gap-control-md text-control-md",
                    leadingIcon: icon.md,
                },
                lg: {
                    trigger:
                        "h-control-lg px-control-lg py-0 gap-control-lg text-control-lg",
                    leadingIcon: icon.lg,
                },
                xl: {
                    trigger:
                        "h-control-lg px-control-lg py-0 gap-control-lg text-control-lg",
                    leadingIcon: icon.lg,
                },
            },
        },
        compoundVariants: [
            // Link tabs are plain text, and the active one is darker.
            {
                orientation: "horizontal",
                variant: "link",
                class: {
                    list: "border-b-0 mb-0 p-0 gap-tabs-gap",
                    trigger:
                        "rounded-md bg-transparent shadow-none ring-0 text-dimmed hover:text-default data-[state=active]:shadow-none data-[state=active]:ring-0 data-[state=active]:text-highlighted",
                },
            },
        ],
        defaultVariants: {
            color: "neutral",
            variant: "pill",
            size: "md",
        },
    },

    textarea: {
        slots: {
            base: `rounded-md ${controlBase}`,
        },
        variants: {
            variant: controlBorder,
            size: {
                xs: {
                    base: "min-h-control-xs px-control-xs py-tight gap-control-xs text-control-xs",
                    leadingIcon: icon.xs,
                    trailingIcon: icon.xs,
                },
                sm: {
                    base: "min-h-control-sm px-control-sm py-tight gap-control-sm text-control-sm",
                    leadingIcon: icon.sm,
                    trailingIcon: icon.sm,
                },
                md: {
                    base: "min-h-control-md px-control-md py-tight gap-control-md text-control-md",
                    leadingIcon: icon.md,
                    trailingIcon: icon.md,
                },
                lg: {
                    base: "min-h-control-lg px-control-lg py-tight gap-control-lg text-control-lg",
                    leadingIcon: icon.lg,
                    trailingIcon: icon.lg,
                },
                xl: {
                    base: "min-h-control-lg px-control-lg py-tight gap-control-lg text-control-lg",
                    leadingIcon: icon.lg,
                    trailingIcon: icon.lg,
                },
            },
        },
        defaultVariants: {
            color: "neutral",
            variant: "outline",
            size: "md",
        },
    },

    toast: {
        slots: {
            root: "bg-overlay rounded-md shadow-overlay p-toast gap-base",
            description: "mt-tight",
            icon: icon.lg,
            actions: "gap-base",
        },
        defaultVariants: {
            color: "neutral",
        },
    },

    toaster: {
        slots: {
            viewport: "z-toast",
            base: "duration-base",
        },
    },

    tooltip: {
        slots: {
            // Reka places tooltips a fixed distance away, so the nudge pulls them toward the trigger.
            content: `bg-overlay rounded-sm shadow-overlay data-[side=top]:translate-y-tooltip-nudge data-[side=bottom]:-translate-y-tooltip-nudge data-[side=left]:translate-x-tooltip-nudge data-[side=right]:-translate-x-tooltip-nudge h-item-sm px-control-sm py-0 ${fadeOverlay} data-[state=delayed-open]:animate-fade-in-fast`,
        },
    },
};
