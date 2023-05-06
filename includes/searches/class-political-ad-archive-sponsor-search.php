<?php

/**
 * The sponsor search class
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes/models
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveAdSponsorSearch implements PoliticalAdArchiveBufferedQuery {

    private $posts_per_page;

  public function __construct($args = null) {
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

  public function get_total_rows() {
      return -1;
  }

  public function get_chunk($page) {

    global $wpdb;

    // Collect the counts of ads per market
    $postmeta_table = $wpdb->prefix . 'postmeta';
    $post_table = $wpdb->prefix . 'posts';
    $sponsors_table = $wpdb->prefix.'ad_sponsors';

    // Collect the counts of ads per market
    $query = "SELECT ".$sponsors_table.".*
                FROM ".$sponsors_table."
                JOIN ".$postmeta_table." ON ".$postmeta_table.".meta_value = ".$sponsors_table.".name
                JOIN ".$post_table." ON ".$postmeta_table.".post_id = ".$post_table.".ID
               WHERE ".$postmeta_table.".meta_key LIKE 'ad_sponsors_%ad_sponsor'
                 AND ".$post_table.".post_status = 'publish'
            GROUP BY name
            ORDER BY ad_count DESC";
    $results = $wpdb->get_results($query);
    $rows = array();
    foreach ($results as $sponsor_result) {
        $rows[] = $this->generate_row($sponsor_result);
    }
    if ($page < 1) {
      return $rows;
    }
  }

  private function generate_row($row) {
        $name = $row->name;
        $race = $row->race;
        $type = $row->type;
        $ad_count = $row->ad_count;

      // Create the row
      $parsed_row = [
          "name" => $name,
          "race" => $race,
          "type" => $type,
          "ad_count" => $ad_count
      ];
      return $parsed_row;
  }
}
