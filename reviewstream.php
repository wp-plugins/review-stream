<?php
/*
Plugin Name: Review Stream
Plugin URI: https://wordpress.org/plugins/review-stream
Description: Stream your latest and greatest reviews from around the Web to your Wordpress site and display them with SEO-friendly rich-snippet markup.
Version: 0.11
Author: Grade Us, Inc.
Author URI: https://www.grade.us/home
Author Email: hello@grade.us
License:

  Copyright 2013-2015 | Grade Us, Inc.

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  
*/


class ReviewStream {

  /*--------------------------------------------*
   * Constants
   *--------------------------------------------*/
  const name = 'Review Stream';
  const slug = 'reviewstream';
  
  /**
   * Constructor
   */
  function __construct() {
    //register an activation hook for the plugin
    register_activation_hook( __FILE__, array( &$this, 'install_reviewstream' ) );

    //Hook up to the init action
    add_action( 'init', array( &$this, 'init_reviewstream' ) );

    add_action('admin_init', array(&$this, 'admin_init'));
    add_action('admin_menu', array(&$this, 'add_menu'));

    //shortcode
    add_shortcode('reviewstream', array($this, 'shortcode'));
  }
  
  /**
   * Runs when the plugin is activated
   */  
  function install_reviewstream() {
    // do not generate any output here
  }
  
