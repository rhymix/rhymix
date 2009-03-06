<?php
    /**
     * @file   modules/module/lang/fr.lang.php
     * @author zero (zero@nzeo.com) traduit par Pierre duvent <PierreDuvent@gmail.com>
     * @brief  Paque du langage en français pour le module de Module
     **/

    $lang->virtual_site = "Virtual Site";
    $lang->module_list = "Liste des Modules";
    $lang->module_index = "Liste des Modules";
    $lang->module_category = "Catégorie des Modules";
    $lang->module_info = "Information de Module";
    $lang->add_shortcut = "Ajouter un raccourci dans le menu pour l'administrateur";
    $lang->module_action = "Actions";
    $lang->module_maker = "Développeur du Module";
    $lang->module_license = 'Licence';
    $lang->module_history = "Histoire de Mise à Jour";
    $lang->category_title = "Titre de la Catégorie";
    $lang->header_text = 'Texte en-tête';
    $lang->footer_text = 'Text au bas de page';
    $lang->use_category = 'Utiliser catégorie';
    $lang->category_title = 'Titre de la Catégorie';
    $lang->checked_count = 'somme des Articles choisis';
    $lang->skin_default_info = 'Information fondamental de l\'habillage';
    $lang->skin_author = 'Developpeur de l\'habillage';
    $lang->skin_license = 'Licence';
    $lang->skin_history = 'Histoire des Mises à jour';
    $lang->module_copy = "Copier un Module";
    $lang->module_selector = "Module Selector";
    $lang->do_selected = "선택된 것들을...";
    $lang->bundle_setup = "일괄 기본 설정";
    $lang->bundle_addition_setup = "일괄 추가 설정";
    $lang->bundle_grant_setup = "일괄 권한 설정";
    $lang->lang_code = "언어 코드";
    $lang->filebox = "파일박스";

    $lang->header_script = "Script en-tête";
    $lang->about_header_script = "Vous pouvez entrer un script en html par vous-même entre &lt;header&gt; et &lt;/header&gt;.<br />Vous pouvez utiliser &lt;script, &lt;style ou &lt;meta tag";

    $lang->grant_access = "Access";
    $lang->grant_manager = "Management";

    $lang->grant_to_all = "All users";
    $lang->grant_to_login_user = "Logged users";
    $lang->grant_to_site_user = "Joined users";
    $lang->grant_to_group = "Specification group users";

    $lang->cmd_add_shortcut = "Ajouter un raccourci";
    $lang->cmd_install = "Installer";
    $lang->cmd_update = "Mettre à Jour";
    $lang->cmd_manage_category = 'Administrer des Catégories';
    $lang->cmd_manage_grant = 'Administrer des Permissions';
    $lang->cmd_manage_skin = 'Administrer des Habillages';
    $lang->cmd_manage_document = 'Administrer des Articles';
    $lang->cmd_find_module = '모듈 찾기';
    $lang->cmd_find_langcode = 'Find lang code';

    $lang->msg_new_module = "Créer un module";
    $lang->msg_update_module = "Modifier un module";
    $lang->msg_module_name_exists = "Le nom existe déjà. Essayez un autre nom, S.V.P.";
    $lang->msg_category_is_null = 'Il n\'y a pas de catégorie enrégistrée.';
    $lang->msg_grant_is_null = 'Il n\'y a pas de liste de permission.';
    $lang->msg_no_checked_document = 'Pas un article est choisi.';
    $lang->msg_move_failed = 'Echoué de bouger';
    $lang->msg_cannot_delete_for_child = 'On ne peut pas supprimer une catégorie qui a des catégories inférieures.';

    $lang->about_browser_title = "C'est la valeur qui se représentera dans le titre de navigateur Web. Ce sera encore utilisé dans RSS/Rétrolien.";
    $lang->about_mid = "Le nom de module sera utilisé comme http://adresse/?mid=ModuleName.\n(alphabet anglais + [alphabet anglais, nombres, et soulignement(_)] sont seulement permis)";
    $lang->about_default = "Si c'est coché, on verra ce module quand on connecte ce site sans aucune valeur de mid(mid=Nulle Valeur).";
    $lang->about_module_category = "Ça vous permet d'administrer le module par la catégorie.\nOn peut administrer la classification des modules à <a href=\"./?module=admin&amp;act=dispModuleAdminCategory\">Administration des modules > Catégorie des Modules </a>.";
    $lang->about_description= 'C\'est la description pour la facilité à administrer.';
    $lang->about_default = 'Si c\'est coché, on verra ce module quand on connecte ce site sans aucune valeur de mid(mid=Nulle Valeur).';
    $lang->about_header_text = 'Ce contenu sera exposé en tête du module.(balise en html est disponible)';
    $lang->about_footer_text = 'Ce contenu sera exposé en bas du module.(balise en html est disponible)';
    $lang->about_skin = 'Vous pouvez choisir un habillage pour le module.';
    $lang->about_use_category = 'Cochez pour utiliser la fonction de catégorie, .';
    $lang->about_list_count = 'Vous pouvez configurer combien d\'articles soient exposés dans une page.(20 par défaut)';
	$lang->about_search_list_count = 'Vous pouvez configurer combien d\'articles soient exposés quand vous utilisez la fonction de recherche ou de catégorie. (20 par défaut)';
    $lang->about_page_count = 'Vous pouvez configurer combien de liens pour les Pages à Bouger en bas de chaque page.(10 par défaut)';
    $lang->about_admin_id = 'Vous pouvez désigner un directeur qui aura tous les permissions sur le module.\nVous pouvez entrer plusieurs compte en utilisant.';
    $lang->about_grant = 'Si vous ne donnez pas la permission à aucune personne, même les membres qui n\'a pas ouvert la connexion auront la permission. '; 
    $lang->about_grant_deatil = '가입한 사용자는 cafeXE등 분양형 가상 사이트에 가입을 한 로그인 사용자를 의미합니다';
    $lang->about_module = "XE se compose des modules sauf la bibliothèque fondamental.\nLe module [Administration des Modules] montera tous les modules installés et vous aidera les administrer.";

	$lang->about_extra_vars_default_value = 'Si plusieurs valeurs sont nécessaires, vous pouvez les connecter avec la virgule(,).';
    $lang->about_search_virtual_site = "가상 사이트(카페XE등)의 도메인을 입력하신 후 검색하세요.<br/>가상 사이트이외의 모듈은 내용을 비우고 검색하시면 됩니다.  (http:// 는 제외)";
    $lang->about_langcode = "언어별로 다르게 설정하고 싶으시면 언어코드 찾기를 이용해주세요";
    $lang->about_file_extension= "%s 파일만 가능합니다.";
?>
