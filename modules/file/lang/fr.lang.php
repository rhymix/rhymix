<?php
    /**
     * @file   modules/file/lang/fr.lang.php
     * @author zero <zero@nzeo.com>  Traduit par Pierre Duvent <PierreDuvent@gmail.com>
     * @brief  Paquet du langage en français pour le module d\'Annexe
     **/

    $lang->file = 'Annexe';
    $lang->file_name = 'Nom du Fichier';
    $lang->file_size = 'Mesure du Fichier';
    $lang->download_count = 'Somme du Téléchargé';
    $lang->status = 'Statut';
    $lang->is_valid = 'Valide';
    $lang->is_stand_by = 'Attente';
    $lang->file_list = 'Liste des Annexes';
    $lang->allowed_filesize = 'Mesure du Fichier Maximum';
    $lang->allowed_attach_size = 'Somme des Annexes Maximum';
    $lang->allowed_filetypes = 'Extensions consentis';
    $lang->enable_download_group = 'Groupe permis de télécharger';

    $lang->about_allowed_filesize = 'Vous pouvez désigner la limite de mesure pour chaque fichier. (Exclure administrateurs)';
    $lang->about_allowed_attach_size = 'Vous pouvez désigner la limite de mesure pour chaque document. (Exclure administrateurs)';
    $lang->about_allowed_filetypes = 'Extensions consentis seulement peuvent être attachés. Pour consentir une extension, utilisez "*.[extention]". Pour consentir plusieurs extensions, utilisez ";" entre chaque extension.<br />ex) *.* ou *.jpg;*.gif;<br />(Exclure Administrateurs)';

    $lang->cmd_delete_checked_file = 'Supprimer item(s) slectionné(s)';
    $lang->cmd_move_to_document = 'Bouger au Document';
    $lang->cmd_download = 'Télécharger';

    $lang->msg_not_permitted_download = 'Vous n\'êtes pas permis(e) de télécharger';
    $lang->msg_cart_is_null = 'Choisissez un(des) fichier(s) à supprimer';
    $lang->msg_checked_file_is_deleted = '%d Annexe(s) est(sont) supprimé(s)';
    $lang->msg_exceeds_limit_size = 'La mesure de l\'(des) Annexe(s) est plus grande que celle consentie.';

    $lang->file_search_target_list = array(
        'filename' => 'Nom de Fichier',
        'filesize' => 'Mesure de Fichier (octet, surplus)',
        'filesize_mega' => '파일크기 (Mb, 이상)',
        'download_count' => 'Téléchargés (surplus)',
        'user_id' => '아이디',
        'user_name' => '이름',
        'nick_name' => '닉네임',
        'regdate' => 'Enrgistré',
        'ipaddress' => 'Adresse IP',
    );
?>
