<?php
    /**
     * @file   modules/spamfilter/lang/fr.lang.php
     * @author zero <zero@nzeo.com> Traduit par Pierre Duvent <PierreDuvent@gmail.com>
     * @brief  Paquet du langage en français pour le module du Filtre contre polluriel
     **/

    // de l'action
    $lang->cmd_denied_ip = "Liste noire d'Address IP";
    $lang->cmd_denied_word = "Liste noire des Mots";

    // mots générals
    $lang->spamfilter = "Filtre contre Polluriel";
    $lang->denied_ip = "IP à bloquer";
    $lang->interval = "Intervalle à filtrer contre polluriel";
    $lang->limit_count = "Limite d'affichage";
    $lang->check_trackback = "Vérifier les Rétroliens";
    $lang->word = "Mot";
    $lang->hit = '히트';
    $lang->latest_hit = '최근 히트';

    // descriptions
    $lang->about_interval = "L'affichage sera bloqué pendant le temps designé.";
    $lang->about_limit_count = "Si l'on excéde la limite d'affichage pendant le temps désigné,\nles articles en plus seront reconnus comme polluriel, et l'adresse IP sera bloqué.";
    $lang->about_denied_ip = "Vous pouvez bloquer l'étendue de l'adresse IP comme 127.0.0.* en utilisant *.";
    $lang->about_denied_word = "Quand vous enrégistrez un mot dans la liste noire, \nl'article qui comporte le mot ne sera pas affichagé.";
    $lang->about_check_trackback = "Le rétrolien peut être permis à un seul IP par article.";

    // messages
    $lang->msg_alert_limited_by_config = 'L\'Affichage d\'un article en %s secondes n\'est pas permis.\n Si vous essayez encore, votre adresse IP peut être enrégistré dans la liste noire.';
    $lang->msg_alert_denied_word = 'Le mot "%s" n\'est pas permis d\'afficher.';
    $lang->msg_alert_registered_denied_ip = 'Your IP address is blacklisted,\n so you may have limitations on normal using of this site.\n If you have any questions on that matter, please contact to the site administrator. Votre adresse IP est dans la liste noire, \nvous pouvez donc avoir limitation d\'activité dans ce site. Si vous avez quelque question sur ce fait, contactez l\'administrateur du site, S.V.P.';
    $lang->msg_alert_trackback_denied = 'Un seul rétrolien par article est permis.';
?>