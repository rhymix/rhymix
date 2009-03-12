<?php
    /**
     * @file   fr.lang.php
     * @author zero (zero@nzeo.com)  Traduit par Pierre Duvent <PierreDuvent@gamil.com>
     * @brief  Paquet du langage en français le module d\'Importateur
     **/

    // words for button
    $lang->cmd_sync_member = 'Synchroniser';
    $lang->cmd_continue = 'Continuer';
    $lang->preprocessing = 'On est en train de préparer pour transférer les données.';

    // items
    $lang->importer = 'Transférer les Données du Zeroboard';
    $lang->source_type = 'Sorte de la Source';
    $lang->type_member = 'Données des Membres';
    $lang->type_message = 'Données des Messages';
    $lang->type_ttxml = 'TTXML';
    $lang->type_module = 'Données des Articles';
    $lang->type_syncmember = 'Synchroniser les Données des Membres';
    $lang->target_module = 'Module objectif';
    $lang->xml_file = 'Fichier en XML';

    $lang->import_step_title = array(
        1 => 'Pas 1. Choisir l\'objet à transférer',
        12 => 'Pas 1-2. Choisir le module à transférer',
        13 => 'Pas 1-3. Choisir la catégorie à transférer',
        2 => 'Pas 2. Télécharger fichier en XML',
        3 => 'Pas 2. Synchroniser données des membres et des articles',
		99 => 'Trensférer des données',
    );

    $lang->import_step_desc = array(
        1 => 'Choisissez la sorte du fichier en XML que vous voulez transférer.',
        12 => 'Choisissez le module objectif dans lequel vous voulez tranférer les données.',
        121 => '글:',
        122 => '방명록:',
        13 => 'Choisissez la catégorie objective dans laquelle vous voulez transférer les données.',
        2 => "Entrez le chemin du fichier en XML pour transférer les données.\nS'il est localisé dans le même compte, entréz le chemin absolut ou relatif. Sinon, entrez l'URL commençant avec http://..",
        3 => 'Les données des membres et ceux des articles peuvent ne pas s\'accorder après la transfèrement. Dans ce cas, synchronisez S.V.P. Ça arrangera les données en étant basé sur le compte d\'utilisateur.',
		99 => 'En train de transférer',
    );

    // guide/alert
    $lang->msg_sync_member = 'On commencera à synchroniser les données des membres et des articles quand vous cliquez le bouton de synchroniser.';
    $lang->msg_no_xml_file = 'On ne peut pas trouver le fichier de XML. Vérifiez le chemin encore une fois, S.V.P.';
    $lang->msg_invalid_xml_file = 'Ce fichier de XML est invalide.';
    $lang->msg_importing = 'On écrit %d données sur %d. (Si c\'est arrêté longtemps, cliquez le bouton "Continuer")';
    $lang->msg_import_finished = '%d/%d données sont entrées complètement. En dépendant sur la situation, il y aura quelques données qui n\'ont pas été entrées.';
    $lang->msg_sync_completed = 'On a terminé de synchroniser les données des membres, des articles et des commentaires.';

    // blah blah..
    $lang->about_type_member = 'Choisissez cette option si vous voulez transférer les informations des membres';
    $lang->about_type_message = 'Choisissez cette option si vous voulez transférer les informations des messages';
    $lang->about_type_ttxml = 'Choisissez cette option si vous voulez transférer les informations des TTXML(textcube)';
	$lang->about_ttxml_user_id = 'Entrez le compte d\'utilisateur pour déclarer comme l\'auteur. (Le compte d\'utilisateur doit être déjà inscrit)';
    $lang->about_type_module = 'Choisissez cette option si vous voulez transférer les informations des panneaux ou des articles.';
    $lang->about_type_syncmember = 'Choisissez cette option si vous voulez synchroniser les informations des membres après le transfér des informations des membres et des articles.';
    $lang->about_importer = "Vous pouvez transférer les données de Zeroboard4, de Zeroboard5 Beta ou d\'autres logiciels aux données de XE.\nPour transférer, vous devez utiliser <a href=\"http://svn.zeroboard.com/zeroboard_xe/migration_tools/\" onclick=\"winopen(this.href);return false;\">Exporteur de XML</a> pour convertir les données en fichier de XML, et puis téléchargez-le.";

    $lang->about_target_path = "Pour obtenir les attachés de Zeroboard4, Entrez l\'adresse où Zeroboard4 est installé.\nSi elle se trouve dans le même serveur, entrez le chemin comme '/home/USERID/public_html/bbs'\nSi elle ne se trouve pas dans le même serveur, entrez l\'adresse où Zeroboard4 est installé comme 'http://Domain/bbs'";
?>
