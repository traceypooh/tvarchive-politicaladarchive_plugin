<?php

/**
 * The Political Ad Archive
 * (code layout based on https://github.com/DevinVinson/WordPress-Plugin-Boilerplate)
 *
 * @link              https://politicaladarchive.org
 * @since             1.0.0
 * @package           PoliticalAdArchive
 *
 * @wordpress-plugin
 * Plugin Name:       Political Ad Archive
 * Plugin URI:        http://politicaladarchive.com/
 * Description:       Enables integration with the Internet Archive's Political Ad dataset
 * Version:           1.0.0
 * Author:            The Internet Archive
 * Author URI:        http://slifty.com
 * License:           TBD
 * License URI:       TBD
 * Text Domain:       political-ad-archive
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-political-ad-archive-activator.php
 */
function activate_political_ad_archive() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-political-ad-archive-activator.php';
    PoliticalAdArchiveActivator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-political-ad-archive-deactivator.php
 */
function deactivate_political_ad_archive() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-political-ad-archive-deactivator.php';
    PoliticalAdArchiveDeactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_political_ad_archive');
register_deactivation_hook(__FILE__, 'deactivate_political_ad_archive');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-political-ad-archive.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_political_ad_archive() {

    $plugin = new PoliticalAdArchive();
    $plugin->run();

}
run_political_ad_archive();

?>
