# Base (App) Components

This folder is the only place that talks to Nuxt UI.
Every `App*` component is a thin wrapper over a Nuxt UI component.
The rest of the app imports the `App*` wrappers and never imports `@nuxt/ui`.

## Rules

1. Only files in this folder import from `@nuxt/ui`.
   The exceptions are `app/app.ts`, `app/App.vue`, the test setup, and the Vite config.
2. A wrapper holds no styling.
   It has no sizes, colors, spacing, or classes.
   It exposes the Nuxt UI vocabulary (`color`, `variant`, `size`, `items`, slots) and passes `data-testid` and other attributes through.
3. Visual decisions live in two places.
   The theme preset is `tools/ui/theme.js`, with one section per Nuxt UI component.
   The design tokens are in `resources/css/themes/base.css`.
4. Domain code never imports a Nuxt UI type.
   Use the aliases exported by the base barrels, for example `AppDropdownMenuItem`, `AppSelectItem`, `AppTabsItem`, `AppTableColumn`, `AppCommandGroup`, and `AppSplitterItem`.
5. Import `reka-ui` only where Nuxt UI has no equivalent, and say what is missing in a comment.
6. Do not edit the wrappers to change how something looks.
   Change the preset or the tokens.

## Spacing

1. Every padding and gap comes from one scale of tokens in `resources/css/themes/base.css`.
   The preset reads them, so wrappers never set spacing.
2. Use `--gap-tight` (4px), `--gap-base` (8px), and `--gap-loose` (12px) for gaps.
3. Surfaces have their own padding token.
   Menus use `--menu-padding` (4px), popovers `--popover-padding` (8px), cards `--card-padding` (12px), and toasts `--toast-padding` (12px).
   Alerts pad 8px by 10px with `text-xs` text.
   Dialogs use `--dialog-padding` (12px) around the surface and `--dialog-gap` (12px) between header, body, and footer.
   Dialogs are `max-w-dialog` (480px) wide, or `max-w-dialog-lg` (560px) for code blocks, and stop at `max-h-dialog` (70dvh).
4. Content passed into a popover or dialog slot adds no outer padding of its own, because the surface already has it.
5. The full table is in the Design System section of `wiki/contribution-guide/README.md`.

## Icons

1. Icons come from the HugeIcons collection on Iconify, through Nuxt UI.
2. Write the full literal name, for example `i-hugeicons-cancel-01`.
   Never build a name from parts, because it is not bundled and would be fetched from the network.
3. Use `AppIcon` in templates, or pass the name to a prop such as `icon`, `leading-icon`, or `trailing-icon`.
4. Nuxt UI's own icons, such as chevrons, close, and loading, are set to HugeIcons in `vite.config.js`.

## How a wrapper is written

A wrapper declares the props, emits, and slots of the Nuxt UI component, so call sites are type checked.

1. Import the props type and the component from `@nuxt/ui/components/<Name>.vue`.
2. Declare props with the Nuxt UI props type.
   Extend it with an interface for plain components, or use `generic` when the items are generic (Select, Tabs, Table, DropdownMenu, Command).
3. Forward props with `useForwardedProps`.
   Binding `$props` directly sends `false` for absent booleans and turns uncontrolled components into controlled ones.
4. Declare the emits and forward each one.
   Declared emits are removed from `$attrs`, so they are not forwarded for you.
5. Declare the slots with `defineSlots` and forward every slot.
6. Do not bind both `$attrs` and `$props` to the same component.

When Vue cannot resolve a type that is declared in the same file as a generic script, move that type to a `types.ts` file next to the wrapper.

## Adding a wrapper

1. Create `<folder>/App<Name>.vue` and use an existing wrapper such as `tabs/AppTabs.vue` as the model.
2. Export it from the folder `index.ts`.
   Also export the type aliases that domain code needs, with the `App` prefix.
3. Write a test in `resources/js/tests/components/base`.
   Test rendered text, attributes, emitted events, roles, and `data-testid`.
   Never assert class names.
4. If the component needs a look that Nuxt UI does not have, add it to the preset.

## Adding a variant

1. Open the component section in `tools/ui/theme.js`.
2. Add the variant to the `variants` of that component, using the named token utilities such as `h-control-md`, `gap-base` and `shadow-overlay`.
   The wiki Design System section lists them all.
   Run `npm run check:theme` afterwards.
3. Use it from the call site with the Nuxt UI prop, for example `variant="soft"`.
4. Do not add a class or a prop to the wrapper for it.

## Components that are not wrappers

These are small and local to the app, and they take colors from the tokens.
They hold their own classes because Nuxt UI has no component they could wrap.
Nuxt UI's FormField is a layout with a hint and error, so it is not a match for a bare label.

- `AppLabel` is a plain `<label>` with the form label typography.
- `AppPanel*` are layout parts for panel headers, titles, and content, and `AppPanelStateContainer` centers state content over a ripple background.
- `AppRipple*` and `AppPanelRipple` are CSS animations with no Nuxt UI equivalent.
- `AppSpinner` is an animated SVG loading indicator.
- `AppRoundIndicator` is a colored status dot.
- `AppBorderBeam` and `AppGlowingButton` are decorative glow effects.
- `AppSwapIcon` crossfades between two icons when the name changes.
- `AppBrandIcon` is the app logo.
- `AppIcon` is an icon wrapper that only sets the Iconify name.
- `AppEditable` wraps Reka directly because Nuxt UI has no inline editing component.

## Documented exceptions

- `AppTabs` imports the `useTabHorizontalScroll` composable.
  The scroll fade and scroll into view behavior belongs to the tab list, so the composable stays with the wrapper.

Business logic stays out of this folder.
For example, double Shift value generation lives in `GeneratorInput` in `components/common`, not in `AppInput`.
