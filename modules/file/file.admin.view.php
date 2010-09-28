<?php
    /**
     * @class  fileAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief  file 모듈의 admin view 클래스
     **/

    class fileAdminView extends file {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 목록 출력 (관리자용)
         **/
        function dispFileAdminList() {
            // 목록을 구하기 위한 옵션
            $args->page = Context::get('page'); ///< 페이지
            $args->list_count = 30; ///< 한페이지에 보여줄 글 수
            $args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수

            $args->sort_index = 'file_srl'; ///< 소팅 값
            $args->isvalid = Context::get('isvalid');
            $args->module_srl = Context::get('module_srl');

            // 목록 구함
            $oFileModel = &getAdminModel('file');
            $output = $oFileModel->getFileList($args);

            // 목록의 loop를 돌면서 document를 구하기
            if($output->data) {
                $oCommentModel = &getModel('comment');
                $oDocumentModel = &getModel('document');
                $oModuleModel = &getModel('module');

                $file_list = array();
                $document_list = array();
                $comment_list = array();
                $module_list= array();

                $doc_srls = array();
                $com_srls = array();
                $mod_srls= array();

                foreach($output->data as $file) {
                    $file_srl = $file->file_srl;
                    $target_srl = $file->upload_target_srl;
                    $file_update_args = null;
                    $file_update_args->file_srl = $file_srl;

                    // upload_target_type이 없으면 찾아서 업데이트
                    if(!$file->upload_target_type) {
                        // 찾아둔게 있으면 패스
                        if($document_list[$target_srl]) {
                            $file->upload_target_type = 'doc';
                        } else if($comment_list[$target_srl]) {
                            $file->upload_target_type = 'com';
                        } else if($module_list[$target_srl]) {
                            $file->upload_target_type = 'mod';
                        } else {
                            // document
                            $document = $oDocumentModel->getDocument($target_srl);
                            if($document->isExists()) {
                                $file->upload_target_type = 'doc';
                                $file_update_args->upload_target_type = $file->upload_target_type;
                                $document_list[$target_srl] = $document;
                            }
                            // comment
                            if(!$file->upload_target_type) {
                                $comment = $oCommentModel->getComment($target_srl);
                                if($comment->isExists()) {
                                    $file->upload_target_type = 'com';
                                    $file->target_document_srl = $comment->document_srl;
                                    $file_update_args->upload_target_type = $file->upload_target_type;
                                    $comment_list[$target_srl] = $comment;
                                    $doc_srls[] = $comment->document_srl;
                                }
                            }
                            // module (페이지인 경우)
                            if(!$file->upload_target_type) {
                                $module = $oModuleModel->getModulesInfo($target_srl);
                                if($module) {
                                    $file->upload_target_type = 'mod';
                                    $file_update_args->upload_target_type = $file->upload_target_type;
                                    $module_list[$module->comment_srl] = $module;
                                }
                            }
                            if($file_update_args->upload_target_type) {
                                executeQuery('file.updateFileTargetType', $file_update_args);
                            }
                        }

                        // 이미 구해진 데이터가 있는 확인
                        for($i = 0; $i < $com_srls_count; ++$i) {
                            if($comment_list[$com_srls[$i]]) delete($com_srls[$i]);
                        }
                        for($i = 0; $i < $doc_srls_count; ++$i) {
                            if($document_list[$doc_srls[$i]]) delete($doc_srls[$i]);
                        }
                        for($i = 0; $i < $mod_srls_count; ++$i) {
                            if($module_list[$mod_srls[$i]]) delete($mod_srls[$i]);
                        }
                    }

                    if($file->upload_target_type) {
                        if(!in_array($file->upload_target_srl, ${$file->upload_target_type.'_srls'})) {
                            ${$file->upload_target_type.'_srls'}[] = $target_srl;
                        }
                    }

                    $file_list[$file_srl] = $file;
                    $mod_srls[] = $file->module_srl;
                }

                // 중복 제거
                $doc_srls = array_unique($doc_srls);
                $com_srls = array_unique($com_srls);
                $mod_srls = array_unique($mod_srls);

                // 댓글 목록
                $com_srls_count = count($com_srls);
                if($com_srls_count) {
                    $comment_output = $oCommentModel->getComments($com_srls);
                    foreach($comment_output as $comment) {
                        $comment_list[$comment->comment_srl] = $comment;
                        $doc_srls[] = $comment->document_srl;
                    }
                }

                // 문서 목록
                $doc_srls_count = count($doc_srls);
                if($doc_srls_count) {
                    $document_output = $oDocumentModel->getDocuments($doc_srls);
                    foreach($document_output as $document) {
                        $document_list[$document->document_srl] = $document;
                    }
                }

                // 모듈 목록
                $mod_srls_count = count($mod_srls);
                if($mod_srls_count) {
                    $module_output = $oModuleModel->getModulesInfo($mod_srls);
					if($module_output && is_array($module_output)){
						foreach($module_output as $module) {
							$module_list[$module->module_srl] = $module;
						}
					}
                }

                foreach($file_list as $srl => $file) {
                    if($file->upload_target_type == 'com') {
                        $file_list[$srl]->target_document_srl = $comment_list[$file->upload_target_srl]->document_srl;
                    }
                }
            }
            Context::set('file_list', $file_list);
            Context::set('document_list', $document_list);
            Context::set('comment_list', $comment_list);
            Context::set('module_list', $module_list);
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('file_list');
        }

        /**
         * @brief 첨부파일 정보 설정 (관리자용)
         **/
        function dispFileAdminConfig() {
            $oFileModel = &getModel('file');
            $config = $oFileModel->getFileConfig();
            Context::set('config',$config);

            // 템플릿 파일 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('file_config');
        }

    }
?>
