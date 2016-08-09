<?php

/**
 * The sponsor model
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveSponsor {

	private $id; // The local database ID / local unique ID
	private $crp_unique_id; // The ID assigned by CRP
	private $name; // The name of the candidate
	private $race; // The political race the candidate participated in
	private $cycle; // The time period the candidate ran
	private $type; // The time period the candidate ran
	private $single_ad_candidate_id; // The candidate affiliation of the sponsor
	private $does_support_candidate; // Supports or opposes the candidate
	private $ad_count; // Number of unique ads
	private $air_count; // Number of unique airings
	private $date_created; // The date this record was created in this system

	public function PoliticalAdArchiveSponsor() {}

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
	
	public static function get_sponsors() {
		global $wpdb;

        $sponsors_table = $wpdb->prefix . 'ad_sponsors';
        $posts_table = $wpdb->prefix . 'posts';
        $meta_table = $wpdb->prefix . 'postmeta';
        $query = "SELECT ".$sponsors_table.".*
                    FROM ".$sponsors_table."
				    JOIN (SELECT DISTINCT ".$meta_table.".meta_value as meta_value
				            FROM ".$meta_table."
				            JOIN ".$posts_table." ON ".$meta_table.".post_id = ".$posts_table.".ID
				           WHERE ".$meta_table.".meta_key LIKE 'ad_sponsors_%ad_sponsor'
				             AND ".$posts_table.".post_status = 'publish') as t
				      ON ".$sponsors_table.".name = t.meta_value";

        $results = $wpdb->get_row($query);

        // Package the results
        $sponsors = array();
        foreach($results as $result) {
        	$sponsor = new PoliticalAdArchiveSponsor();
        	$sponsor->id = $result->id;
        	$sponsor->crp_unique_id = $result->crp_unique_id;
        	$sponsor->name = $result->name;
        	$sponsor->race = $result->race;
        	$sponsor->cycle = $result->cycle;
        	$sponsor->type = $result->type;
        	$sponsor->single_ad_candidate_id = $result->single_ad_candidate_id;
        	$sponsor->does_support_candidate = $result->does_support_candidate;
        	$sponsor->ad_count = $result->ad_count;
        	$sponsor->air_count = $result->air_count;
        	$sponsor->date_created = $result->date_created;
        	$sponsors[] = $sponsor;
        }

        return $sponsors;
	}

	public static function get_sponsor_by_name($name) {
		global $wpdb;

        $table_name = $wpdb->prefix . 'ad_sponsors';

        $query = "SELECT *
                    FROM ".$table_name."
                    WHERE name = '".esc_sql($name)."'
                LIMIT 0,1";

        $result = $wpdb->get_row($query);
    	$sponsor = new PoliticalAdArchiveSponsor();
    	$sponsor->id = $result->id;
    	$sponsor->crp_unique_id = $result->crp_unique_id;
    	$sponsor->name = $result->name;
    	$sponsor->race = $result->race;
    	$sponsor->cycle = $result->cycle;
    	$sponsor->type = $result->type;
    	$sponsor->single_ad_candidate_id = $result->single_ad_candidate_id;
    	$sponsor->does_support_candidate = $result->does_support_candidate;
    	$sponsor->ad_count = $result->ad_count;
    	$sponsor->air_count = $result->air_count;
    	$sponsor->date_created = $result->date_created;
        return $sponsor;
	}

	public static function get_sponsors_by_names($names) {
		global $wpdb;

		// Sanitize the names
		$sanitized_names = array();
		foreach($names as $name) {
			$sanitized_names[] = "'".esc_sql($name)."'";
		}
		
		if(sizeof($sanitized_names) == 0)
			return array();

        $table_name = $wpdb->prefix . 'ad_sponsors';
        $query = "SELECT *
                    FROM ".$table_name."
                    WHERE name IN (".implode(",",$sanitized_names).")";

        $result = $wpdb->get_results($query);

        return $result;
	}

	/**
	 * This returns a list of sponsor results based on an ACF repeater field
	 * @param  [type] $sponsors_field [description]
	 * @return [type]                 [description]
	 */
	public static function get_sponsors_by_acf_field_value($sponsors_field) {
		$sponsor_names = array();
		foreach($sponsors_field as $sponsor_field) {
			$sponsor_names[] = $sponsor_field['ad_sponsor'];
		}
		return self::get_sponsors_by_names($sponsor_names);
	}

}