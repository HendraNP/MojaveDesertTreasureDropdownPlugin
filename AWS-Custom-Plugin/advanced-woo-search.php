<?php

/*
Plugin Name: Advanced Woo Search
Description: Advance ajax WooCommerce product search.
Version: 1.74
Author: ILLID
Author URI: https://advanced-woo-search.com/
Text Domain: aws
WC requires at least: 3.0.0
WC tested up to: 3.6.0
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AWS_VERSION', '1.74' );


define( 'AWS_DIR', dirname( __FILE__ ) );
define( 'AWS_URL', plugins_url( '', __FILE__ ) );


define( 'AWS_INDEX_TABLE_NAME', 'aws_index' );
define( 'AWS_CACHE_TABLE_NAME', 'aws_cache' );


if ( ! class_exists( 'AWS_Main' ) ) :

/**
 * Main plugin class
 *
 * @class AWS_Main
 */
final class AWS_Main {

	/**
	 * @var AWS_Main The single instance of the class
	 */
	protected static $_instance = null;

    /**
     * @var AWS_Main Array of all plugin data $data
     */
    private $data = array();

    /**
     * @var AWS_Main Cache instance
     */
    public $cache = null;

	/**
	 * Main AWS_Main Instance
	 *
	 * Ensures only one instance of AWS_Main is loaded or can be loaded.
	 *
	 * @static
	 * @return AWS_Main - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {

        $this->data['settings'] = get_option( 'aws_settings' );

		add_filter( 'widget_text', 'do_shortcode' );

		add_shortcode( 'aws_search_form', array( $this, 'markup' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );

        add_action( 'wp_ajax_product_dropdown', 'product_dropdown' );
        add_action( 'wp_ajax_nopriv_product_dropdown', 'product_dropdown' );

        add_action( 'wp_enqueue_scripts', array( $this, 'siteurl_scripts' ) );

		add_filter( 'plugin_action_links', array( $this, 'add_action_link' ), 10, 2 );

		load_plugin_textdomain( 'aws', false, dirname( plugin_basename( __FILE__ ) ). '/languages/' );

        $this->includes();
                
        add_action( 'init', array( $this, 'init' ), 0 );

        add_filter( 'wcml_multi_currency_ajax_actions', array( $this, 'add_wpml_ajax_actions' ) );

        if ( $this->get_settings('seamless') === 'true' ) {
            add_filter( 'get_search_form', array( $this, 'markup' ), 999999 );
            add_filter( 'get_product_search_form', array( $this, 'markup' ), 999999 );
        }

    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    public function includes() {
        include_once( 'includes/class-aws-helpers.php' );
        include_once( 'includes/class-aws-versions.php' );
        include_once( 'includes/class-aws-admin-fields.php' );
        include_once( 'includes/class-aws-admin.php' );
        include_once( 'includes/class-aws-cache.php' );
        include_once( 'includes/class-aws-table.php' );
        include_once( 'includes/class-aws-markup.php' );
        include_once( 'includes/class-aws-search.php' );
        include_once( 'includes/class-aws-search-page.php' );
        include_once( 'includes/class-aws-order.php' );
        include_once( 'includes/class-aws-integrations.php' );
        include_once( 'includes/widget.php' );
    }

	/*
	 * Generate search box markup
	 */
	 public function markup( $args = array() ) {

         $markup = new AWS_Markup();

         return $markup->markup();

	}

    /*
	 * Sort products
	 */
    public function order( $products, $order_by ) {

        $order = new AWS_Order( $products, $order_by );

        return $order->result();

    }

    /*
     * Init plugin classes
     */
    public function init() {
        $this->cache = AWS_Cache::factory();
    }

	/*
	 * Load assets for search form
	 */
	public function load_scripts() {
		wp_enqueue_style( 'aws-style', AWS_URL . '/assets/css/common.css', array(), AWS_VERSION );
        wp_enqueue_script('aws-script', AWS_URL . '/assets/js/common.js', array('jquery'), AWS_VERSION, true);
        wp_localize_script('aws-script', 'aws_vars', array(
            'url'        => get_site_url(),
            'ajaxurl'    => admin_url('admin-ajax.php'),
            'ajax_nonce' => wp_create_nonce( 'product_dropdown' ),
            'sale'       => __('Sale!', 'aws'),
            'sku'        => __('SKU', 'aws'),
            'showmore'   => $this->get_settings('show_more_text') ? AWS_Helpers::translate( 'show_more_text', stripslashes( $this->get_settings('show_more_text') ) ) : __('View all results', 'aws'),
            'noresults'  => $this->get_settings('not_found_text') ? AWS_Helpers::translate( 'not_found_text', stripslashes( $this->get_settings('not_found_text') ) ) : __('Nothing found', 'aws'),
        ));
	}


	/*
	 * Add settings link to plugins
	 */
	public function add_action_link( $links, $file ) {
		$plugin_base = plugin_basename( __FILE__ );

		if ( $file == $plugin_base ) {
			$setting_link = '<a href="' . admin_url('admin.php?page=aws-options') . '">'.esc_html__( 'Settings', 'aws' ).'</a>';
			array_unshift( $links, $setting_link );

            $premium_link = '<a href="https://advanced-woo-search.com/?utm_source=plugin&utm_medium=settings-link&utm_campaign=aws-pro-plugin" target="_blank">'.esc_html__( 'Get Premium', 'aws' ).'</a>';
            array_unshift( $links, $premium_link );
		}

		return $links;
	}

    /*
     * Get plugin settings
     */
    public function get_settings( $name ) {
        $plugin_options = $this->data['settings'];
		$return_value = isset( $plugin_options[ $name ] ) ? $plugin_options[ $name ] : '';
        return $return_value;
    }

