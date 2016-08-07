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

        // Collect the counts of ads per market
        $table_name = $wpdb->prefix.'ad_instances';
        $query = "SELECT
                  COUNT(*) as ad_count
                  ,market as market_code
                  ,location as location
                  FROM ".$table_name."
                  GROUP BY market_code";
        $results = $wpdb->get_results($query);
	    $rows = array();
	    foreach($results as $market_result) {
	    	$rows[] = $this->generate_row($market_result);
	    }
      if ($page < 1){
        return $rows;
      }

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
