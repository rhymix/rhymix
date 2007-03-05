<?php
    /**
     * @class  installController
     * @author zero (zero@nzeo.com)
     * @brief  install module의 Controller class
     **/

    class installController extends install {


        /**
         * @brief 초기화
         **/
        function init() {
            // 설치가 되어 있으면 오류
            if(Context::isInstalled()) return $this->dispMessage('msg_already_installed');
        }

        /**
         * @brief 인스톨 환경을 체크하여 결과 return 
         **/
        function checkInstallEnv() {
            // 각 필요한 항목 체크
            $checklist = array();

            // 1. permission 체크
            if(is_writable('./')||is_writable('./files')) $checklist['permission'] = true;
            else $checklist['permission'] = false;

            // 2. xml_parser_create함수 유무 체크
            if(function_exists('xml_parser_create')) $checklist['xml'] = true;
            else $checklist['xml'] = false;

            // 3. ini_get(session.auto_start)==1 체크
            if(ini_get(session.auto_start)!=1) $checklist['session'] = true;
            else $checklist['session'] = false;

            // 4. iconv 체크
            if(function_exists('iconv')) $checklist['iconv'] = true;
            else $checklist['iconv'] = false;

            // 5. gd 체크 (imagecreatefromgif함수)
            if(function_exists('imagecreatefromgif')) $checklist['gd'] = true;
            else $checklist['gd'] = false;

            // 6. mysql_get_client_info() 체크
            if(mysql_get_client_info() < "4.1.00") $checklist['mysql'] = false;
            else $checklist['mysql'] = true;

            if(!$checklist['permission'] || !$checklist['xml'] || !$checklist['session']) $install_enable = false;
            else $install_enable = true;

            // 체크 결과를 Context에 저장
            Context::set('checklist', $checklist);
            Context::set('install_enable', $install_enable);

            return $install_enable;
        }


        /**
         * @brief 입력받은 정보로 설치를 함
         **/
        function procInstall() {
            // 설치가 되어 있는지에 대한 체크
            if(Context::isInstalled()) return new Object(-1, 'msg_already_installed');

            // DB와 관련된 변수를 받음
            $db_info = Context::gets('db_type','db_hostname','db_userid','db_password','db_database','db_table_prefix');

            // DB의 타입과 정보를 등록
            Context::setDBInfo($db_info);

            // DB Instance 생성
            $oDB = &DB::getInstance();

            // DB접속이 가능한지 체크
            if(!$oDB->isConnected()) return new Object(-1, 'msg_dbconnect_failed');

            // 모든 모듈의 테이블 생성
            $output = $this->makeTable();
            if(!$output->toBool()) return $output;

            // 멤버 컨트롤러 객체 생성
            $oMemberController = &getController('member');

            // 그룹을 입력
            $group_args->title = Context::getLang('default_group_1');
            $group_args->is_default = 'Y';
            $output = $oMemberController->insertGroup($group_args);

            $group_args->title = Context::getLang('default_group_2');
            $group_args->is_default = 'N';
            $oMemberController->insertGroup($group_args);

            // 관리자 정보 세팅
            $admin_info = Context::gets('user_id','password','nick_name','user_name', 'email_address');

            // 관리자 정보 입력
            $oMemberController->insertAdmin($admin_info);

            // 금지 아이디 등록 (기본 + 모듈명)
            $oAdminModel = &getModel('admin');
            $module_list = $oAdminModel->getModuleList();
            foreach($module_list as $key => $val) {
                $oMemberController->insertDeniedID($val->module,'');
            }
            $oMemberController->insertDeniedID('www','');
            $oMemberController->insertDeniedID('root','');
            $oMemberController->insertDeniedID('administrator','');
            $oMemberController->insertDeniedID('telnet','');
            $oMemberController->insertDeniedID('ftp','');
            $oMemberController->insertDeniedID('http','');

            // 로그인 처리시킴
            $output = $oMemberController->procLogin($admin_info->user_id, $admin_info->password);
            if(!$output) return $output;

            // 기본 모듈을 생성
            $oModule = &getController('module');
            $oModule->makeDefaultModule();

            // config 파일 생성
            if(!$this->makeConfigFile()) return new Object(-1, 'msg_install_failed');

            // 설치 완료 메세지 출력
            $this->setMessage('msg_install_completed');
            $this->setRedirectUrl('./');
        }

        /**
         * @brief files 및 하위 디렉토리 생성
         * DB 정보를 바탕으로 실제 install하기 전에 로컬 환경 설저d
         **/
        function makeDefaultDirectory() {
            $directory_list = array(
                    './files',
                    './files/config',
                    './files/modules',
                    './files/plugins',
                    './files/addons',
                    './files/layouts',
                    './files/cache',
                    './files/cache/queries',
                    './files/cache/js_filter_compiled',
                    './files/cache/template_compiled',
                    './files/cache/module_info',
                    './files/attach',
                    './files/attach/images',
                    './files/attach/binaries',
                );

            foreach($directory_list as $dir) {
                if(is_dir($dir)) continue;
                @mkdir($dir, 0707);
                @chmod($dir, 0707);
            }
        }

        /**
         * @brief DB Table 생성 
         *
         * 모든 module의 schemas 디렉토리를 확인하여 schema xml을 이용, 테이블 생성
         **/
        function makeTable() {
            // db instance생성
            $oDB = &DB::getInstance();

            // 각 모듈의 schemas/*.xml 파일을 모두 찾아서 table 생성
            $module_list_1 = FileHandler::readDir('./modules/', NULL, false, true);
            $module_list_2 = FileHandler::readDir('./files/modules/', NULL, false, true);
            $module_list = array_merge($module_list_1, $module_list_2);
            foreach($module_list as $module_path) {
                $schema_dir = sprintf('%s/schemas/', $module_path);
                $schema_files = FileHandler::readDir($schema_dir, NULL, false, true);
                $file_cnt = count($schema_files);
                if(!$file_cnt) continue;

                for($i=0;$i<$file_cnt;$i++) {
                    $file = trim($schema_files[$i]);
                    if(!$file || substr($file,-4)!='.xml') continue;
                    $output = $oDB->createTableByXmlFile($file);
                    if($oDB->isError()) return $oDB->getError();
                }
            }
            return new Object();
        }

        /**
         * @brief config 파일을 생성
         * 모든 설정이 이상없이 끝난 후에 config파일 생성
         **/
        function makeConfigFile() {
            $config_file = Context::getConfigFile();
            if(file_exists($config_file)) return;

            $db_info = Context::getDbInfo();
            if(!$db_info) return;

            $buff = '<?php if(!__ZB5__) exit();'."\n";
            foreach($db_info as $key => $val) {
                $buff .= sprintf("\$db_info->%s = \"%s\";\n", $key, $val);
            }
            $buff .= "?>";

            FileHandler::writeFile($config_file, $buff);

            if(@file_exists($config_file)) return true;
            return false;
        }
    }
?>
