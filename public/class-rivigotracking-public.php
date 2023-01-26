<?php

require_once __DIR__ . './../includes/RivigoTracking/RivigoTracking.php';
require_once __DIR__ . './../includes/RivigoTracking/RivigoTrackerShortCode.php';

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.linkedin.com/in/naeem-akhtar-cs/
 * @since      1.0.0
 *
 * @package    Rivigotracking
 * @subpackage Rivigotracking/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Rivigotracking
 * @subpackage Rivigotracking/public
 * @author     Naeem Akhtar <naeem.akhtar.cs@gmail.com>
 */
class Rivigotracking_Public
{

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
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Rivigotracking_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Rivigotracking_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/rivigotracking-public.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Rivigotracking_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Rivigotracking_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/rivigotracking-public.js', array('jquery'), $this->version, false);

    }

    public function trackRivigoApi()
    {
        register_rest_route(
            'trackRivigo',
            '/(?P<data>[a-zA-Z0-9-]+)',
            array(
                'methods' => 'GET',
                'callback' => 'trackRivigoParcel',
            )
        );
    }

    public function registerTrackerShortCode()
    {
        add_shortcode('RivigoTracking', 'rivigoTrackerShortCode');

        global $wpdb;
        $table_name = $wpdb->prefix . "rivigo_tracking";

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id INT NOT NULL AUTO_INCREMENT,
            appversion INT,
            PRIMARY KEY  (id)
            ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        $result = $wpdb->get_results("SELECT count(*) AS count FROM " . $wpdb->prefix . "rivigo_tracking");

        if ($result[0]->count == 0) {
            $wpdb->insert(
                $table_name,
                array(
                    'appversion' => 10329,
                )
            );
        } else {
            $wpdb->get_results("UPDATE " . $wpdb->prefix . "rivigo_tracking SET appversion=10329");
        }
    }

}
