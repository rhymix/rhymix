<?php
    /**
     * @file   modules/menu/lang/fr.lang.php
     * @author zero <zero@nzeo.com> Traduit par Pierre Duvent <PierreDuvent@gamail.com>
     * @brief Paque du langage en français pour le module de Menu
     **/

    $lang->cmd_menu_insert = 'Créer un Menu';
    $lang->cmd_menu_management = 'Administer des Menus';

    $lang->menu = 'Menu'; 
    $lang->menu_count = 'Somme de menu';
    $lang->menu_management = 'Administration de Menu';
    $lang->depth = 'Niveau';
    $lang->parent_menu_name = 'Nom de Menu supérieur';
    $lang->menu_name = 'Nom de Menu';
    $lang->menu_srl = 'Numéro de série de Menu';
    $lang->menu_id = 'Nom d\'Identité de Menu';
    $lang->menu_url = 'URL de Menu';
    $lang->menu_open_window = 'Ouvrire une nouvelle fenêtre';
    $lang->menu_expand = 'Étendre';
    $lang->menu_img_btn = 'Bouton en Image';
    $lang->menu_normal_btn = 'Normal';
    $lang->menu_hover_btn = 'Survolé';
    $lang->menu_active_btn = 'Choisi';
    $lang->menu_group_srls = 'Groupes qui peuvent accéder';
    $lang->layout_maker = "Auteur de la Mise en Page";
    $lang->layout_history = "Histoire des Mises à Jour";
    $lang->layout_info = "Information de la Mise en Page";
    $lang->layout_list = 'Liste des Mises en Page';
    $lang->downloaded_list = 'Liste de Téléchargement';
    $lang->limit_menu_depth = 'Niveau permis d\'exposer';

    $lang->cmd_make_child = 'Ajouter un menu inférieur';
    $lang->cmd_move_to_installed_list = "Voir la liste créé";
    $lang->cmd_enable_move_menu = "Bouger le Menu (glisser-déposer un menu après cocher)";
    $lang->cmd_search_mid = "Rechercher mid";

    $lang->msg_cannot_delete_for_child = 'Un menu qui a des menus inférieurs ne peut pas être supprimé.';

    $lang->about_title = 'Entrez un titre facile à vérifier quand on le connecte à un module.';
    $lang->about_menu_management = "Administration de Menu vous permet de composer le menu dans la Mise en Page que vous choisissez.\nVous pouvez créer le menu jusqu'au niveau permis et entrer des informations détaillées si vou cliquez le menu.\nMenu sera étendu si vous cliquez l'image de dossier.\nSi le menu n'est pas représenté normalement, rafraîchir les informations en cliquant le bouton \"Recréer \'antémémoire de fichier\".\n* Menu cré qui passe plus que le niveau permis pourra être représenté incorrectement.";
    $lang->about_menu_name = 'Ce nom sera représenté comme le nom de menu si ce n\'est pas le bouton en image ou le bouton pour administrer.';
    $lang->about_menu_url = "C'est le URL où l'on bouge quand on choisit le menu.<br />Vous pouvez entrer la valeur d'identité(nom d'idendité) seulement pour lier à un autre module.<br />Si nul contenu n'existe, rien n'aura lieu même si l'on clique le menu.";
    $lang->about_menu_open_window = 'Vous pouvez faire ouvrir une page dans une nouvelle fenêtre quand le menu est cliqué.';
    $lang->about_menu_expand = 'L\'Arbre de Menu(tree_menu.js) peut faire resté le menu étendu toujours.';
    $lang->about_menu_img_btn = 'Si vous enrégistez un bouton en image, l\'image remplacera automatiquement le bouton en texte, et ce sera représenté dans la Mise en Page.';
    $lang->about_menu_group_srls = 'Si vous choisissez un groupe, les membres de ce groupe seulement peuvent voir le menu. (Si l\'on ouvre un fichier xml, le fichier sera exposé.)';

    $lang->about_menu = "Le Module de Menu vous aidrera à établir un site complet par l'administration confortable qui arrange les modules créés et liens à la mise en page sans aucun travaux manuels.\nMenu n'est pas un administrateur du Site, mais il a seulement l'information qui peut lier les modules à la mise en page, et on peut représenter les menu en formes diverses par la mise en page.";

    $lang->alert_image_only = "Fichiers d'image seulement peuvent être enrégistrés.";
?>
