<?php

namespace RSJG;

use RSJG\Exceptions\PluginInactifException;

require_once( realpath( __DIR__ . '/../sdk-api-php/src/RSJGServices.php' ) );
require_once( realpath( __DIR__ . '/../sdk-api-php/src/RSJGApiClient.php' ) );
require_once( realpath( __DIR__ . '/../sdk-api-php/src/RSJGApi.php' ) );
require_once( realpath( __DIR__ . '/../sdk-api-php/src/Exceptions.php' ) );

class RSJGWP extends RSJGApiClient {
	protected $mode = 'prod';
	protected $available_services = array(
		'check_api',
		'check_added',
		'categories',
		'post',
		'delete',
		'fetch',
		'update'
	);
	
	function __construct() {
		parent::__construct();
		
		//        $this->endpoint = 'http://redacteur.site/api/';
		$this->endpoint = 'http://redacteur.local/api/';
		
		$this->wp_set_credentials();
		
		try {
			$this->is_plugin_active();
			$this->check_request();
		} catch( \Exception $e ) {
			echo json_encode( array( 'error' => get_class( $e ), 'message' => $e->getMessage() ) );
		}
	}
	
	/**
	 * Charge les credentials depuis la base de données (utilisé par Wordpress)
	 *
	 * @param string $prefix Préfixe SQL pour les options
	 */
	public function wp_set_credentials( $prefix = 'rsjg_' ) {
		$this->email      = get_option( $prefix . 'email' );
		$this->api_key    = get_option( $prefix . 'api_key' );
		$this->api_secret = get_option( $prefix . 'api_secret' );
		$this->author     = get_option( $prefix . 'author' );
	}
	
	public function is_plugin_active() {
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		
		foreach ( $active_plugins as $active_plugin ) {
			if ( strpos( $active_plugin, 'redacteur-autopublish' ) !== false ) {
				return true;
			}
		}
		
		throw new PluginInactifException();
	}
	
	protected function check_request() {
		if ( ! isset( $_GET['method'] ) ) {
			return true;
		}
		
		$service = $_GET['method'];
		$params  = ( $this->mode == 'test' ) ? $_GET : $_POST;
		
		if ( ! in_array( $service, $this->available_services ) ) {
			throw new \Exception( "Service inconnu" );
		}
		
		$this->service = $service;
		$this->params  = $params;
		
		$this->check_signature( $service, $params );
		
		$this->$service( $params );
	}
	
	public function site_add( $url = null, $cms = null, $endpoint = null ) {
		$params = array(
			'url' => $url,
			'cms' => $cms
		);
		
		if ( $endpoint ) {
			$params['endpoint'] = $endpoint;
		}
		
		$res = $this->request( 'sites', $params );
		
		return $res;
	}
	
	public function request( $service, $post_params = array() ) {
		$endpoint    = $this->endpoint . $service;
		$post_params = $this->sign( $service, $post_params );
		
		$res = wp_remote_post( $endpoint, array( 'body' => $post_params ) );
		if ( is_wp_error( $res ) ) {
			return array( 'status' => 'error', 'message' => $res->get_error_message() );
		}
		
		return json_decode( $res['body'] );
	}
	
	public function check_added( $params ) {
		$args = array(
			'post__not_in'        => get_option( "sticky_posts" ),
			'ignore_sticky_posts' => 1,
			'meta_query'          => array(
				array(
					'key'     => get_option( 'rsjg_url_field', true ),
					'value'   => $params['url'],
					'compare' => '=',
				)
			)
		);
		
		$meta_query = new WP_Query( $args );
		if ( $meta_query->have_posts() ) {
			while( $meta_query->have_posts() ) {
				$meta_query->the_post();
				echo json_encode( array( 'status' => 'found', 'url' => get_permalink() ) );
				die();
			}
		} else {
			echo json_encode( array( 'status' => 'not_found' ) );
			die();
		}
	}
	
	public function categories( $params ) {
		$ret        = array();
		$categories = get_categories( array(
			'taxonomy'   => 'category',
			'orderby'    => 'name',
			'hide_empty' => false,
		) );
		
		foreach ( $categories as $category ) {
			//            $parent = $category->parent != 0 ? $category->parent : '#';
			$ret[] = array( 'id' => $category->term_id, 'text' => $category->name, 'parent' => $category->parent );
		}
		echo json_encode( $ret );
	}
	
	public function test() {
		$res = $this->request( 'test' );
		
		echo json_encode( $res );
		die();
	}
	
	public function post( $params ) {
		
		if ( isset( $params['draft'] ) ) {
			$post_status = 'draft';
		} else {
			$post_status = 'publish';
		}
		
		$post_content = $params['content'];
		
		
		$post_arr = array(
			'post_status'   => $post_status,
			'post_title'    => $params['title'],
			'post_content'  => $post_content,
			'post_category' => array( $params['category'] ),
			'post_author'   => get_option( 'rsjg_author' ),
		);
		
		/**
		 * Permet de modifier les paramètres
		 */
		$post_arr = apply_filters( 'rsjg_instert_post_args', $post_arr );
		
		$post_ID = wp_insert_post( $post_arr );
		
		if ( ! is_wp_error( $post_ID ) ) {
			
			add_post_meta( $post_ID, '_rsjg_task_id', $params['task_id'], true );
			
			if ( isset( $params['image'] ) ) {
				$this->set_featured_image( $params['image'], $post_ID );
			}
			
			echo json_encode( array( 'status' => 'ok', 'id' => $post_ID, 'url' => get_permalink( $post_ID ) ) );
		} else {
			echo json_encode( array( 'status' => 'error', 'post_arr' => $post_arr ) );
		}
	}
	
