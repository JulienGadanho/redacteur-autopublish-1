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

$plateform_link = sprintf( '<a href="http://redacteur.site" target="_blank" rel="noreferrer noopener">%1$s</a>', esc_html_x( 'Redacteur.site', 'Plateform name', 'redacteur-autopublish' ) );

$profile_link = sprintf( '<a href="http://redacteur.site/api/" target="_blank" rel="noreferrer noopener">%1$s</a>', esc_html__( 'Get my API credentials', 'redacteur-autopublish' ) );


?>
<div class="wrap">
	
	<h1><?php esc_html_e( 'Redacteur.site Autopublish', 'redacteur-autopublish' ); ?></h1>
	
	<div class="notice notice-info">
		<p>
		<?php
		printf( esc_html_x( 'This plugin allow to add your website on %s to autopublish writers text.', 'Plateform link', 'redacteur-autopublish' ), $plateform_link );
		?>
		</p>
	</div>
	
	<div class="notice notice-success">
		<p><?php printf( esc_html_x( 'You must create an account on %s', 'Plateform link', 'redacteur-autopublish' ), $plateform_link ); ?></p>
		
		
		<p><?php printf( esc_html_x( 'You can create your API credentials here: %s', 'profile link', 'redacteur-autopublish' ), $profile_link ); ?></p>
	</div>
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><?php echo esc_html_x( 'Email', 'Form label', 'redacteur-autopublish' ); ?></th>
			<td>
				<input type="text" title="<?php esc_html_e( 'Your email', 'redacteur-autopublish' ); ?>" aria-labelledby="label_email" id="rsjg_email" name="email" value="<?= esc_attr( $email ); ?>" <?php if ( defined( 'RSJG_EMAIL' ) ) echo 'disabled'; ?> class="regular-text ltr"/>
				<p class="description" id="label_email"><?php printf( esc_html_x( 'Enter your email address used on %s', 'Plateform link', 'redacteur-autopublish' ), $plateform_link ); ?></p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php echo esc_html_x( 'API Key', 'Form label', 'redacteur-autopublish' ); ?></th>
			<td>
				<input type="text" title="<?php esc_attr_e( 'Your API key', 'redacteur-autopublish' ) ?>" id="rsjg_api_key" name="api_key" value="<?= esc_attr( $api_key ); ?>" <?php if ( defined( 'RSJG_API_KEY' ) ) echo 'disabled'; ?> class="regular-text ltr"/>
			</td>
			<td></td>
		</tr>
		<tr valign="top">
			<th scope="row"><?php echo esc_html_x( 'API Secret', 'Form label', 'redacteur-autopublish' ); ?></th>
			<td>
				<input type="text" title="<?php esc_attr_e( 'Your API secret', 'redacteur-autopublish' ) ?>" id="rsjg_api_secret" name="api_secret" value="<?= esc_attr( $api_secret ); ?>" <?php if ( defined( 'RSJG_API_SECRET' ) ) 	echo 'disabled'; ?> class="regular-text ltr"/>
			</td>
		</tr>
		
		<tr valign="top">
			<th scope="row">
		  <?php echo esc_html_x( 'Author', 'Form label', 'redacteur-autopublish' ); ?>
			</th>
			<td>
		  <?php wp_dropdown_users( array(
			  'show_option_none' => esc_attr__( 'Random user', 'redacteur-autopublish' ),
			  'name'             => 'author',
			  'selected'         => get_option( 'rsjg_author' )
		  ) ); ?>
				<p class="description"><?php esc_html_e( 'Which author to associate with published posts', 'redacteur-autopublish' ); ?></p>
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
				<p class="description"><?php printf( esc_html_x( 'This button saves your updates and records your site on %s', 'Plateform link', 'redacteur-autopublish' ), $plateform_link ) ?></p>
			</td>
			<td><span id="test_api_res"></span></td>
		</tr>
	</table>
</div>