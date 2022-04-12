<?php

namespace OGalerie\Models;

class CoreModel 
{

    // la propriété $database nous permet de récupérer le composant pour intéragir avec la BDD
    protected $database;

    public function __construct()
    {
        // https://developer.wordpress.org/reference/classes/wpdb/
        global $wpdb;
        $this->database = $wpdb;
    }

    // Methode qui va nous permettre de faire une requete préparée (prévention contre les injections SQL)
    // Si j'ai bien des parametres (token) daans ma requete
    // dans $parameters je vais donner sous la forme d'un tableau les VALEURS des "tokens" si il y en a
    public function executePreparedStatement($sql, $parameters = [])
    {
        // gestion des requêtes SQL sans partie "variable" (cad qu'il n'y a pas de paramètre)
        // STEP WP CUSTOMTABLE requête sql sans paramètre
        // si je n'ai pas de parametres
        if(empty($parameters)){
            // j'execute un requete "normale"
            return $this->database->get_results($sql);
        }
        else {
                // si j'ai des parametres
                // je prépare une requete préparée
                $preparedStatement = $this->database->prepare(
                    $sql,
                    $parameters
                );
            // pour l'executer et renvoyer le resultat
            return $this->database->get_results($preparedStatement);
        }

    }


}