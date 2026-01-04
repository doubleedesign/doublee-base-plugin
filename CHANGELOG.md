# Double-E Plugin changelog

## Version 4.0.0
Date: 4 January 2026

- Refactor: Refactor plugin structure to use PSR-4 autoloading via Composer
- Cleanup: Cleanup: Remove unused code from when ACF JSON was used for field group storage
## Version 3.1.0 
Date: 20 December 2025

- Feature: Add custom welcome screen and disable the default one
- Refactor: Move Page Behaviour field group definition into PHP
- Cleanup: Remove block pattern menu item customisation and block instructions fields (now handled in my block plugin)

## Version 4.0.0
Date: 4 January 2026

- Refactor: Refactor plugin structure to use PSR-4 autoloading via Composer
- Cleanup: Remove leftover field definitions from my brief experimentation with ACF Extended

## Version 3.1.0 
Date: 20 December 2025

- Feature: Add custom welcome screen and disable the default one
- Refactor: Move Page Behaviour field group definition into PHP
- Cleanup: Remove block pattern menu item customisation and block instructions fields (now handled in my block plugin)

## Version 3.0.0

- Feature: Add Page Behaviour options
- Feature: Highlight my plugins in the plugins list
- Enhancement: Improved messaging and ability to override global settings fields from other plugins/themes
- Refactor: Move global options ACF field registration into PHP (instead of JSON)
- Refactor: Simplify custom admin menu implementation
- Fix: Set PAGE_FOR_POSTS constant within the plugin
- Fix: Add ACF Pro as a required plugin
- Fix: Show where event fields are saved in backend
- Fix: Remove WP version requirement to enable ClassicPress support
- Cleanup: Remove dev logging functions that I now have in separate local dev plugin

## Version 2.1.0
Date: 21 April 2024

### Approach change
- Renamed plugin and updated README to reflect using this plugin as-is across client sites because much of the functionality doesn't change, it just gets added to. So it makes sense to make this more easily updatable and put further customisations in a second, per-client plugin. 

### General fixes and improvements
- `show_where_acf_fields_are_loaded_from`: Account for Double-E Events plugin
- Better account for WooCommerce and Ninja Forms in admin menu sectioning/ordering
- Add basic SEO-friendly title for sites that don't require a full SEO plugin (intending to add more basic SEO functionality)
- Add permissions to Editor Plus role for managing Smash Balloon Instagram and Facebook plugin settings

## Version 2.0.0
Date: 4 November 2023

### Refactors
- Removed the loader class, instead running actions and filters in the individual classes' constructors
  - This is a more modular approach, keeping things more self-contained
  - This removes the need to tag functions with @wp-hook because PHPStorm now correctly identifies that they are being used
- Move the setup of ACF 'Global Options' page into this plugin instead of my Starterkit theme

### New features
- Load 'Global Options' ACF field group from, and save it to, the plugin folder instead of the active theme
  - Note: The same process should be followed for fields for custom post types and taxonomies added via the plugin. Example for an 'Event' CPT is below.
- Enable loading of ACF JSON files from the plugin (`assets/acf-json` folder), while keeping loading from the theme intact 
  - Update Local JSON column in the ACF Field Groups list to say whether the fields are in the plugin or the theme
- Hide some edit screen metaboxes by default for UI simplicity (all can still be shown using Screen Options for individual users as standard)
  - Dashboard: Quick Draft, WordPress News  & Events
  - Pages: Comments, revisions, Yoast SEO (if active)
  - Posts: Yoast SEO, comments, revisions, tags, Yoast SEO (if active)
  - Products (if WooCommerce is active): Short description, reviews, tags, product gallery, Yoast SEO (if active)
- Hide some admin list table columns by default for UI simplicity
  - Pages: Yoast SEO columns (if active)
  - Posts: Tags, Yoast SEO columns (if active)
  - Products (if WooCommerce is active): Tags, SKU, Yoast SEO columns (if active)
- Remove the Welcome panel that appears after an update to WordPress (I may bring this back if/when I add block theme capabilities)
- Add section titles to the admin menu and organise menu items into groups accordingly
  - Plugin-wise, this accounts for WooCommerce, Ninja Forms, ACF, and Yoast SEO out of the box
- Load an admin stylesheet for plugin-specific admin CSS customisations
- Add a new `after_title` metabox context
  - If WooCommerce is active, this is used to move the Product Data box above the description by default (users can still drag and drop it to an alternative location as normal though)

### Fixes
- Added requirement for Classic Editor as an admin notice if it isn't installed and active
- Added minimum WordPress and PHP version requirements* (*these are just what I'm working with at the time of writing; slightly lower versions may work, I haven't tested extensively)

