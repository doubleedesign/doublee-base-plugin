# doublee-plugin-framework

Framework/template for building a WordPress plugin in an OOP fashion, including some common stuff I use for client sites.

## What's included

 - Admin notices for required/recommended plugins
 - Defaults for hiding and positioning of certain metaboxes in the admin edit screens (for simplicity)
 - Defaults for hiding and positioning of certain columns in the admin list tables (for simplicity)
 - Conditionally loading and saving certain ACF field groups within the plugin, rather than the active theme
 - Customised admin menu ordering and sectioning
 - An additional context for displaying metaboxes (`after_title`)
 - "Editor Plus" custom role that works like so:
    - Upon plugin activation, the Editor Plus role is created based on the built-in Editor role, and some capabilities I commonly assign to clients are added to it
    - Upon deactivation, users with the role are reverted to Editors
    - Upon reactivation (without uninstallation), users who had the Editor Plus role should get it back (note: this is because a capability by the same name is left there unless the plugin is uninstalled; if you intend to use `current_user_can('editor_plus')` then this may not suit your needs)
    - Upon uninstallation, the remnants of the role are totally wiped so if the plugin is reactivated again, custom roles must be manually reassigned.

Please see the [changelog](CHANGELOG.md) for more information and the latest updates.

## How to use

1. Update and rename `myplugin.php` with your own plugin name, description, author, and text domain. 
2. Rename the plugin folder and find & replace `doublee-plugin-framework` with it throughout.
3. Rename `class-myplugin.php` so `myplugin` is the all-lowercase name of your plugin
4. Rename and find & replace references to `MYPLUGIN_VERSION` and `MYPLUGIN_PLUGIN_PATH`.
5. Do a case-sensitive find and replace throughout the folder for `myplugin`, replacing it with the all-lowercase name of your plugin.
6. Do a case-sensitive find and replace throughout the folder for `MyPlugin`, replacing it with the PascalCase name of your plugin.
7. Find & replace `@author     Leesa Ward` with your name throughout.
8. Remove/modify/add to the provided classes to suit your needs.
9. Start adding your own classes for the units of functionality you require.
10. More code stuff. Build all the things.
11. Profit.

## General intentions and advice

I use this with my own [theme starterkit](https://github.com/doubleedesign/doublee-theme-starter-kit) to create custom sites with clear separation of concerns as much as is practical. As a  guide:
- Code related to front-end design and content display belongs in the theme
- Custom functionality, custom post types, custom taxonomies, modifications to WordPress functionality (including the admin UI), site-specific data structures and management belong in the plugin.
