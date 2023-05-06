<?php

/**
 * The channel model
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes/models
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveChannel {

    private $channel; // The name of the channel

  public function __construct() {}

  public function __get($property) {
    if (property_exists($this, $property)) {
        return $this->$property;
    }
  }

  public function __set($property, $value) {
    if (property_exists($this, $property)) {
        $this->$property = $value;
    }

      return $this;
  }

    /**
     * Returns a list of candidates
     * @return [type] [description]
     */
  public static function get_channels() {
      global $wpdb;

      // Run the query
      $posts_table = $wpdb->prefix . 'posts';
      $instances_table = $wpdb->prefix . 'ad_instances';
      $query = "SELECT DISTINCT ".$instances_table.".network as network
				    FROM ".$instances_table."
				    JOIN ".$posts_table." ON ".$instances_table.".wp_identifier = ".$posts_table.".ID
				   WHERE ".$posts_table.".post_status = 'publish'";
      $results = $wpdb->get_results($query);

      // Package the results
      $channels = array();
    foreach ($results as $result) {
        $channel = new PoliticalAdArchiveChannel();
        $channel->channel = $result->network;
        $channels[] = $channel;
    }

      return $channels;
  }

}