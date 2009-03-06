<?php
    /**
     * @file   modules/document/lang/fr.lang.php
     * @author zero <zero@nzeo.com>  Traduit par Pierre Duvent <Pierreduvent@gmail.com>
     * @brief  Paquet du langage en français pour le module de Document
     **/

    $lang->document_list = 'Liste des Documents';
    $lang->thumbnail_type = 'Type de la Vignette';
    $lang->thumbnail_crop = 'Rogner';
    $lang->thumbnail_ratio = 'Proportion';
    $lang->cmd_delete_all_thumbnail = 'Supprimer toutes les vignettes';
    $lang->title_bold = 'Gras';
    $lang->title_color = 'Couleur';
    $lang->new_document_count = '새글';

    $lang->parent_category_title = 'catégorie supérieure';
    $lang->category_title = 'Catégorie';
    $lang->category_color = '분류 폰트색깔';
    $lang->expand = 'Etendre';
    $lang->category_group_srls = 'Groupe Accessible';
    $lang->cmd_make_child = 'Ajouter une catégorie inférieure';
    $lang->cmd_enable_move_category = "Bouger la position de la catégorie (Cochez la case et puis glisser la catégorie que vous voulez déplacer)";
    $lang->about_category_title = 'Entrez le nom de la catégorie, S.V.P.';
    $lang->about_expand = 'Si vous cochez la case à cocher, ce sera toujours tendu';
    $lang->about_category_group_srls = 'Le groupe choisi seulement pourra utiliser la catégorie courante';
    $lang->about_category_color = 'You can set font color of category.';

    $lang->cmd_search_next = 'Recherche Suivante';

    $lang->cmd_temp_save = 'Conserver temporairement';

	$lang->cmd_toggle_checked_document = 'Renverser les choisis';
    $lang->cmd_delete_checked_document = 'Supprimer les choisis';
    $lang->cmd_document_do = 'Vous voudriez..';

    $lang->msg_cart_is_null = 'Choisissez les articles à supprimer, S.V.P.';
    $lang->msg_category_not_moved = 'Ne peut(peuvent) pas être bougé(s)';
    $lang->msg_is_secret = 'Cet article est secret';
    $lang->msg_checked_document_is_deleted = '%d article(s) est(sont) supprimé(s)';

    $lang->move_target_module = "Module à déménager";

	// Search targets in admin page
        $lang->search_target_list = array(
        'title' => 'Titre',
        'content' => 'Contenu',
        'user_id' => 'Compte',
        'member_srl' => 'Numéro de Série du Membre',
        'user_name' => 'Nom',
        'nick_name' => 'Surnom',
        'email_address' => 'Mél',
        'homepage' => 'Page d\'accueil',
        'is_notice' => 'Notice',
        'is_secret' => 'Secret',
        'tags' => 'Balises',
        'readed_count' => 'Vues (surplus)',
        'voted_count' => 'Recommandés (surplus)',
        'comment_count ' => 'Commentaires (surplus)',
        'trackback_count ' => 'Rétroliens (surplus)',
        'uploaded_count ' => 'Fichiers Attachés (surplus)',
        'regdate' => 'Enrégistré',
        'last_update' => 'La Dernière Mise à Jour',
        'ipaddress' => 'Adresse IP',
    );
    $lang->alias = "Alias";
    $lang->history = "히스토리";
    $lang->about_use_history = "히스토리 기능의 사용여부를 지정합니다. 히스토리 기능을 사용할 경우 문서 수정시 이전 리비전을 기록하고 복원할 수 있습니다.";
    $lang->trace_only = "흔적만 남김";
?>
