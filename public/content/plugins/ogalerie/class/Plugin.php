<?php

namespace OGalerie;

use OGalerie\Models\PostLikeModel;

class Plugin
{
    public function __construct()
    {
        add_action(
            'init',
            [$this,'createSalonCustomPostType']
        );

        add_action(
            'init',
            [$this, 'createArtworkCustomPostType']
        );
        add_action(
            'init',
            [$this, 'createTechnicalCustomTaxonomie']
        );

        add_action(
            'init',
            [$this, 'createArtTypeTaxonomie']
        );

        add_action(
            'init',
            [$this, 'createThemeTaxonomie']
        );
    }
    
    public function createSalonCustomPostType()
    {
       register_post_type(
        // identifiant du post type
        'salon',
        // les options pour configurer le post type
        [   // intitulé
            'label' => 'salon',
            'public' => true,
            'hierarchical' => false,
            'menu_icon' => 'dashicons-format-gallery',
            'supports' => [
                'title',
                'thumbnail',
                'editor',
                'author',
                'excerpt',
                'comments'
            ],
            'capability_type' => 'salon',
            'map_meta_cap' => true,
            'show_in_rest' => true
        ]
      );
    }

    public function createArtworkCustomPostType()
    {
       register_post_type(
        // identifiant du post type
        'artwork',
        // les options pour configurer le post type
        [   // intitulé
            'label' => 'oeuvre d\'art',
            'public' => true,
            'hierarchical' => false,
            'menu_icon' => 'dashicons-art',
            'supports' => [
                'title',
                'thumbnail',
                'editor',
                '',
                '',
                'salon',
                'comments'
            ],
            'capability_type' => 'artwork',
            'map_meta_cap' => true,
            'show_in_rest' => true
        ]
      );
    }
    

    public function createTechnicalCustomTaxonomie()
    {
      register_taxonomy(
        //identifiant taxonomie 
        'technical',
        // cette "étiquette" pourra etre utilisé sur le CPT project
        ['artwork'],
        // tableau d'options
        [
            'label' => 'technique',
            'hierarchical' => true,
            'public' => true,
            'show_in_rest' => true
        ]
      );
    }


    public function createArtTypeTaxonomie()
    {
    register_taxonomy(
         //identifiant taxonomie 
         'artType',
         // cette "étiquette" pourra etre utilisé sur le CPT project
        ['artwork'],
         // tableau d'options
        [
        'label' => 'type d\'art',
        'hierarchical' => true,
        'public' => true,
        'show_in_rest' => true
        ]
      );
    }


    public function createThemeTaxonomie()
    {
     register_taxonomy(
         //identifiant taxonomie 
         'theme',
         // cette "étiquette" pourra etre utilisé sur le CPT project
        ['artwork'],
         // tableau d'options
        [
        'label' => 'thème',
        'hierarchical' => true,
        'public' => true,
        'show_in_rest' => true
        ]
     );
   }

    public function activate()
    {
        $this->addCapAdmin(['salon', 'artwork']);
        $this->registerArtisteRole(); 
        $modelObject = new PostLikeModel();
        $modelObject->createTable();

    }
    public function deactivate()
    {
        remove_role('artist');

        $modelObject = new PostLikeModel();
        $modelObject->dropTable();
    }


    public function registerArtisteRole()
    {  
    
        add_role(
            // identifiant du role 
            'artist',
            // libellé
            'Artiste',
            // liste des autorisatrions
            [
                'delete_artists' => false,
                'delete_others_artists' => false,
                'delete_private_artists' => false,
                'delete_published_artists' => false,
                'edit_artists' => true,
                'edit_others_artists' => false,
                'edit_private_artists' => false,
                'edit_published_artists' => true,
                'publish_artists' => false,
                'read_private_artists' => false
            ]
         );
       
    }


    public function addCapAdmin($customCapArray)
  {
    // methode qui nous permet d'ajouter les droits sur les CPT  pour le role administrateur
    //! Attention, sans cette opération les CPT recipe vont disparaitre
    //! en effet, nous avons définis un "capability_type" pour ce dernier
    //! et l'adminstrateur ne vas pas avoir automatiquement les droits 
    // il est possible d'intéragir avec les roles et capabilities en dehors de la création des roles a l'aide des deux fonctions suivante :
    // je peux récupérer un role WP sous la forme d'un objet grace fonction WP get_role()
    $role = get_role('administrator');
    foreach($customCapArray as $customCap){
        // grace a la methode add_cap de cet objet qui représente le role, je peux ajouter des droits a ce dernier
        $role->add_cap('delete_others_' . $customCap . 's');
        $role->add_cap('delete_private_' . $customCap . 's');
        $role->add_cap('delete_' . $customCap . 's');
        $role->add_cap('delete_published_' . $customCap . 's');
        $role->add_cap('edit_others_' . $customCap . 's');
        $role->add_cap('edit_private_' . $customCap . 's');
        $role->add_cap('edit_' . $customCap . 's');
        $role->add_cap('edit_published_' . $customCap . 's');
        $role->add_cap('publish_' . $customCap . 's');
        $role->add_cap('read_private_' . $customCap . 's');
    }
  }
}