<?php
    /**
     * @file  modules/board/lang/ fr.lang.php
     * @author zero (zero@nzeo.com) Traduit par Pierre Duvent(PierreDuvent@gamil.com)
     * @brief  Paquet de la langue fondamental pour le module de Panneau d\'Affichage
     **/

    $lang->board = "Panneau"; 

    $lang->except_notice = "Exclure des Notices";

    $lang->cmd_manage_menu = 'Arrangement de Menu';
    $lang->cmd_make_child = 'Ajouter une enfant catgorie';
    $lang->cmd_enable_move_category = "Bouger la position de la catgorie (Cochez la case et puis glisser le menu que vous voulez dplacer)";

    // Item
    $lang->parent_category_title = 'catgorie mre';
    $lang->category_title = 'Catgorie';
    $lang->expand = 'Etendre';
    $lang->category_group_srls = 'Groupe Accessible';
    $lang->search_result = 'Rsultat de la Recherche';
    $lang->consultation = 'Consultation';

    // Mots utiliss en bouton
    $lang->cmd_board_list = 'Liste des Panneaux';
    $lang->cmd_module_config = 'Configuration commun pour les Panneau';
    $lang->cmd_view_info = 'Information des Panneau';

    // blah blah..
    $lang->about_category_title = 'Entrez le nom de la catgorie, SVP.';
    $lang->about_expand = 'Si vous cochez la case, ce sera toujours tendu';
    $lang->about_category_group_srls = 'Le groupe slectionn seulement pourra voir ces catgories. (Ouvrir manuellement le fiche de xml, c\'est l\'exposer)';
    $lang->about_layout_setup = 'Vous pouvez manuellement modifier le code de Mise en Page du Panneau. Insrez ou arrangez le code de Widget n\'importe o vous voulez.';
    $lang->about_board_category = 'Vous pouvez crer des catgories d\'affichage dans le tableau. Quand la catgorie d\'affichage est cass, essayez manuellement rtablir la fichier cache.';
    $lang->about_except_notice = "L\'Article de Notice ne sera expos sur la liste normale.";
    $lang->about_board = "Ce module se sert à créer et arranger des Panneau.\nAprés avoir créé un module, si vous cliquez le nom sur le liste, vous pouvez configurer spécifiquement.\nFaites attention quand vous choisissez le nom du module du Panneau, car ce sera URL. (ex : http://domain/zb/?mid=nom_de_module)"; 
	$lang->about_consultation = "Les membres non-administratifs verront seulement les ariticles d\'eux-mme.\nNon-membres ne pourraient pas crire des articles quand la Consultation est appliqu.";
?>
