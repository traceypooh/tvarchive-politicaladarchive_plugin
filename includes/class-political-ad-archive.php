<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    PoliticalAdArchive
 * @subpackage PoliticalAdArchive/includes
 * @author     Daniel Schultz <dan.schultz@archive.org>
 */
class PoliticalAdArchive {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Plugin_Name_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {

        $this->plugin_name = 'political-ad-archive';
        $this->version = '1.0.0';

        $this->load_dependencies();
        $this->define_general_hooks();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - PoliticalAdArchiveLoader. Orchestrates the hooks of the plugin.
     * - PoliticalAdArchiveAdmin. Defines all hooks for the admin area.
     * - PoliticalAdArchivePublic. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-political-ad-archive-loader.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-political-ad-archive-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-political-ad-archive-public.php';

        /**
         * The class responsible for defining all actions that are shared across both admin and public
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-political-ad-archive-general.php';

        /**
         * Load in support objects
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/models/interface-political-ad-archive-buffered-query.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/models/class-political-ad-archive-api-response.php';

        /**
         * Load in the model files
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/models/class-political-ad-archive-ad.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/models/class-political-ad-archive-candidate.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/models/class-political-ad-archive-sponsor.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/models/class-political-ad-archive-message.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/models/class-political-ad-archive-channel.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/models/class-political-ad-archive-program.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/models/class-political-ad-archive-ad-type.php';

        /**
         * Load in the search tools
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/searches/class-political-ad-archive-ad-search.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/searches/class-political-ad-archive-ad-instance-search.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/searches/class-political-ad-archive-market-counts-search.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/searches/class-political-ad-archive-candidate-search.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/searches/class-political-ad-archive-sponsor-search.php';

        /**
         * Load in the api files
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/api/class-political-ad-archive-api-get-ads.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/api/class-political-ad-archive-api-get-ad-instances.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/api/class-political-ad-archive-api-get-market-counts.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/api/class-political-ad-archive-api-get-candidates.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/api/class-political-ad-archive-api-get-sponsors.php';

        $this->loader = new PoliticalAdArchiveLoader();

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {

        $plugin_admin = new PoliticalAdArchiveAdmin( $this->get_plugin_name(), $this->get_version() );

        // Set up ACF
        $this->loader->add_action( 'admin_notices', $plugin_admin, 'verify_acf_pro_enabled');
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'register_admin_menu', 1);
        $this->loader->add_filter( 'acf/settings/load_json', $plugin_admin, 'add_acf_json_load_point' );

        if(class_exists('acf'))
            $this->loader->add_filter( 'acf/settings/save_json', $plugin_admin, 'override_acf_json_save_point' );


        $this->loader->add_action( 'archive_sync', $plugin_admin, 'load_candidates' );
        $this->loader->add_action( 'archive_sync', $plugin_admin, 'load_sponsors' );
        $this->loader->add_action( 'archive_sync', $plugin_admin, 'load_canonical_ads' );
        $this->loader->add_action( 'archive_sync', $plugin_admin, 'load_ad_metadata' );
        $this->loader->add_action( 'archive_sync', $plugin_admin, 'load_ad_instances' );

        // Set up admin interface hooks
        $this->loader->add_action( 'admin_notices', $plugin_admin, 'check_ad_metadata' );

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {

        $plugin_public = new PoliticalAdArchivePublic( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        $this->loader->add_action( 'init', $plugin_public, 'register_api_routes' );
        $this->loader->add_filter( 'query_vars', $plugin_public, 'filter_api_query_vars' );
        $this->loader->add_action( 'parse_request', $plugin_public, 'parse_request' );

    }

    /**
     * Register all of the hooks that are used both in admin and public contexts
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_general_hooks() {
        $plugin_general = new PoliticalAdArchiveGeneral( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'init', $plugin_general, 'register_archive_political_ad_type');

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Plugin_Name_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

}