	protected function set_featured_image( $image_url, $post_id ) {
		$id_attachment = $this->import_image( $image_url, $post_id );
		if ( ! is_wp_error( $id_attachment ) ) {
			set_post_thumbnail( $post_id, $id_attachment );
		}
	}
	
	/**
	 * Import de l'image dans WordPress
	 *
	 * @see https://wabeo.fr/migration-donnees-wordpress/
	 * @author Willy Bahuaud - Wabeo
	 *
	 * @param $url
	 * @param $post_id
	 *
	 * @return int|object
	 */
	protected function import_image( $url, $post_id ) {
		// Importer les fonctions d’admin
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
			require_once( ABSPATH . 'wp-admin/includes/media.php' );
		}
		$tmp        = download_url( $url );
		$file_array = array();
		
		preg_match( '/[^\?]+\.(jpg|jpe|jpeg|gif|png|pdf)/i', $url, $matches );
		$file_array['name']     = basename( $matches[0] );
		$file_array['tmp_name'] = $tmp;
		
		if ( is_wp_error( $tmp ) ) {
			@unlink( $file_array['tmp_name'] );
			$file_array['tmp_name'] = '';
		}
		
		$id = media_handle_sideload( $file_array, $post_id );
		
		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			
			return $id;
		}
		
		return $id;
	}
	
	public function fetch( $params ) {
		header( 'Content-Type: application/json' );
		
		$task_ID = $params['task_id'];
		$url     = $params['url'];
		$post    = false;
		
		if ( ! is_numeric( $task_ID ) && strlen( $url ) < 5 ) {
			echo json_encode( array( 'status' => 'error', 'message' => 'wrong parameters' ) );
			
			return false;
		}
		
		// get post by task_id
		if ( is_numeric( $task_ID ) ) {
			$post = $this->get_post_by_task_id( $task_ID );
		}
		if ( ! $post ) {
			$post = $this->get_post_by_url( $url );
		}
		
		if ( ! $post ) {
			echo json_encode( array( 'status' => 'error', 'message' => 'no posts' ) );
			
			return false;
		}
		
		echo json_encode( $post );
		die();
	}
	
	private function get_post_by_task_id( $task_ID ) {
		$loop = new \WP_Query( array(
			'ignore_sticky_posts' => 1,
			'meta_key'            => '_soumettre_task_id',
			'meta_value'          => $task_ID
		) );
		if ( ! $loop->have_posts() ) {
			return false;
		}
		
		$loop->the_post();
		
		global $post;
		
		return $post;
	}
	
	private function get_post_by_url( $url ) {
		$post_id = url_to_postid( $url );
		$post    = get_post( $post_id );
		
		return $post;
	}
	
	public function update( $params ) {
		header( 'Content-Type: application/json' );
		
		$task_ID = $params['task_id'];
		$url     = $params['url'];
		$post    = false;
		
		if ( ! is_numeric( $task_ID ) && strlen( $url ) < 5 ) {
			echo json_encode( array( 'status' => 'error', 'message' => 'wrong parameters' ) );
			
			return false;
		}
		
		// get post by task_id
		if ( is_numeric( $task_ID ) ) {
			$post = $this->get_post_by_task_id( $task_ID );
		}
		if ( ! $post ) {
			$post = $this->get_post_by_url( $url );
		}
		
		$post->post_title   = stripslashes( $params['title'] );
		$post->post_content = stripslashes( $params['editedContent'] );
		
		if ( wp_update_post( $post ) ) {
			echo json_encode( array( 'status' => 'ok' ) );
			die();
		}
		
		echo json_encode( array( 'status' => 'error', 'message' => 'no_update' ) );
		die();
	}
	
	protected function set_directorypress_image_fields( $image_url, $post_id ) {
		$upload_dir = wp_upload_dir();
		$image_data = file_get_contents( $image_url );
		$filename   = basename( $image_url );
		if ( wp_mkdir_p( $upload_dir['path'] ) ) {
			$file = $upload_dir['path'] . '/' . $filename;
		} else {
			$file = $upload_dir['basedir'] . '/' . $filename;
		}
		file_put_contents( $file, $image_data );
		
		if ( wp_mkdir_p( $upload_dir['path'] ) ) {
			$image_url = get_home_url() . $upload_dir['path'] . '/' . $filename;
		} else {
			$image_url = get_home_url() . $upload_dir['basedir'] . '/' . $filename;
		}
		
		add_post_meta( $post_id, 'image', $image_url, true );
	}
	
	protected function check_api() {
		$default_headers = array(
			'Name'        => 'Plugin Name',
			'PluginURI'   => 'Plugin URI',
			'Version'     => 'Version',
			'Description' => 'Description',
			'Author'      => 'Author',
			'AuthorURI'   => 'Author URI',
			'TextDomain'  => 'Text Domain',
			'DomainPath'  => 'Domain Path',
			'Network'     => 'Network',
			'_sitewide'   => 'Site Wide Only',
		);
		
		$plugin_data = get_file_data( dirname( __FILE__ ) . '/../redacteur-autopublish.php', $default_headers, 'plugin' );
		
		header( 'Content-Type: application/json' );
		echo json_encode( array( 'status' => 'ok', 'version' => $plugin_data['Version'] ) );
	}
}
