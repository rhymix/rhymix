<?php
    /**
     * @file   fr.lang.php
     * @author zero (zero@nzeo.com)  Traduit par Pierre Duvent <PierreDuvent@gamil.com>
     * @brief  Paquet de la langue fondamental du module d\'Importateur
     **/

    // words for button
    $lang->cmd_sync_member = 'Synchroniser';
    $lang->cmd_continue = 'Continuer';

    // items
    $lang->importer = 'Transférer des Données du Zeroboard';
    $lang->source_type = 'Sorte de Source';
    $lang->type_member = 'Données des Membres';
    $lang->type_message = 'Données des Messages';
    $lang->type_ttxml = 'TTXML';
    $lang->type_module = 'Données des Articles';
    $lang->type_syncmember = 'Synchroniser les Données des Membres';
    $lang->target_module = 'Module de cible ';
    $lang->xml_file = 'Fichier de XML';

    $lang->import_step_title = array(
        1 => 'Step 1. Choisir cible à transférer',
        12 => 'Step 1-2. Choisir module de Cible',
        13 => 'Step 1-3. Choisir categorie de Cible',
        2 => 'Step 2. Télécharger fichier XML',
        3 => 'Step 2. Synchroniser données des membres et des articles',
    );

    $lang->import_step_desc = array(
        1 => 'Sélectionnez la sorte du fichier de XML que vous voulez transférer.',
        12 => 'Sélectionnez le module objectif dans lequel vous voulez tranférer des données.',
        13 => 'Séléctionnez la categorie objective dans laquelle vous voulez transférer des données.',
        2 => "Entrez le chemin du fichier de XML pour transférer des données.\nS\'il est localisé dans le même compte, entréz le chemin absolute/relative. Sinon, entrez le URL commençant avec http://..",
        3 => 'Les données des membres et ceux des articles ne peuvent pas corrects après la transfèrement. Dans ce cas, synchronisez pour les réparer  fondé sur le compte d\'utilisateur.',
    );

    // guide/alert
    $lang->msg_sync_member = 'Member and article data synchronization will begin by clicking the synchronize button.';
    $lang->msg_no_xml_file = 'Could not find XML file. Please check the path again';
    $lang->msg_invalid_xml_file = 'Invalid type of XML file.';
    $lang->msg_importing = 'Writing %d datas of %d. (If it keeps being frozen, click the button "Continue")';
    $lang->msg_import_finished = '%d/%d datas were inputted completely. Depending on the situation, there might be some datas which couldn\'t be inputted.';
    $lang->msg_sync_completed = 'Completed synchronzing member article and comments.';

    // blah blah..
    $lang->about_type_member = 'If you are transfering the member information, select this option';
    $lang->about_type_message = 'If you are transfering the message information, select this option';
    $lang->about_type_ttxml = 'If you are transfering the TTXML(textcube) information, select this option';
	$lang->about_ttxml_user_id = 'Please input user ID to set as author on transfering TTXML. (user ID must be already signed up)';
    $lang->about_type_module = 'If you are transfering the board or articles information, select this option';
    $lang->about_type_syncmember = 'If you are trying to synchronize the member information after transfering member and article information, select this option';
    $lang->about_importer = "You can transfer Zeroboard4, Zeroboard5 Beta or other program's data into ZeroboardXE's data.\nIn order to tranfer, you have to use <a href=\"#\" onclick=\"winopen('');return false;\">XML Exporter</a> to convert the data you want into XML File then upload it.";

    $lang->about_target_path = "To get attachments from Zeroboard4, please input the address where Zeroboard4 is installed.\nIf it is located in the same server, input Zeroboard4's path such as /home/USERID/public_html/bbs\nIf it is not located in the same server, input the address where Zeroboard4 is installed. ex. http://Domain/bbs";
?>
