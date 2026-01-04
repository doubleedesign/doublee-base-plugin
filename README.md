# doublee-base-plugin

Common customisations for client websites.

## What's included
 - Customised welcome screen 
 - ACF fields for logo, contact information, social media links etc
 - Admin notices for required/recommended plugins
 - Defaults for hiding and positioning of certain metaboxes in the admin edit screens (for simplicity)
 - Defaults for hiding and positioning of certain columns in the admin list tables (for simplicity)
 - Conditionally loading and saving certain ACF field groups within the plugin, rather than the active theme
 - Customised admin menu ordering and sectioning
 - An additional context for displaying metaboxes (`after_title`)
 - Automatic basic `<title>` tags (for sites that don't need a full SEO plugin)
 - "Editor Plus" custom role:
   - Permissions:
     - All the capabilities an Editor has
     - Capabiltiies to add, edit, promote, and delete non-admin users
     - All capabilities for Ninja Forms
     - Capabilities to manage SmashBalloon Instagram and Facebook feed settings
   - How it works:
     - Upon plugin activation, the Editor Plus role is created based on the built-in Editor role, and some capabilities I commonly assign to clients are added to it
     - Upon deactivation, users with the role are reverted to Editors
     - Upon reactivation (without uninstallation), users who had the Editor Plus role should get it back (note: this is because a capability by the same name is left there unless the plugin is uninstalled; if you intend to use `current_user_can('editor_plus')` then this may not suit your needs)
     - Upon uninstallation, the remnants of the role are totally wiped so if the plugin is reactivated again, custom roles must be manually reassigned.

Please see the [changelog](CHANGELOG.md) for more information and the latest updates.

## General intentions and advice

I use this with my own theme starterkits, and client-specific custom plugins, and other plugins I have developed to create custom sites with clear separation of concerns as much as is practical. As a  guide:
- Code related to front-end design and content display belongs in the theme
- Custom functionality, custom post types, custom taxonomies, modifications to WordPress functionality (including the admin UI), site-specific data structures and management belong in plugins.
