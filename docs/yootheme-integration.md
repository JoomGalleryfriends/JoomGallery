# YOOtheme UIkit Layouts

## Goal

This addon provides optional UIkit-based frontend layouts for sites running
YOOtheme Pro, without changing the existing JoomGallery default templates.

The integration is parallel: existing gallery and category menu items keep their
current markup unless the new `uikit` alternative layout is selected.

## Added layouts

- `site/com_joomgallery/tmpl/gallery/uikit.php`
- `site/com_joomgallery/tmpl/gallery/uikit.xml`
- `site/com_joomgallery/tmpl/category/uikit.php`
- `site/com_joomgallery/tmpl/category/uikit.xml`
- `site/com_joomgallery/layouts/joomgallery/uikit/images.php`
- `site/com_joomgallery/layouts/joomgallery/uikit/subcategories.php`

The default `gallery`, `category`, `image`, `images` and shared `grids` layout
files are intentionally not modified.

## Configuration

The UIkit layouts can use global settings or menu-item overrides for:

- UIkit grid gap
- UIkit masonry
- UIkit lightbox
- Overlay style
- Overlay text color
- Image ratio
- Title position
- Overlay button text

Global defaults are available in `Frontend Views -> UIkit Layouts`.

Menu item fields use `useglobal="true"` so each UIkit menu item can either
inherit the global configuration or override individual values.

## Database Update

The update SQL file adds only `jg_uikit_*` configuration columns. Existing
installations need to run the component database update before saving the new
global UIkit options.

If the form files are copied without running the database update, the UIkit
settings are hidden and a warning is shown because the selected values cannot be
persisted yet.
