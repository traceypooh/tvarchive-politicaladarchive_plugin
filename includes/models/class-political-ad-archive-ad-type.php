<?php

/**
 * The ad type model
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes/models
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveAdType {

    private $type; // The string value of the type

  public function __construct() {
  }

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
  public static function get_ad_types() {
      global $wpdb;

      // Run the query
      $posts_table = $wpdb->prefix . 'posts';
      $meta_table = $wpdb->prefix . 'postmeta';
      $query = "SELECT DISTINCT ".$meta_table.".meta_value as type
				    FROM ".$meta_table."
				    JOIN ".$posts_table." ON ".$meta_table.".post_id = ".$posts_table.".ID
				   WHERE ".$meta_table.".meta_key LIKE 'ad_type'
				     AND ".$posts_table.".post_status = 'publish'";
      $results = $wpdb->get_results($query);

      // Package the results
      $ad_types = array();
    foreach ($results as $result) {
        $ad_type = new PoliticalAdArchiveAdType();
        $ad_type->type = $result->type;
        $ad_types[] = $ad_type;
    }

      return $ad_types;
  }
}
