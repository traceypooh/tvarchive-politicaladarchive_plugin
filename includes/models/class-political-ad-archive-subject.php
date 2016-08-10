<?php

/**
 * The subject model
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes/models
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveSubject {

	private $subject; // The string value of the type

	public function PoliticalAdArchiveSubject() {}

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
	public static function get_subjects() {
		global $wpdb;

		// Run the query
        $posts_table = $wpdb->prefix . 'posts';
        $meta_table = $wpdb->prefix . 'postmeta';
        $query = "SELECT DISTINCT ".$meta_table.".meta_value as subject
				    FROM ".$meta_table."
				    JOIN ".$posts_table." ON ".$meta_table.".post_id = ".$posts_table.".ID
				   WHERE ".$meta_table.".meta_key LIKE 'ad_subjects_%ad_subject'
				     AND ".$posts_table.".post_status = 'publish'";
        $results = $wpdb->get_results($query);

        // Package the results
        $ad_subjects = array();
        foreach($results as $result) {
        	$ad_subject = new PoliticalAdArchiveSubject();
        	$ad_subject->subject = $result->subject;
        	$ad_subjects[] = $ad_subject;
        }

        return $ad_subjects;
	}
}