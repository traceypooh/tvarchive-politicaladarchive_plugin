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
	private $date_created; // The date this record was created in this system

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
        $query = "SELECT ".$candidates_table.".*,
                    FROM ".$table_name;

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
        	$candidate->date_created = $result->date_created;
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

        return $result;
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
        $result = $wpdb->get_results($query);
        return $result;
	}

	public static function get_candidates_by_acf_field_value($candidates_field) {
		$candidate_names = array();
		foreach($candidates_field as $candidate_field) {
			$candidate_names[] = $candidate_field['ad_candidate'];
		}
		return self::get_candidates_by_names($candidate_names);
	}

}