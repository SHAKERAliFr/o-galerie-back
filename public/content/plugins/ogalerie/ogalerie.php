<?php
/**
 * Plugin Name: oGalerie
 * Author: ogalerie team
 */

use OGalerie\Plugin;
use OGalerie\Api;

require __DIR__ . '/vendor-ogalerie/autoload.php';

$oGalerie = new Plugin();

register_activation_hook(
    // premier argument, le chemin vers le fichier de déclaration du plugin
    __FILE__,
    // Deuxieme argument, je vais indiquer la methode a executer sur l'objet $oProfile
    [$oGalerie, 'activate']

    

 );

 register_deactivation_hook(
    __FILE__,
    [$oGalerie, 'deactivate']
 );


 $api = new Api();
