<?php

namespace OGalerie;

use OGalerie\Models\PostLikeModel;
use WP_REST_Request;
use WP_User;

class Api {

    protected $baseURI;

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'initialize']);
    }

    public function initialize()
    {
        // récupération de la baseURI grace a la surperglobale $SERVER et la fonction php dirname
        // https://www.php.net/manual/fr/function.dirname.php
        $this->baseURI = dirname($_SERVER['SCRIPT_NAME']);

        // creation d'une route API permettant d'afficher les oeuvres par salon
        // https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/

        register_rest_route(
            'ogalerie/v1', // le nom de notre API
            'salon/(?P<id>\d+)/artworks', // la route qui se mettra apres le nom de notre api
            [
                // attention, methods avec un s !
                'methods' => 'GET', 
                // si on a un utilisateur qui sollicite notre API sur /ocooking/v1/inscription en utilisant la methode HTTP "post" on va executer la methode inscription
                'callback' => [$this, 'getArtworksBySalon']
            ]
        );

        register_rest_route(
            'ogalerie/v1', // le nom de notre API
            'inscription', // la route qui se mettra apres le nom de notre api
            [
                // attention, methods avec un s !
                'methods' => 'post', 
                // si on a un utilisateur qui sollicite notre API sur /ocooking/v1/inscription en utilisant la methode HTTP "post" on va executer la methode inscription
                'callback' => [$this, 'inscription']
            ]
        );

        register_rest_route(
            'ogalerie/v1', // le nom de notre API
            'artwork/(?P<id>\d+)/like', // la route qui se mettra apres le nom de notre api
            [
                // attention, methods avec un s !
                'methods' => 'post', 
                // si on a un utilisateur qui sollicite notre API sur /ocooking/v1/inscription en utilisant la methode HTTP "post" on va executer la methode inscription
                'callback' => [$this, 'addLikeToArtwork']
            ]
        );
                  
    }
    
    // avec mon parametre WP_REST_Request $request j'indique qu'on va reçevoir un objet de type WP_REST_Request 
    // attention, contrairement a un docbloc (purement informatif), cette instruction va contraidre le type de donnée du parametre
    // méthode permettant de remonter en API les infos suivantes (sous forme de tableaux) :
    // tous les éléments de toutes les oeuvres appartenant à un salon d'exposition
    // y compris le nombre de likes de chaque oeuvre (calculé d'après les infos de la custom table artwork_likes)
    // et y compris les infos concernant le salon d'exposition
    public function getArtworksBySalon(WP_REST_Request $request) {
        // dans l'objet request je vais récupérer ce qui a été envoyé a l'api sur le endpoint /ogalerie/v1/salon/id/artworks en GET ( et donc je vais récupérer un titre, un contenu, une photo de chaque oeuvre)

        // l'odre de récupération des données n'est pas important
        $salonId = $request->get_param('id');
        // on va récupérer toutes les oeuvres liées à un salon déterminé
        $args = [
            'numberposts' => 10,
            "post_type" => 'artwork',
            'meta_query' => array(
                array(
                    'key' => 'salon_lie',
                    'value' => $salonId,
                    'compare' => '=',
                )
            
            )
        ];

        $artworks = get_posts($args);
        // tableaux à remplir des infos nécessaires côté API :
        //tableau de toutes les oeuvres par salon et tableau des infos propres à un salon
        $artworksArray = [];
        $salonArray = [];

        //$all_meta_for_user = get_user_meta( $artworks[0]->post_author ); (en cas d'utilisation de la notion d'artiste pour les infos du salon)

        $the_post = get_post($salonId); // On récupère le post

        $the_title = $the_post->post_title;
        $the_content = $the_post->post_content; // On récupère le contenu du post
        $the_excerpt = $the_post->post_excerpt;
        $salonArray[] = [
            // on range dans ce tableau toutes les infos propres à un salon déterminé dont on a besoin
            // on affecte dans le CPT salon côté wordpress les champs 
            // "title" au nom d'artiste,
            // "content" à la biographie de l'artiste
            // "excerpt" à la date de l'expo
            'salonId' => $salonId,
            'post_author' => $artworks[0]->post_author,
            'salon_author' => $the_title,          
            'salon_biography' => $the_content,
            'salon_date' => $the_excerpt
            //dans le cas où la biographie est renseignée dans la case "biographical info" de l'artiste côté BO wordpress, on fait
            //'author_biography' => $all_meta_for_user['description']
        ];

        // J'instancie le Model PostLike
        $postLikeModel = new PostLikeModel();

        // boucle sur toutes les oeuvres récupérées
        foreach($artworks as $artwork) {

        $artworkId = $artwork->ID;  
        $likesByArtwork = $postLikeModel->getLikes($artworkId);            
          
            $artworksArray[] = [
 
                'ID' => $artworkId,
                'salonId' => $salonId,
                'artwork_likes' => $likesByArtwork['likes_number'],
                'post_author' => $artwork->post_author,               
                'post_title' => $artwork->post_title,
                'post_content' => $artwork->post_content,
                'post_image' => get_the_post_thumbnail_url( $artwork, 'large' ) // image de l'oeuvre
            ];

        }       
        return [
            'salon' => $salonArray,
            'artworks' => $artworksArray,
            'success' => true
        ];

    }


    public function inscription(WP_REST_Request $request)
    {
        // dans l'objet request je vais récupérer ce qui a été envoyé a l'api sur le endpoint /ocooking/v1/inscription en POST ( et donc je vais récupérer un nom d'utilisateur, un email et un password)

        // l'odre de récupération des données n'est pas important
        $userName = $request->get_param('username');
        // équivalent a un filter_input(INPUT_POST, 'password')
        $email = $request->get_param('email');
        $password = $request->get_param('password');

        // creation d'un nouvel utilisateur
        // https://developer.wordpress.org/reference/functions/wp_create_user/

        $userCreateResult = wp_create_user(
            $userName,
            $password,
            $email
        );
        // si l'utilisateur a bien été créé, nous allons récupérer dans $userCreateResult l'ID de se dernier

        // vérification: est ce que l'utilisateur a bien été créé

        if(is_int($userCreateResult)){
            // modification du role de l'utilisateur...
            // je vais récupérer un objet user qui représente l'utilisateur fraichement inscrit.
            // pour ce faire, j'instancie la classe WP_User en lui donnant l'ID de l'utilisateur
            $user = new WP_User($userCreateResult);
            $user->remove_role('subscriber');
            $user->add_role('artist');
            return [
                'success' => true,
                'userId' => $userCreateResult,
                'username' => $userName,
                'email' => $email,
                'role' => 'artist'
            ];
        } else {
            return [
                'success' => false,
                'error' => $userCreateResult
            ];
        }
    }

    // méthode appelée par le endpoint de l'API permettant d'ajouter en BDD un like pour une oeuvre et pour un user connecté 
    public function addLikeToArtwork(WP_REST_Request $request)
    {
        // On récupère l'id de l'utilisateur connecté
        $user = wp_get_current_user();
        $userId = $user->ID;
        
        // On récupère l'id de l'oeuvre d'art qui est dans l'url
        $artworkId = $request->get_param('id');

        // J'instancie le Model PostLike
        $postLikeModel = new PostLikeModel();

        // J'appelle la méthode insert de PostLikeModel pour ajouter le like sur l'oeuvre d'art 
        // (ajout d'une ligne dans ma table custom)
        
        if($postLikeModel->insert($userId, $artworkId)){
            return [
                'success' => true
            ];
        } else {
            return [
                'success' => false,
                'error' => "L'utilisateur a déjà liké cette oeuvre"
            ];
        }
    }


    
  

}