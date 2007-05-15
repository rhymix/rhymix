<?php
    /**
     * @class  importerController
     * @author zero (zero@nzeo.com)
     * @brief  importer 모듈의 Controller class
     **/

    class importerController extends importer {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief import step1
         * import하려는 대상에 따라 결과값을 구해서 return
         * 회원정보 : next_step=2, module_list = null
         * 모듈정보 : next_step=12, module_list = modules..
         * 회원정보 동기화 : next_step=3
         **/
        function procImporterAdminStep1() {
            $source_type = Context::get('source_type');
            switch($source_type) {
                case 'module' :
                        // 모듈 목록을 구함
                        $oModuleModel = &getModel('module');
                        $module_list = $oModuleModel->getMidList();
                        foreach($module_list as $key => $val) {
                            $module_list_arr[] = sprintf('%d,%s (%s)', $val->module_srl, $val->browser_title, $val->mid);
                        }
                        if(count($module_list_arr)) $module_list = implode("\n",$module_list_arr);
                        $next_step = 12;
                    break;
                case 'member' :
                        $next_step = 2;
                    break;
                case 'syncmember' :
                        $next_step = 3;
                    break;
            }

            $this->add('next_step', $next_step);
            $this->add('module_list', $module_list);
        }

        /**
         * @brief import step12
         * module_srl을 이용하여 대상 모듈에 카테고리값이 있는지 확인하여
         * 있으면 카테고리 정보를 return, 아니면 파일 업로드 단계로 이동
         **/
        function procImporterAdminStep12() {
            $target_module= Context::get('target_module');

            // 대상 모듈의 카테고리 목록을 구해옴
            $oDocumentModel = &getModel('document');
            $category_list = $oDocumentModel->getCategoryList($target_module);

            if(count($category_list)) {
                foreach($category_list as $key => $val) {
                    $category_list_arr[] = sprintf('%d,%s', $val->category_srl, $val->title);
                }
                if(count($category_list_arr)) {
                    $category_list = implode("\n",$category_list_arr);
                    $next_step = 13;
                }
            } else {
                $category_list = null;
                $next_step = 2;
            }

            $this->add('next_step', $next_step);
            $this->add('category_list', $category_list);
        }

        /**
         * @brief import xml file
         * XML File을 읽어서 파싱 후 입력..
         **/
        function procImporterAdminImport() {
            set_time_limit(0);

            $module_srl = Context::get('module_srl');
            $category_srl = Context::get('category_srl');
            $xml_file = Context::get('xml_file');

            // 파일을 찾을 수 없으면 에러 표시
            if(!file_exists($xml_file)) return new Object(-1,'msg_no_xml_file');

            // XML Parser로 XML을 읽음
            $xml_doc = XmlParser::loadXmlFile($xml_file);

            $this->setError(-1);
            $this->setMessage('haha');
        }
    }
?>
