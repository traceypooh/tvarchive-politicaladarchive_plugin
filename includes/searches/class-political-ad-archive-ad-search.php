<?php

/**
 * The candidate model
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes/models
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveAdSearch implements PoliticalAdArchiveBufferedQuery {

	private $posts_per_page;

	public function PoliticalAdArchiveAdSearch($args = null) {
		$this->posts_per_page = 500;
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
	    $args = array(
	        'post_type'      => 'archive_political_ad',
	        'post_status'    => 'publish',
	        'orderby'        => 'post_date',
	        'order'          => 'DESC',
            'posts_per_page' => $this->posts_per_page,
            'paged' => $page + 1,
	    );

	    $wp_query = new WP_Query($args);
	    $ads = $wp_query->posts;

	    $rows = array();
	    foreach($ads as $ad) {
	    	$rows[] = $this->generate_row($ad);
	    }
	    return $rows;
	}

	private function generate_row($row) {
        $ad = new PoliticalAdArchiveAd($row->ID);
        $parsed_row = [
            "wp_identifier" => $ad->wp_id,
            "archive_id" => $ad->archive_id,
            "embed_url" => $ad->embed_url,
            "sponsors" => implode(", ", $ad->sponsor_names),
            "sponsor_types" => implode(", ", $ad->sponsor_types),
            "sponsor_affiliations" => implode(", ", $ad->sponsor_affiliations),
            "sponsor_affiliation_types" => implode(", ", $ad->sponsor_affiliation_types),
            "subjects" => implode(", ", $ad->subjects),
            "candidates" => implode(", ", $ad->candidate_names),
            "type" => $ad->type,
            "race" => $ad->race,
            "cycle" => $ad->cycle,
            "message" => $ad->message,
            "air_count" => $ad->air_count,
            "reference_count" => $ad->references,
            "market_count" => $ad->market_count,
            "transcript" => $ad->transcript,
            "date_ingested" => $ad->ingest_date
        ];

		return $parsed_row;
	}
}