# YOOtheme Integration

## Goal

This addon adds an optional YOOtheme/UIkit rendering mode for selected frontend
views. The default remains Joomla/Bootstrap so existing sites keep their current
markup unless the new option is enabled.

## Configuration

The new setting is:

`Configuration -> Frontend -> Gallery view -> Frontend framework`

Available values:

- `Joomla (Bootstrap)`, the default value
- `YOOtheme (UIkit)`

The stored parameter is `jg_gallery_view_framework`.

## Behavior

When `Joomla (Bootstrap)` is selected, the affected views keep the existing
Bootstrap-oriented classes.

When `YOOtheme (UIkit)` is selected, the affected views:

- use UIkit classes for headings, buttons, alignment and module wrappers
- replace the `jg-image-thumbnail` wrapper with `uk-display-block` in the shared
  image grid layout
- render the frontend image-management table with UIkit table, label, button,
  alert, grid and responsive visibility classes
- keep the existing lightbox, pagination, grid script and module positions

## Database Update

The update SQL file adds:

```sql
`jg_gallery_view_framework` VARCHAR(25) NOT NULL DEFAULT "joomla"
```

Existing installations need to run the component update so the new configuration
column is created.

If the form files are copied without running the database update, the setting is
hidden and a warning is shown because the selected value cannot be persisted.

## Covered Views

- `view=gallery`
- `view=images`
- shared layout `joomgallery.grids.images`
