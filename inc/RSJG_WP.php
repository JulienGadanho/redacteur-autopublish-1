<?php

namespace RSJG;

use RSJG\Exceptions\PluginInactifException;

require_once( realpath( __DIR__ . '/../sdk-api-php/src/RSJGServices.php' ) );
require_once( realpath( __DIR__ . '/../sdk-api-php/src/RSJGApiClient.php' ) );
require_once( realpath( __DIR__ . '/../sdk-api-php/src/RSJGApi.php' ) );
require_once( realpath( __DIR__ . '/../sdk-api-php/src/Exceptions.php' ) );

/**
 * Class RSJGWP
 * @package RSJG
 */
class RSJGWP extends RSJGApiClient {
	
	/**
	 * @var string
	 */
	protected $mode = 'prod';
	
	/**
	 * @var array
	 */
	protected $available_services = array(
		'authors',
		'categories',
		'image_size',
		'post',
	);
	
	/**
	 * @var
	 */
	protected $author;
	/**
	 * @var
	 */
	protected $service;
	/**
	 * @var
	 */
	protected $params;
	
	
	/**
	 * RSJGWP constructor.
	 */
	function __construct() {
		parent::__construct();
		
		
		$this->endpoint = 'http://www.redacteur.site/api-v2/';
		
		$this->wp_set_credentials();
		
		try {
			$this->is_plugin_active();
			$this->check_request();
		} catch( \Exception $e ) {
			echo json_encode( array( 'error' => get_class( $e ), 'message' => $e->getMessage() ) );
		}
	}
	
	/**
	 * Charge les credentials depuis la base de données ou les constantes (utilisé par Wordpress)
	 *
	 * @param string $prefix Préfixe SQL pour les options
	 */
	public function wp_set_credentials( $prefix = 'rsjg_' ) {
		$this->email      = ( defined( 'RSJG_EMAIL' ) ) ? RSJG_EMAIL : get_option( $prefix . 'email' );
		$this->api_key    = ( defined( 'RSJG_API_KEY' ) ) ? RSJG_API_KEY : get_option( $prefix . 'api_key' );
		$this->api_secret = ( defined( 'RSJG_API_SECRET' ) ) ? RSJG_API_SECRET : get_option( $prefix . 'api_secret' );
		$this->author     = get_option( $prefix . 'author' );
	}
	
	/**
	 * @return bool
	 * @throws PluginInactifException
	 */
	public function is_plugin_active() {
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
		
		foreach ( $active_plugins as $active_plugin ) {
			if ( strpos( $active_plugin, 'redacteur-autopublish' ) !== false ) {
				return true;
			}
		}
		
		throw new PluginInactifException();
	}
	
	/**
	 * Vérifie une requete
	 * @return bool
	 * @throws \Exception
	 */
	protected function check_request() {
		if ( ! isset( $_GET['method'] ) ) {
			return false;
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
		
		return true;
	}
	
	/**
	 * Ajout d'un site sur la plateforme
	 *
	 * @param null $url
	 * @param null $cms
	 * @param null $endpoint
	 *
	 * @return array|mixed|object
	 */
	public function site_add( $url = null, $cms = null, $endpoint = null ) {
		$params = array(
			'url' => $url,
			'cms' => $cms
		);
		
		if ( $endpoint ) {
			$params['endpoint'] = $endpoint;
		}
		
		$res = $this->request( 'site/register/', $params );
		
		return $res;
	}
	
	/**
	 * Execute une requete
	 *
	 * @param string $service
	 * @param array $post_params
	 *
	 * @return array|mixed|object
	 */
	public function request( $service, $post_params = array() ) {

		$endpoint    = $this->endpoint . $service;
		$post_params = $this->sign( $service, $post_params );
		
		$res = wp_remote_post( $endpoint, array( 'body' => $post_params ) );
		if ( is_wp_error( $res ) ) {
			return array( 'status' => 'error', 'message' => $res->get_error_message() );
		}
		
		return json_decode( $res['body'] );
	}
	
	/**
	 * Envoie la liste des catégories
	 */
	public function categories() {
		$ret        = array();
		$categories = get_categories( array(
			'taxonomy'   => 'category',
			'orderby'    => 'name',
			'hide_empty' => false,
		) );
		
		foreach ( $categories as $category ) {
			$ret[] = array( 'id' => $category->term_id, 'text' => $category->name, 'parent' => $category->parent );
		}
		echo json_encode( $ret );
	}
	
	/**
	 * Envoie la liste des auteurs
	 */
	public function authors() {
		$ret = [];
		
		$users = get_users();
		$exclude_users = apply_filters( 'rsjg_exclude_random_users', array() );
		
		foreach ( $users as $user ) {
			if ( $user->has_cap( 'publish_posts' ) && ! in_array( $user->ID, $exclude_users ) ) {
				$ret[] = [
					'id' => $user->ID,
					'user_login'    => $user->user_login,
					'user_nicename' => $user->user_nicename,
				];
			}
		}
		
		echo json_encode($ret);
	}
	
	/**
	 * Envoie les différentes tailles d'image
	 */
	public function image_size() {
		global $_wp_additional_image_sizes;
		
		$sizes = array();
		
		foreach ( get_intermediate_image_sizes() as $_size ) {
			if ( in_array( $_size, array('thumbnail', 'medium', 'medium_large', 'large') ) ) {
				$sizes[ $_size ]['width']  = get_option( "{$_size}_size_w" );
				$sizes[ $_size ]['height'] = get_option( "{$_size}_size_h" );
				$sizes[ $_size ]['crop']   = (bool) get_option( "{$_size}_crop" );
			} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
				$sizes[ $_size ] = array(
					'width'  => $_wp_additional_image_sizes[ $_size ]['width'],
					'height' => $_wp_additional_image_sizes[ $_size ]['height'],
					'crop'   => $_wp_additional_image_sizes[ $_size ]['crop'],
				);
			}
		}
		
		echo json_encode($sizes);
	}
	
	
	/**
	 * Créé un post
	 *
	 * @param $params
	 */
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
		
