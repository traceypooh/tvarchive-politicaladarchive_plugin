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

	public static function get_sponsor_by_name($name) {
		global $wpdb;

        $table_name = $wpdb->prefix . 'ad_sponsors';

        $query = "SELECT *
                    FROM ".$table_name."
                    WHERE name = '".esc_sql($name)."'
                LIMIT 0,1";

        $result = $wpdb->get_row($query);

        return $result;
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