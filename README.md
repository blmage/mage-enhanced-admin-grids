# Enhanced Admin Grids
## Version 1.0.0 (work-in-progress)

_This version is a work-in-progress, it is strictly for testing purposes, unless you know what you are doing._

More informations about the extension can be found on its Magento Connect page here : https://www.magentocommerce.com/magento-connect/enhanced-admin-grids-editor.html.

### Backwards compatibility
Due to the code refactoring, any custom development based on classes coming from the previous versions of the extension, or using an own `customgrid.xml` file, may certainly not be compatible as-is with the new version. Please review the changes and adapt your code accordingly before using the new version on a live environment.

_Backwards compatibility is assured for all the previously existing data. **If you're upgrading from a previous version, flushing the cache storage is likely to be needed to ensure that everything works fine (due to some renamings in the database structure).**_

### Main changes / New features :
- massive code refactoring (goals: better maintainability, more consistency, more independence, better practices and a smaller footprint)
- every in-grid customization is now saved via Ajax, for a seamless integration in Ajax-based grids
- profiles system (different columns lists and default parameters for each grid, assignable to different roles)
- advanced filtering possibilities for text, options and country columns (expect for original grid columns, as for the rest)
- forms in configuration windows are now split in multiple collapsible fieldsets
- refined permissions
- various bug fixes
- **_todo_** forcable grid types (use advanced features for the grids that you know to be compatible with a given grid type, but by default are not associated to it)

### Continuous changes :
- new custom columns for different grids (especially the sales grids)
- better compatibility with certain grids
- various small improvements

### Other considered changes (secondary todo list) :
- callbacks system for the editors, then editable custom columns
- duplicatable custom columns
- profiles groups ?
- better design :)