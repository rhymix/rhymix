<?php
    /**
     * @file   modules/file/lang/fr.lang.php
     * @author zero <zero@nzeo.com>  Traduit par Pierre Duvent <PierreDuvent@gmail.com>
     * @brief  Paquet de la langue fondamentale du module pour Attachement
     **/

    $lang->file = 'Attachement';
    $lang->file_name = 'Nom du Fichier';
    $lang->file_size = 'Mesure du Fichier';
    $lang->download_count = 'Somme du Téléchargé';
    $lang->status = 'Statut';
    $lang->is_valid = 'Valide';
    $lang->is_stand_by = 'Attente';
    $lang->file_list = 'Liste des Attachements';
    $lang->allowed_filesize = 'Mesure du Fichier Maximum';
    $lang->allowed_attach_size = 'Somme des Attachements Maximum';
    $lang->allowed_filetypes = 'Extensions consentis';
    $lang->enable_download_group = 'Groupe autorisé Télécharger';

    $lang->about_allowed_filesize = 'Vous pouvez assigner la limite de mesure pour chaque fichier. (Exclure administrateurs)';
    $lang->about_allowed_attach_size = 'Vous pouvez assigner la limite de mesure pour chaque document. (Exclure administrateurs)';
    $lang->about_allowed_filetypes = 'Extensions consentis seulement peuvent être attachés. Pour consentir une extension, utilisez "*.[extention]". Pour consentir plusieurs extensions, utilisez ";" entre chaque extension.<br />ex) *.* ou *.jpg;*.gif;<br />(Exclure Administrateurs)';

    $lang->cmd_delete_checked_file = 'Supprimer item(s) slectionné(s)';
    $lang->cmd_move_to_document = 'Bouger au Document';
    $lang->cmd_download = 'Télécharger';

    $lang->msg_not_permitted_download = 'Vous n\'tes pas autorisé à télécharger';
    $lang->msg_cart_is_null = 'Choisissez un(des) fichier(s) à supprimer';
    $lang->msg_checked_file_is_deleted = '%d attachement(s) est(sont) supprimé(s)';
    $lang->msg_exceeds_limit_size = 'La mesure de l\'(des) attachement(s) est plus grande que celle de la consentie.';

    $lang->search_target_list = array(
        'filename' => 'Nom de Fichier',
        'filesize' => 'Mesure de Fichier (octet, surplus)',
        'download_count' => 'Téléchargés (surplus)',
        'regdate' => 'Enrgistré',
        'ipaddress' => 'Addresse IP',
    );
?>
