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

    public function check_ad_metadata() {
        global $post;

        if(!isset($post) || 'archive_political_ad' != $post->post_type)
            return;

        // Check sponsors
        $sponsors = get_field('ad_sponsors', $post->ID) ?: array();

        foreach($sponsors as $sponsor) {
            $sponsor_name = $sponsor["ad_sponsor"];
            $sponsor_object = PoliticalAdArchiveSponsor::get_sponsor_by_name($sponsor_name);
            if(!$sponsor_object ||
                $sponsor_object->in_crp == false) {
                $class = 'notice notice-error';
                $message = __( 'WARNING: A sponsor does not have metadata in the sponsors table: '. $sponsor_name, 'political-ad-archive' );
                printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
            }
        }

        // Check candidates
        $candidates = get_field('ad_candidates', $post->ID) ?: array();

        foreach($candidates as $candidate) {
            $candidate_name = $candidate["ad_candidate"];
            $candidate_object = PoliticalAdArchiveCandidate::get_candidate_by_name($candidate_name);
            if(!$candidate_object||
                $candidate_object->in_crp == false) {
                $class = 'notice notice-error';
                $message = __( 'WARNING: A candidate does not have metadata in the candidates table: '. $candidate_name, 'political-ad-archive' );
                printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
            }
        }
    }

    /**
     * Register the admin page with settings for the political ad archive
     */
    public function register_admin_menu() {
        if( function_exists('acf_add_options_page') ) {
            acf_add_options_page(array(
                'page_title' => 'Political Ad Archive Options',
                'menu_title' => 'Political Ad Archive',
                'menu_slug' => 'political-ad-archive',
                'capability' => 'manage_options',
                'parent_slug' => 'options-general.php'));
        }
    }

    /**
     * Get the latest list of canonical ads from the Internet Archive
     */
    public function load_canonical_ads() {
        // This script runs in the background and should be allowed to run as long as needed
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        // Get a list of ad instances from the archive
        $canonical_ads = $this->get_ad_list();

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
            update_field('field_576368cd22146', true, $wp_identifier); // Queued for reset
        }
    }

    /**
     * Load metadata for canonical ads
     */
    public function load_ad_metadata() {
        // This script runs in the background and should be allowed to run as long as needed
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        error_log("Loading Ad Metadata");
        global $wpdb;
        $transcript_lookup = $this->get_transcripts();
        $canonical_ad_lookup = $this->get_ad_list();

        $existing_ads = get_posts(array(
            'post_type' => 'archive_political_ad',
            'post_status' => 'any',
            'numberposts' => -1
        ));

        foreach($existing_ads as $existing_ad) {
            $wp_identifier = $existing_ad->ID;
            $ad_identifier = $existing_ad->post_title;
            $is_queued_for_reset = get_field('queue_for_reset', $wp_identifier);

            if($is_queued_for_reset === null)
                $is_queued_for_reset = true;

            // Load the latest transcript
            if(array_key_exists($ad_identifier, $transcript_lookup)) {
                $transcript = $transcript_lookup[$ad_identifier];
                update_field('field_56f2bc3b38669', $transcript , $wp_identifier); // transcript
            }

            if($is_queued_for_reset
            && array_key_exists($ad_identifier, $canonical_ad_lookup)) {
                error_log("Refreshing metadata for ".$ad_identifier);
                $metadata = $canonical_ad_lookup[$ad_identifier]->json;
                $ad_embed_url = 'https://archive.org/embed/'.$ad_identifier;
                $ad_type = "Political Ad";
                $ad_race = "";
                $ad_message = property_exists($metadata, 'message')?$metadata->message:'unknown';
                // Check if message is an array (unclear why this happens sometimes)
                $ad_message = is_array($ad_message)?array_pop($ad_message):$ad_message;

                // Store the basics
                update_field('field_566e30c856e35', $ad_embed_url , $wp_identifier); // embed_url
                update_field('field_566e328a943a3', $ad_identifier, $wp_identifier); // archive_id
                update_field('field_566e359261c2e', $ad_type, $wp_identifier); // ad_type
                update_field('field_566e360961c2f', $ad_message, $wp_identifier); // ad_message
                update_field('field_566e359261c2e', 'campaign', $wp_identifier); // ad type

                // Store the sponsors
                $sponsors = array();
                if(property_exists($metadata, 'sponsor')
                && is_array($metadata->sponsor)) {
                    foreach($metadata->sponsor as $sponsor) {
                        $new_sponsor = array(
                            'field_566e32fb943a5' => $sponsor // Name
                        );
                        $sponsors[] = $new_sponsor;
                    }
                }
                update_field('field_566e32bd943a4', $sponsors, $wp_identifier);

                // We're going to look up race / cycle from the candidate
                $race = "";
                $cycle = "";

                // Store the candidates
                $candidates = array();
                if(property_exists($metadata, 'candidate')
                && is_array($metadata->candidate)) {
                    foreach($metadata->candidate as $candidate) {

                        // Look up the race / cycle information
                        if($race == "") {
                            $candidate_object = PoliticalAdArchiveCandidate::get_candidate_by_name($candidate);
                            $race = $candidate_object->race;
                            $cycle = $candidate_object->cycle;
                        }

                        // Store the new candidate
                        $new_candidate = array(
                            'field_566e3573943a8' => $candidate // Name
                        );
                        $candidates[] = $new_candidate;
                    }
                }
                update_field('field_566e3533943a7', $candidates, $wp_identifier);

                // Store the race / cycle
                update_field('field_56e62a2927944', $cycle, $wp_identifier);
                update_field('field_56e62a2127943', $race, $wp_identifier);

                // Store the subjects
                $subjects = array();
                if(property_exists($metadata, 'subject')
                && is_array($metadata->subject)) {
                    foreach($metadata->subject as $subject) {
                        $new_subject = array(
                            'field_569d12ec487ef' => $subject // Name
                        );
                        $subjects[] = $new_subject;
                    }
                }
                update_field('field_569d12c8487ee', $subjects, $wp_identifier); // Subjects

                // Mark this as reset
                update_field('field_5787f9fbe5eda', false, $wp_identifier); // Queued for reset
            }
        }
    }

    /**
     * Get a list of actual airings for each ad
     * Then update the ad counts
     */
    public function load_ad_instances() {
        // This script runs in the background and should be allowed to run as long as needed
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        error_log("Loading Ad Instances");
        global $wpdb;
        $network_lookup = $this->get_network_lookup();
        $market_translations = $this->get_market_translations();

        // Load a list of canonical ads
        $existing_ads = get_posts(array(
            'post_type' => 'archive_political_ad',
            'post_status' => 'any',
            'numberposts' => -1
        ));

        // For each ad, load the ad instances associated
        foreach($existing_ads as $count => $existing_ad) {
            error_log("(".$count."/".sizeof($existing_ads).")"." Loading Instances for ".$existing_ad->post_title);
            try {
                $wp_identifier = $existing_ad->ID;
                $ad_identifier = $existing_ad->post_title;

                // Get the list of instances alraedy stored to prevent duplicate entry attempts
                $table_name = $wpdb->prefix . 'ad_instances';
                $query = "SELECT id as id,
                                 network as network,
                                 start_time as start_time,
                                 archive_identifier as archive_identifier,
                                 wp_identifier as wp_identifier
                            FROM ".$table_name."
                           WHERE archive_identifier = '".esc_sql($ad_identifier)."'";
                $results = $wpdb->get_results($query);

                $existing_instances = array();
                foreach($results as $result) {
                    $network = $result->network;
                    $start_time = $result->start_time;
                    if(!array_key_exists($network, $existing_instances)) {
                        $existing_instances[$network] = array();
                    }
                    $existing_instances[$network][] = "".strtotime($start_time);
                }

                // STEP 2: Get every instance, and create a record for each instance
                // NOTE: it won't double insert when run more than once due to the unique key
                $url = 'https://archive.org/details/tv?ad_instances='.$ad_identifier.'&output=json';
                $url_result = file_get_contents($url);
                $instances = json_decode($url_result);

                // Load the overrides
                $start_override = get_field('start_override', $wp_identifier);
                $end_override = get_field('end_override', $wp_identifier);

                // If this is a house or senate ad, set up a regional override...
                $ad_race = get_field('ad_race', $wp_identifier);
                $market_overrides = array();
                if($ad_race != ""
                && $ad_race != "PRES") {
                    $ad_state = substr($ad_race, 0,2);
                    error_log('Ad State: "'.$ad_state.'"');
                    $market_overrides = $this->get_market_overrides_by_state($ad_state);
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

                    // Does this instance already exist in our database?
                    if(array_key_exists($network, $existing_instances)
                    && array_search("".strtotime($start_time), $existing_instances[$network]) !== false)
                        continue;

                    // Does this instance happen in a market we care about
                    if(sizeof($market_overrides) > 0
                    && !in_array($market, $market_overrides)) {
                        error_log("Skipped market mismatch: in ".$market);
                        continue;
                    }

                    // If the start time isn't within the override range, skip this airing
                    if($start_override != null
                    && strtotime($start_override) > strtotime($start_time)) {
                        error_log("Skipped start override: ".$start_time);
                        continue;
                    }

                    if($end_override != null
                    && strtotime($end_override) < strtotime($start_time)) {
                        error_log("Skipped end override: ".$start_time);
                        continue;
                    }

                    error_log("New Instance: ".$network.": ".$start_time);

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

                // Remove any overrode airings that we may have saved in the past
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

                if(sizeof($market_overrides) > 0) {
                    $table_name = $wpdb->prefix . 'ad_instances';
                    $query = $wpdb->prepare('DELETE FROM %1$s WHERE market NOT IN ("'.implode('","', $market_overrides).'") && wp_identifier = %2$d', array($table_name, $wp_identifier));
                    $wpdb->query($query);
                }

            } catch( Exception $e ) {
                error_log($e);
            }
        }

        // Prep the table names
        $candidates_table = $wpdb->prefix . 'ad_candidates';
        $sponsors_table = $wpdb->prefix . 'ad_sponsors';
        $instances_table = $wpdb->prefix . 'ad_instances';
        $postmeta_table = $wpdb->prefix . 'postmeta';
        $post_table = $wpdb->prefix . 'posts';

        // Update ad air counts
        error_log ("Updating Ad Air Counts");
        $query = "SELECT count(*) as air_count,
                         count(DISTINCT network) as network_count,
                         count(DISTINCT market) as market_count,
                         MIN(start_time) as first_seen,
                         MAX(start_time) as last_seen,
                         wp_identifier as wp_identifier
                    FROM ".$instances_table."
                GROUP BY wp_identifier";

        $results = $wpdb->get_results($query);
        $air_metadata = array();
        foreach($results as $result) {
            $wp_identifier = $result->wp_identifier;
            update_field('field_566e3659fb227', $result->air_count, $wp_identifier); // air_count
            update_field('field_566e367e962e2', $result->market_count, $wp_identifier); // market_count
            update_field('field_566e3697962e3', $result->network_count, $wp_identifier); // network_count
            update_field('field_566e36b0962e4', $result->first_seen, $wp_identifier); // first_seen
            update_field('field_566e36d5962e5', $result->last_seen,  $wp_identifier); // last_seen
            error_log("Updating ID ".$wp_identifier.": ".print_r($result, true));
        }

        // Update candidate air counts
        error_log ("Updating Candidate Counts");
        $query = "SELECT count(DISTINCT ".$instances_table.".id) as air_count,
                         count(DISTINCT ".$postmeta_table.".post_id) as ad_count,
                         meta_value as ad_candidate
                    FROM ".$postmeta_table."
                    JOIN ".$post_table." ON ".$postmeta_table.".post_id = ".$post_table.".ID
               LEFT JOIN ".$instances_table." ON ".$postmeta_table.".post_id = ".$instances_table.".wp_identifier
                   WHERE ".$post_table.".post_status = 'publish'
                     AND meta_key LIKE 'ad_candidates_%_ad_candidate'
                GROUP BY ".$postmeta_table.".meta_value";

        $results = $wpdb->get_results($query);
        $air_metadata = array();
        foreach($results as $result) {
            $query = "UPDATE ".$candidates_table." SET ad_count = ".$result->ad_count.", air_count = ".$result->air_count." where name = '".esc_sql($result->ad_candidate)."'";
            $wpdb->query($query);
        }

        // Update sponsor air counts
        error_log ("Updating Sponsor Counts");
        $query = "SELECT count(DISTINCT ".$instances_table.".id) as air_count,
                         count(DISTINCT ".$postmeta_table.".post_id) as ad_count,
                         meta_value as ad_sponsor
                    FROM ".$postmeta_table."
                    JOIN ".$post_table." ON ".$postmeta_table.".post_id = ".$post_table.".ID
               LEFT JOIN ".$instances_table." ON ".$postmeta_table.".post_id = ".$instances_table.".wp_identifier
                   WHERE ".$post_table.".post_status = 'publish'
                     AND meta_key LIKE 'ad_sponsors_%_ad_sponsor'
                GROUP BY ".$postmeta_table.".meta_value";

        $results = $wpdb->get_results($query);
        $air_metadata = array();
        foreach($results as $result) {
            $query = "UPDATE ".$sponsors_table." SET ad_count = ".$result->ad_count.", air_count = ".$result->air_count." where name = '".esc_sql($result->ad_sponsor)."'";
            $wpdb->query($query);
        }
        error_log ("Finished Updating Counts");

    }

    public function load_candidates() {
        // This script runs in the background and should be allowed to run as long as needed
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        error_log("Loading Candidates");
        global $wpdb;
        $api_key = get_field('open_secrets_api_key', 'option');
        $url = 'http://www.opensecrets.org/api/index.php?method=internetArchive&apikey='.$api_key.'&output=json';

        // Load existing candidates
        $table_name = $wpdb->prefix . 'ad_candidates';

        $query = "SELECT id as id,
                         crp_unique_id as crp_unique_id
                    FROM ".$table_name;

        $results = $wpdb->get_results($query);
        $existing_candidates = array();
        foreach($results as $result) {
            $existing_candidates[$result->crp_unique_id] = $result->id;
        }

        // Create the GET
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 400);
        $curl_result = curl_exec($ch);
        curl_close($ch);

        // Parse the result
        $result = json_decode($curl_result);

        // Save the records
        foreach($result->response->record as $sponsor) {
            $sponsor = $sponsor->{'@attributes'};

            // We only care about candidates
            if($sponsor->type != "cand")
                continue;

            // Double check to make sure it fits the expected pattern
            if(substr($sponsor->sponsorname, -1) != ")")
                continue;

            // Make sure the record is valid
            if($sponsor->uniqueid == "")
                continue;

            error_log("Loading Candidate: ".$sponsor->sponsorname." (".$sponsor->uniqueid.")");

            $id = 0;
            $name = substr($sponsor->sponsorname, 0, -4);
            $affiliation = substr($sponsor->sponsorname, -2, 1);
            $race = $sponsor->race;
            $cycle = $sponsor->cycle;
            $crp_unique_id = $sponsor->uniqueid;
            $date_created = date("Y-m-d H:i:s");

            $values = array(
                'crp_unique_id' => $crp_unique_id,
                'name' => $name,
                'race' => $race,
                'cycle' => $cycle,
                'affiliation' => $affiliation,
                'date_created' => $date_created
            );
            $where = array(
                'crp_unique_id' => $crp_unique_id
            );

            $table_name = $wpdb->prefix . 'ad_candidates';
            if(array_key_exists($crp_unique_id, $existing_candidates)) {
                $values['id'] = $existing_candidates[$crp_unique_id];
                $wpdb->update(
                    $table_name,
                    $values,
                    $where
                );
            } else {
                $wpdb->insert(
                    $table_name,
                    $values
                );

                // Add the ID to prevent double creation if there's a dupe in one session
                $existing_candidates[$crp_unique_id] = $wpdb->insert_id;
            }
        }
    }

    public function load_sponsors() {
        // This script runs in the background and should be allowed to run as long as needed
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        error_log("Loading Sponsors");
        global $wpdb;
        $api_key = get_field('open_secrets_api_key', 'option');
        $url = 'http://www.opensecrets.org/api/index.php?method=internetArchive&apikey='.$api_key.'&output=json';

        // Load existing sponsors
        $table_name = $wpdb->prefix . 'ad_sponsors';

        $query = "SELECT id as id,
                         crp_unique_id as crp_unique_id
                    FROM ".$table_name;

        $results = $wpdb->get_results($query);
        $existing_sponsors = array();
        foreach($results as $result) {
            $existing_sponsors[$result->crp_unique_id] = $result->id;
        }

        // Create the GET
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 400);
        $curl_result = curl_exec($ch);
        curl_close ($ch);

        // Parse the result
        $result = json_decode($curl_result);

        // Process the result
        $sponsors = array();
        // Save the records
        foreach($result->response->record as $sponsor) {
            $sponsor = $sponsor->{'@attributes'};

            // We only care about candidates
            if($sponsor->type == "cand")
                continue;

            // Skip sponsors with a blank type
            // This is a bug on CRPs end
            if($sponsor->type == "")
                continue;

            // Make sure the record is valid
            if($sponsor->uniqueid == "")
                continue;

            error_log("Loading Sponsor: ".$sponsor->sponsorname." (".$sponsor->uniqueid.")");

            $name = $sponsor->sponsorname;
            $race = $sponsor->race;
            $cycle = $sponsor->cycle;
            $crp_unique_id = $sponsor->uniqueid.$name; // TODO: remove name from unique ID
            $type = $sponsor->type;
            if($sponsor->c4 != "")
                $type .= "4";
            if($sponsor->c5 != "")
                $type .= "5";
            if($sponsor->c6 != "")
                $type .= "6";

            $single_ad_candidate_id = $sponsor->singlecandCID;
            $does_support_candidate = ($sponsor->suppopp == "")?false:true;

            $date_created = date("Y-m-d H:i:s");

            $table_name = $wpdb->prefix . 'ad_sponsors';
            $values = array(
                'crp_unique_id' => $crp_unique_id,
                'name' => $name,
                'race' => $race,
                'cycle' => $cycle,
                'type' => $type,
                'single_ad_candidate_id' => $single_ad_candidate_id,
                'does_support_candidate' => $does_support_candidate,
                'date_created' => $date_created
            );
            $where = array(
                'crp_unique_id' => $crp_unique_id
            );


            $table_name = $wpdb->prefix . 'ad_sponsors';
            if(array_key_exists($crp_unique_id, $existing_sponsors)) {
                $values['id'] = $existing_sponsors[$crp_unique_id];
                $wpdb->update(
                    $table_name,
                    $values,
                    $where
                );
            } else {
                $wpdb->insert(
                    $table_name,
                    $values
                );

                // Add the ID to prevent double creation if there's a dupe in one session
                $existing_candidates[$crp_unique_id] = $wpdb->insert_id;             
            }

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

    private function get_network_lookup() {
        // Get a list of ad instances from the archive
        $url = 'https://archive.org/tv.php?chan2market=1&output=json';
        $url_result = file_get_contents($url);
        $results = json_decode($url_result);

        // Convert results to an expected format
        $networks = array();
        foreach($results as $network => $values) {
            $networks[$network] = array(
                'market' => $values[0],
                'location' => $values[1]
            );
        }
        return $networks;
    }

    private function get_market_translations() {
        return array(
            "BOS" => "Boston, MA/Manchester, NH",
            "CAE" => "Columbia, SC",
            "CID" => "Ceder Rapids-Waterloo-Iowa City-Dublin, Iowa",
            "CLE" => "Cleveland, Ohio",
            "CLT" => "Charlotte, NC",
            "COS" => "Colorado Springs-Pueblo, CO",
            "CVG" => "Cincinnati, OH",
            "DEN" => "Denver, CO",
            "DSM" => "Des Moines-Ames, Iowa",
            "GSP" => "Greenville-Spartanburg, SC/Asheville-Anderson, NC",
            "LAS" => "Las Vegas, NV",
            "MCO" => "Orlando-Daytona Beach-Melbourne, FL",
            "MIA" => "Miami-Fort Lauderdale, FL",
            "NYC" => "New York City, NY",
            "ORF" => "Norfolk-Portsmouth-Newport News, NC",
            "PHL" => "Philadelphia, PA",
            "RDU" => "Raleigh-Durham-Fayetteville,  NC",
            "RNO" => "Reno, NV",
            "ROA" => "Roanoke-Lynchburg, VA",
            "MKE" => "Milwaukee, WI",
            "PHX" => "Phoenix-Prescott, AZ",
            "SF" =>  "San Francisco-Oakland-San Jose, CA",
            "SUX" => "Sioux City, Iowa",
            "TPA" => "Tampa-St. Petersburg, FL",
            "VA" =>  "Washington, DC/Hagerstown, MD"
        );
    }

    private function get_transcripts() {
        $url = 'https://archive.org/advancedsearch.php?q=collection%3Apolitical_ads+AND+mediatype%3Amovies&fl%5B%5D=description&fl%5B%5D=identifier&sort%5B%5D=&sort%5B%5D=&sort%5B%5D=&rows=10000&page=1&output=json&save=yes';
        $url_result = file_get_contents($url);
        $results = json_decode($url_result);

        // Convert results to an expected format
        $transcripts = array();
        if($results) {
            if(property_exists($results, 'response')
            && property_exists($results->response, 'docs')) {
                foreach($results->response->docs as $result) {
                    if(property_exists($result, 'identifier')
                    && property_exists($result, 'description'))
                        $transcripts[$result->identifier] = $result->description;
                }
            }
        }

        return $transcripts;
    }

    private function get_market_overrides_by_state($state_code) {
        switch($state_code) {
            case "AL":
                return array();
            case "AK":
                return array();
            case "AZ":
                return array("PHX");
            case "AR":
                return array();
            case "CA":
                return array("SF");
            case "CO":
                return array("COS", "DEN");
            case "CT":
                return array();
            case "DE":
                return array();
            case "FL":
                return array("MCO", "MIA", "TPA");
            case "GA":
                return array();
            case "HI":
                return array();
            case "ID":
                return array();
            case "IL":
                return array();
            case "IN":
                return array();
            case "IA":
                return array("CID", "DSM", "SUX");
            case "KS":
                return array();
            case "KY":
                return array();
            case "LA":
                return array();
            case "ME":
                return array();
            case "MD":
                return array("VA");
            case "MA":
                return array("BOS");
            case "MI":
                return array();
            case "MN":
                return array();
            case "MS":
                return array();
            case "MO":
                return array();
            case "MT":
                return array();
            case "NE":
                return array();
            case "NV":
                return array("LAS", "RNO");
            case "NH":
                return array("BOS");
            case "NJ":
                return array();
            case "NM":
                return array();
            case "NY":
                return array("NYC");
            case "NC":
                return array("CLT", "GSP", "ORF", "RDU");
            case "ND":
                return array();
            case "OH":
                return array("CLE", "CVG");
            case "OK":
                return array();
            case "OR":
                return array();
            case "PA":
                return array("PHL");
            case "RI":
                return array("BOS");
            case "SC":
                return array("CAE");
            case "SD":
                return array();
            case "TN":
                return array();
            case "TX":
                return array();
            case "UT":
                return array();
            case "VT":
                return array("BOS");
            case "VA":
                return array("ROA");
            case "WA":
                return array();
            case "WV":
                return array();
            case "WI":
                return array("MKE");
            case "WY":
                return array();
            default:
                return array();
        }
        return array();
    }

    private function get_ad_list() {
        $url = 'https://archive.org/details/tv?canonical_ads=1&metadata=1&output=json';
        $url_result = file_get_contents($url);
        $canonical_ads = json_decode($url_result);

        $organized_canonical_ads = array();
        foreach($canonical_ads as $canonical_ad) {
            $organized_canonical_ads[$canonical_ad->identifier] = $canonical_ad;
        }

        return $organized_canonical_ads;
    }

    private function get_air_metadata() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ad_instances';
        $query = "SELECT count(*) as air_count,
                         count(DISTINCT network) as network_count,
                         count(DISTINCT market) as market_count,
                         MIN(start_time) as first_seen,
                         MAX(start_time) as last_seen,
                         archive_identifier as archive_identifier
                    FROM ".$table_name."
                GROUP BY archive_identifier";

        $results = $wpdb->get_results($query);
        $air_metadata = array();
        foreach($results as $result)
            $air_metadata[$result->archive_identifier] = $result;

        return $air_metadata;
    }
}
?>
