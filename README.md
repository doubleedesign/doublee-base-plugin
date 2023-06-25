# doublee-plugin-framework

Framework/template for building a plugin in an OOP fashion. Based on plugins I have created using the [Enrique Chávez/Tom McFarlin/Devin Vinson WordPress Plugin Boilerplate Generator](https://wppb.me/) but largely simplified and restructured to suit my usual needs.

## What's included

To show you how to use it, this package comes with examples of some of the common things I do in client-specific plugins:
 - How you could add admin notices
 - The creation of custom roles; out-of-the box it has an "Editor Plus" role and works like so:
    - Upon plugin activation, the Editor Plus role is created based on the built-in Editor role, and the `edit_theme_options` capability is added
    - Upon deactivation, users with the role are reverted to Editors
    - Upon reactivation (without uninstallation), users who had the Editor Plus role should get it back (note: this is because a capability by the same name is left there unless the plugin is uninstalled; if you intend to use `current_user_can('editor_plus')` then this may not suit your needs)
    - Upon uninstallation, the remnants of the role are totally wiped so if the plugin is reactivated again, custom roles must be manually reassigned.
 - A `frontend` folder where I sometimes put templates/partials when they're so tightly coupled to plugin functionality that it makes more sense than putting them in the theme. (Not super common, but it's happened.)

## How to use

1. Update and rename `myplugin.php` with your own plugin name, description, author, and text domain.
2. Rename `class-myplugin.php` so `myplugin` is the all-lowercase name of your plugin. This is the "plugin bootstrap file".
3. Rename and find & replace references to `MYPLUGIN_VERSION` and `MYPLUGIN_PLUGIN_PATH`.
4. Do a case-sensitive find and replace throughout the folder for `myplugin`, replacing it with the all-lowercase name of your plugin.
5. Do a case-sensitive find and replace throughout the folder for `MyPlugin`, replacing it with the PascalCase name of your plugin.
6. Find & replace `@author     Leesa Ward` with your name throughout.
7. Familiarise yourself with the plugin bootstrap file to see how functions from the various classes are called using WordPress hooks and filters. 
8. Remove/modify the examples to suit your needs.
9. Start adding your own classes for the units of functionality you require, and call their functions in the bootstrap file.
10. More code stuff. Build all the things.
11. Profit.
