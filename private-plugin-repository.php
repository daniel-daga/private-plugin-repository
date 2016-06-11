<?php

/**
 * @link              http://gruetzmacher.at
 * @since             1.0.0
 * @package           Private_Plugin_Repository
 *
 * @wordpress-plugin
 * Plugin Name:       Private Plugin Repository
 * Plugin URI:        http://gruetzmacher.at
 * Description:       Provides Updates for self-hosted plugins
 * Version:           1.0.4
 * Author:            Daniel Grützmacher
 * Author URI:        http://gruetzmacher.at
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       private-plugin-repository
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-private-plugin-repository-activator.php
 */
function activate_private_plugin_repository() {
  flush_rewrite_rules( true );
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-private-plugin-repository-deactivator.php
 */
function deactivate_private_plugin_repository() {
  flush_rewrite_rules( true );
}

register_activation_hook( __FILE__, 'activate_private_plugin_repository' );
register_deactivation_hook( __FILE__, 'deactivate_private_plugin_repository' );

/**
 * get autoupdate
 */
require_once( 'wp-autoupdate.php' );

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_private_plugin_repository() {

	$plugin = new Private_Plugin_Repository();
	$plugin->run();

}
run_private_plugin_repository();


/**
 * Main Plugin class
 */
class Private_Plugin_Repository {

  // Repository folder
  private $repo_url;
  private $repo_path;

  /**
   * Required hooks
   */
	public function run() {
		add_filter( 'rewrite_rules_array', array( $this, 'add_rewrite_rules' ) );
		add_action( 'parse_request', array( $this, 'sniff_requests' ), 0 );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
    add_action( 'init', array( $this, 'activate_auto_update' ) );

    $upload_dir = wp_upload_dir();
    $repo_dirname = $upload_dir['basedir'] . '/repository/';
    if( ! file_exists($repo_dirname)) wp_mkdir_p($repo_dirname);

    $this->repo_url = $upload_dir['baseurl'] . '/repository/';
    $this->repo_path = $repo_dirname;
	}

  /**
   * Hook to add rewrite rules to reach api
   */
	public function add_rewrite_rules( $rules ) {
	  $new_rules = array(
	    '^plugin_update/?(.+)?/?' => 'index.php?__plugin_update_api=1&plugin_name=$matches[1]',
	  );
	  $rules = $new_rules + $rules;
	  return $rules;
	}

  /**
   * Execute API handler when the query var is set
   */
	public function sniff_requests() {
	  global $wp;
	  if( isset( $wp->query_vars['__plugin_update_api'] ) ) {
	    $this->handle_api_request();
	    exit;
	  }
	}

  /**
   * Specify query vars
   */
	public function add_query_vars( $query_vars )
	{
	  $query_vars[] = '__plugin_update_api';
	  $query_vars[] = 'plugin_name';
	  return $query_vars;
	}

  /**
   * Handle API requests
   */
	private function handle_api_request() {
	  global $wp;
	  $plugin_name = sanitize_title($wp->query_vars['plugin_name']);

	  if ( ! $plugin_name ) {
      $response = new WP_Error( 'update_error', 'Please specify a plugin name.' );
	    $this->send_api_response( $response, 500 );
    }

    if ( isset( $_POST['action'] ) ) {

      $plugin = $this->get_plugin_by_slug( $plugin_name );

      if ( ! $plugin ) {
        $response = new WP_Error( 'update_error', 'Please specify a valid plugin name.' );
        $this->send_api_response( $response, 500 );
      }

      if ( ! isset( $_POST['license_user'] ) || $_POST['license_user'] != 'culinariusat' ) {
        $response = new WP_Error( 'license_error', 'Please specify a user name.' );
        $this->send_api_response( $response, 500 );
      }

      if ( ! isset( $_POST['license_key'] ) || $_POST['license_key'] != '93bpfs1a8vb5cel5ra9273sv0x4sd13c' ) {
        $response = new WP_Error( 'license_error', 'Please specify a valid license key.' );
        $this->send_api_response( $response, 500 );
      }

      switch ( $_POST['action'] ) {
        case 'version':
          $this->send_api_response( $plugin, 200 );
          break;
        case 'info':
          $this->send_api_response( $plugin, 200 );
          break;
      }
    } else {
      $response = new WP_Error( 'update_error', 'No action specified.' );
      $this->send_api_response( $response, 500 );
    }

	  die();
	}

  /**
   * Get plugin data
   * @param  string $slug Plugin slug
   * @return stdClass | bool       Either an object containing the Plugin data, or false if the plugin hasn't been found
   */
  private function get_plugin_by_slug( $slug ) {

    $plugin_path = $this->repo_path . $slug . '/' . $slug . '.php';

    if ( ! file_exists( $plugin_path ) )
      return false;

    $plugin_url = $this->repo_url . $slug . '/' . $slug . '.zip';

    $plugin_data = $this->get_plugin_data( $plugin_path );

    $obj                = new stdClass();
    $obj->slug          = 'plugin.php';
    $obj->plugin_name   = $plugin_data['Name'];
    $obj->new_version   = $plugin_data['Version'];
    $obj->requires      = '3.0';
    $obj->tested        = '3.3.1';
    $obj->downloaded    = 1;
    $obj->last_updated  = '2015-01-01';
    $obj->url           = $plugin_data["PluginURI"];
    $obj->package       = $plugin_url;

    return $obj;
  }

  /**
   * Format and output an API response
   * @param  string | array | object  $content Content to be output
   * @param  integer $status  HTTP Status code
   */
	private function send_api_response( $content, $status = 200 ) {
	  if ( $content ) {
	    $response = $content;
	    header('Content-Type: application/json');

	    switch ( $status ) {
	      case 500:
	        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Not all parameters provided', true, 500);
	        break;
	      case 404:
	        header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found');
	        break;
	      default:
	        break;
	    }

	    echo @serialize( $response )."\n";
	  } else {
	    echo @serialize( 'Empty response.' );
	  }
	  exit;
	}

  private function get_plugin_data( $plugin_file ) {

    $default_headers = array(
      'Name' => 'Plugin Name',
      'PluginURI' => 'Plugin URI',
      'Version' => 'Version',
      'Description' => 'Description',
      'Author' => 'Author',
      'AuthorURI' => 'Author URI',
      'TextDomain' => 'Text Domain',
      'DomainPath' => 'Domain Path',
      'Network' => 'Network',
      // Site Wide Only is deprecated in favor of Network.
      '_sitewide' => 'Site Wide Only',
    );

    $plugin_data = get_file_data( $plugin_file, $default_headers, 'plugin' );

    // Site Wide Only is the old header for Network
    if ( ! $plugin_data['Network'] && $plugin_data['_sitewide'] ) {
      /* translators: 1: Site Wide Only: true, 2: Network: true */
      _deprecated_argument( __FUNCTION__, '3.0', sprintf( __( 'The %1$s plugin header is deprecated. Use %2$s instead.' ), '<code>Site Wide Only: true</code>', '<code>Network: true</code>' ) );
      $plugin_data['Network'] = $plugin_data['_sitewide'];
    }
    $plugin_data['Network'] = ( 'true' == strtolower( $plugin_data['Network'] ) );
    unset( $plugin_data['_sitewide'] );

    // If no text domain is defined fall back to the plugin slug.
    if ( ! $plugin_data['TextDomain'] ) {
      $plugin_slug = dirname( plugin_basename( $plugin_file ) );
      if ( '.' !== $plugin_slug && false === strpos( $plugin_slug, '/' ) ) {
        $plugin_data['TextDomain'] = $plugin_slug;
      }
    }

    $plugin_data['Title']      = $plugin_data['Name'];
    $plugin_data['AuthorName'] = $plugin_data['Author'];

    return $plugin_data;
  }

  public function activate_auto_update() {
    // set auto-update params
    $plugin_current_version = '1.0.4';
    $plugin_remote_path     = '';
    $plugin_slug            = plugin_basename(__FILE__);
    $license_user           = '';
    $license_key            = '';

    // only perform Auto-Update call if a license_user and license_key is given
    if ( $license_user && $license_key && $plugin_remote_path )
    {
        new wp_autoupdate ($plugin_current_version, $plugin_remote_path, $plugin_slug, $license_user, $license_key);
    }
  }

}