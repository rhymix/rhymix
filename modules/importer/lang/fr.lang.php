<?php
    /**
     * @file   fr.lang.php
     * @author zero (zero@nzeo.com)  Traduit par Pierre Duvent <PierreDuvent@gamil.com>
     * @brief  Paquet de la langue fondamental du module d\'Importateur
     **/

    // words for button
    $lang->cmd_sync_member = 'Synchroniser';
    $lang->cmd_continue = 'Continuer';
    $lang->preprocessing = 'On est en train de préparer pour transférer les données.';

    // items
    $lang->importer = 'Transférer les Données du Zeroboard';
    $lang->source_type = 'Sorte de Source';
    $lang->type_member = 'Données des Membres';
    $lang->type_message = 'Données des Messages';
    $lang->type_ttxml = 'TTXML';
    $lang->type_module = 'Données des Articles';
    $lang->type_syncmember = 'Synchroniser les Données des Membres';
    $lang->target_module = 'Module objectif';
    $lang->xml_file = 'Fichier de XML';

    $lang->import_step_title = array(
        1 => 'Pas 1. Choisir cible à transférer',
        12 => 'Pas 1-2. Choisir module de Cible',
        13 => 'Pas 1-3. Choisir categorie de Cible',
        2 => 'Pas 2. Télécharger fichier de XML',
        3 => 'Pas 2. Synchroniser données des membres et des articles',
    );

    $lang->import_step_desc = array(
        1 => 'Sélectionnez la sorte du fichier de XML que vous voulez transférer.',
        12 => 'Sélectionnez le module objectif dans lequel vous voulez tranférer les données.',
        13 => 'Séléctionnez la catégorie objective dans laquelle vous voulez transférer les données.',
        2 => "Entrez le chemin du fichier de XML pour transférer les données.\nS'il est localisé dans le même compte, entréz le chemin absolut ou relatif. Sinon, entrez l'URL commençant avec http://..",
        3 => 'Les données des membres et ceux des articles ne peuvent pas corrects après la transfèrement. Dans ce cas, synchronisez SVP. Ça arrangera les données en fondant sur le compte d\'utilisateur.',
    );

    // guide/alert
    $lang->msg_sync_member = 'On commencera à synchroniser les données des membres et des articles quand vous cliquez le boutton de synchroniser.';
    $lang->msg_no_xml_file = 'On ne peut pas trouver le fichier de XML. Vérifiez le chemin encore une fois, SVP.';
    $lang->msg_invalid_xml_file = 'Ce fichier de XML est invalide.';
    $lang->msg_importing = 'On écrit %d données sur %d. (Si c\'est arrêté, cliquez le boutton "Continuer")';
    $lang->msg_import_finished = '%d/%d données sont insérées complètement. En dépendant sur la situation, il y aura quelques données qui n\'ont pas été insérées.';
    $lang->msg_sync_completed = 'On a terminé de synchroniser les données des membres, des articles et des commentaires.';

    // blah blah..
    $lang->about_type_member = 'Sélectionnez cette option si vous voulez transférer les informations des membres';
    $lang->about_type_message = 'Sélectionnez cette option si vous voulez transférer les informations des messages';
    $lang->about_type_ttxml = 'Sélectionnez cette option si vous voulez transférer les informations des TTXML(textcube)';
	$lang->about_ttxml_user_id = 'Insérez le compte d\'utilisateur pour déclarer comme l\'auteur. (Le compte d\'utilisateur doit être déjà inscrit)';
    $lang->about_type_module = 'Sélectionnez cette option si vous voulez transférer les informations des panneaux ou des articles.';
    $lang->about_type_syncmember = 'Sélectionnez cette option si vous voulez synchroniser les informations des membres après le trensfert des informations des membres et des articles.';
    $lang->about_importer = "Vous pouvez transeférer les données de Zeroboard4, de Zeroboard5 Beta ou d\'autres logiciels en les données de ZeroboardXE.\nPour tranférer, vous devez utiliser <a href=\"http://svn.zeroboard.com/zeroboard_xe/migration_tools/\" onclick=\"winopen(this.href);return false;\">Exporteur de XML</a> pour convertir les données en fichier de XML, et puis téléchargez-le.";

    $lang->about_target_path = "Pour obtenir les attachés de Zeroboard4, Insérez l\'addresse ou Zeroboard4 est installé.\nSi ça se trouve dans le même serveur, entrez le chemin comme \'/home/USERID/public_html/bbs\'\nSi ça ne se trouve pas dans le même serveur, entrez l\'address où Zeroboard4 est installé comme \'http://Domain/bbs\'";
?>
