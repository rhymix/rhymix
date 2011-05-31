<?php
    /**
     * @file   fr.lang.php
     * @author NHN (developers@xpressengine.com)  Traduit par Pierre Duvent (PierreDuvent@gmail.com)
     * @brief  Paquet du langage en français pour le module d\'Installation
     **/

    $lang->introduce_title = 'Installation du XE ';
	$lang->lgpl_agree = 'GNU 약소 일반 공중 사용 허가서(LGPL v2) 동의';
	$lang->enviroment_gather = '설치 환경 수집 동의';
	$lang->install_progress_menu = array(
			'agree'=>'라이선스 동의',
			'condition'=>'설치 조건 확인',
			'ftp'=>'FTP 정보 입력',
			'dbSelect'=>'DB 선택',
			'dbInfo'=>'DB 정보 입력',
			'configInfo'=>'환경 설정',
			'adminInfo'=>'관리자 정보 입력'
		);
    $lang->install_condition_title = "Vérifiez  les conditions obligatoires pour l'installation, S.V.P.";
    $lang->install_checklist_title = array(
			'php_version' => 'Version de PHP',
            'permission' => 'Autorisation',
            'xml' => 'Bibliothèque de XML',
            'iconv' => 'Bibliothèque de ICONV',
            'gd' => 'Bibliothèque de GD',
            'session' => 'Configuration de Session.auto_start',
            'db' => 'DB',
        );

    $lang->install_checklist_desc = array(
			'php_version' => '[Obligatoire] Si la version de PHP est 5.2.2, XE ne sera pas  installé à cause du bogue',
            'permission' => '[Obligatoire] Chemin de l\'installation de XE ou la permission de  répertoire de ./files doit être 707',
            'xml' => '[Obligatoire] La bibliothèque de XML est nécessaire pour la communication de XML',
            'session' => '[Obligatoire] \'Session.auto_start\' dans le fichier de configuration pour PHP (php.ini) doit être égal à zéro car XE utilise la session',
            'iconv' => 'Iconv doit être installé afin de convertir UTF-8 et des autres assortiments des  langues',
            'gd' => 'La bibliothèque de GD doit être installé afin d\'utiliser la fonction à convertir des images',
        );

    $lang->install_checklist_xml = 'Installation la bibliothèque de XML';
    $lang->install_without_xml = 'La bibliothèque de XML n\'est pas installée';
    $lang->install_checklist_gd = 'Installation la bibliothèque de  GD';
    $lang->install_without_gd  = 'La bibliothèque de GD pour convertir des images n\'est pas installée';
    $lang->install_checklist_iconv = 'Installation la bibliothèque d\'Iconv';
    $lang->install_without_iconv = 'La bibliothèque d\'Iconv pour traiter les caractères  n\'est pas installée';
    $lang->install_session_auto_start = 'Des problèmes possibles peuvent avoir lieu car  session.auto_start==1 dans la configuration de PHP';
    $lang->install_permission_denied = 'La permission du chemin d\'installation n\'est pas égale à 707';

    $lang->cmd_agree_license = 'Je suis d\'accord avec la licence';
    $lang->cmd_install_fix_checklist = 'J\'ai corrigé les conditions obligatoires.';
    $lang->cmd_install_next = 'Continuer à  installer';
    $lang->cmd_ignore = 'Ignore';

    $lang->db_desc = array(
        'mysql' => 'Utilisera fonction mysql*() pour utiliser la base de données de mysql.<br />La transaction sera invalidé parce que le fichier de Base de Données est créé par myisam.',
        'mysqli' => 'Utilisera fonction mysqli*() pour utiliser la base de données de mysql.<br />La transaction sera invalidé parce que le fichier de Base de Données est créé par myisam.',
        'mysql_innodb' => 'Utilisera innodb pour utiliser Base de Données de mysql.<br />La transaction sera validé pour innodb',
        'sqlite2' => 'Surpporter sqlite2 qui conserve les données dans les fichiers.<br />Quand vous installez, vous devez créer le fichier de Base de Données dans une place que l\'on ne peut pas accéder par web.<br />(Jamais  testé sur  stabilization)',
        'sqlite3_pdo' => 'Supporter sqlite3 PDO de PHP.<br />Quand vous installez, vous devez cr?r le fichier de Base de Données dans une place que l\'on ne peut pas accéder par Web.',
        'cubrid' => 'Utiliser la Base de Données de CUBRID.  <a href="http://www.xpressengine.com/?mid=manual&pageid=2880556" onclick="window.open(this.href);return false;" class="manual">manual</a>',
        'mssql' => 'Utiliser la Base de Données de MSSQL.',
        'postgresql' => 'Utiliser la Base de Données de PostgreSql.',
        'firebird' => 'Utiliser la Base de Données de firebird.',
    );

    $lang->form_title = 'Entrer des informations de Base de données et Administrateur';
    $lang->db_title = 'Entrez l\'information de Base de Données, S.V.P.';
    $lang->db_type = 'Sorte de Base de Données';
    $lang->select_db_type = 'Choisissez la Base de Données que vous voulez utiliser.';
    $lang->db_hostname = 'Hostname(Nom de l\'ordinateur central) de Base de Données (LOCALHOST généralement)';
    $lang->db_port = 'Port de Base de Données';
    $lang->db_userid = 'Compte(ID) pour le Base de Données';
    $lang->db_password = 'Mot de passe pour le Base de Données';
    $lang->db_database = 'Nom de Base de Données';
    $lang->db_database_file = 'Fichier de Base de Données';
    $lang->db_table_prefix = 'En-tête de la table';

    $lang->admin_title = 'Informations d\'Administrateur';

    $lang->env_title = 'Configuration';
    $lang->use_optimizer = 'Valider Optimiseur';
    $lang->about_optimizer = 'Si l\'optimiseur est validé, utilisateur peut accéder rapidement ce site parce que plusieurs fichiers de CSS / JS sont reliés ensemble et comprimés avant transmission. <br /> Néanmoins, cette optimisation peut arriver problématique selong CSS ou JS. Si vous l\'invalidez, ça marchera correctement pourtant il marchera plus lentement.';
    $lang->use_rewrite = 'Utiliser mode de récrire(rewrite mod)';
    $lang->use_sso = 'SSO';
    $lang->about_rewrite = "Si le serveur de web est capable d'utiliser le mode de récrire, URL longue comme http://murmure/?document_srl=123 peut être abrégé comme http://murmure/123";
	$lang->about_sso = '사용자가 한 번만 로그인하면 기본 사이트와 가상 사이트에 동시에 로그인이 되는 기능입니다. 가상 사이트를 사용할 때만 필요합니다.';
    $lang->time_zone = 'Fuseau horaire';
    $lang->about_time_zone = "Si l'heure de serveur et celle de votre emplacement ne s'accordent pas,  vous pouvez remettre l'heure comme le même heure de votre lieu en configurant le fuseau horaire ";
    $lang->qmail_compatibility = 'Compatible avec Qmail';
    $lang->about_qmail_compatibility = 'Le mél sera envoyé en MTA qui ne peut pas reconnaître le CRLF comme délimiteur des lignes comme le Qmail.';
    $lang->about_database_file = 'Sqlite conserve des données dans le fichier. Vous devez placer le fichier de la base de données où l\'on ne peut pas accéder par web.<br/><span style="color:red">Le fichier des Donées doit être en dedans la permission 707.</span>';
    $lang->success_installed = 'Installation s\'est complété';
    $lang->msg_cannot_proc = 'Environnement d\'Installation n\'est pas équipé à procéder.';
    $lang->msg_already_installed = 'XE est déjà installé';
    $lang->msg_dbconnect_failed = "Erreur a lieu en essayant connecter à la Base de Données.\nVérifiez encore une fois les informations sur la Base de Données, S.V.P.";
    $lang->msg_table_is_exists = "La Table est déjà créée dans la Base de Données.\nLe fichier de Configuration est recréé.";
    $lang->msg_install_completed = "Installation a complété.\nMerci pour choisir XE";
    $lang->msg_install_failed = "Une erreur a lieu en créant le fichier d\'installation.";
    $lang->ftp_get_list = "Get List";
	$lang->read_all = '전문 읽기';
	$lang->license_agreement_desc = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 <em>반드시 동의해야 합니다</em>.';
	$lang->license_agreement_alert = 'XE를 사용하려면 \'GNU 약소 일반 공중 사용 허가서(LGPL v2)\'에 반드시 동의해야 합니다.';
	$lang->intall_env_agreement = '설치 환경 수집 동의';
	$lang->intall_env_agreement_desc = '설치 환경 수집에 동의하는 경우 사용자의 XE 설치 환경과 관련되어 있는 \'<em>OS, DBMS, #, #</em>\' 정보가 XE 통계 수집 서버로 전송됩니다. 수집된 정보는 더 나은 SW를 제작하기 위한 통계 수집 이외의 목적으로 활용하지 않습니다. XE는 사용자의 설치 환경 정보를 외부에 공개하지 않습니다. <em>이 항목에 반드시 동의하지 않아도 됩니다.</em>';
	$lang->lgpl_license_url = 'http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html';
?>
