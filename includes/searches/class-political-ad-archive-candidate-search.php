<?php

/**
 * The candidate search class
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes/models
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveAdCandidateSearch implements PoliticalAdArchiveBufferedQuery {

    private $posts_per_page;

  public function __construct($args = null) {

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

  public function get_total_rows() {
      return -1;
  }

  public function get_chunk($page) {

    global $wpdb;
    $postmeta_table = $wpdb->prefix . 'postmeta';
    $post_table = $wpdb->prefix . 'posts';
    $candidates_table = $wpdb->prefix.'ad_candidates';

    // Collect the counts of ads per market
    $query = "SELECT ".$candidates_table.".id
                FROM ".$candidates_table."
                JOIN ".$postmeta_table." ON ".$postmeta_table.".meta_value = ".$candidates_table.".name
                JOIN ".$post_table." ON ".$postmeta_table.".post_id = ".$post_table.".ID
               WHERE ".$postmeta_table.".meta_key LIKE 'ad_candidates_%ad_candidate'
                 AND ".$post_table.".post_status = 'publish'
            GROUP BY name
            ORDER BY ad_count DESC";
    $results = $wpdb->get_results($query);
    $rows = array();
    foreach ($results as $candidate_result) {
        $rows[] = $this->generate_row($candidate_result->id);
    }
    if ($page < 1) {
      return $rows;
    }

  }

  private function generate_row($candidate_id) {
    $candidate = new PoliticalAdArchiveCandidate($candidate_id);
    // Create the row
    $parsed_row = [
    'id' => $candidate->id,
    'crp_unique_id' => $candidate->crp_unique_id,
    'name' => $candidate->name,
    'race' => $candidate->race,
    'cycle' => $candidate->cycle,
    'affiliation' => $candidate->affiliation,
    'ad_count' => $candidate->ad_count,
    'air_count' => $candidate->air_count,
    'date_created' => $candidate->date_created,
    'in_crp' => $candidate->in_crp
    ];
    return $parsed_row;
  }
}
