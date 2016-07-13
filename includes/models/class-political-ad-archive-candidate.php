<?php

/**
 * The candidate model
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveCandidate {

	public function PoliticalAdArchiveCandidate() {}

	public static function getCandidates() {
		global $wpdb;
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

}