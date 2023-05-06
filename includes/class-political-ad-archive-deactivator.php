<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveDeactivator {

    /**
     * The plugin is disabled, but not uninstalled.
     * Make sure it isn't clogging any pipes but leave the data behind for now.
     *
     * @since    1.0.0
     */
  public static function deactivate() {
      PoliticalAdArchiveDeactivator::deactivate_archive_sync();
  }

  private static function deactivate_archive_sync() {
      // Does the scheduled task exist already?
      $schedule = wp_get_schedule('archive_sync') === false;
    if ($schedule) {
        wp_unschedule_event(wp_next_scheduled('archive_sync'), 'archive_sync');
    }
  }
}
