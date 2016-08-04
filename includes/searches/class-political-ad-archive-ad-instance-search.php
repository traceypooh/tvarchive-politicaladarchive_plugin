<?php

/**
 * The candidate model
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes/models
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveAdInstanceSearch implements PoliticalAdArchiveBufferedQuery {

	private $posts_per_page;
    private $ad_ids;

	public function PoliticalAdArchiveAdInstanceSearch() {
		$this->posts_per_page = 3000;
	}

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

	public function get_chunk($page) {
        global $wpdb;

        // Collect the data associated with this ad
        $table_name = $wpdb->prefix . 'ad_instances';
        $query = "SELECT id as id,
                         network as network,
                         market as market,
                         location as location,
                         program as program,
                         program_type as program_type,
                         start_time as start_time,
                         end_time as end_time,
                         archive_identifier as archive_identifier,
                         wp_identifier as wp_identifier,
                         date_created as date_created
                    FROM ".$table_name;

        if(is_array($this->ad_ids)) {
            if(sizeof($this->ad_ids) == 0)
                $this->ad_ids = array(-1);
            $query .= " WHERE wp_identifier IN (".implode(",", $this->ad_ids).") ";
        }
        $query .= " LIMIT ".($page * $this->posts_per_page).", ".$this->posts_per_page;

        $results = $wpdb->get_results($query);
	    $rows = array();
	    foreach($results as $ad_instance) {
	    	$rows[] = $this->generate_row($ad_instance);
	    }
	    return $rows;
	}

    private function get_filtered_ad_ids() {
    }

	private function generate_row($row) {
        $wp_identifier = $row->wp_identifier;
        $ad = new PoliticalAdArchiveAd($wp_identifier);
        $network = $row->network;
        $market = $row->market;
        $location = $row->location;
        $program = $row->program;
        $program_type = $row->program_type;
        $start_time = $row->start_time.' UTC';
        $end_time = $row->end_time.' UTC';
        $date_created = $row->date_created;

        // Create the row
        $parsed_row = [
            "wp_identifier" => $ad->wp_id,
            "network" => $network,
            "location" => $location,
            "program" => $program,
            "program_type" => $program_type,
            "start_time" => $start_time,
            "end_time" => $end_time,
            "archive_id" => $ad->archive_id,
            "embed_url" => $ad->embed_url,
            "sponsors" => implode(', ', $ad->sponsor_names),
            "sponsor_types" => implode(', ', $ad->sponsor_types),
            "sponsor_affiliations" => implode(', ', $ad->sponsor_affiliations),
            "sponsor_affiliation_types" => implode(', ', $ad->sponsor_affiliation_types),
            "race" => $ad->race,
            "cycle" => $ad->cycle,
            "subjects" => implode(", ", $ad->subjects),
            "candidates" => implode(", ", $ad->candidate_names),
            "type" => $ad->type,
            "message" => $ad->message,
            "date_created" => $date_created
        ];
        return $parsed_row;
	}
}