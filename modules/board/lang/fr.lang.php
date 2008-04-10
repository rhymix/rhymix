<?php
    /**
     * @file  modules/board/lang/ fr.lang.php
     * @author zero (zero@nzeo.com) Traduit par Pierre Duvent(PierreDuvent@gamil.com)
     * @brief  Paquet de la langue fondamental pour le module de Panneau d\'Affichage
     **/

    $lang->board = "Panneau"; 

    $lang->except_notice = "Exclure des Notices";

    $lang->cmd_manage_menu = 'Arrangement de Menu';
    $lang->cmd_make_child = 'Ajouter une enfant catégorie';
    $lang->cmd_enable_move_category = "Bouger la position de la catégorie (Cochez la case et puis glisser le menu que vous voulez déplacer)";

    // Item
    $lang->parent_category_title = 'catégorie mère';
    $lang->category_title = 'Catégorie';
    $lang->expand = 'Etendre';
    $lang->category_group_srls = 'Groupe Accessible';
    $lang->search_result = 'Résultat de la Recherche';
    $lang->consultation = 'Consultation';
    $lang->admin_mail = '관리자 메일';

    // Mots utiliss en bouton
    $lang->cmd_board_list = 'Liste des Panneaux';
    $lang->cmd_module_config = 'Configuration commun pour les Panneaux';
    $lang->cmd_view_info = 'Information des Panneaux';

    // blah blah..
    $lang->about_category_title = 'Entrez le nom de la catégorie, SVP.';
    $lang->about_expand = 'Si vous cochez la case, ce sera toujours tendu';
    $lang->about_category_group_srls = 'Le groupe sélectionné seulement pourra voir ces catégories. (Ouvrir manuellement le fiche de xml, c\'est l\'exposer)';
    $lang->about_layout_setup = 'Vous pouvez manuellement modifier le code de Mise en Page du Panneau. Insérez ou arrangez le code de Widget n\'importe où vous voulez.';
    $lang->about_board_category = 'Vous pouvez créer des catégories d\'affichage dans le tableau. Quand la catégorie d\'affichage est cassé, essayez manuellement rétablir la cachette du fichier.';
    $lang->about_except_notice = "L'Article de Notice ne sera exposé sur la liste normale.";
    $lang->about_board = "Ce module se sert à créer et arranger des Panneaux.\nAprés avoir créé un module, si vous cliquez le nom sur le liste, vous pouvez configurer spécifiquement.\nFaites attention quand vous nomer un module du Panneau, car ce sera URL. (ex : http://domain/zb/?mid=nom_de_module)"; 
	$lang->about_consultation = "Les membres non-administratifs verront seulement les ariticles d\'eux-même.\nNon-membres ne pourraient pas écrire des articles quand la Consultation est appliqué.";
    $lang->about_admin_mail = '글이나 댓글이 등록될때 등록된 메일주소로 메일이 발송됩니다<br /> ,(콤마)로 연결시 다수의 메일주소로 발송할 수 있습니다.';
?>
