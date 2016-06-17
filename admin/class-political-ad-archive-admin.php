<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchiveAdmin {

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
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    public function verify_acf_pro_enabled() {
        // Check to make sure ACF is installed
        if( ! class_exists('acf') && is_admin()) {
            $class = 'notice notice-error';
            $message = __( 'ACF Pro is required for the Political Ad Archive plugin to function.', 'political-ad-archive' );
            printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
        }
    }

    /**
     * Get the latest list of canonical ads from the Internet Archive
     */
    public function load_canonical_ads() {
        // Get a list of ad instances from the archive
        $url = 'https://archive.org/details/tv?canonical_ads=1&metadata=1&output=json';
        $url_result = file_get_contents($url);

        // Parse the result
        $canonical_ads = json_decode($url_result);

        // STEP 1.2: Go through the list and make sure there is a "post" for each ad
        foreach($canonical_ads as $canonical_ad) {

            $ad_identifier = $canonical_ad->identifier;
            // Does the ad already exist?
            $existing_ad = get_page_by_title( $ad_identifier, OBJECT, 'archive_political_ad');
            if($existing_ad)
                continue;

            // Create a new post for the ad
            $post = array(
                'post_name'      => $ad_identifier,
                'post_title'     => $ad_identifier,
                'post_status'    => 'draft', // Eventually we may want this to be 'publish'
                'post_type'      => 'archive_political_ad'
            );
            $wp_identifier = wp_insert_post( $post );

            // Set the "refresh" custom field to true
            // TODO: define that custom field
            update_field('', true);
        }
    }

    /**
     * Load metadata for canonical ads
     */
    function load_ad_metadata() {
        return;
        global $wpdb;
        $existing_ads = get_posts(array(
            'post_type' => 'archive_political_ad',
            'post_status' => 'any',
            'numberposts' => -1
        ));

        // Always Load the transcript
        if(array_key_exists($ad_identifier, $transcript_lookup))
            $transcript = $transcript_lookup[$ad_identifier];
        else
            $transcript = "";

        update_field('field_56f2bc3b38669', $transcript , $wp_identifier); // transcript


        // Some items we only want to sync the first time
        if(!$existing_ad) {
            // Load the metadata for this ad
            $metadata = $canonical_ad->json;

            // Store the basic information
            $ad_embed_url = 'https://archive.org/embed/'.$ad_identifier;
            $ad_id = $ad_identifier;
            $ad_type = "Political Ad";
            $ad_race = "";
            $ad_message = property_exists($metadata, 'message')?$metadata->message:'unknown';

            // Check if message is an array (unclear why this happens sometimes)
            $ad_message = is_array($ad_message)?array_pop($ad_message):$ad_message;

            update_field('field_566e30c856e35', $ad_embed_url , $wp_identifier); // embed_url
            update_field('field_566e328a943a3', $ad_id, $wp_identifier); // archive_id
            update_field('field_566e359261c2e', $ad_type, $wp_identifier); // ad_type
            update_field('field_566e360961c2f', $ad_message, $wp_identifier); // ad_message
            update_field('field_566e359261c2e', 'campaign', $wp_identifier); // ad type

            // Store the sponsors
            // TODO: metadata field should be "sponsors" not "sponsor"
            if(property_exists($metadata, 'sponsor')
            && is_array($metadata->sponsor)) {
                $new_sponsors = array();
                foreach($metadata->sponsor as $sponsor) {
                    if(array_key_exists(strtoupper($sponsor), $sponsor_lookup)) {
                        $sponsor_metadata = end($sponsor_lookup[strtoupper($sponsor)]);
                        // Was there a sponsor?
                        if($sponsor_metadata === false) {
                            $sponsor_type = "unknown";
                            $affiliated_candidate = "";
                            $affiliation_type = "none";
                        } else {
                            if($ad_race == "") {
                                $ad_race = $sponsor_metadata->race;
                                $ad_cycle = $sponsor_metadata->cycle;
                            }
                            $sponsor_type = $sponsor_metadata->type;

                            // Load in the candidate
                            $affiliated_candidate = "";
                            if(array_key_exists($sponsor_metadata->singlecandCID, $sponsor_lookup)
                            && array_key_exists('cand', $sponsor_lookup[$sponsor_metadata->singlecandCID]))
                                $affiliated_candidate = $sponsor_lookup[$sponsor_metadata->singlecandCID]['cand']->sponsorname;

                            // Is there an affiliated candidate?
                            if($affiliated_candidate == "")
                                $affiliation_type = "none";
                            else
                                $affiliation_type = $sponsor_metadata->suppopp?'opposes':'supports';

                            // If this is a candidate committee, load the candidate from the committee
                            // NOTE: cand + committees share a unique ID in the open secrets database
                            if($sponsor_type == "candcmte") {
                                $associated_metadata = $sponsor_lookup[$sponsor_metadata->uniqueid];
                                if(array_key_exists('cand', $associated_metadata)) {
                                    $affiliated_candidate = $associated_metadata['cand']->sponsorname;
                                    $affiliation_type = 'supports';
                                }
                            }
                        }
                    }
                    else {
                        $affiliated_candidate = "";
                        $affiliation_type = 'none';
                        $sponsor_type = "unknown";
                    }

                    $new_sponsor = array(
                        'field_566e32fb943a5' => $sponsor, // Name
                        'field_566e3353943a6' => $sponsor_type, // Type
                        'field_56e1a39716543' => $affiliated_candidate, // Affiliated candidate
                        'field_56e1a3e316544' => $affiliation_type // Affiliation type
                    );
                    $new_sponsors[] = $new_sponsor;
                }
                update_field('field_566e32bd943a4', $new_sponsors, $wp_identifier);
            }

            // Store the candidates
            if(property_exists($metadata, 'candidate')
            && is_array($metadata->candidate)) {
                $new_candidates = array();
                foreach($metadata->candidate as $candidate) {

                    // Does this candidate have associated metadata
                    if(array_key_exists(strtoupper($candidate), $sponsor_lookup)
                    && array_key_exists('cand', $sponsor_lookup[strtoupper($candidate)])) {
                        $candidate_metadata = $sponsor_lookup[strtoupper($candidate)]['cand'];
                        // Load in the race
                        if($ad_race == "") {
                            $ad_race = $candidate_metadata->race;
                            $ad_cycle = $candidate_metadata->cycle;
                        }
                    }

                    $new_candidate = array(
                        'field_566e3573943a8' => $candidate // Name
                    );
                    $new_candidates[] = $new_candidate;
                }
                update_field('field_566e3533943a7', $new_candidates, $wp_identifier);
            }

            // Update extra fields
            update_field('field_56e62a2127943', $ad_race, $wp_identifier); // Ad Race
            update_field('field_56e62a2927944', $ad_cycle, $wp_identifier); // Ad Cycle

            // Store the subjects
            if(property_exists($metadata, 'subject')
            && is_array($metadata->subject)) {
                $new_subjects = array();
                foreach($metadata->subject as $subject) {
                    $new_subject = array(
                        'field_569d12ec487ef' => $subject // Name
                    );
                    $new_subjects[] = $new_subject;
                }
                update_field('field_569d12c8487ee', $new_subjects, $wp_identifier);
            }
        }
    }

    /**
     * Get a list of actual airings for each ad
     * Then update the ad counts
     */
    function load_ad_instances() {
        return;
        global $wpdb;
        $existing_ads = get_posts(array(
            'post_type' => 'archive_political_ad',
            'post_status' => 'any',
            'numberposts' => -1
        ));

        foreach($existing_ads as $existing_ad) {
            $wp_identifier = $existing_ad->ID;
            $ad_identifier = $existing_ad->post_title;

            // STEP 2: Get every instance, and create a record for each instance
            // NOTE: it won't double insert when run more than once due to the unique key
            $instances = get_ad_archive_instances($ad_identifier);

            // Load the overrides
            $start_override = get_field('start_override', $wp_identifier);
            $end_override = get_field('end_override', $wp_identifier);

            // Collect existing instances;
            $existing_instances = array();

            // Collect the data associated with this ad
            $table_name = $wpdb->prefix . 'ad_instances';

            $query = "SELECT id as id,
                             network as network,
                             start_time as start_time,
                             archive_identifier as archive_identifier,
                             wp_identifier as wp_identifier
                        FROM ".$table_name."
                       WHERE archive_identifier = '".esc_sql($ad_identifier)."'";

            $results = $wpdb->get_results($query);
            foreach($results as $result) {
                $network = $result->network;
                $start_time = $result->start_time;
                $archive_identifier = $result->archive_identifier;
                if(!array_key_exists($network, $existing_instances)) {
                    $existing_instances[$network] = array();
                }
                $existing_instances[$network][] = "".strtotime($start_time);
            }

            // Iterate through each instance
            foreach($instances as $instance) {
                $network = $instance->chan;
                $market = array_key_exists($network, $network_lookup)?$network_lookup[$network]['market']:'';
                $location = array_key_exists($market, $market_translations)?$market_translations[$market]:'';
                $start_time = date("Y-m-d H:i:s", $instance->start);
                $end_time = date("Y-m-d H:i:s", $instance->end);
                $date_created = date("Y-m-d H:i:s");
                $program = $instance->title;
                $program_type = $instance->program_type;

                // If the start time isn't within the override range, skip this airing
                if($start_override != null
                && strtotime($start_override) > strtotime($start_time))
                    continue;

                if($end_override != null
                && strtotime($end_override) < strtotime($start_time))
                    continue;

                // Only try to insert if it doesn't exist already
                if(!array_key_exists($network, $existing_instances)
                || !in_array("".strtotime($start_time), $existing_instances[$network])) {
                    $table_name = $wpdb->prefix . 'ad_instances';
                    $wpdb->insert(
                        $table_name,
                        array(
                            'wp_identifier' => $wp_identifier,
                            'archive_identifier' => $ad_identifier,
                            'network' => $network,
                            'market' => $market,
                            'location' => $location,
                            'start_time' => $start_time,
                            'end_time' => $end_time,
                            'program' => $program,
                            'program_type' => $program_type,
                            'date_created' => $date_created
                        )
                    );
                }
            }

            // If there is an override, remove any airings that we may have saved in the past that don't fall within the override range
            if($start_override != null) {
                $table_name = $wpdb->prefix . 'ad_instances';
                $query = $wpdb->prepare('DELETE FROM %1$s WHERE UNIX_TIMESTAMP(start_time) < %2$d && wp_identifier = %3$d', array($table_name, strtotime($start_override), $wp_identifier));
                $wpdb->query($query);
            }

            if($end_override != null) {
                $table_name = $wpdb->prefix . 'ad_instances';
                $query = $wpdb->prepare('DELETE FROM %1$s WHERE UNIX_TIMESTAMP(start_time) > %2$d && wp_identifier = %3$d', array($table_name, strtotime($end_override), $wp_identifier));
                $wpdb->query($query);
            }
        }

        foreach($existing_ads as $existing_ad) {
            $wp_identifier = $existing_ad->ID;
            $ad_identifier = $existing_ad->post_title;

            // We have the data, lets update the metadata for the canonical ad itself
            $table_name = $wpdb->prefix . 'ad_instances';

            $query = "SELECT count(*) as air_count,
                             count(DISTINCT network) as network_count,
                             count(DISTINCT market) as market_count,
                             MIN(start_time) as first_seen,
                             MAX(start_time) as last_seen
                        FROM ".$table_name."
                       WHERE archive_identifier = '".esc_sql($ad_identifier)."'
                    GROUP BY archive_identifier";

            $results = $wpdb->get_results($query);

            if(sizeof($results) == 0) {
                $ad_air_count = 0;
                $ad_market_count = 0;
                $ad_network_count = 0;
                $ad_first_seen = '';
                $ad_last_seen = '';
            }
            else {
                $results = $results[0];
                $ad_air_count = $results->air_count;
                $ad_market_count = $results->market_count;
                $ad_network_count = $results->network_count;
                $ad_first_seen = date('Ymd', strtotime($results->first_seen));
                $ad_last_seen = date('Ymd', strtotime($results->last_seen));
            }

            // Note: the keys here are defined by the Advanced Custom Fields settings
            update_field('field_566e3659fb227', $ad_air_count, $wp_identifier); // air_count
            update_field('field_566e367e962e2', $ad_market_count, $wp_identifier); // market_count
            update_field('field_566e3697962e3', $ad_network_count, $wp_identifier); // network_count
            update_field('field_566e36b0962e4', $ad_first_seen, $wp_identifier); // first_seen
            update_field('field_566e36d5962e5', $ad_last_seen,  $wp_identifier); // last_seen
        }
    }

    /**
     * Define a new location to load acf json files
     */
    public function add_acf_json_load_point($paths) {
        // append path
        $paths[] = plugin_dir_path( __FILE__ ).'acf-json/';

        // return
        return $paths;
    }

    /**
     * Define a new location to load acf json files
     */
    public function override_acf_json_save_point($path) {
        // append path
        $path = plugin_dir_path( __FILE__ ).'acf-json/';

        // return
        return $path;
    }

}
?>
