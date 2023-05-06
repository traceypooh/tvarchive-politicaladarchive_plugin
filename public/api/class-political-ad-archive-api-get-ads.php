<?php

/**
 * The instance retrieval API
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/public/api
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveApiGetAds {

  private static $endpoint_code = '__political_ads';

  public static function register_route() {
      $triggering_endpoint = '^api/v1/ads/?(.*)?/?';
      add_rewrite_rule($triggering_endpoint, 'index.php?'.self::$endpoint_code.'=1&'.self::$endpoint_code.'_options=$matches[1]', 'top');
  }

  public static function filter_query_vars($query_vars) {
      $query_vars[] = self::$endpoint_code;
      $query_vars[] = self::$endpoint_code.'_options';
      return $query_vars;
  }

  public static function parse_request(&$wp) {
    if (array_key_exists(self::$endpoint_code, $wp->query_vars)) {
        // Set up the search
        $search = new PoliticalAdArchiveAdSearch();

        if (array_key_exists('per_page', $_GET))
            $search->posts_per_page = $_GET['per_page'];
        if (array_key_exists('page', $_GET))
            $search->pages = array($_GET['page']);
      if (array_key_exists('sort', $_GET)) {
        $search->sort = $_GET['sort'];
      }

        // Add in filters
        $search->word_filters = array_key_exists('word_filter', $_GET) ? $_GET['word_filter'] : array();
        $search->candidate_filters = array_key_exists('candidate_filter', $_GET) ? $_GET['candidate_filter'] : array();
        $search->sponsor_filters = array_key_exists('sponsor_filter', $_GET) ? $_GET['sponsor_filter'] : array();
        $search->sponsor_type_filters = array_key_exists('sponsor_type_filter', $_GET) ? $_GET['sponsor_type_filter'] : array();
        $search->sponsor_type_filters = array_map(function($x) {
            $x['term'] = PoliticalAdArchiveSponsorType::get_sponsor_type_code($x['term']);
            return $x;
        }, $search->sponsor_type_filters);
        $search->subject_filters = array_key_exists('subject_filter', $_GET) ? $_GET['subject_filter'] : array();
        $search->type_filters = array_key_exists('type_filter', $_GET) ? $_GET['type_filter'] : array();
        $search->market_filters = array_key_exists('market_filter', $_GET) ? $_GET['market_filter'] : array();
        $search->channel_filters = array_key_exists('channel_filter', $_GET) ? $_GET['channel_filter'] : array();
        $search->program_filters = array_key_exists('program_filter', $_GET) ? $_GET['program_filter'] : array();
        $search->transcript_filters = array_key_exists('transcript_filter', $_GET) ? $_GET['transcript_filter'] : array();

        // Add in air time constraints
        if (array_key_exists("start_time", $_GET))
            $search->start_time = $_GET['start_time'];
        if (array_key_exists("end_time", $_GET))
            $search->end_time = $_GET['end_time'];

      if (array_key_exists('output', $_GET)
        && $_GET['output'] == "csv") {
        $output = PoliticalAdArchiveApiResponse::FORMAT_CSV;
      } else {
        $output = PoliticalAdArchiveApiResponse::FORMAT_JSON;
      }

        // Set up the response
        $response = new PoliticalAdArchiveApiResponse($search, $output);
        $response->send();
        exit();
    }
  }

}