		if ( '-1' == $post_arr['post_author'] ) {
			$rand_user               = $this->get_random_user();
			$post_arr['post_author'] = $rand_user->ID;
		}
		
		$post_arr = $this->transfrom_img_src( $post_arr );
		
		/**
		 * Permet de modifier les paramètres
		 */
		$post_arr = apply_filters( 'rsjg_insert_post_args', $post_arr );
		
		$post_ID = wp_insert_post( $post_arr );
		
		if ( ! is_wp_error( $post_ID ) ) {
			
			add_post_meta( $post_ID, '_rsjg_task_id', $params['task_id'], true );
			
			do_action( 'rsjg_post_created', $post_ID );
			
			$this->import_html_images( $post_ID );
			
			if ( isset( $params['image'] ) && '' !== $params['image'] ) {
				$this->set_featured_image( $params['image'], $post_ID );
			}
			
			echo json_encode( array( 'status' => 'ok', 'id' => $post_ID, 'url' => get_permalink( $post_ID ) ) );
		} else {
			echo json_encode( array( 'status' => 'error', 'post_arr' => $post_arr ) );
		}
	}
	
	/**
	 * Retourne un utilisateur aléatoire
	 *
	 * @return \WP_User
	 */
	protected function get_random_user() {
		$all_users      = get_users();
		$specific_users = array();
		
		$exclude_users = apply_filters( 'rsjg_exclude_random_users', array() );
		
		foreach ( $all_users as $user ) {
			
			if ( $user->has_cap( 'publish_posts' ) && ! in_array( $user->ID, $exclude_users ) ) {
				$specific_users[] = $user;
			}
		}
		
		return $specific_users[ array_rand( $specific_users ) ];
	}
	
	/**
	 * Transforme les URL d'images vers des balises <img>
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	protected function transfrom_img_src( $args ) {
		$extensions_to_import = apply_filters( 'rsjg_images_extensions', [
			'jpg',
			'png',
			'gif',
			'jpeg',
			'webp',
		] );
		
		$patten_image = '#[^\'"](https?://.*[\.](?:' . implode( '|', $extensions_to_import ) . '))[^\'"]#i';
		preg_match_all( $patten_image, $args['post_content'], $matches, PREG_SET_ORDER );
		$image_format_html = "<img class=\"aligncenter\" src=\"%s\">";
		
		foreach ( $matches as $image ) {
			$img_html             = sprintf( $image_format_html, $image[1] );
			$args['post_content'] = str_replace( $image[0], $img_html, $args['post_content'] );
		}
		
		return $args;
	}
	
	/**
	 * Import d'image en HTML
	 *
	 * @param int $post_id
	 */
	protected function import_html_images( $post_id ) {
		
		$post = get_post( $post_id );
		if ( is_wp_error( $post ) || is_null( $post ) ) {
			return;
		}
		
		$content = $post->post_content;
		$count   = preg_match_all( '#<img.*?src=[\'"](.*?)[\'"].*?>#i', $content, $matches, PREG_SET_ORDER );
		if ( false === $count || 0 === $count ) {
			return;
		}
		
		foreach ( $matches as $image ) {
			// SRC de l'image = $image[1]
			$attachment_id = $this->import_image( $image[1], $post_id );
			if ( $attachment_id > 0 ) {
				$new_img_url = wp_get_attachment_url( $attachment_id );
				$content     = str_replace( $image[1], $new_img_url, $content );
			}
		}
		wp_update_post( [
			'ID'           => $post_id,
			'post_content' => $content
		] );
		
		return;
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
		
		$id = media_handle_sideload( $file_array, $post_id, '' );
		
		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			
			return $id;
		}
		
		return $id;
	}
	
	/**
	 * Ajout d'une image à la une
	 *
	 * @param $image_url
	 * @param $post_id
	 */
	protected function set_featured_image( $image_url, $post_id ) {
		$id_attachment = $this->import_image( $image_url, $post_id );
		if ( ! is_wp_error( $id_attachment ) ) {
			set_post_thumbnail( $post_id, $id_attachment );
		}
	}
	
	/**
	 * Récupère un post à l'aide de sa meta_key _rsjg_task_id
	 *
	 * @param $task_ID
	 *
	 * @return bool|\WP_Post
	 */
	protected function get_post_by_task_id( $task_ID ) {
		$loop = new \WP_Query( array(
			'ignore_sticky_posts' => 1,
			'meta_key'            => '_rsjg_task_id',
			'meta_value'          => $task_ID
		) );
		if ( ! $loop->have_posts() ) {
			return false;
		}
		
		$loop->the_post();
		
		global $post;
		
		return $post;
	}
}
