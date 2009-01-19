<?php
    /**
     * @file   modules/admin/lang/fr.lang.php
     * @author zero (zero@nzeo.com)  Traduit par Pierre Duvent(PierreDuvent@gamil.com)
     * @brief  Paquet du langage en français pour le module d\'Administration
     **/

    $lang->admin_info = 'Informations d\'Administrateur';
    $lang->admin_index = 'Page de l\'indice pour l\'Administrateur';

    $lang->module_category_title = array(
        'service' => 'Modules de Service',
        'manager' => 'Modules Administratif',
        'utility' => 'Modules d\'Utilité',
        'accessory' => 'Modules Additionnels',
        'base' => 'Modules Fondamentaux',
    );

    $lang->newest_news = "Dernières Nouvelles";
    
    $lang->env_setup = "Configuration";

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
?>