  /**
   * Runs when the plugin is initialized
   */
  function init_reviewstream() {
    // Setup localization
    load_plugin_textdomain( self::slug, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
    // Load JavaScript and stylesheets
    $this->register_scripts_and_styles();

    // Check for custom config options
    if(file_exists(dirname(__FILE__).'/config.php')) {
      include (dirname(__FILE__).'/config.php');
      $this->brand = $brand;
      $this->brand_domain = $brand_domain;
      $this->powered_by = $powered_by;
    } else {
      $this->brand = 'Grade.us';
      $this->brand_domain = 'grade.us';
      $this->powered_by = 'Powered by <a href="https://www.grade.us/home" title="Grade.us Review Management Software">Grade.us</a>';
    }

    // Register the shortcode [reviewstream]
    add_shortcode( 'reviewstream', array( &$this, 'render_shortcode' ) );
  
    if ( is_admin() ) {
      //this will run when in the WordPress admin
    } else {
      //this will run when on the frontend
    }

    /*
     * TODO: Define custom functionality for your plugin here
     *
     * For more information: 
     * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
     */
    //add_action( 'your_action_here', array( &$this, 'action_callback_method_name' ) );
    //add_filter( 'your_filter_here', array( &$this, 'filter_callback_method_name' ) );    
  }

  function action_callback_method_name() {
    // TODO define your action method here
  }

  function filter_callback_method_name() {
    // TODO define your filter method here
  }

  // main reviewstream shortcode
  function render_shortcode($atts) {
    $base_url = "https://www.grade.us/api/v1/reviews";
    $token = get_option('rs_api_token');
    // Extract the attributes
    extract(shortcode_atts(array(
      'path' => get_option('rs_path'),
      'count' => get_option('rs_default_count'),
      'type' => get_option('rs_type'),
      'format' => get_option('rs_schema'),
      'show_aggregate_rating' => get_option('rs_show_aggregate_rating'),
      'show_reviews' => get_option('rs_show_reviews'),
      'show_powered_by' => get_option('rs_show_powered_by')
      ), $atts));
    // Set defaults just in case
    if(!$type) {
      // Default
      $type = 'LocalBusiness';
    }
    if(!$format) {
      // Default
      $format = 'microdata';
    }
    if($show_aggregate_rating != 'false' && $show_aggregate_rating != false) {
      // Default
      $show_aggregate_rating = true;
    } else {
      $show_aggregate_rating = false;
    }
    if($show_reviews != 'false' && $show_reviews != false) {
      // Default
      $show_reviews = true;
    } else {
      $show_reviews = false;
    }
    if($show_powered_by != 'false' && $show_powered_by != false) {
      // Default
      $show_powered_by = true;
    } else {
      $show_powered_by = false;
    }
    // Build the query, remove double slashes
    $query = '/'.$path.'/?count='.$count;
    while (strpos($query, '//') > -1) {
      $query = str_replace('//', '/', $query);
    }
    $url = $base_url.$query;
    $args = array(
      'headers' => 'Authorization: Token token='.$token,
      'timeout' => 30
    );
    // Retrieve from cache or get and set cache
    $response = get_transient('reviewstream-'.$path.'-'.$count);
    if ($response === false) {
      $req = wp_remote_get($url, $args);
      if($req['response']['code'] == 200) {
        $response = $req['body'];
        set_transient('reviewstream-'.$path.'-'.$count, $response, 3600 * 6);
      } else {
        return 'Error connecting, check your Review Stream settings';
      }
    }
    // Get JSON into assoc array
    $response = json_decode($response, true);
    $output = '';
    // Add aggregate rating content
    if ($show_aggregate_rating) {
      $template_content = file_get_contents(dirname(__FILE__).'/templates/aggregate_rating_'.$format.'.php');
      $widget = '<div class="rating-widget"><span class="stars">';
      for($s=1;$s<=$response['ratings_max'];$s++) {
        $class = 'star-md';
        if($response['ratings_average'] >= $s) {
          //$class .= '';
        } elseif($response['ratings_average'] > $s-1) {
          $class .= '-half';
        } else {
          $class .= '-off';
        }
        $widget .= '<i class="'.$class.'">&nbsp;</i>';
      }
      $widget .= '</span></div>';
      $template_content = str_replace('[[ratings_widget]]', $widget, $template_content);
      $template_content = str_replace('[[ratings_average]]', $response['ratings_average'], $template_content);
      $template_content = str_replace('[[ratings_max]]', $response['ratings_max'], $template_content);
      $template_content = str_replace('[[reviews_count]]', $response['reviews_count'], $template_content);
      $output .= $template_content;
    }
    // Add review content
    if ($show_reviews) {
      $template_content = file_get_contents(dirname(__FILE__).'/templates/review_'.$format.'.php');
      $tokens = array('category', 'attribution', 'snippet', 'rating', 'url');
      foreach($response['reviews'] as $indresp) {
        $widget = '<div class="rating-widget"><span class="stars">';
        for($s=1;$s<=$response['ratings_max'];$s++) {
          $class = 'star-sm';
          if($indresp['rating'] >= $s) {
            //$class .= '';
          } elseif($indresp['rating'] > $s-1) {
            $class .= '-half';
          } else {
            $class .= '-off';
          }
          $widget .= '<i class="'.$class.'">&nbsp;</i>';
        }
        $widget .= '</span></div>';
        $tempcontent = $template_content;
        $dt = date('m/d/Y', strtotime($indresp['date']));
        $tempcontent = str_replace('[[reviewdate]]', $dt, $tempcontent);
        $tempcontent = str_replace('[[ratingwidget]]', $widget, $tempcontent);
        foreach($tokens as $token) {
          $tempcontent = str_replace('[['.$token.']]', $indresp[$token], $tempcontent);
        }
        $output .= $tempcontent;
      }
    }
    $template_wrapper = file_get_contents(dirname(__FILE__).'/templates/wrapper_'.$format.'.php');
    $template_wrapper = str_replace('[[type]]', $type, $template_wrapper);
    $output = str_replace('[[content]]', $output, $template_wrapper);
    $output = str_replace('[[name]]', $response['name'], $output);
    if ($show_powered_by) {
      $output = str_replace('[[powered_by]]', $this->powered_by, $output);
    } else {
      $output = str_replace('[[powered_by]]', '', $output);
    }
    return $output;
  }

  /**
   * Registers and enqueues stylesheets for the administration panel and the
   * public facing site.
   */
  private function register_scripts_and_styles() {
    if ( !is_admin() ) {
      wp_enqueue_style('reviewstream', 'https://static.grade.us/assets/reviewstream.css?v='.date('Ymd'));
    }
  } // end register_scripts_and_styles
  
  /**
   * Helper function for registering and enqueueing scripts and styles.
   *
   * @name  The   ID to register with WordPress
   * @file_path   The path to the actual file
   * @is_script   Optional argument for if the incoming file_path is a JavaScript source file.
   */
  private function load_file( $name, $file_path, $is_script = false ) {

    $url = plugins_url($file_path, __FILE__);
    $file = plugin_dir_path(__FILE__) . $file_path;

    if( file_exists( $file ) ) {
      if( $is_script ) {
        wp_register_script( $name, $url, array('jquery') ); //depends on jquery
        wp_enqueue_script( $name );
      } else {
        wp_register_style( $name, $url );
        wp_enqueue_style( $name );
      } // end if
    } // end if

  } // end load_file

  /* admin_init */
  public function admin_init() {
    $this->init_settings();
  }
  public function init_settings() {
    register_setting('wprs_group', 'rs_type');
    register_setting('wprs_group', 'rs_schema');
    register_setting('wprs_group', 'rs_path', array($this, 'rs_path_validate'));
    register_setting('wprs_group', 'rs_api_token', array($this, 'rs_token_validate'));
    register_setting('wprs_group', 'rs_show_aggregate_rating');
    register_setting('wprs_group', 'rs_show_reviews');
    register_setting('wprs_group', 'rs_show_powered_by');
    register_setting('wprs_group', 'rs_default_count', array($this, 'rs_default_count_validate')/*'intval'*/);
  }
  public function add_menu() {
    add_options_page('Review Stream Settings', 'Review Stream', 'manage_options', 'wprs_plugin', array(&$this, 'wprs_settings_page'));
  }
  public function wprs_settings_page() {
    if(!current_user_can('manage_options')) {
      wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
  }
  function rs_default_count_validate($input) {
    $max = 20;
    $newinput = intval($input);
    if($newinput < 1) {
      $newinput = 1;
      add_settings_error('rs_default_count', esc_attr('settings_updated'), 'Default count must be at least 1. Minimum value has been added.');
    }
    if($newinput > $max) {
      $newinput = $max;
      add_settings_error('rs_default_count', esc_attr('settings_updated'), 'Default count cannot be more than '.$max.'. Maximum value has been added.');
    }
    return $newinput;
  }
  function rs_path_validate($input) {
    if(empty($input)) {
      add_settings_error('rs_path', esc_attr('settings_updated'), 'Path cannot be empty.', 'error');
    }
    return $input;
  }
  function rs_token_validate($input) {
    if(empty($input)) {
      add_settings_error('rs_api_token', esc_attr('settings_updated'), 'API token cannot be empty.', 'error');
    }
    return $input;
  }


} // end class

if(class_exists('ReviewStream')) {
  $reviewstream = new ReviewStream(); 
}

if(isset($reviewstream)) {
  function plugin_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=wprs_plugin">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
  }
  $plugin = plugin_basename(__FILE__);
  add_filter("plugin_action_links_$plugin", 'plugin_settings_link');
}
?>