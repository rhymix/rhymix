<?php
    /**
     * @file   modules/krzip/lang/fr.lang.php
     * @author zero <zero@nzeo.com>  Traduit par Pierre Duvent <PierreDuvent@gmail.com>
     * @brief  Paquet de la langue franaise (Contenus fondamentals seulement sont lists)
     **/

    // mots normaux
    $lang->krzip = "Code postal coréen";
    $lang->krzip_server_hostname = "Nom de serveur pour vérifier le code postal";
    $lang->krzip_server_port = "Port de serveur pour vérifier le code postal";
    $lang->krzip_server_query = "Chemin de serveur pour vérifier le code postal";

    // descriptions
    $lang->about_krzip_server_hostname = "Entrez le domaine de serveur pour vérifier le code postal et recevoir le liste des résultats, SVP.";
    $lang->about_krzip_server_port = "Entrez le nombre de port de serveur pour vérifier le code postal, SVP";
    $lang->about_krzip_server_query = "Entrez l'URL à requérir qui sera requis pour vérifier le code postal";

    // messages des erreurs
    $lang->msg_not_exists_addr = "Objectifs à rechercher n'existe pas";
    $lang->msg_fail_to_socket_open = "Echoué à connecter au serveur pour vérifier le code postal";
?>
