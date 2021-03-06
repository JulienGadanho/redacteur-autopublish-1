<?php namespace RSJG;

use RSJG\Exceptions\SignatureInvalideException;

class RSJGApiClient {
	protected $endpoint = 'http://redacteur.site/api-v2/';
	protected $email;
	protected $api_key;
	protected $api_secret;
	
	/**
	 * Si les paramètres ne sont pas fournis au constructeur, vous DEVEZ utiliser une fonction  *_load_credentials
	 *
	 * @param string $email
	 * @param string $api_key
	 * @param string $api_secret
	 */
	function __construct( $email = null, $api_key = null, $api_secret = null ) {
		$this->email      = $email;
		$this->api_key    = $api_key;
		$this->api_secret = $api_secret;
	}
	
	/**
	 * Change le endpoint de l'API
	 *
	 * @param string $url URL where to find API
	 */
	public function set_endpoint( $url ) {
		$this->endpoint = $url;
	}
	
	/**
	 * Teste la connexion à l'API via une requête signée
	 */
	public function test() {
		$res = $this->request( 'test' );
		echo $res['data'];
		die();
	}
	
	/**
	 * Envoie une requête signée
	 *
	 * @param string $service
	 * @param array $post_params
	 *
	 * @return object Réponse en JSON
	 */
	public function request( $service, $post_params = array() ) {
		
		$endpoint    = $this->endpoint . $service;
		$post_params = $this->sign( $service, $post_params );
		
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_URL, $endpoint );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_params );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_USERAGENT, 'RSJGApi' );
		
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 30 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
		
		$output = curl_exec( $ch );
		curl_close( $ch );
		
		return $output;
	}
	
	/**
	 * Ajoute les paramètres de signature à une requête (utilisée par $this->request)
	 *
	 * @param string $endpoint
	 * @param array $post_params
	 *
	 * @return array
	 */
	protected function sign( $endpoint, $post_params ) {
		$time = time();
		
		$signature = md5( sprintf( "%s-%s-%d-%s", $this->api_key, $this->api_secret, $time, $endpoint ) );
		
		$post_params['user']    = $this->email;
		$post_params['api_key'] = $this->api_key;
		$post_params['time']    = $time;
		$post_params['sign']    = $signature;
		
		return $post_params;
	}
	
	/**
	 * Enregistre votre site dans la base de Soumettre.fr. pour Wordpress, passer get_home_url() en paramètre
	 *
	 * @param string $url URL de la homepage du site.
	 */
	public function site_add( $url ) 
	{
		$res = $this->request( 'site/register/', array( 'site' => $url ) );
		echo $res['data'];
		die();
	}
	
	/**
	 * Lors du traitement d'une requête, vérifie la signature fournie
	 *
	 * @param string $endpoint
	 * @param array $params
	 *
	 * @return bool
	 * @throws \Exception Signature invalide
	 */
	public function check_signature( $endpoint, $params ) {
		$signature = $params['sign'];
		$time      = $params['time'];
		
		$check = md5( sprintf( "%s-%s-%d-%s", $this->api_key, $this->api_secret, $time, $endpoint ) );
		
		if ( $signature != $check ) {
			throw new SignatureInvalideException( $endpoint );
		}
		
		return true;
	}
	
	protected function response( $array ) {
		header( 'Content-Type: application/json' );
		echo json_encode( $array );
		die();
	}
}