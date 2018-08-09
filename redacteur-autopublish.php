<?php
/**
 * Plugin Name:       Redacteur.site Autopublish
 * Description:       Autopublish from http://www.redacteur.site/
 * Version:           0.6
 * Author:            Etienne Mommalier
 * Author URI:        https://etienne-mommalier.fr/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       redacteur-autopublish
 * Domain Path:        /lang
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) || ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'RSJG_API_URL', plugin_dir_url( __FILE__ ) . 'api/' );

function load_text_domain() {
	$plugin_dir = basename( dirname( __FILE__ ) ) . '/lang/';
	load_plugin_textdomain( 'redacteur-autopublish', false, $plugin_dir );
}
add_action( 'plugins_loaded', 'load_text_domain' );

if ( is_admin() ) {
	add_action( 'init', 'call_rsgj_Admin' );
}

function call_rsgj_Admin() {
	new RSJG_Admin();
}

class RSJG_Admin {
	protected $endpoint = '';
	protected $prefix = 'rsjg_';
	
	protected $plugin_url;
	
	public function __construct() {
		
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		
		// ajax
		add_action( 'admin_footer', array( $this, 'ajax' ) );
		add_action( 'wp_ajax_rsjg_save_options', array( $this, 'ajax_save_options' ) );
	  
	  /**
	   * Mise à jour auto du plugin
	   */
	  require plugin_dir_path( __FILE__ ) . 'lib/plugin-update-checker/plugin-update-checker.php';
	  $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
		  'https://github.com/DoudouMoii/redacteur-autopublish/',
		  __FILE__,
		  'redacteur-autopublish'
	  );
	  
	  $myUpdateChecker->getVcsApi()->enableReleaseAssets();
	  
	  //Optional: Set the branch that contains the stable release.
	  $myUpdateChecker->setBranch('master');
		
	}
	
	public function ajax_save_options() {
		// sanitize and save options to database
		
		$api_key = $_POST['api_key'];
		if ( strlen( $api_key ) > 42 ) {
			$api_key = substr( $api_key, 0, 42 );
		}
		update_option( $this->prefix . 'api_key', $api_key );
		
		$api_secret = $_POST['api_secret'];
		if ( strlen( $api_secret ) > 42 ) {
			$api_secret = substr( $api_secret, 0, 42 );
		}
		
		$author_id = $_POST['author'];
		if ( ! is_numeric( $author_id ) ) {
			$author_id = 1;
		}
		
		update_option( $this->prefix . 'api_secret', $api_secret );
		update_option( $this->prefix . 'email', sanitize_email( $_POST['email'] ) );
		update_option( $this->prefix . 'author', $author_id );
		
		// register website on redacteur.fr
		require_once( 'inc/RSJG_WP.php' );
		
		$api = new RSJG\RSJGWP();
		$res = $api->site_add( get_home_url(), 'WordPress', plugin_dir_url( __FILE__ ) . 'api/index.php' );
		
		echo json_encode( $res );
		die();
	}
	
	public function admin_menu() {
		add_menu_page( __( 'Autopulish Redacteur.site', 'redacteur-autopublish' ), _x( 'Autopublish', 'Menu name', 'redacteur-autopublish' ), 'manage_options', 'rsjg-autopublish', array(
				$this,
				'admin_page'
			), 'dashicons-shield-alt'        // TODO: Update Menu Icon with dashicon or custom image - https://developer.wordpress.org/resource/dashicons/
		);
	}
	
	public function admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		
		wp_enqueue_script( 'jquery-form' );
		
		include( 'inc/admin_options.php' );
	}
	
	public function ajax() { ?>
			<script type="text/javascript">
				jQuery(document).ready(function ($) {

					jQuery('#rsjg_save_changes').click(function (event) {
						event.preventDefault();

						let data = {
							action: 'rsjg_save_options',
							email: jQuery('#rsjg_email').val(),
							api_key: jQuery('#rsjg_api_key').val(),
							api_secret: jQuery('#rsjg_api_secret').val(),
							author: jQuery('select[name="author"] option:selected').val()
						};

						jQuery.post(ajaxurl, data, function (response) {
							if (response == null) {
								$('#test_api_res').html('<span class="dashicons dashicons-no"></span> Une Erreur inconnue s\'est produite (response). :(');
								return;
							}

							json = jQuery.parseJSON(response);

							if (json == null) {
								$('#test_api_res').html('<span class="dashicons dashicons-no"></span> Une Erreur inconnue s\'est produite. :(');
								return;
							}

							if (json.status === 'OK') {
								$('#test_api_res').html('<span class="dashicons dashicons-yes"></span>' + json.message);
							} else {
								$('#test_api_res').html('<span class="dashicons dashicons-no"></span>' + json.message);
							}
						});
						return false;
					});
				});
			</script> <?php
	}
}


