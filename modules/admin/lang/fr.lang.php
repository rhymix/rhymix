<?php
    /**
     * @file   modules/admin/lang/fr.lang.php
     * @author NHN (developers@xpressengine.com)  Traduit par Pierre Duvent(PierreDuvent@gamil.com)
     * @brief  Paquet du langage en français pour le module d\'Administration
     **/

    $lang->admin_info = 'Informations d\'Administrateur';
    $lang->admin_index = 'Page de l\'indice pour l\'Administrateur';
    $lang->control_panel = 'Control panel';
    $lang->start_module = 'Start Module';
    $lang->about_start_module = 'Vous pouvez spécifier début module par défaut.';

    $lang->module_category_title = array(
        'service' => 'Service Setting',
        'member' => 'Member Setting',
        'content' => 'Content Setting',
        'statistics' => 'Statistics',
        'construction' => 'Construction',
        'utility' => 'Utility Setting',
        'interlock' => 'Interlock Setting',
        'accessory' => 'Accessories',
        'migration' => 'Data Migration',
        'system' => 'System Setting',
    );

    $lang->newest_news = "Dernières Nouvelles";
    
    $lang->env_setup = "Configuration";
    $lang->default_url = "기본 URL";
    $lang->about_default_url = "XE 가상 사이트(cafeXE등)의 기능을 사용할때 기본 URL을 입력해 주셔야 가상 사이트간 인증 연동이 되고 게시글/모듈등의 연결이 정상적으로 이루어집니다. (ex: http://도메인/설치경로)";


    $lang->env_information = "Informations de l'Environnement";
    $lang->current_version = "Version Courante";
    $lang->current_path = "Chemin Installé";
    $lang->released_version = "Dernière Version";
    $lang->about_download_link = "Nouvelle version est disponible.\nPour télécharger la dernière version, cliquez le lien.";
    
    $lang->item_module = "Liste des Modules";
    $lang->item_addon  = "Liste des Compagnons";
    $lang->item_widget = "Liste des Gadgets";
    $lang->item_layout = "Liste des Mises en Pages";

    $lang->module_name = "Nom de Module";
    $lang->addon_name = "Nom de Compagnon";
    $lang->version = "Version";
    $lang->author = "Auteur";
    $lang->table_count = "Somme de Tables";
    $lang->installed_path = "Chemin Installé";

    $lang->cmd_shortcut_management = "Editer le Menu";

    $lang->msg_is_not_administrator = 'Administrateur seulement';
    $lang->msg_manage_module_cannot_delete = 'On ne peut pas supprimer les raccourcis pour les modules, les compagnons, les mises en page ou les gadgets';
    $lang->msg_default_act_is_null = 'on ne peut pas enrégistrer les raccourcis parce que les Actions Par Défaut de l\'Administrateur ne sont pas établies';

    $lang->welcome_to_xe = 'Bienvenue sur la Page d\'Administration du XE';
    $lang->about_admin_page = "La Page d\'Administration est encore en train de développer,\nNous allons ajouter des contenus essentiels par accepter beauoup de bons suggestions pendant Béta Proche.";
    $lang->about_lang_env = "Vous pouvez fixer la Langue Par Défaut par cliquer le boutton [Conserver] au-dessous. Les visiteurs vont voir tous les menus et les messages en langue que vous choisissez.";

    $lang->xe_license = 'XE s\'applique la GPL';
    $lang->about_shortcut = 'Vous pouvez supprimer les raccourcis pour les modules qui sont enrgistrés sur le liste des modules qui sont utilisés fréquemment';

    $lang->yesterday = "Yesterday";
    $lang->today = "Today";

    $lang->cmd_lang_select = "langue";
    $lang->about_cmd_lang_select = "La langue choisie seulement sera servie";
    $lang->about_recompile_cache = "Vous pouvez arranger les fichiers inutils ou les fichiers invalides d'antémémoire";
    $lang->use_ssl = "Utiliser SSL";
    $lang->ssl_options = array(
        'none' => "Ne Pas utiliser",
        'optional' => "Optionnel",
        'always' => "Toujours"
    );
    $lang->about_use_ssl = "Si l'on choisit 'Optionnel' , on utilise protocole SSL seulement dans quelques services comme inscription ou modification. Si l'on choisit 'Toujours', on utilise protocole SSL dans tous les services.";
    $lang->server_ports = "déclarer le port de serveur";
    $lang->about_server_ports = "Si l'on ne veut pas utiliser le port 80 pour HTTP mais un autre port, ou bien, si l'on ne veut pas utiliser le port 443 pour HTTPS mais un autre port, on doit déclarer les ports.";
    $lang->use_db_session = '인증 세션 DB 사용';
    $lang->about_db_session = '인증시 사용되는 PHP 세션을 DB로 사용하는 기능입니다.<br/>웹서버의 사용율이 낮은 사이트에서는 비활성화시 사이트 응답 속도가 향상될 수 있습니다<br/>단 현재 접속자를 구할 수 없어 관련된 기능을 사용할 수 없게 됩니다.';
    $lang->sftp = "Use SFTP";
    $lang->ftp_get_list = "Get List";
    $lang->ftp_remove_info = 'Remove FTP Info.';
	$lang->msg_ftp_invalid_path = 'Failed to read the specified FTP Path.';
	$lang->msg_self_restart_cache_engine = 'Please restart Memcached or cache daemon.';
	$lang->mobile_view = 'Use Mobile View';
	$lang->about_mobile_view = 'If accessing with a smartphone, display content with mobile layout.';
    $lang->autoinstall = 'EasyInstall';

    $lang->last_week = 'Last week';
    $lang->this_week = 'This week';
?>
