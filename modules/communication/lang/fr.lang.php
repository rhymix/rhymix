<?php
    /**
     * @file   fr.lang.php
     * @author zero (zero@nzeo.com) Traduit par Pierre Duvent(PierreDuvent@gamil.com)
     * @brief  Paquet du langage en français pour le module de Communication
     **/

    $lang->communication = 'Communication';
    $lang->about_communication = 'Ce module exécute des fonctions communicatives comme Messages ou Amis';

    $lang->allow_message = 'Recevoir les Messages';
    $lang->allow_message_type = array(
             'Y' => 'Recevoir tout',
             'N' => 'Refuser tout',
             'F' => 'Amis seulement',
        );

    $lang->message_box = array(
        'R' => 'Reçu',
        'S' => 'Envoyé',
        'T' => 'Boîte aux Lettres',
    );
    $lang->readed_date = "Jour lu"; 

    $lang->sender = 'Envoyeur';
    $lang->receiver = 'Receveur';
    $lang->friend_group = 'Groupe des Amis';
    $lang->default_friend_group = 'Groupe pas assigné ';

    $lang->cmd_send_message = 'Envoyer un Message';
    $lang->cmd_reply_message = 'Répondre à un Message';
    $lang->cmd_view_friend = 'Amis';
    $lang->cmd_add_friend = 'Inscrire des Amis';
    $lang->cmd_view_message_box = 'Lire des Messages';
    $lang->cmd_store = "Conserver";
    $lang->cmd_add_friend_group = 'Ajouter un Groupe des Amis';
    $lang->cmd_rename_friend_group = 'Modifier le Nom du Groupe des Amis';

    $lang->msg_no_message = 'Nul Message';
    $lang->message_received = 'Nouveau message';

    $lang->msg_title_is_null = 'Entrez le titre du message, S.V.P.';
    $lang->msg_content_is_null = 'Entrez le contenu, S.V.P.';
    $lang->msg_allow_message_to_friend = "Echoué à envoyer parce que le receveur permet seulement les messages des Amis.";
    $lang->msg_disallow_message = 'Echoué à envoyer parce que le receveur refuse la réception des messages';

    $lang->about_allow_message = 'Vous pouvez refuser la réception des messages';
?>
