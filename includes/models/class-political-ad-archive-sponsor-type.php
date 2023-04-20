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

    public static function get_friendly_sponsor_type_name($sponsor_type) {
        switch(strtolower($sponsor_type)) {
            case "candcmte":
                return "Candidate Committee";
            case "superpac":
                return "Super PAC";
            case "pac":
                return "PAC";
            case "501c4":
                return "Non Profit";
            case "501c6":
                return "Trade Association";
            case "carey":
                return "Hybrid Super PAC";
            case "527":
                return "527";
            case "corp":
                return "Corporation";
            case "jfc":
                return "Joint Fundraising Committee";
            default:
                return $sponsor_type;
        }
    }

    public static function get_sponsor_type_code($sponsor_type) {
        switch(strtolower($sponsor_type)) {
            case "candidate committee":
                return "candcmte";
            case "super pac":
                return "superpac";
            case "pac":
                return "pac";
            case "non profit":
                return "501c4";
            case "trade association":
                return "501c6";
            case "hybrid super pac":
                return "carey";
            case "527":
                return "527";
            case "corporation":
                return "corp";
            case "joint fundraising committee":
                return "jfc";
            default:
                return $sponsor_type;
        }
    }
}