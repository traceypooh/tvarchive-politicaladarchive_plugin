<?php

/**
 * The ad candidate retrieval API
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/public/api
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveApiGetCandidates {

  private static $endpoint_code = '__ad_candidates';

  public static function register_route() {
      $triggering_endpoint = '^api/v1/ad_candidates/?(.*)?/?';
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
      $search = new PoliticalAdArchiveAdCandidateSearch();

      // Set up the response
      $response = new PoliticalAdArchiveApiResponse($search, PoliticalAdArchiveApiResponse::FORMAT_JSON);
      $response->send();
      exit();
    }
  }

}
