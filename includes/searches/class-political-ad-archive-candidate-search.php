<?php

/**
 * The candidate search class
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes/models
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveAdCandidateSearch implements PoliticalAdArchiveBufferedQuery {

	private $posts_per_page;

	public function PoliticalAdArchiveAdCandidateSearch($args = null) {

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
        $table_name = $wpdb->prefix.'ad_candidates';
        $query = "SELECT *
                  FROM ".$table_name."
                  JOIN (SELECT DISTINCT wp_postmeta.meta_value as meta_value FROM wp_postmeta
                    JOIN wp_posts ON wp_postmeta.post_id = wp_posts.ID
                    WHERE wp_postmeta.meta_key LIKE 'ad_candidates_%ad_candidate'
                    AND wp_posts.post_status = 'publish')
                  as t ON ".$table_name.".name = t.meta_value
                  GROUP BY name
                  ORDER BY ad_count DESC";
        $results = $wpdb->get_results($query);
	    $rows = array();
	    foreach($results as $candidate_result) {
	    	$rows[] = $this->generate_row($candidate_result);
	    }
      if ($page < 1){
        return $rows;
      }

	}

	private function generate_row($row) {
          $name = $row->name;
          $race = $row->race;
          $affiliation = $row->affiliation;
          $ad_count = $row->ad_count;

        // Create the row
        $parsed_row = [
            "name" => $name,
            "race" => $race,
            "affiliation" => $affiliation,
            "ad_count" => $ad_count
        ];
        return $parsed_row;
	}
}
