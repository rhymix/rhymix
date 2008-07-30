<?php
    /**
     * @file   fr.lang.php
     * @author zero (zero@nzeo.com) traduit par duvent (duvent@gmail.com)
     * @brief  Paque du langage primaire pour le module de page extérieure
     **/

    $lang->opage = "Page Extérieure";
    $lang->opage_path = "Localisation du Document Extérieur";
    $lang->opage_caching_interval = "Temps de cache";

    $lang->about_opage = "Ce module vous fait pouvoir utiliser des fichiers extérieurs en html ou en php dans Zeroboard XE.<br />C'est possible de utiliser le chemin absolu ou relatif, et si l'URL commence avec 'http://' , il est possible de représenter des pages extérieurs du serveur.";
    $lang->about_opage_path= "Entrez la localisation du document extérieur.<br />Non seulement le chemin absolu comme '/path1/path2/sample.php' mais aussi le chemin relatif comme '../path2/sample.php' peuvent être utilisés.<br />Si vous entrez le chemin comme 'http://url/sample.php', le résultat sera reçu et puis exposé<br />Le chemin suivant, c'est le chemin absolu de Zeroboard Xe.<br />";
    $lang->about_opage_caching_interval = "L'unité est minute, et ça exposera des données conservées temporairement pendant le temps assigné.<br />Il est recommandé d'utiliser l'antémémoire pendant le temps convenable si beaucoup de ressource est nécessaire pour représanter les données ou l'information d'autre serveur.<br />La valeur 0 signifie de ne pas utiliser antémémoire.";
?>
