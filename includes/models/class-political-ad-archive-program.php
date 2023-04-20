<?php

/**
 * The program model
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes/models
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveProgram {

	private $program; // The name of the program
	private $program_type; // The type of the program

	public function __construct() {}

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
	public static function get_programs() {
		global $wpdb;

		// Run the query
        $posts_table = $wpdb->prefix . 'posts';
        $instances_table = $wpdb->prefix . 'ad_instances';
        $query = "SELECT DISTINCT ".$instances_table.".program as program,
        			     ".$instances_table.".program_type as program_type
				    FROM ".$instances_table."
				    JOIN ".$posts_table." ON ".$instances_table.".wp_identifier = ".$posts_table.".ID
				   WHERE ".$posts_table.".post_status = 'publish'";
        $results = $wpdb->get_results($query);

        // Package the results
        $programs = array();
        foreach($results as $result) {
        	$program = new PoliticalAdArchiveProgram();
        	$program->program = $result->program;
        	$program->program_type = $result->program_type;
        	$programs[] = $program;
        }

        return $programs;
	}

}