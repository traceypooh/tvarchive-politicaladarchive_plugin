<?php

/**
 * The instance retrieval API
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveApiGetInstances {

	private static $endpoint_code = '__political_ad_instances';

	public static function register_route() {
	    $triggering_endpoint = '^api/v1/instances/?(.*)?/?';
	    add_rewrite_rule($triggering_endpoint,'index.php?__political_ad_instances=1&political_ad_instances_options=$matches[1]','top');
	}

	public static function filter_query_vars( $query_vars ) {
	    $query_vars[] = '__political_ad_instances';
	    $query_vars[] = 'political_ad_instances_options';
	    return $query_vars;
	}

	public static function parse_request( &$wp ) {
	    if ( array_key_exists( '__political_ad_instances', $wp->query_vars ) ) {
	        
	        exit();
	    }
	}

}