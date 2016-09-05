<?php

/**
 * The ad type model
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes/models
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveSponsorType {

	private $type; // The string value of the type

	public function PoliticalAdArchiveSponsorType() {}

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
	public static function get_sponsor_types() {
		global $wpdb;

		// Run the query
        $sponsors_table = $wpdb->prefix . 'ad_sponsors';
        $posts_table = $wpdb->prefix . 'posts';
        $meta_table = $wpdb->prefix . 'postmeta';
        $query = "SELECT DISTINCT ".$sponsors_table.".type as type
                    FROM ".$sponsors_table."
				    JOIN (SELECT DISTINCT ".$meta_table.".meta_value as meta_value
				            FROM ".$meta_table."
				            JOIN ".$posts_table." ON ".$meta_table.".post_id = ".$posts_table.".ID
				           WHERE ".$meta_table.".meta_key LIKE 'ad_sponsors_%ad_sponsor'
				             AND ".$posts_table.".post_status = 'publish') as t
				      ON ".$sponsors_table.".name = t.meta_value";
        $results = $wpdb->get_results($query);

        // Package the results
        $sponsor_types = array();
        foreach($results as $result) {
        	$sponsor_type = new PoliticalAdArchiveSponsorType();
        	$sponsor_type->type = $result->type;
        	$sponsor_types[] = $sponsor_type;
        }

        return $sponsor_types;
	}
}