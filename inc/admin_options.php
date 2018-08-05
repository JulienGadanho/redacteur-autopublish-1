<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$api_key    = ( defined( 'RSJG_API_KEY' ) ) ? RSJG_API_KEY : get_option( $this->prefix . 'api_key' );
$api_secret = ( defined( 'RSJG_API_SECRET' ) ) ? RSJG_API_SECRET : get_option( $this->prefix . 'api_secret' );
$email      = ( defined( 'RSJG_EMAIL' ) ) ? RSJG_EMAIL : get_option( $this->prefix . 'email' );
$plugin_url = get_option( $this->prefix . 'plugin_url' );

if ( ! $plugin_url ) {
	$plugin_url = $this->plugin_url;
}

$plateform_link = sprintf(
	'<a href="http://redacteur.site" target="_blank" rel="noreferrer noopener">%1$s</a>',
	_x( 'Redacteur.site', 'Plateform name', 'redacteur-autopublish' )
);


?>
<div class="wrap">
	
	<h1><?php _e( 'Redacteur.site Autopublish', 'redacteur-autopublish' ); ?></h1>
	
	<div class="notice notice-info">
		<p>
		<?php
		printf( __( 'This plugin allow to add your website on %s to autopublish writers text.', 'redacteur-autopublish' ), $plateform_link );
		?>
		</p>
		<p><?php _e( 'Une fois votre plugin configuré, votre site sera ajouté à notre liste de Sites Partenaires. Afin de pouvoir commencer à recevoir des articles rémunérés, vous devrez passer sur la plate-forme pour renseigner les thématiques de votre site.', 'redacteur-autopublish' ); ?></p>
	</div>
	
	<div class="notice notice-success">
		<p><?php printf( __( 'Si vous n\'avez pas encore de compte sur %s, vous devez en créer un.', 'redacteur-autopublish' ), $plateform_link ); ?></p>
		
		<p>
			Vos identifiants API sont disponibles ici : <a target="_blank" href="https://soumettre.fr/user/profile">Récupérer
				mes identifiants API</a>.
		</p>
	</div>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row">Email</th>
			<td>
				<input type="text" id="rsjg_email" name="email" value="<?= esc_attr( $email ); ?>" <?php if ( defined( 'RSJG_EMAIL' ) ) {
			echo 'disabled';
		} ?>
							 class="regular-text ltr"/>
				<p class="description">
					Entrez l'adresse email qui correspond à votre compte utilisateur sur
					<a target="_blank" href="https://soumettre.fr/">Soumettre.fr</a>.
				</p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">API Key</th>
			<td>
				<input type="text" id="rsjg_api_key" name="api_key" value="<?= esc_attr( $api_key ); ?>" <?php if ( defined( 'RSJG_API_KEY' ) ) {
			echo 'disabled';
		} ?>
							 class="regular-text ltr"/>
			</td>
			<td></td>
		</tr>
		<tr valign="top">
			<th scope="row">API Secret</th>
			<td>
				<input type="text" id="rsjg_api_secret" name="api_secret" value="<?= esc_attr( $api_secret ); ?>" <?php if ( defined( 'RSJG_API_SECRET' ) ) {
			echo 'disabled';
		} ?>
							 class="regular-text ltr"/>
				<p class="description">
					Vos identifiants API sont disponibles
					<a target="_blank" href="https://soumettre.fr/user/profile">
						sur votre page de profil</a>.
				</p>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">
				Auteur
			</th>
			<td>
		  <?php wp_dropdown_users( array(
			  'show_option_none' => __( 'Random user', 'rsjg-autopublish' ),
			  'name'             => 'author',
			  'selected'         => get_option( 'rsjg_author' )
		  ) ); ?>
				<p class="description">Quel Auteur associer aux posts de Soumettre ?</p>
			</td>
		</tr>
	</table>
	
	<table>
		<tr>
			<td>
				<p class="submit">
					<button id="rsjg_save_changes" class="button-primary"
									value="<?php esc_attr_e( 'Save Changes' ) ?>">
			  <?php esc_attr_e( 'Save Changes' ) ?>
					</button>
				</p>
				<p class="description">
					Ce bouton sauvegarde vos changements et enregistre votre site sur
					<a target="_blank" href="https://soumettre.fr/">Soumettre.fr</a>,
					afin que celui-ci puisse commencer à recevoir des publications rémunérées.
				</p>
			</td>
			<td><span id="test_api_res"></span></td>
		</tr>
	</table>
</div>