<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchivePublic {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    public function register_api_routes() {

        // Go through all the API routes
        PoliticalAdArchiveApiGetAds::register_route();
        PoliticalAdArchiveApiGetAdInstances::register_route();
        PoliticalAdArchiveApiGetMarketCounts::register_route();
        PoliticalAdArchiveApiGetCandidates::register_route();
        PoliticalAdArchiveApiGetSponsors::register_route();
    }

    public function filter_api_query_vars( $query_vars ) {

        // Go through all the API filters
        $query_vars = PoliticalAdArchiveApiGetAds::filter_query_vars($query_vars);
        $query_vars = PoliticalAdArchiveApiGetAdInstances::filter_query_vars($query_vars);
        $query_vars = PoliticalAdArchiveApiGetMarketCounts::filter_query_vars($query_vars);
        $query_vars = PoliticalAdArchiveApiGetCandidates::filter_query_vars($query_vars);
        $query_vars = PoliticalAdArchiveApiGetSponsors::filter_query_vars($query_vars);

        return $query_vars;
    }

    public function parse_request( &$wp ) {

        // Go through all the API parsers
        PoliticalAdArchiveApiGetAds::parse_request($wp);
        PoliticalAdArchiveApiGetAdInstances::parse_request($wp);
        PoliticalAdArchiveApiGetMarketCounts::parse_request($wp);
        PoliticalAdArchiveApiGetCandidates::parse_request($wp);
        PoliticalAdArchiveApiGetSponsors::parse_request($wp);
    }

}
?>
