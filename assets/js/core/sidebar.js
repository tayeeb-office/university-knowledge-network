/**
 * Left sidebar behavior.
 *
 * This used to auto-initialize Bootstrap's Tooltip component on every nav
 * link so labels stayed reachable when the sidebar collapses to an
 * icon-only rail at tablet widths (768px-991px). That fired at every
 * screen width, though, and Bootstrap's default (unthemed) tooltip has a
 * solid black background — so hovering any nav item, even at full desktop
 * width where the label text is already visible, popped up a redundant
 * black box beside it.
 *
 * The plain `title` attribute already on each link (see
 * includes/left-sidebar.php) gives the same icon-rail hint via the
 * browser's own native tooltip, with no JS and no unthemed black
 * component — so nothing needs to be initialized here anymore. Kept as an
 * intentional no-op (rather than deleted) so this stays the one place to
 * add future left-sidebar-only behavior.
 */
