<?php
    /**
     * @file   modules/file/lang/fr.lang.php
     * @author zero <zero@nzeo.com>  Traduit par Pierre Duvent <PierreDuvent@gmail.com>
     * @brief  Paquet du langage en francais pour le module d\'Annexe
     **/

    $lang->file = 'Annexe';
    $lang->file_name = 'Nom du Fichier';
    $lang->file_size = 'Mesure du Fichier';
    $lang->download_count = 'Somme du Telecharge';
    $lang->status = 'Statut';
    $lang->is_valid = 'Valide';
    $lang->is_stand_by = 'Attente';
    $lang->file_list = 'Liste des Annexes';
    $lang->allow_outlink = '파일 외부 링크';
    $lang->allowed_filesize = 'Mesure du Fichier Maximum';
    $lang->allowed_attach_size = 'Somme des Annexes Maximum';
    $lang->allowed_filetypes = 'Extensions consentis';
    $lang->enable_download_group = 'Groupe permis de telecharger';

    $lang->about_allow_outlink = '리퍼러에 따라 파일 외부 링크를 차단할 수 있습니다.(*.wmv, *.mp3등 미디어 파일 제외)';
    $lang->about_allowed_filesize = 'Vous pouvez designer la limite de mesure pour chaque fichier. (Exclure administrateurs)';
    $lang->about_allowed_attach_size = 'Vous pouvez designer la limite de mesure pour chaque document. (Exclure administrateurs)';
    $lang->about_allowed_filetypes = 'Extensions consentis seulement peuvent etre attaches. Pour consentir une extension, utilisez "*.[extention]". Pour consentir plusieurs extensions, utilisez ";" entre chaque extension.<br />ex) *.* ou *.jpg;*.gif;<br />(Exclure Administrateurs)';

    $lang->cmd_delete_checked_file = 'Supprimer item(s) slectionne(s)';
    $lang->cmd_move_to_document = 'Bouger au Document';
    $lang->cmd_download = 'Telecharger';

    $lang->msg_not_permitted_download = 'Vous n\'etes pas permis(e) de telecharger';
    $lang->msg_cart_is_null = 'Choisissez un(des) fichier(s) a supprimer';
    $lang->msg_checked_file_is_deleted = '%d Annexe(s) est(sont) supprime(s)';
    $lang->msg_exceeds_limit_size = 'La mesure de l\'(des) Annexe(s) est plus grande que celle consentie.';

    $lang->file_search_target_list = array(
        'filename' => 'Nom de Fichier',
        'filesize' => 'Mesure de Fichier (octet, surplus)',
        'filesize_mega' => '파일크기 (Mb, 이상)',
        'download_count' => 'Telecharges (surplus)',
        'user_id' => '아이디',
        'user_name' => '이름',
        'nick_name' => '닉네임',
        'regdate' => 'Enrgistre',
        'ipaddress' => 'Adresse IP',
    );
?>
