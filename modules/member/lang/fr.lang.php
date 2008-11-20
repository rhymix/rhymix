<?php
    /**
     * @file   fr.lang.php
     * @author zero (zero@nzeo.com)  Traduit par Pierre Duvent (PierreDuvent@gmail.com)
     * @brief  Paquet du langage en français pour le module de Membre 
     **/

    $lang->member = 'Membre';
    $lang->member_default_info = 'Information fondamentale';
    $lang->member_extend_info = 'Information additionnelle';
    $lang->default_group_1 = "Membre Associé";
    $lang->default_group_2 = "Membre Régulier";
    $lang->admin_group = "Groupe des administrateurs";
    $lang->keep_signed = 'Garder la session ouverte';
    $lang->remember_user_id = 'Mémorisez mon Compte';
    $lang->already_logged = "La session est déjà ouverte";
    $lang->denied_user_id = 'C\'est un comte interdit.';
    $lang->null_user_id = 'Entrez le compte, S.V.P.';
    $lang->null_password = 'Entrez le mot de passe, S.V.P.';
    $lang->invalid_authorization = 'Le compte n\'est pas encore certifié.';
    $lang->invalid_user_id= "C'est un compte qui n'existe pas.";
    $lang->invalid_password = 'C\'est un mot de passe invalide';
    $lang->allow_mailing = 'Inscrire au Mailing';
    $lang->denied = 'Arrêté à utiliser';
    $lang->is_admin = 'Permission Superadministrative';
    $lang->group = 'Groupe assigné';
    $lang->group_title = 'Nom du Groupe';
    $lang->group_srl = 'Numéro du Groupe';
    $lang->signature = 'Signature';
    $lang->profile_image = 'Image du profil';
    $lang->profile_image_max_width = 'Largeur Maximum';
    $lang->profile_image_max_height = 'Hauteur Maximum';
    $lang->image_name = 'Nom en Image';
    $lang->image_name_max_width = 'Largeur Maximum';
    $lang->image_name_max_height = 'Hauteur Maximum';
    $lang->image_mark = 'Marque en Image';
    $lang->image_mark_max_width = 'Largeur Maximum';
    $lang->image_mark_max_height = 'Hauteur Maximum';
	$lang->signature_max_height = 'Hauteur Maximum de la Signature';
    $lang->enable_openid = 'Permettre OpenID';
    $lang->enable_join = 'Permettre l\'inscription';
    $lang->enable_confirm = 'Utiliser Authentification par mél';
	$lang->enable_ssl = 'Utiliser SSL';
	$lang->security_sign_in = 'Ouvrir une Session en utilisant sécurité rehaussé';
    $lang->limit_day = 'Jour de Limite Temporaire';
    $lang->limit_date = 'Jour de Limite';
    $lang->after_login_url = 'URL après la connexion';
    $lang->after_logout_url = 'URL après la déconnexion ';
    $lang->redirect_url = 'URL après l\'Inscription';
    $lang->agreement = 'Accord de l\'Inscription comme Membre';
	$lang->accept_agreement = 'D\'accord';
    $lang->member_info = 'Information de Membre';
    $lang->current_password = 'Mot de Passe courant';
    $lang->openid = 'OpenID';
    $lang->allow_message = '쪽지 허용';
    $lang->allow_message_type = array(
            'Y' => '모두 허용',
            'F' => '등록된 친구들만 허용',
            'N' => '모두 금지',
    );
    $lang->about_allow_message = '쪽지 허용 방법 및 대상을 지정할 수 있습니다';

    $lang->webmaster_name = "Nom de Webmestre";
    $lang->webmaster_email = "Mél de Webmestre";

    $lang->about_keep_signed = 'Malgré que le navigateur est fermé, votre session peut rester ouverte. \n\nSi vous utilisez cette fonction sur l\'ordinateur publique, vos informations privé peut être exposé. Nous vous recommandons de ne pas utiliser cette fonctions sur l\'ordinateur publique.';
    $lang->about_webmaster_name = "Entrez le nom de webmestre qui va être utilisé pour le mél de certification ou l\'autre administration du site. (défaut : webmestre)";
    $lang->about_webmaster_email = "Entrez l\'adresse du mél de webmestre, S.V.P.";

    $lang->search_target_list = array(
        'user_id' => 'Compte',
        'user_name' => 'Nom',
        'nick_name' => 'Surnom',
        'email_address' => 'Mél',
        'regdate' => 'Jour d\'Inscription',
        'last_login' => 'Jour de la connexion dernière',
        'extra_vars' => 'Variables additionnels ',
    );

    $lang->cmd_login = 'Connexion';
    $lang->cmd_logout = 'Déconnexion';
    $lang->cmd_signup = 'Inscription';
    $lang->cmd_modify_member_info = 'Modifier Mon Information';
    $lang->cmd_modify_member_password = 'Modifier le Mot de Passe';
    $lang->cmd_view_member_info = 'Voir Mon Information';
    $lang->cmd_leave = 'Quitter';
    $lang->cmd_find_member_account = 'J\'ai perdu le compte / le mot de passe';

    $lang->cmd_member_list = 'Liste de Membres';
    $lang->cmd_module_config = 'Configuration par Défaut';
    $lang->cmd_member_group = 'Administrer des Groupes';
    $lang->cmd_send_mail = 'Envoyer des Méls';
    $lang->cmd_manage_id = 'Administrer les Comptes Interdits';
    $lang->cmd_manage_form = 'Administrer la Forme d\'Inscription';
    $lang->cmd_view_own_document = 'Voir les Articles écrits';
    $lang->cmd_trace_document = 'Tracer les Articles écrits';
    $lang->cmd_trace_comment = 'Tracer les Commentaires écrits';
    $lang->cmd_view_scrapped_document = 'Voir les Coupures';
    $lang->cmd_view_saved_document = 'Voir les Articles conservés';
    $lang->cmd_send_email = 'Envoyer des Méls';

    $lang->msg_email_not_exists = "L'adresse mél n'existe pas.";

    $lang->msg_alreay_scrapped = 'Cet article est déjà coupé.';

    $lang->msg_cart_is_null = 'Choisissez l\'Objet, S.V.P.';
    $lang->msg_checked_file_is_deleted = '%d fichier(s) attaché(s) est(sont) supprimé(s)';

    $lang->msg_find_account_title = 'Information de compte';
    $lang->msg_find_account_info = 'Voilà votre information de compte.';
    $lang->msg_find_account_comment = 'Le Mot de Passe sera modifié comme celui ci-dessus si vous cliquez le lien ci-dessous.<br />Modifiez le Mot de Passe après ouvrir la connexion, S.V.P.';
    $lang->msg_confirm_account_title = 'Mél à confirmer l\'Authentification';
    $lang->msg_confirm_account_info = 'Voilà votre Information de l\'inscription du Compte:';
    $lang->msg_confirm_account_comment = 'Cliquez le lien de confirmation suivant pour compléter votre inscription.';
    $lang->msg_auth_mail_sent = 'Le mél de certification a été envoyé à %s. Vérifiez votre mél.';
    $lang->msg_confirm_mail_sent = 'On a justement envoyé un mél de confirmation à %s. Cliquez sur le lien de confirmation dans le mél pour compléter l\'inscription.';
    $lang->msg_invalid_auth_key = 'Cette Requête à Certifier est invalide.<br />Essayez encore une fois à retrouver votre information de compte ou contactez l\'administrateur.';
    $lang->msg_success_authed = 'Votre compte a été certifié avec succès et ouvert une session. \n Modifiez le Mot de Passe après vous ouvrez une session en utilisant le Mot de Passe dans le mél.';
    $lang->msg_success_confirmed = 'L\'authentification est complétée avec succèss.';

    $lang->msg_new_member = 'Ajouter un membre';
    $lang->msg_update_member = 'Modifier l\'Information de Membre';
    $lang->msg_leave_member = 'Sécession';
    $lang->msg_group_is_null = 'Il n\'y a pas de groupe enrégistré';
    $lang->msg_not_delete_default = 'Elément fondamental ne pourra pas être supprimé';
    $lang->msg_not_exists_member = "Membre Invalide";
    $lang->msg_cannot_delete_admin = 'Le Compte de l\'Administrateur ne pourra pas être supprimé. Annulez l\'administration du compte et essayez encore une fois.';
    $lang->msg_exists_user_id = 'Le compte existe déjà. Essayez un autre.';
    $lang->msg_exists_email_address = 'L\'adresse mél existe déjà. Essayez une autre.';
    $lang->msg_exists_nick_name = 'Le surnom existe déjà. Essayez un autre.';
    $lang->msg_signup_disabled = 'Vous ne pouvez pas vous inscrire.';
    $lang->msg_already_logged = 'Vous vous êtes déjà inscrit(e).';
    $lang->msg_not_logged = 'Ouvrez une session d\'abord';
    $lang->msg_insert_group_name = 'Entrez le nom de groupe, S.V.P.';
    $lang->msg_check_group = 'Choisissez le groupe';

    $lang->msg_not_uploaded_profile_image = 'L\'image de Profil n\'a pas pu être enrégistré';
    $lang->msg_not_uploaded_image_name = 'Le nom d\'image n\'a pas pu être enrégistré';
    $lang->msg_not_uploaded_image_mark = 'La marque en image n\'a pas pu être enrégistrée';

    $lang->msg_accept_agreement = 'Vous devez agréer l\'accord'; 

    $lang->msg_user_denied = 'Le compte que vous avez entré est suspendu';
    $lang->msg_user_not_confirmed = 'Vous n\'avez pas encore authentifié. Verifiez votre mél, S.V.P.';
    $lang->msg_user_limited = 'Vous avez entré un compte qui peut être utilisé depuis %s';

    $lang->about_user_id = 'Le compte d\'utilisateur doit être long de 3~20 lettres et se composer des alphabets et des chiffres avec un alphabet au premier.';
    $lang->about_password = 'Le Mot de Passe doit être long de 6~20 lettres.';
    $lang->about_user_name = 'Le Nom doit être long de 2~20 lettres.';
    $lang->about_nick_name = 'Le Surnom doit être long de 2~20 lettres.';
    $lang->about_email_address = 'L\'Adresse mél sera utilisé à modifier/trouver le Mot de Passe après la certification en mél.';
    $lang->about_homepage = 'Entrez si vous avez un site Web.';
    $lang->about_blog_url = 'Entrez si vous avez un blogue.';
    $lang->about_birthday = 'Entrez votre anniversaire.';
    $lang->about_allow_mailing = "Si vous ne vous inscrivez pas sur mailing, vous ne pouvez pas recevoir le mél du groupe.";
    $lang->about_denied = 'Cocher pour interdire le compte';
    $lang->about_is_admin = 'Cocher pour autoriser la permission de Superadministrateur';
    $lang->about_member_description = "La description de l\'Administrateur sur le membre";
    $lang->about_group = 'Un compte peut appartenir aux plusieurs groupes.';

    $lang->about_column_type = 'Choisissez la format que vous voulez ajouter';
    $lang->about_column_name = 'Entrez le nom composé en alphabet qui peut être utilisé dans le modèle (nom comme variable)';
    $lang->about_column_title = 'Ce titre sera exposé sur la formule d\'inscription ou sur l\'écran pour modifier/voir les informations de membre';
    $lang->about_default_value = 'Vous pouvez mettre les valeurs par défaut';
    $lang->about_active = 'Cochez si vous voulez l\'exposer sur la formule d\'inscription';
    $lang->about_form_description = 'Si vous entrez la description, elle sera exposé sur la formule d\'incription';
    $lang->about_required = 'Si vous cochez, ce sera obligatoire';

    $lang->about_enable_openid = 'Cochez si vous voulez permettre OpenID';
    $lang->about_enable_join = 'Cochez si vous voulez permettre l\'inscription';
    $lang->about_enable_confirm = 'Envoyer mél de confirmation pour compléter l\'inscription.';
    $lang->about_enable_ssl = 'Les informations personnelles (Inscription / Modification des informations du membre / Connexion) peuvent être envoyées comme mode SSL(https) si le serveur offre le service SSL.';
    $lang->about_limit_day = 'Vous pouvez limiter le jour de certification après l\'inscription';
    $lang->about_limit_date = 'Utilisateur ne peut pas ouverir la connexion jusqu\'au jour assigné';
    $lang->about_after_login_url = 'Vous pouvez indiquer URL où l\'on va après la connexion. Le vide signifie la page courante.';
    $lang->about_after_logout_url = 'Vous pouvez indiquer URL où l\'on va après la déconnexion. Le vide signifie la page courrante.';
    $lang->about_redirect_url = 'Entrez URL où l\'utilisateur irra après l\'inscription, S.V.P. Si c\'est vide, ce sera la page précédente de la page d\'inscription.';
    $lang->about_agreement = "L'Accord d'Inscription comme Membre sera exposé seulement quand il n'est pas vide.";

    $lang->about_image_name = "Permettre aux utilisateurs d'utiliser une image pour présenter leurs noms au lieu des lettres";
    $lang->about_image_mark = "Permettre aux utilisateurs d'utiliser une marque devent leurs noms";
    $lang->about_profile_image = 'Permettre aux utilisateurs d\'utiliser une image de profil';
    $lang->about_accept_agreement = "J'ai lu l'Accord et je suis d'accord."; 

    $lang->about_member_default = 'On sera par défaut dans ce groupe après l\'inscription';

    $lang->about_openid = 'Si vous vous inscrivez avec OpenID, vos informations primaires comme le Compte(ID) ou l\'adresse mél sera gardés sur ce site. Mais le procès pour le Mot de Passe et la certification sera fait sur le service courant qui offre OpenID';
    $lang->about_openid_leave = 'La sécession de OpenID nous fait supprimer vos informations du membre dans notre site.<br />Si vous ouvrez la connexion après la sécession, nous vous reconnaîtrons comme un nouveau membre, c\'est-à-dire, vous n\'aurez plus la permission sur les articles que vous avez écrits avant.';

    $lang->about_find_member_account = 'L\information de votre compte sera annoncé par le mél sur l\'inscription. <br />Entrez l\'adresse mél que vous avez entré sur l\'inscription et appuyez le bouton "Recherce l\'Information du Compte".<br />';

	$lang->about_member = "C'est le module pour administrer des membres avec lequel vous pouvez créer/modifier/supprimer des membres ou administrer les groupes et la formule d'inscription.\nVous pouvez administrer membres par création un nouveau groupe, ou gagner les informations additionnelles par l'administration la formule d'inscrioption.";
?>
