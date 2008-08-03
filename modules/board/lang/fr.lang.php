<?php
    /**
     * @fichier  modules/board/lang/ fr.lang.php
     * @auteur zero (zero@nzeo.com) Traduit par Pierre Duvent(PierreDuvent@gamil.com)
     * @brève  Paquet du langage en français pour le module de Panneau d\'Affichage
     **/

    $lang->board = "Panneau"; 

    $lang->except_notice = "Exclure des Notices";

    $lang->cmd_manage_menu = 'Administration de Menu';
    $lang->cmd_make_child = 'Ajouter une catégorie inférieure';
    $lang->cmd_enable_move_category = "Bouger la position de la catégorie (Cochez la case et puis glisser la catégorie que vous voulez déplacer)";

    // Item
    $lang->parent_category_title = 'catégorie supérieure';
    $lang->category_title = 'Catégorie';
    $lang->expand = 'Etendre';
    $lang->category_group_srls = 'Groupe Accessible';
    $lang->search_result = 'Résultat de la Recherche';
    $lang->consultation = 'Consultation';
    $lang->admin_mail = 'Mél de l\'administrateur';

    // Mots utilisés en bouton
    $lang->cmd_board_list = 'Liste des Panneaux';
    $lang->cmd_module_config = 'Configuration commun pour les Panneaux';
    $lang->cmd_view_info = 'Information des Panneaux';

    // murmure..
    $lang->about_category_title = 'Entrez le nom de la catégorie, S.V.P.';
    $lang->about_expand = 'Si vous cochez la case à cocher, ce sera toujours tendu';
    $lang->about_category_group_srls = 'Le groupe choisi seulement pourra utiliser la catégorie courante';
    $lang->about_layout_setup = 'Vous pouvez manuellement modifier le code de Mise en Page du blogue. Insérez ou administrez le code de Gadget n\'importe où vous voulez.';
    $lang->about_board_category = 'Vous pouvez créer des catégories de Panneau d\'Affichage. Quand la catégorie d\'affichage est cassé, essayez manuellement rétablir l\'antémémoire du fichier.';
    $lang->about_except_notice = "Le titre de Notice dont l'article se représentera toujours en tête de la liste ne sera exposé sur la liste générale.";
    $lang->about_board = "Ce module se sert à créer et à administrer des Panneaux d'Affichage.\nAprés avoir créé un module, si vous cliquez le nom sur le liste, vous pouvez configurer particulièrement.\nFaites attention quand vous nomer un module du Panneau, car ce sera URL. (ex : http://domain/zb/?mid=nom_de_module)"; 
	$lang->about_consultation = "Les membres non-administratifs verront seulement les ariticles d\'eux-même.\nNon-membres ne pourraient pas écrire des articles quand la Consultation est appliqué.";
    $lang->about_admin_mail = 'Un message éléctronique sera envoyé à l\'adresse inscrite quand un article ou commentaire se soumet. <br />On peut inscrire multiple adresses délimité par les virgules.';
?>
