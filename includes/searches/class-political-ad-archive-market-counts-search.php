<?php

/**
 * The market counts search class
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes/models
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveAdMarketCountsSearch implements PoliticalAdArchiveBufferedQuery {

	private $posts_per_page;
  private $start_time;
  private $end_time;

	public function PoliticalAdArchiveAdMarketCountsSearch($args = null) {

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

	public function get_chunk($page) {
    global $wpdb;

    // This query isn't paged
    if($page >= 1)
      return array();

    $published_ad_ids = get_posts(array(
      'fields' => 'ids',
      'post_status' => 'publish',
      'post_type'   => 'archive_political_ad',
      'numberposts' => -1
    ));

    // Collect the counts of ads per market
    $table_name = $wpdb->prefix.'ad_instances';
    $query = "SELECT
              COUNT(*) as ad_count
              ,market as market_code
              ,location as location
              FROM ".$table_name;
    
    $query_conditions = array();
    $query_conditions[] = "wp_identifier IN (".implode(",", $published_ad_ids).")";
    if($this->start_time != null)
        $query_conditions[] = "end_time > '".esc_sql(date('Y-m-d H:i:s',strtotime($this->start_time)))."'";
    
    if($this->end_time != null)
        $query_conditions[] = "start_time < '".esc_sql(date('Y-m-d H:i:s',strtotime($this->end_time)))."'";

    if(sizeof($query_conditions) > 0)
        $query .= " WHERE ".implode(" AND ", $query_conditions);

    $query.= " GROUP BY market_code";
    $results = $wpdb->get_results($query);
    $rows = array();
    foreach($results as $market_result) {
    	$rows[] = $this->generate_row($market_result);
    }
    return $rows;
	}

	private function generate_row($row) {
          $market_code = $row->market_code;
          $location = $row->location;
          $ad_count = $row->ad_count;

        // Create the row
        $parsed_row = [
            "market_code" => $market_code,
            "location" => $location,
            "ad_count" => $ad_count,
        ];
        return $parsed_row;
	}
}
