<?php
    /**
     * @file   fr.lang.php
     * @author zero (zero@nzeo.com)  Traduit par Pierre Duvent (PierreDuvent@gmail.com)
     * @brief  Paquet de la langue francaise (Choses fondamentales seulement) 
     **/

    $lang->member = 'Membre';
    $lang->member_default_info = 'Information fondamentale';
    $lang->member_extend_info = 'Information additionnelle';
    $lang->default_group_1 = "Membre Associe";
    $lang->default_group_2 = "Membre Regulier";
    $lang->admin_group = "Groupe des administrateurs";
    $lang->keep_signed = 'Gardez la session ouverte';
    $lang->remember_user_id = 'Memorisez mon Compte';
    $lang->already_logged = "Vous avez deja ouvert une session";
    $lang->denied_user_id = 'Vous avez entre un comte interdit.';
    $lang->null_user_id = 'Entrez votre compte';
    $lang->null_password = 'Entrez le mot de passe';
    $lang->invalid_authorization = 'Votre compte n\'est pas certifie.';
    $lang->invalid_user_id= "Vous avez entre un compte invalide";
    $lang->invalid_password = 'Vous avez entre un mot de passe invalide';
    $lang->allow_mailing = 'Joindre au Mailing';
    $lang->denied = 'Interdit';
    $lang->is_admin = 'Permission Superadministrative';
    $lang->group = 'Groupe assigne';
    $lang->group_title = 'Nom du Groupe';
    $lang->group_srl = 'Numero du Groupe';
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
    $lang->enable_confirm = 'Utiliser Authentification par mel';
	$lang->enable_ssl = 'Utiliser SSL';
	$lang->security_sign_in = 'Ouvrir une Session en utilisant securite rehausse';
    $lang->limit_day = 'Jour de Limite Temporaire';
    $lang->limit_date = 'Jour de Limite';
    $lang->after_login_url = 'URL apres la connexion';
    $lang->after_logout_url = 'URL apres la deconnexion ';
    $lang->redirect_url = 'URL apres Inscription';
    $lang->agreement = 'Accord d\'Inscription comme Membre';
	$lang->accept_agreement = 'D\'accord';
    $lang->member_info = 'Information de Membre';
    $lang->current_password = 'Mot de Passe courrant';
    $lang->openid = 'OpenID';

    $lang->webmaster_name = "Nom de Webmestre";
    $lang->webmaster_email = "Mel de Webmestre";

    $lang->about_keep_signed = 'Malgre que le navigateur est ferme, votre session peut etre ouverte. \n\nSi vous utilisez cette fonction sur l\'ordinateur commun, vos informations prive peut etre expose. Nous vous recommandons de ne pas utiliser cette fonctions sur l\'ordinateur commun.';
    $lang->about_webmaster_name = "Entrez le nom de webmestre qui va etre utilise pour le mel de certification ou l\'autre administration du site. (defaut : webmestre)";
    $lang->about_webmaster_email = "Entrez l\'address du mel de webmestre, S.V.P.";

    $lang->search_target_list = array(
        'user_id' => 'Compte',
        'user_name' => 'Nom',
        'nick_name' => 'Surnom',
        'email_address' => 'Mel',
        'regdate' => 'Jour d\'enregistrer',
        'last_login' => 'Jour de la connexion derniere',
        'extra_vars' => 'Variables extra ',
    );

    $lang->cmd_login = 'Connexion ';
    $lang->cmd_logout = 'Deconnexion';
    $lang->cmd_signup = 'Inscription';
    $lang->cmd_modify_member_info = 'Modifier Mon Information';
    $lang->cmd_modify_member_password = 'Modifier le Mot de Passe';
    $lang->cmd_view_member_info = 'Voir Mon Information';
    $lang->cmd_leave = 'Quitter';
    $lang->cmd_find_member_account = 'J\'ai perdu le compte / le mot de passe';

    $lang->cmd_member_list = 'Liste de Membres';
    $lang->cmd_module_config = 'Mise par Defaut';
    $lang->cmd_member_group = 'Manage Groups';
    $lang->cmd_send_mail = 'Envoyer des Mels';
    $lang->cmd_manage_id = 'Administrer les Comptes Interdits';
    $lang->cmd_manage_form = 'Administrer la Forme d\'Inscription';
    $lang->cmd_view_own_document = 'Voir les Articles ecrits';
    $lang->cmd_trace_document = 'Tracer les Articles ecrits';
    $lang->cmd_trace_comment = 'Trace les Commentaires ecrits';
    $lang->cmd_view_scrapped_document = 'Voir les Coupures';
    $lang->cmd_view_saved_document = 'Voir les Articles conserves';
    $lang->cmd_send_email = 'Envoyer des Mels';

    $lang->msg_email_not_exists = "Vous avez entre un adresse mel invalide.";

    $lang->msg_alreay_scrapped = 'Vous avez deja la coupure de cet article';

    $lang->msg_cart_is_null = 'Choisissez l\'Objet, S.V.P.';
    $lang->msg_checked_file_is_deleted = '%d fichier(s) attache(s) est(sont) suprime(s)';

    $lang->msg_find_account_title = 'Information de compte';
    $lang->msg_find_account_info = 'Voila votre information de compte.';
    $lang->msg_find_account_comment = 'Le Mot de passe sera modifie comme celui ci-dessus si vous cliquez le lien ci-dessous.<br />Modifiez le Mot de passe apres ouvrir la connexion, S.V.P.';
    $lang->msg_confirm_account_title = 'Mel a confirmer l\'Authentification';
    $lang->msg_confirm_account_info = 'Voila votre Information enregistre du Compte:';
    $lang->msg_confirm_account_comment = 'Cliquez le lien de confirmation suivant pour completer votre inscription.';
    $lang->msg_auth_mail_sent = 'Le mel de certification a ete envoye  a %s. Verifiez votre mel.';
    $lang->msg_confirm_mail_sent = 'On a justement envoye un mel de confirmation a %s. Cliquez sur le lien de confirmation dans le mel pour completer votre inscription.';
    $lang->msg_invalid_auth_key = 'Cette requete a certifier est invalide.<br />Essayez encore une fois a retrouver votre information de compte ou contactez l\'administrateur.';
    $lang->msg_success_authed = 'Votre compte a ete certifie avec succes et ouvert une session. \n Modifiez le mot de passe apres vous ouvrez une session en utilisant le mot de passe dans le mel.';
    $lang->msg_success_confirmed = 'L\'authentification est completee avec success.';

    $lang->msg_new_member = 'Ajouter un membre';
    $lang->msg_update_member = 'Modifier l\'Information de Membre';
    $lang->msg_leave_member = 'Quitter';
    $lang->msg_group_is_null = 'Il n\'y a pas de groupe enregistre';
    $lang->msg_not_delete_default = 'Element par Defaut ne pourra pas etre supprime';
    $lang->msg_not_exists_member = "Membre Invalide";
    $lang->msg_cannot_delete_admin = 'Le Compte de l\'Administrateur ne pourra pas etre supprime. Disqualifiez l\'administration du compte et essayez encore une fois.';
    $lang->msg_exists_user_id = 'Le compte existe deja. Essayez un autre.';
    $lang->msg_exists_email_address = 'L\'adresse mel existe deja. Essayez une autre.';
    $lang->msg_exists_nick_name = 'Le surnom existe deja. Essayez un autre.';
    $lang->msg_signup_disabled = 'Vous ne pouvez pas inscrire.';
    $lang->msg_already_logged = 'Vous avez deja inscrit.';
    $lang->msg_not_logged = 'Ouvrez une session d\'abord';
    $lang->msg_insert_group_name = 'Entrez le nom de groupe, S.V.P.';
    $lang->msg_check_group = 'Choisissez le groupe';

    $lang->msg_not_uploaded_profile_image = 'L\'image de Profil n\'a pas pu etre enregistre';
    $lang->msg_not_uploaded_image_name = 'Le nom d\'image n\'a pas pu etre enregistre';
    $lang->msg_not_uploaded_image_mark = 'La marque d\'image n\'a pas pu etre enregistre';

    $lang->msg_accept_agreement = 'Vous devez agreer l\'accord'; 

    $lang->msg_user_denied = 'Vous avez entre un compte interdit';
    $lang->msg_user_not_confirmed = 'Vous n\'avez pas encore authentifie. Verifiez votre mel, S.V.P.';
    $lang->msg_user_limited = 'Vous avez entre un compte qui peut ertre utilise depuis %s';

    $lang->about_user_id = 'Le compte d\'utilisateur doit etre long de 3~20 lettres et se composer des alphabets et des chiffres avec un alphabet comme le premier lettre.';
    $lang->about_password = 'Le Mot de passe doit etre long de 6~20 lettres.';
    $lang->about_user_name = 'Le Nom doit etre long de 2~20 lettres.';
    $lang->about_nick_name = 'Le Surnom doit etre long de 2~20 lettres.';
    $lang->about_email_address = 'L\'Adresse mel sera utilise a modifier/trouver le mot de passe apres la certification en mel.';
    $lang->about_homepage = 'Entrez  si vous avez un site web.';
    $lang->about_blog_url = 'Entrez si vous avez un blogue.';
    $lang->about_birthday = 'Entrez votre anniversaire.';
    $lang->about_allow_mailing = "Si vous n'inscrivez pas sur mailing, vous ne pouvez pas recevoir mel du groupe.";
    $lang->about_denied = 'Cocher pour interdire le compte';
    $lang->about_is_admin = 'Cocher pour autoriser la permission de Superadministrateur';
    $lang->about_member_description = "L\'Administrateur peut noter sur le membre";
    $lang->about_group = 'Un compte peut appartenir aux plusieurs groupes.';

    $lang->about_column_type = 'Choisissez la formule du champ que vous voulez ajouter';
    $lang->about_column_name = 'Entrez le nom compose en alphabet qui peut etre utilise dans le modele (nom comme variable)';
    $lang->about_column_title = 'Ce titre sera expose sur la formule pour inscrire ou sur l\'ecran pour modifier/voir les informations de membre';
    $lang->about_default_value = 'Vous pouvez mettre les valeurs par defaut';
    $lang->about_active = 'Vous devez cocher sur les elements actifs pour les exposer sur la formule d\'inscription';
    $lang->about_form_description = 'Si vous entrez dans le champ du description, elle sera expose sur la formule d\'incription';
    $lang->about_required = 'Si vous cochez, ce sera obligatoire pour inscrire';

    $lang->about_enable_openid = 'Cochez si vous voulez permettre OpenID';
    $lang->about_enable_join = 'Cochez si vous voulez permettre inscrire';
    $lang->about_enable_confirm = 'Envoyer mel de confirmation pour completer inscription.';
    $lang->about_enable_ssl = 'Les informations personnelles (Inscription / Modification des informations du membre / Connexion) peuvent etre envoyees comme mode SSL(https) si le serveur offre le service SSL.';
    $lang->about_limit_day = 'Vous pouvez limiter le jour de certification apres l\'inscription';
    $lang->about_limit_date = 'Utilisateur ne peut pas ouverir la connexion jusqu\'au jour assigne';
    $lang->about_after_login_url = 'Vous pouvez indiquer URL ou l\'on va apres la connexion. Le vide signifie la page courrante.';
    $lang->about_after_logout_url = 'Vous pouvez indiquer URL ou l\'on va apres la deconnexion. Le vide signifie la page courrante.';
    $lang->about_redirect_url = 'Entrez URL ou l\'utilisateur irra apres l\'inscription, S.V.P. Si c\'est vide, ce sera la page precedente de la page d\'inscrire.';
    $lang->about_agreement = "L\'Accord d\'Inscription comme Membre sera expose seulement quand il n'est pas vide.";

    $lang->about_image_name = "Permettre aux utilisateurs utiliser une image pour presenter leurs noms au lieu des lettres";
    $lang->about_image_mark = "Permettre aux utilisateurs utiliser une marque devent leurs noms";
    $lang->about_profile_image = 'Permettre aux utilisateurs utiliser une image de profil';
    $lang->about_accept_agreement = "J\'ai lu l\'Accord et je suis d\'accord."; 

    $lang->about_member_default = 'On sera dans ce groupe apres l\'inscription par defaut';

    $lang->about_openid = 'Si vous inscrivez avec OpenID, vos informations primaires comme le Compte(ID) soit l\'address mel sera gardes sur ce site. Mais le proces pour le Mot de Passe et la certification sera fait sur le service courrant qui offre OpenID';
    $lang->about_openid_leave = 'La secession de OpenID nous fait effacer vos informations du membre dans notre site.<br />Si vous ouvrez la connexion apres la secession, nous vous reconnaitrons comme un nouveau membre, c\'est-a-dire, vous n\'aurez plus la permission sur les articles que vous avez ecrits avant.';

    $lang->about_find_member_account = 'L\information de votre compte sera annonce par l\'address mel d\'inscription. <br />Entrez l\'address mel que vous avez entre en inscrivant et appuyez le bouton "Recherce l\'Information du Compte" button.<br />';

	$lang->about_member = "C'est le module pour administrer des membres avec lequel vous pouvez creer/modifier/effacer des membres ou administrer les groupes et la formule d'inscription.\nVous pouvez administrer membres par creation un nouveau groupe, ou gagner les informations additionnelles par l'administration la formule d'inscrioption.";
?>
