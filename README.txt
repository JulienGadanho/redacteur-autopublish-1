=== Redacteur.site Autopublish ===
Contributors: DoudouMoii
Tags: seo, redaction
Requires at least: 4.7
Tested up to: 4.9.8
Requires PHP: 5.6
Stable tag: stable
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ce plugin vous permet de publier vos textes depuis la plateforme Redacteur.site

== Description ==

Ce plugin vous permet de publier vos textes depuis la plateforme Redacteur.site
Il utilise comme base le plugin de la plateforme Soumettre.fr

= Filtres =

* `rsjg_insert_post_args` permet de modifier les arguments de `wp_instert_post` avant la publication de l'article.

   **Exemple**

   ~~~
   function test_update_args( $args ) {

       $args['post_title'] = "[Publication auto] {$args['post_title']}";

       return $args;

   }
   add_filter( 'rsjg_insert_post_args', 'test_update_args', 10, 1 );
   ~~~

* `rsjg_exclude_random_users` permet d'exclure des utilisateurs de mode aléatoire.

  **Exemple**

  ~~~
  function test_exclude_rand_users( $users_id ) {

      $users_id[] = 5; // On ne veut pas que l'utilisateur id 5 ne soit dans la liste des publication
      return $users_id;

  }
  add_filter( 'rsjg_exclude_random_users', 'test_exclude_rand_users', 10, 1 );
  ~~~

* `rsjg_images_extensions` permet de modifier la liste des extensions autorisées pour les images.

  **Exemple**

  ~~~
  function test_add_raw_img( $extensions ) {

      $extensions[] = "raw"
      return $extensions;

  }
  add_filter( 'rsjg_images_extensions', 'test_add_raw_img', 10, 1 );
  ~~~


= Actions =

* `rsjg_post_created` permet d'effectuer une action à la publication effective de l'article. Avant l'import des images

  **Exemple**

  ~~~
  function send_mail_autopublish( $post_id ) {

      $post = get_post( $post_id );
      wp_mail( "john@example.com", "Publication d'un article", "Publication de l'article $post->post_title sur " . get_permalink( $post->ID ) );

  }
  add_action( 'rsjg_post_created', 'send_mail_autopublish', 10, 1 );
  ~~~

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/redacteur-autopublish` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Create your account on https://redacteur.site/
1. Go to your User Profile and get your API credentials
1. Use the Autopublish menu to sync with Redacteur.site

== Screenshots ==



== Changelog ==

= 0.6 =
* Ajout des auteurs à l'API

= 0.5 =
* Test update du clone du repo

= 0.4 =
* Ajout de la possibilité de mettre à jour un clone du repo

= 0.3 =
* Version stable mais avec les infos locales

= 0.2 =
* Ajout de la traduction