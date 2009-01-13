<?php
    /**
     * @file   modules/file/lang/fr.lang.php
     * @author zero <zero@nzeo.com>  Traduit par Pierre Duvent <PierreDuvent@gmail.com>
     * @brief  Paquet du langage en franÃ§ais pour le module d\'Annexe
     **/

    $lang->file = 'Annexe';
    $lang->file_name = 'Nom du Fichier';
    $lang->file_size = 'Mesure du Fichier';
    $lang->download_count = 'Somme du TÃ©lÃ©chargÃ©';
    $lang->status = 'Statut';
    $lang->is_valid = 'Valide';
    $lang->is_stand_by = 'Attente';
    $lang->file_list = 'Liste des Annexes';
    $lang->allow_outlink = '파일 외부 링크';
    $lang->allowed_filesize = 'Mesure du Fichier Maximum';
    $lang->allowed_attach_size = 'Somme des Annexes Maximum';
    $lang->allowed_filetypes = 'Extensions consentis';
    $lang->enable_download_group = 'Groupe permis de tÃ©lÃ©charger';

    $lang->about_allow_outlink = '리퍼러에 따라 파일 외부 링크를 차단할 수 있습니다.(*.wmv, *.mp3등 미디어 파일 제외)';
    $lang->about_allowed_filesize = 'Vous pouvez dÃ©signer la limite de mesure pour chaque fichier. (Exclure administrateurs)';
    $lang->about_allowed_attach_size = 'Vous pouvez dÃ©signer la limite de mesure pour chaque document. (Exclure administrateurs)';
    $lang->about_allowed_filetypes = 'Extensions consentis seulement peuvent Ãªtre attachÃ©s. Pour consentir une extension, utilisez "*.[extention]". Pour consentir plusieurs extensions, utilisez ";" entre chaque extension.<br />ex) *.* ou *.jpg;*.gif;<br />(Exclure Administrateurs)';

    $lang->cmd_delete_checked_file = 'Supprimer item(s) slectionnÃ©(s)';
    $lang->cmd_move_to_document = 'Bouger au Document';
    $lang->cmd_download = 'TÃ©lÃ©charger';

    $lang->msg_not_permitted_download = 'Vous n\'Ãªtes pas permis(e) de tÃ©lÃ©charger';
    $lang->msg_cart_is_null = 'Choisissez un(des) fichier(s) Ã  supprimer';
    $lang->msg_checked_file_is_deleted = '%d Annexe(s) est(sont) supprimÃ©(s)';
    $lang->msg_exceeds_limit_size = 'La mesure de l\'(des) Annexe(s) est plus grande que celle consentie.';

    $lang->file_search_target_list = array(
        'filename' => 'Nom de Fichier',
        'filesize' => 'Mesure de Fichier (octet, surplus)',
        'filesize_mega' => 'íŒŒì¼í¬ê¸° (Mb, ì´ìƒ)',
        'download_count' => 'TÃ©lÃ©chargÃ©s (surplus)',
        'user_id' => 'ì•„ì´ë””',
        'user_name' => 'ì´ë¦„',
        'nick_name' => 'ë‹‰ë„¤ìž„',
        'regdate' => 'EnrgistrÃ©',
        'ipaddress' => 'Adresse IP',
    );
?>
