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

	public function PoliticalAdArchiveSponsor() {}

	public static function getSponsors() {
		global $wpdb;
	}

	public static function getSponsorByName($name) {
		global $wpdb;

        $table_name = $wpdb->prefix . 'ad_sponsors';

        $query = "SELECT *
                    FROM ".$table_name."
                    WHERE name = '".esc_sql($name)."'
                LIMIT 0,1";

        $result = $wpdb->get_row($query);

        return $result;
	}

}