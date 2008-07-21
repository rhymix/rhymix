<?php
    /**
     * @file   fr.lang.php
     * @author zero (zero@nzeo.com)  Traduit par Pierre Duvent (PierreDuvent@gmail.com)
     * @brief  Paquet de la langue française (Choses fondamentales seulement) 
     **/

    $lang->member = 'Membre';
    $lang->member_default_info = 'Information fondamentale';
    $lang->member_extend_info = 'Information additionnelle';
    $lang->default_group_1 = "Membre Associé";
    $lang->default_group_2 = "Membre Régulier";
    $lang->admin_group = "Groupe des administrateurs";
    $lang->keep_signed = 'Gardez la session ouverte';
    $lang->remember_user_id = 'Mémorisez mon Compte';
    $lang->already_logged = "Vous avez déjà ouvert une session";
    $lang->denied_user_id = 'Vous avez entré un comte interdit.';
    $lang->null_user_id = 'Entrez votre compte';
    $lang->null_password = 'Entrez le mot de passe';
    $lang->invalid_authorization = 'Votre compte n\'est pas certifié.';
    $lang->invalid_user_id= "Vous avez entré un compte invalide";
    $lang->invalid_password = 'Vous avez entré un mot de passe invalide';
    $lang->allow_mailing = 'Joindre au Mailing';
    $lang->denied = 'Interdit';
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
    $lang->enable_join = 'Permettre inscrire';
    $lang->enable_confirm = 'Utiliser Authentification par mél';
	$lang->enable_ssl = 'Utiliser SSL';
	$lang->security_sign_in = 'Ouvrir une Session en utilisant sécurité rehaussé';
    $lang->limit_day = 'Jour de Limite Temporaire';
    $lang->limit_date = 'Jour de Limite';
    $lang->after_login_url = 'URL après la connexion';
    $lang->after_logout_url = 'URL après la déconnexion ';
    $lang->redirect_url = 'URL après Inscription';
    $lang->agreement = 'Accord d\'Inscription comme Membre';
	$lang->accept_agreement = 'D\'accord';
    $lang->member_info = 'Information de Membre';
    $lang->current_password = 'Mot de Passe courrant';
    $lang->openid = 'OpenID';

    $lang->webmaster_name = "Nom de Webmestre";
    $lang->webmaster_email = "Mél de Webmestre";

    $lang->about_keep_signed = 'Malgré que le navigateur est fermé, votre session peut être ouverte. \n\nSi vous utilisez cette fonction sur l\'ordinateur commun, vos informations privé peut être exposé. Nous vous recommandons de ne pas utiliser cette fonctions sur l\'ordinateur commun.';
    $lang->about_webmaster_name = "Entrez le nom de webmestre qui va être utilisé pour le mél de certification ou l\'autre administration du site. (défaut : webmestre)";
    $lang->about_webmaster_email = "Entrez l\'address du mél de webmestre, S.V.P.";

    $lang->search_target_list = array(
        'user_id' => 'Compte',
        'user_name' => 'Nom',
        'nick_name' => 'Surnom',
        'email_address' => 'Mél',
        'regdate' => 'Jour d\'enregistrer',
        'last_login' => 'Jour de la connexion dernière',
        'extra_vars' => 'Variables extra ',
    );

    $lang->cmd_login = 'Connexion ';
    $lang->cmd_logout = 'Déconnexion';
    $lang->cmd_signup = 'Inscription';
    $lang->cmd_modify_member_info = 'Modifier Mon Information';
    $lang->cmd_modify_member_password = 'Modifier le Mot de Passe';
    $lang->cmd_view_member_info = 'Voir Mon Information';
    $lang->cmd_leave = 'Quitter';
    $lang->cmd_find_member_account = 'J\'ai perdu le compte / le mot de passe';

    $lang->cmd_member_list = 'Liste de Membres';
    $lang->cmd_module_config = 'Mise par Défaut';
    $lang->cmd_member_group = 'Manage Groups';
    $lang->cmd_send_mail = 'Envoyer des Méls';
    $lang->cmd_manage_id = 'Administrer les Comptes Interdits';
    $lang->cmd_manage_form = 'Administrer la Forme d\'Inscription';
    $lang->cmd_view_own_document = 'Voir les Articles écrits';
    $lang->cmd_trace_document = 'Tracer les Articles écrits';
    $lang->cmd_trace_comment = 'Trace les Commentaires écrits';
    $lang->cmd_view_scrapped_document = 'Voir les Coupures';
    $lang->cmd_view_saved_document = 'Voir les Articles conservés';
    $lang->cmd_send_email = 'Envoyer des Méls';

    $lang->msg_email_not_exists = "Vous avez entré un adresse mél invalide.";

    $lang->msg_alreay_scrapped = 'Vous avez déjà la coupure de cet article';

    $lang->msg_cart_is_null = 'Choisissez l\'Objet, S.V.P.';
    $lang->msg_checked_file_is_deleted = '%d fichier(s) attaché(s) est(sont) suprimé(s)';

    $lang->msg_find_account_title = 'Information de compte';
    $lang->msg_find_account_info = 'Voilà votre information de compte.';
    $lang->msg_find_account_comment = 'Le Mot de passe sera modifié comme celui ci-dessus si vous cliquez le lien ci-dessous.<br />Modifiez le Mot de passe après ouvrir la connexion, S.V.P.';
    $lang->msg_confirm_account_title = 'Mél à confirmer l\'Authentification';
    $lang->msg_confirm_account_info = 'Voilà votre Information enregistré du Compte:';
    $lang->msg_confirm_account_comment = 'Cliquez le lien de confirmation suivant pour compléter votre inscription.';
    $lang->msg_auth_mail_sent = 'Le mél de certification a été envoyé  à %s. Vérifiez votre mél.';
    $lang->msg_confirm_mail_sent = 'On a justement envoyé un mél de confirmation à %s. Cliquez sur le lien de confirmation dans le mél pour compléter votre inscription.';
    $lang->msg_invalid_auth_key = 'Cette requête à certifier est invalide.<br />Essayez encore une fois à retrouver votre information de compte ou contactez l\'administrateur.';
    $lang->msg_success_authed = 'Votre compte a été certifié avec succès et ouvert une session. \n Modifiez le mot de passe après vous ouvrez une session en utilisant le mot de passe dans le mél.';
    $lang->msg_success_confirmed = 'L\'authentification est complétée avec succèss.';

    $lang->msg_new_member = 'Ajouter un membre';
    $lang->msg_update_member = 'Modifier l\'Information de Membre';
    $lang->msg_leave_member = 'Quitter';
    $lang->msg_group_is_null = 'Il n\'y a pas de groupe enrégistré';
    $lang->msg_not_delete_default = 'Elément par Défaut ne pourra pas être supprimé';
    $lang->msg_not_exists_member = "Membre Invalide";
    $lang->msg_cannot_delete_admin = 'Le Compte de l\'Administrateur ne pourra pas être supprimé. Disqualifiez l\'administration du compte et essayez encore une fois.';
    $lang->msg_exists_user_id = 'Le compte existe déjà. Essayez un autre.';
    $lang->msg_exists_email_address = 'L\'adresse mél existe déjà. Essayez une autre.';
    $lang->msg_exists_nick_name = 'Le surnom existe déjà. Essayez un autre.';
    $lang->msg_signup_disabled = 'Vous ne pouvez pas inscrire.';
    $lang->msg_already_logged = 'Vous avez déjà inscrit.';
    $lang->msg_not_logged = 'Ouvrez une session d\'abord';
    $lang->msg_insert_group_name = 'Entrez le nom de groupe, S.V.P.';
    $lang->msg_check_group = 'Choisissez le groupe';

    $lang->msg_not_uploaded_profile_image = 'L\'image de Profil n\'a pas pu être enrégistré';
    $lang->msg_not_uploaded_image_name = 'Le nom d\'image n\'a pas pu être enrégistré';
    $lang->msg_not_uploaded_image_mark = 'La marque d\'image n\'a pas pu être enrégistré';

    $lang->msg_accept_agreement = 'Vous devez agréer l\'accord'; 

    $lang->msg_user_denied = 'Vous avez entré un compte interdit';
    $lang->msg_user_not_confirmed = 'Vous n\'avez pas encore authentifié. Verifiez votre mél, S.V.P.';
    $lang->msg_user_limited = 'Vous avez entré un compte qui peut êrtre utilisé depuis %s';

    $lang->about_user_id = 'Le compte d\'utilisateur doit être long de 3~20 lettres et se composer des alphabets et des chiffres avec un alphabet comme le premier lettre.';
    $lang->about_password = 'Le Mot de passe doit être long de 6~20 lettres.';
    $lang->about_user_name = 'Le Nom doit être long de 2~20 lettres.';
    $lang->about_nick_name = 'Le Surnom doit être long de 2~20 lettres.';
    $lang->about_email_address = 'L\'Adresse mél sera utilisé à modifier/trouver le mot de passe après la certification en mél.';
    $lang->about_homepage = 'Entrez  si vous avez un site web.';
    $lang->about_blog_url = 'Entrez si vous avez un blogue.';
    $lang->about_birthday = 'Entrez votre anniversaire.';
    $lang->about_allow_mailing = "Si vous n'inscrivez pas sur mailing, vous ne pouvez pas recevoir mél du groupe.";
    $lang->about_denied = 'Cocher pour interdire le compte';
    $lang->about_is_admin = 'Cocher pour autoriser la permission de Superadministrateur';
    $lang->about_description = "L\'Administrateur peut noter sur le membre";
    $lang->about_group = 'Un compte peut appartenir aux plusieurs groupes.';

    $lang->about_column_type = 'Choisissez la formule du champ que vous voulez ajouter';
    $lang->about_column_name = 'Entrez le nom composé en alphabet qui peut être utilisé dans le modèle (nom comme variable)';
    $lang->about_column_title = 'Ce titre sera exposé sur la formule pour inscrire ou sur l\'écran pour modifier/voir les informations de membre';
    $lang->about_default_value = 'Vous pouvez mettre les valeurs par défaut';
    $lang->about_active = 'Vous devez cocher sur les éléments actifs pour les exposer sur la formule d\'inscription';
    $lang->about_form_description = 'Si vous entrez dans le champ du description, elle sera exposé sur la formule d\'incription';
    $lang->about_required = 'Si vous cochez, ce sera obligatoire pour inscrire';

    $lang->about_enable_openid = 'Cochez si vous voulez permettre OpenID';
    $lang->about_enable_join = 'Cochez si vous voulez permettre inscrire';
    $lang->about_enable_confirm = 'Envoyer mél de confirmation pour compléter inscription.';
    $lang->about_enable_ssl = 'Les informations personnelles (Inscription / Modification des informations du membre / Connexion) peuvent être envoyées comme mode SSL(https) si le serveur offre le service SSL.';
    $lang->about_limit_day = 'Vous pouvez limiter le jour de certification après l\'inscription';
    $lang->about_limit_date = 'Utilisateur ne peut pas ouverir la connexion jusqu\'au jour assigné';
    $lang->about_after_login_url = 'Vous pouvez indiquer URL où l\'on va après la connexion. Le vide signifie la page courrante.';
    $lang->about_after_logout_url = 'Vous pouvez indiquer URL où l\'on va après la déconnexion. Le vide signifie la page courrante.';
    $lang->about_redirect_url = 'Entrez URL où l\'utilisateur irra après l\'inscription, S.V.P. Si c\'est vide, ce sera la page précédente de la page d\'inscrire.';
    $lang->about_agreement = "L\'Accord d\'Inscription comme Membre sera exposé seulement quand il n'est pas vide.";

    $lang->about_image_name = "Permettre aux utilisateurs utiliser une image pour présenter leurs noms au lieu des lettres";
    $lang->about_image_mark = "Permettre aux utilisateurs utiliser une marque devent leurs noms";
    $lang->about_profile_image = 'Permettre aux utilisateurs utiliser une image de profil';
    $lang->about_accept_agreement = "J\'ai lu l\'Accord et je suis d\'accord."; 

    $lang->about_member_default = 'On sera dans ce groupe après l\'inscription par défaut';

    $lang->about_openid = 'Si vous inscrivez avec OpenID, vos informations primaires comme le Compte(ID) soit l\'address mél sera gardés sur ce site. Mais le procès pour le Mot de Passe et la certification sera fait sur le service courrant qui offre OpenID';
    $lang->about_openid_leave = 'La sécession de OpenID nous fait effacer vos informations du membre dans notre site.<br />Si vous ouvrez la connexion après la sécession, nous vous reconnaîtrons comme un nouveau membre, c\'est-à-dire, vous n\'aurez plus la permission sur les articles que vous avez écrits avant.';

    $lang->about_find_member_account = 'L\information de votre compte sera annoncé par l\'address mél d\'inscription. <br />Entrez l\'address mél que vous avez entré en inscrivant et appuyez le bouton "Recherce l\'Information du Compte" button.<br />';

	$lang->about_member = "C'est le module pour administrer des membres avec lequel vous pouvez créer/modifier/effacer des membres ou administrer les groupes et la formule d'inscription.\nVous pouvez administrer membres par création un nouveau groupe, ou gagner les informations additionnelles par l'administration la formule d'inscrioption.";
?>
