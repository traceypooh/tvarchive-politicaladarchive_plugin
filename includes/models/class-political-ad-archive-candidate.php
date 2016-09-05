<?php

/**
 * The candidate model
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes/models
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveCandidate {

	private $id; // The local database ID / local unique ID
	private $crp_unique_id; // The ID assigned by CRP
	private $name; // The name of the candidate
	private $race; // The political race the candidate participated in
	private $cycle; // The time period the candidate ran
	private $affiliation; // The political party affiliation of the candidate
	private $ad_count; // Number of unique ads
	private $air_count; // Number of unique airings	
	private $date_created; // The date this record was created in this system
	private $in_crp; // Is this item in the CRP database or not

	public function PoliticalAdArchiveCandidate() {}

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

	public static function generate_full_candidate($candidate) {

	}

	/**
	 * Returns a list of candidates
	 * @return [type] [description]
	 */
	public static function get_candidates() {
		global $wpdb;

		// Run the query
        $candidates_table = $wpdb->prefix . 'ad_candidates';
        $posts_table = $wpdb->prefix . 'posts';
        $meta_table = $wpdb->prefix . 'postmeta';
        $query = "SELECT ".$candidates_table.".*
                    FROM ".$candidates_table."
				    JOIN (SELECT DISTINCT ".$meta_table.".meta_value as meta_value
				            FROM ".$meta_table."
				            JOIN ".$posts_table." ON ".$meta_table.".post_id = ".$posts_table.".ID
				           WHERE ".$meta_table.".meta_key LIKE 'ad_candidates_%ad_candidate'
				             AND ".$posts_table.".post_status = 'publish') as t
				      ON ".$candidates_table.".name = t.meta_value";
        $results = $wpdb->get_results($query);

        // Package the results
        $candidates = array();
        foreach($results as $result) {
        	$candidate = new PoliticalAdArchiveCandidate();
        	$candidate->id = $result->id;
        	$candidate->crp_unique_id = $result->crp_unique_id;
        	$candidate->name = $result->name;
        	$candidate->race = $result->race;
        	$candidate->cycle = $result->cycle;
        	$candidate->affiliation = $result->affiliation;
        	$candidate->ad_count = $result->ad_count;
        	$candidate->air_count = $result->air_count;
        	$candidate->date_created = $result->date_created;
        	$candidate->in_crp = true;
        	$candidates[] = $candidate;
        }

        return $candidates;
	}

	public static function get_candidate_by_name($name) {
		global $wpdb;

        $table_name = $wpdb->prefix . 'ad_candidates';

        $query = "SELECT *
                    FROM ".$table_name."
                    WHERE name = '".esc_sql($name)."'
                LIMIT 0,1";

        $result = $wpdb->get_row($query);

        if($result) {
	    	$candidate = new PoliticalAdArchiveCandidate();
	    	$candidate->id = $result->id;
	    	$candidate->crp_unique_id = $result->crp_unique_id;
	    	$candidate->name = $result->name;
	    	$candidate->race = $result->race;
	    	$candidate->cycle = $result->cycle;
	    	$candidate->affiliation = $result->affiliation;
	    	$candidate->ad_count = $result->ad_count;
	    	$candidate->air_count = $result->air_count;
	    	$candidate->date_created = $result->date_created;
	    	$candidate->in_crp = true;
	    	return $candidate;
	    } else {
        	$candidate = new PoliticalAdArchiveCandidate();
        	$candidate->name = $name;
	    	$candidate->in_crp = false;
        	return $candidate;
	    }
	}

	public static function get_candidates_by_names($names) {
		global $wpdb;

		// Sanitize the names
		$sanitized_names = array();
		foreach($names as $name) {
			$sanitized_names[] = "'".esc_sql($name)."'";
		}

		if(sizeof($sanitized_names) == 0)
			return array();

        $table_name = $wpdb->prefix . 'ad_candidates';
        $query = "SELECT *
                    FROM ".$table_name."
                    WHERE name IN (".implode(", ", $sanitized_names).")";
        $results = $wpdb->get_results($query);

        $leftover_candidates = $names;
        foreach($results as $result) {
        	$candidate = new PoliticalAdArchiveCandidate();
        	$candidate->id = $result->id;
        	$candidate->crp_unique_id = $result->crp_unique_id;
        	$candidate->name = $result->name;
        	$candidate->race = $result->race;
        	$candidate->cycle = $result->cycle;
        	$candidate->affiliation = $result->affiliation;
        	$candidate->ad_count = $result->ad_count;
        	$candidate->air_count = $result->air_count;
        	$candidate->date_created = $result->date_created;
	    	$candidate->in_crp = true;
        	$candidates[] = $candidate;

	    	// Flag the name as found
	    	$leftover_candidates = array_udiff($leftover_candidates, array($result->name), 'strcasecmp');
        }

        // Create basic objects for names that aren't found
        foreach($leftover_candidates as $leftover_candidate) {
	    	$candidate = new PoliticalAdArchiveSponsor();
	    	$candidate->name = $leftover_candidate;
	    	$candidate->affiliation = "?";
	    	$candidate->in_crp = false;
	    	$candidates[] = $candidate;
        }
        return $candidates;
	}

	public static function get_candidates_by_acf_field_value($candidates_field) {
		$candidate_names = array();
		foreach($candidates_field as $candidate_field) {
			$candidate_names[] = $candidate_field['ad_candidate'];
		}
		return self::get_candidates_by_names($candidate_names);
	}

}