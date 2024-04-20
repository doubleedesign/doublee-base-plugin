<?php

/**
 * This class defines functions to add admin messages.
 *
 * @since      1.0.0
 * @package    MyPlugin
 * @author     Leesa Ward
 */
class MyPlugin_Admin_Notices {

    public function __construct() {
        add_action('admin_notices', array($this, 'required_plugins_notification'));
    }

    /**
     * The admin notice for if required plugins are missing
     * @wp-hook
     *
     * @return void
     */
    function required_plugins_notification(): void {
        $warnings = array();
//        if (!is_plugin_active('classic-editor/classic-editor.php')) {
//            $warnings[] = 'Classic Editor';
//        }
        if (!is_plugin_active('advanced-custom-fields-pro/acf.php')) {
            $warnings[] = 'Advanced Custom Fields Pro';
        }

        if (count($warnings) > 0) {
            echo '<div class="notice notice-error">';
            echo '<p>The ' . MyPlugin::get_name() . ' plugin requires the following plugins to be installed and activated for full functionality. Without them, some features may be missing or not work as expected.</p>';
            echo '<ul>';
            foreach ($warnings as $warning) {
                echo '<li>' . $warning . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    }

}
