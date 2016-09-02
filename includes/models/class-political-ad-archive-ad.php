<?php

/**
 * The candidate model
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes/models
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveAd {
	private $wp_id;
	private $archive_id;
	private $embed_url;
	private $notes;
    private $sponsors;
    private $sponsor_names;
    private $sponsor_types;
	private $subjects;
    private $candidates;
    private $candidate_names;
	private $type;
	private $race;
	private $cycle;
	private $message;
	private $air_count;
	private $reference_count;
    private $references;
	private $market_count;
	private $transcript;
	private $date_ingested;

    // Air count filters (optional)
    private $market_filters = array();
    private $channel_filters = array();
    private $program_filters = array();
    private $start_time;
    private $end_time;


	public function PoliticalAdArchiveAd($wp_id) {
                $post_metadata = get_fields($wp_id);
                $post_date = 
                $this->wp_id = $wp_id;
                $this->embed_url = array_key_exists('embed_url', $post_metadata)?$post_metadata['embed_url']:'';
                $this->notes = array_key_exists('notes', $post_metadata)?$post_metadata['notes']:'';
                $this->archive_id = array_key_exists('archive_id', $post_metadata)?$post_metadata['archive_id']:'';
                $ad_sponsors_acf_value = array_key_exists('ad_sponsors', $post_metadata)?$post_metadata['ad_sponsors']:array();
                $ad_sponsors_acf_value = $ad_sponsors_acf_value?$ad_sponsors_acf_value:array();
                $this->sponsors = PoliticalAdArchiveSponsor::get_sponsors_by_acf_field_value($ad_sponsors_acf_value);
                $this->sponsor_names = array_map(function($x) { return $x->name; }, $this->sponsors);
                $this->sponsor_names = array_unique($this->sponsor_names);
                $this->sponsor_types = array_map(function($x) { return PoliticalAdArchiveAd::get_friendly_sponsor_type_name($x->type); }, $this->sponsors);
                $this->sponsor_types = sizeof($this->sponsor_types) > 1?array("Multiple"):$this->sponsor_types;
                $this->sponsor_affiliations = array_map(function($x) { return $x->single_ad_candidate_id; }, $this->sponsors);
                $this->sponsor_affiliation_types = array_map(function($x) { return $x->does_support_candidate; }, $this->sponsors);
                $ad_candidates_acf_value = array_key_exists('ad_candidates', $post_metadata)?$post_metadata['ad_candidates']:array();
                $ad_candidates_acf_value = $ad_candidates_acf_value?$ad_candidates_acf_value:array();
                $this->candidates = PoliticalAdArchiveCandidate::get_candidates_by_acf_field_value($ad_candidates_acf_value);
                print_r($this->candidates);
                $this->candidate_names = array_map(function($x) { return $x->name; }, $this->candidates);
                $ad_subjects_acf_value = array_key_exists('ad_subjects', $post_metadata)?$post_metadata['ad_subjects']:array();
                $ad_subjects_acf_value = $ad_subjects_acf_value?$ad_subjects_acf_value:array();
                $this->subjects = array_map(function($x) { return $x['ad_subject']; }, $ad_subjects_acf_value);
                $this->type = array_key_exists('ad_type', $post_metadata)?$post_metadata['ad_type']:'';
                $races = array_merge(
                        array_map(function($x) { return $x->race; }, $this->sponsors),
                        array_map(function($x) { return $x->race; }, $this->candidates)
                );
                $this->race = array_pop($races);
                $cycles = array_merge(
                        array_map(function($x) { return $x->cycle; }, $this->sponsors),
                        array_map(function($x) { return $x->cycle; }, $this->candidates)
                );
                $this->cycle = array_pop($cycles);
                $this->message = array_key_exists('ad_message', $post_metadata)?$post_metadata['ad_message']:'';
                $this->air_count = array_key_exists('air_count', $post_metadata)?$post_metadata['air_count']:'';
                $this->market_count = array_key_exists('market_count', $post_metadata)?$post_metadata['market_count']:'';
                $this->first_seen = array_key_exists('first_seen', $post_metadata)?$post_metadata['first_seen'].' UTC':'';
                $this->last_seen = array_key_exists('last_seen', $post_metadata)? $post_metadata['last_seen'].' UTC':'';
                $this->transcript = array_key_exists('transcript', $post_metadata)? $post_metadata['transcript']:'';
                $this->date_ingested = get_the_date('Y/m/d g:i:s', $wp_id)." UTC";
                $this->references = array_key_exists('references', $post_metadata)? $post_metadata['references']:array();
                $this->reference_count = sizeof($this->references);
	}
	
	public function __get($property) {
		if (property_exists($this, $property)) {
		      return $this->$property;
		}
	}

    public static function get_ads_with_references() {
            global $wpdb;
            $posts_table = $wpdb->prefix . 'posts';
            $meta_table = $wpdb->prefix . 'postmeta';
            $query = "SELECT DISTINCT ".$meta_table.".post_id as id
                        FROM ".$meta_table."
                        JOIN ".$posts_table." ON ".$meta_table.".post_id = ".$posts_table.".ID
                       WHERE ".$meta_table.".meta_key LIKE 'references_%reference_title'
                         AND ".$posts_table.".post_status = 'publish'";
            $results = $wpdb->get_results($query);

            // Package the results
            $ads = array();
            foreach($results as $result) {
                    $ad = new PoliticalAdArchiveAd($result->id);
                    $ads[] = $ad;
            }

            return $ads;
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
            default:
                return $sponsor_type;
        }
    }
}