    /*
     * Add ajax action to WPML plugin
     */
    function add_wpml_ajax_actions( $actions ){
        $actions[] = 'aws_action';
        return $actions;
    }

}

endif;


/**
 * Returns the main instance of AWS_Main
 *
 * @return AWS_Main
 */
function AWS() {
    return AWS_Main::instance();
}


/*
 * Check if WooCommerce is active
 */
if ( ! aws_is_plugin_active( 'advanced-woo-search-pro/advanced-woo-search-pro.php' ) ) {
    if ( aws_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        add_action( 'woocommerce_loaded', 'aws_init' );
    } else {
        add_action( 'admin_notices', 'aws_install_woocommerce_admin_notice' );
    }
}

/*
 * Check whether the plugin is active by checking the active_plugins list.
 */
function aws_is_plugin_active( $plugin ) {
    return in_array( $plugin, (array) get_option( 'active_plugins', array() ) ) || aws_is_plugin_active_for_network( $plugin );
}


/*
 * Check whether the plugin is active for the entire network
 */
function aws_is_plugin_active_for_network( $plugin ) {
    if ( !is_multisite() )
        return false;

    $plugins = get_site_option( 'active_sitewide_plugins' );
    if ( isset($plugins[$plugin]) )
        return true;

    return false;
}


/*
 * Error notice if WooCommerce plugin is not active
 */
function aws_install_woocommerce_admin_notice() {
	?>
	<div class="error">
		<p><?php esc_html_e( 'Advanced Woo Search plugin is enabled but not effective. It requires WooCommerce in order to work.', 'aws' ); ?></p>
	</div>
	<?php
}

/*
 * Init AWS plugin
 */
function aws_init() {
    AWS();
}


if ( ! function_exists( 'aws_get_search_form' ) ) {

    /**
     * Returns search form html
     *
     * @since 1.47
     * @return string
     */
    function aws_get_search_form( $echo = true, $args = array() ) {

        $form = '';

        if ( ! aws_is_plugin_active( 'advanced-woo-search-pro/advanced-woo-search-pro.php' ) ) {
            if ( aws_is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
                $form = AWS()->markup( $args );
            }
        }

        if ( $echo ) {
            echo $form;
        } else {
            return $form;
        }

    }

}

if ( ! function_exists( 'smyles_get_taxonomy_hierarchy' ) ) {
    /**
     * Recursively get taxonomy and its children
     *
     * @param string $taxonomy
     * @param int    $parent Parent term ID (0 for top level)
     * @param array  $args   Array of arguments to pass to get_terms (to override default)
     *
     * @return array
     */
    function smyles_get_taxonomy_hierarchy( $taxonomy, $parent = 0, $args = array( 'taxonomy' => 'product_cat','hide_empty' => false ) ) {
        $defaults = array(
            'parent'     => $parent,
            'show_option_none'   => $type,
            'show_count'    => 1,
            'option_none_value'  => '-1',
            'hide_empty' => false
        );
        $r = wp_parse_args( $args, $defaults );
        // get all direct decendants of the $parent
        $terms = get_terms( $taxonomy, $r );
        // prepare a new array.  these are the children of $parent
        // we'll ultimately copy all the $terms into this new array, but only after they
        // find their own children
        $children = array();
        // go through all the direct decendants of $parent, and gather their children
        foreach ( $terms as $term ) {
            // recurse to get the direct decendants of "this" term
            $term->children = smyles_get_taxonomy_hierarchy( $taxonomy, $term->term_id );
            // add the term to our new array
            $children[ $term->term_id ] = $term;
        }

        /*$args = array(
            'post_type'             => 'product',
            'post_status'           => 'publish',
            'ignore_sticky_posts'   => 1,
            'posts_per_page'        => -1,
            'tax_query'             => array(
                array(
                    'taxonomy'      => 'product_cat',
                    'field' => 'term_id', //This is optional, as it defaults to 'term_id'
                    'terms'         => 114,
                    'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
                ),
                array(
                    'taxonomy'      => 'product_visibility',
                    'field'         => 'slug',
                    'terms'         => 'exclude-from-catalog', // Possibly 'exclude-from-search' too
                    'operator'      => 'NOT IN'
                )
            )
        );

        $products = new WP_Query($args);
        $product = array();

        if ( $products->have_posts() ) {
            while ( $products->have_posts() ) {
                $products->the_post();
                $product[get_the_ID()] = array('id' => get_the_ID(), 'name' => get_the_title(), 'slug' => get_post_field('post_name', get_the_ID()));
            }           
        }

        var_dump($product);*/

        // send the results back to the caller
        return $children;
    }

    function product_dropdown() {
        global $wpdb; // this is how you get access to the database
        $product_cat = intval($_POST['product_cat']);

        $args = array(
            'post_type'             => 'product',
            'post_status'           => 'publish',
            'ignore_sticky_posts'   => 1,
            'posts_per_page'        => -1,
            'tax_query'             => array(
                array(
                    'taxonomy'      => 'product_cat',
                    'field' => 'term_id', //This is optional, as it defaults to 'term_id'
                    'terms'         => $product_cat,
                    'operator'      => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
                ),
                array(
                    'taxonomy'      => 'product_visibility',
                    'field'         => 'slug',
                    'terms'         => 'exclude-from-catalog', // Possibly 'exclude-from-search' too
                    'operator'      => 'NOT IN'
                )
            )
        );

        $products = new WP_Query($args);
        $product = array();
        $i = 0;
        if ( $products->have_posts() ) {
            while ( $products->have_posts() ) {
                $products->the_post();
                $product[$i] = array('id' => get_the_ID(), 'name' => get_the_title(), 'slug' => get_post_field('post_name'));
                $i++;
            }           
        }

        echo json_encode($product);

        wp_die(); // this is required to terminate immediately and return a proper response
    }

}