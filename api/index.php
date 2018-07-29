<?php
namespace RSJG;


$basePath = dirname(dirname( $_SERVER['SCRIPT_FILENAME'] ));

require_once($basePath.'/sdk-api-php/src/RSJGServices.php');
require_once($basePath.'/sdk-api-php/src/RSJGApiClient.php');
require_once($basePath.'/sdk-api-php/src/RSJGApi.php');
require_once($basePath.'/sdk-api-php/src/Exceptions.php');
require_once($basePath.'/inc/RSJG_WP.php');

$bootstrap = 'wp-load.php';
while( !is_file( $bootstrap ) ) {
    if( is_dir( '..' ) )
        chdir( '..' );
    else
        die( 'EN: Could not find WordPress! FR : Impossible de trouver WordPress !' );
}

require_once( $bootstrap );

$api = new RSJGWP();

