<?php

namespace OGalerie\Models;

class PostLikeModel extends CoreModel
{

    public function createTable()
    {
        $sql = "
            CREATE TABLE `artwork_like` (
                `id` bigint(24) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `user_id` bigint(24) unsigned NOT NULL,
                `artwork_id` bigint(24) unsigned NOT NULL,
                `created_at` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
                `updated_at` datetime NULL
            );
        ";

        // pour pouvoir créer la table, WP nous donne une fonction qui va nous permettre de créer la table
        // https://developer.wordpress.org/reference/functions/dbdelta/

        //! Attention, tout comme pour la fonction wp_delete_user, il va nous falloir faire un require pour pouvoir bénéficier de cette fonction dbDelta
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);
    }

    public function dropTable()
    {
        $sql = 'DROP TABLE `artwork_like`';
        // $this->database = objet $wpdb que j'ai récupéré grace a mon CoreModel
        $this->database->query($sql);
    }

    public function insert($userId, $artworkId)
    {
        // On vérifie que l'utilisateur n'a pas déjà liké l'oeuvre
        // donc si il n'existe pas déjà un couple user_id et artwork_id identique à celui là
        // Si le couple existe déjà on return false sinon on fait l'insert
       // attention l'id du user doit être un entier      
        $userId = (int)$userId;
       
        //requête sql permettant de compter le nombre de lignes de la custom table 'artwork_like'
        //contenant la même association userId/artworkId ce qui signifie que le cotributeur a déjà liké l'oeuvre
        $sql = 'SELECT COUNT(*) FROM `artwork_like` WHERE user_id = ' . $userId . ' AND artwork_id = ' . $artworkId;
        // envoi de la requête sql vers la BDD permettant de récupérer le nombre résultat du comptage      
        $nombre = $this->database->get_var($sql);

        // attention le tableau $data ne doit pas être mis dans une condition sinon la réponse de la requête côté front sera inutilisable
            $data = [
            'user_id' => $userId, 
            'artwork_id' => $artworkId,
            'created_at' => date('Y-m-d H:i:s')
        ];
            // Grace a la methode insert de $wpdb
            // je peux réaliser une insertion en BDD
            // sans requete SQL, seulement grace a un tableau associatif qui contient les données de mon insert
        if ($nombre == 0) {
            return $this->database->insert('artwork_like', $data);
            
        } else {
            // si la personne a déjà liké la demande du front implique qu'on considère que la personne a changé d'avis
            // donc on retire la ligne déjà existante dans la custom table du like du user sur une oeuvre
            $this->delete($userId, $artworkId);
            return false;
        }
    }


    // méthode permettant de deleter une ligne de la custom table "artwork_like"
    // cela corresponde à un like fait par un user connecté sur une oeuvre
    public function delete($userId, $artworkId)
    {
        $where = [
            'user_id' => $userId,
            'artwork_id' => $artworkId
        ];
        
        $this->database->delete('artwork_like', $where);

    }


    public function getLikes($artworkId)
    {
        // On récupère le nombre de likes enregistrés en BDD par oeuvre
        // pour cela, on va compter le nombre de lignes pour chaque id d'oeuvre     
        
       
        //requête sql permettant de compter le nombre de lignes de la custom table 'artwork_like' par id de l'oeuvre
        $sql = 'SELECT COUNT(*) FROM `artwork_like` WHERE artwork_id = ' . $artworkId;
        // envoi de la requête sql vers la BDD permettant de récupérer le nombre résultat du comptage  pour une oeuvre    
        $likesNumber = (int)$this->database->get_var($sql);

        // on prépare un tableau contenant toutes les données dont on aura besoin
        $data = [
            'artwork_id' => $artworkId,
            'likes_number' => $likesNumber
        ];
        
            return $data;
            
    }


    public function update($id, $artworkId)
    {
        $data = [
            'artwork_id' => $artworkId,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $where = [
            'id' => $id
        ];

        $this->database->update(
            'artwork_like',
            $data,
            $where
        );

    }

    
}