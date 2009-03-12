<?php
    /**
     * @class  rssAdminController
     * @author zero (zero@nzeo.com)
     * @brief  rss module의 admin controller class
     *
     * RSS 2.0형식으로 문서 출력
     *
     **/

    class rssAdminController extends rss {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief RSS 전체피드 설정
         **/
        function procRssAdminInsertConfig() {
            $oModuleModel = &getModel('module');
            $total_config = $oModuleModel->getModuleConfig('rss');

            $config_vars = Context::getRequestVars();

            $config_vars->feed_document_count = (int)$config_vars->feed_document_count;

            if(!$config_vars->use_total_feed) $alt_message = 'msg_invalid_request';
            if(!in_array($config_vars->use_total_feed, array('Y','N'))) $config_vars->open_rss = 'Y';

            if($config_vars->image || $config_vars->del_image) {
                $image_obj = $config_vars->image;
                $config_vars->image = $total_config->image;

                // 삭제 요청에 대한 변수를 구함
                if($config_vars->del_image == 'Y' || $image_obj) {
                    FileHandler::removeFile($config_vars->image);
                    $config_vars->image = '';
                    $total_config->image = '';
                }

                // 정상적으로 업로드된 파일이 아니면 무시
                if($image_obj['tmp_name'] && is_uploaded_file($image_obj['tmp_name'])) {
                    // 이미지 파일이 아니어도 무시 (swf는 패스~)
                    $image_obj['name'] = Context::convertEncodingStr($image_obj['name']);

                    if(!preg_match("/\.(jpg|jpeg|gif|png)$/i", $image_obj['name'])) $alt_message = 'msg_rss_invalid_image_format';
                    else {
                        // 경로를 정해서 업로드
                        $path = './files/attach/images/rss/';
                        // 디렉토리 생성
                        if(!FileHandler::makeDir($path)) $alt_message = 'msg_error_occured';
                        else{
                            $filename = $path.$image_obj['name'];
                            // 파일 이동
                            if(!move_uploaded_file($image_obj['tmp_name'], $filename)) $alt_message = 'msg_error_occured';
                            else {
                                $config_vars->image = $filename;
                            }
                        }
                    }
                }
            }
            if(!$config_vars->image && $config_vars->del_image != 'Y') $config_vars->image = $total_config->image;

            $output = $this->setFeedConfig($config_vars);

            if(!$alt_message) $alt_message = 'success_updated';

            $alt_message = Context::getLang($alt_message);
            Context::set('msg', $alt_message);

            $this->setLayoutPath('./common/tpl');
            $this->setLayoutFile('default_layout.html');
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile("top_refresh.html");
        }


        /**
         * @brief RSS 모듈별 설정
         **/
        function procRssAdminInsertModuleConfig() {
            // 대상을 구함
            $module_srl = Context::get('target_module_srl');

            // 여러개의 모듈 일괄 설정일 경우
            if(preg_match('/^([0-9,]+)$/',$module_srl)) $module_srl = explode(',',$module_srl);
            else $module_srl = array($module_srl);
            if(!is_array($module_srl)) $module_srl[0] = $module_srl;

            $config_vars = Context::getRequestVars();

            $open_rss = $config_vars->open_rss;
            $open_total_feed = $config_vars->open_total_feed;
            $feed_description = trim($config_vars->feed_description);
            $feed_copyright = trim($config_vars->feed_copyright);

            if(!$module_srl || !$open_rss) return new Object(-1, 'msg_invalid_request');

            if(!in_array($open_rss, array('Y','H','N'))) $open_rss = 'N';

            // 설정 저장
            for($i=0;$i<count($module_srl);$i++) {
                $srl = trim($module_srl[$i]);
                if(!$srl) continue;
                $output = $this->setRssModuleConfig($srl, $open_rss, $open_total_feed, $feed_description, $feed_copyright);
            }

            $this->setError(-1);
            $this->setMessage('success_updated');
        }


        /**
         * @brief RSS모듈의 전체 Feed 설정용 함수
         **/
        function setFeedConfig($config) {
            $oModuleController = &getController('module');
            $oModuleController->insertModuleConfig('rss',$config);
            return new Object();
        }


        /**
         * @brief RSS 모듈별 설정 함수
         **/
        function setRssModuleConfig($module_srl, $open_rss, $open_total_feed = 'N', $feed_description = 'N', $feed_copyright = 'N') {
            $oModuleController = &getController('module');
            $config->open_rss = $open_rss;
            $config->open_total_feed = $open_total_feed;
            if($feed_description != 'N') { $config->feed_description = $feed_description; }
            if($feed_copyright != 'N') { $config->feed_copyright = $feed_copyright; }
            $oModuleController->insertModulePartConfig('rss',$module_srl,$config);
            return new Object();
        }
    }
?>
