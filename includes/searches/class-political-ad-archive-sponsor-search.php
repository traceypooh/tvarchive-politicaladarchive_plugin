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

	public function PoliticalAdArchiveAdSponsorSearch($args = null) {

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
        $table_name = $wpdb->prefix.'ad_sponsors';
        $query = "SELECT *
                  FROM ".$table_name."
                  JOIN (SELECT DISTINCT wp_postmeta.meta_value as meta_value FROM wp_postmeta
                    JOIN wp_posts ON wp_postmeta.post_id = wp_posts.ID
                    WHERE wp_postmeta.meta_key LIKE 'ad_sponsors_%ad_sponsor'
                    AND wp_posts.post_status = 'publish')
                  as t ON ".$table_name.".name = t.meta_value
                  GROUP BY name
                  ORDER BY ad_count DESC";
        $results = $wpdb->get_results($query);
	    $rows = array();
	    foreach($results as $sponsor_result) {
	    	$rows[] = $this->generate_row($sponsor_result);
	    }
      if ($page < 1){
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
