<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveActivator {

    /**
     * Life! Glorious life! Activation commencing!
     *
     * @since    1.0.0
     */
  public static function activate() {
      PoliticalAdArchiveActivator::create_ad_instances_table();
      PoliticalAdArchiveActivator::create_ad_candidates_table();
      PoliticalAdArchiveActivator::create_ad_sponsors_table();
      PoliticalAdArchiveActivator::activate_archive_sync();
  }

    /**
     * Register the custom table that will store information about ad airings
     */
  private static function create_ad_instances_table() {
      global $wpdb;
      global $ad_db_version;
      // Wordpress doesn't load upgrade.php by default
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

      // Create the ad instances data table
      $table_name = $wpdb->prefix . 'ad_instances';

      $charset_collate = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            wp_identifier mediumint(9) NOT NULL,
            archive_identifier varchar(100) NOT NULL,
            network varchar(20),
            market varchar(20),
            location varchar(128),
            program varchar(128),
            program_type varchar(128),
            start_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            end_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            UNIQUE KEY instance_key (archive_identifier,network,start_time),
            KEY archive_identifier_key (archive_identifier),
            KEY wp_identifier_key (wp_identifier),
            KEY network_key (network),
            KEY market_key (market),
            KEY program_key (program),
            KEY program_type_key (program_type)
        ) $charset_collate;";
      dbDelta($sql);
  }

  private static function create_ad_candidates_table() {
      global $wpdb;
      global $ad_db_version;
      // Wordpress doesn't load upgrade.php by default
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

      // Create the ad instances data table
      $table_name = $wpdb->prefix . 'ad_candidates';

      $charset_collate = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            crp_unique_id varchar(100) NOT NULL,
            name varchar(128),
            race char(4),
            cycle char(4),
            affiliation char(1),
            ad_count int,
            air_count int,
            date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            KEY crp_unique_key (crp_unique_id),
            KEY name_key (name),
            KEY race_key (race),
            KEY affiliation_key (affiliation),
            KEY air_count_key (air_count)
        ) $charset_collate;";
      dbDelta($sql);
  }

  private static function create_ad_sponsors_table() {
      global $wpdb;
      global $ad_db_version;
      // Wordpress doesn't load upgrade.php by default
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

      // Create the ad instances data table
      $table_name = $wpdb->prefix . 'ad_sponsors';

      $charset_collate = $wpdb->get_charset_collate();
      $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            crp_unique_id varchar(100) NOT NULL,
            name varchar(128),
            race char(4),
            cycle char(4),
            type varchar(16),
            single_ad_candidate_id varchar(100),
            does_support_candidate tinyint(1),
            ad_count int,
            air_count int,
            date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            KEY crp_unique_key (crp_unique_id),
            KEY single_ad_candidate_key (single_ad_candidate_id),
            KEY name_key (name),
            KEY type_key (type),
            KEY race_key (race),
            KEY air_count_key (air_count)
        ) $charset_collate;";
      dbDelta($sql);
  }

    /**
     * Begin regular ad data synchronization with the Internet Archive
     */
  private static function activate_archive_sync() {
      // Does the scheduled task exist already?
    if (wp_get_schedule('archive_sync') === false) {
        wp_schedule_event(time(), 'hourly', 'archive_sync');
    }
  }

}
