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
class PoliticalAdArchiveGeneral {

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
  public function __construct($plugin_name, $version) {

      $this->plugin_name = $plugin_name;
      $this->version = $version;

  }

    /**
     * Get the latest list of canonical ads from the Internet Archive
     */
  public function register_archive_political_ad_type() {
      register_post_type(
           'archive_political_ad',
          array(
              'rewrite' => array('with_front' => false, 'slug' => 'ad'),
              'labels' => array(
                  'name' => __('Political Ads'),
                  'singular_name' => __('Political Ad'),
                  'add_new_item' => __('Add New Ad'),
                  'edit_item' => __('Edit Ad'),
                  'new_item' => __('New Ad'),
                  'view_item' => __('View Ad'),
                  'search_items' => __('Search Ads'),
                  'not_found' => __('No ads found'),
                  'not_found_in_trash' => __('No ads found in Trash'),
                  'parent_item_colon' => __('Parent Ad'),
              ),
              'capabilities' => array(
                  'edit_post'          => 'edit_ad',
                  'read_post'          => 'read_ad',
                  'delete_post'        => 'delete_ad',
                  'delete_posts'       => 'delete_ads',
                  'edit_posts'         => 'edit_ads',
                  'edit_others_posts'  => 'edit_others_ads',
                  'publish_posts'      => 'publish_ads',
                  'read_private_posts' => 'read_private_ads',
                  'create_posts'       => 'create_ads',
              ),
              'description' => __('A political ad identified and tracked by the Internet Archive.'),
              'public' => true,
              'menu_icon' => 'dashicons-media-video',
              'has_archive' => true,
              'supports' => array( 'title')
          )
      );

      // Set up role capabilities
      $roles = ['author', 'administrator'];

    foreach ($roles as $roleName) {

  // Get the author role
      $role = get_role($roleName);

  // Add full political ad edit capabilities
      $role->add_cap('edit_ad');
      $role->add_cap('read_ad');
      $role->add_cap('delete_ad');
      $role->add_cap('edit_ads');
      $role->add_cap('edit_others_ads');
      $role->add_cap('publish_ads');
      $role->add_cap('read_private_ads');
      $role->add_cap('create_ads');
    }
  }
}
?>
