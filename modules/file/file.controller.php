<?php
    /**
     * @class  fileController
     * @author zero (zero@nzeo.com)
     * @brief  file 모듈의 controller 클래스
     **/

    class fileController extends file {

        /**
         * @brief 초기화
         **/
        function init() {
        }


        /**
         * @brief 에디터에서 첨부파일 업로드 
         * editor_sequence, uploadTargetSrl 변수값을 받아서 이를 바탕으로 첨부 대상 srl을 결정함.
         * 만약 uploadTargetSrl이 없다면 새로 생성하고 return 하여 UI에서 이에 대한 값을 재설정하도록 하여
         * sync이상없도록 함
         **/
        function procFileUpload() {
            // 기본적으로 필요한 변수 설정
            $oFileModel = &getModel('file');
            $editor_sequence = Context::get('editor_sequence');
            $upload_target_srl = intval(Context::get('uploadTargetSrl'));
            $module_srl = $this->module_srl;

            // 업로드 권한이 없거나 정보가 없을시 종료
            if(!$_SESSION['upload_info'][$editor_sequence]->enabled) exit();

            // upload_target_srl 값이 명시되지 않았을 경우 세션정보에서 추출
            if(!$upload_target_srl) $upload_target_srl = $_SESSION['upload_info'][$editor_sequence]->upload_target_srl;

            // 세션정보에도 정의되지 않았다면 새로 생성
            if(!$upload_target_srl) $_SESSION['upload_info'][$editor_sequence]->upload_target_srl = $upload_target_srl = getNextSequence();

            $file_info = Context::get('Filedata');

            // 정상적으로 업로드된 파일이 아니면 오류 출력
            if(!is_uploaded_file($file_info['tmp_name'])) exit();

            return $this->insertFile($file_info, $module_srl, $upload_target_srl);
        }


        /**
         * @brief iframe 첨부파일 업로드 
         **/
        function procFileIframeUpload() {
            // 기본적으로 필요한 변수 설정
            $editor_sequence = Context::get('editor_sequence');
            $callback = Context::get('callback');
            $module_srl = $this->module_srl;
            $upload_target_srl = intval(Context::get('uploadTargetSrl'));

            // 업로드 권한이 없거나 정보가 없을시 종료
            if(!$_SESSION['upload_info'][$editor_sequence]->enabled) exit();

            // upload_target_srl 값이 명시되지 않았을 경우 세션정보에서 추출
            if(!$upload_target_srl) $upload_target_srl = $_SESSION['upload_info'][$editor_sequence]->upload_target_srl;

            // 세션정보에도 정의되지 않았다면 새로 생성
            if(!$upload_target_srl) $_SESSION['upload_info'][$editor_sequence]->upload_target_srl = $upload_target_srl = getNextSequence();

            // file_srl이 요청되었을 경우 삭제 후 재업로드 시도
            $file_srl = Context::get('file_srl');
            if($file_srl) $this->deleteFile($file_srl);

            $file_info = Context::get('Filedata');

            // 정상적으로 업로드된 파일이 아니면 오류 출력
            if(is_uploaded_file($file_info['tmp_name'])) {
                $output = $this->insertFile($file_info, $module_srl, $upload_target_srl);
                Context::set('uploaded_fileinfo',$output);
            }

            Context::set('layout','none');

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('iframe');

        }

        /**
         * @brief image resize
         **/
        function procFileImageResize() {
            $source_src = Context::get('source_src');
            $width = Context::get('width');
            $height = Context::get('height');
            $type = Context::get('type');
            $output_src = Context::get('output_src');

            if(!$source_src || !$width) return new Object(-1,'msg_invalid_request');
            if(!$output_src){
                $output_src = $source_src . '.resized' . strrchr($source_src,'.');
            }
            if(!$type) $type = 'ratio';
            if(!$height) $height = $width-1;

            if(FileHandler::createImageFile($source_src,$output_src,$width,$height,'','ratio')){
                $output->info = getimagesize($output_src);	
                $output->src = $output_src;
            }else{
                return new Object(-1,'msg_invalid_request');
            }

            $this->add('resized_info',$output);		
        }



        /**
         * @brief 첨부파일 다운로드
         * 직접 요청을 받음
         * file_srl : 파일의 sequence
         * sid : db에 저장된 비교 값, 틀리면 다운로드 하지 않음
         **/
        function procFileDownload() {
            $oFileModel = &getModel('file');

            $file_srl = Context::get('file_srl');
            $sid = Context::get('sid');
            $logged_info = Context::get('logged_info');

            // 파일의 정보를 DB에서 받아옴
            $file_obj = $oFileModel->getFile($file_srl);

            // 요청된 파일 정보가 잘못되었다면 파일을 찾을 수 없다는 오류 출력
            if($file_obj->file_srl!=$file_srl || $file_obj->sid!=$sid) return $this->stop('msg_file_not_found');

            // 대기 상태일 경우 파일 다운로드 권한이 없음을 알림 (최고관리자는 다운 로드 허용)
            if($logged_info->is_admin != 'Y' && $file_obj->isvalid!='Y') return $this->stop('msg_not_permitted_download');

            // 파일 이름
            $filename = $file_obj->source_filename;
            $file_module_config = $oFileModel->getFileModuleConfig($file_obj->module_srl);

            // 파일 외부링크 차단
            if($file_module_config->allow_outlink == 'N') {
                //외부링크 허용 확장자 처리
                if($file_module_config->allow_outlink_format) {
                    $allow_outlink_format_array = array();
                    $allow_outlink_format_array = explode(',', $file_module_config->allow_outlink_format);
                    if(!is_array($allow_outlink_format_array)) $allow_outlink_format_array[0] = $file_module_config->allow_outlink_format;

                    foreach($allow_outlink_format_array as $val) {
                        $val = trim($val);
                        if(preg_match("/\.{$val}$/i", $filename)) {
                            $file_module_config->allow_outlink = 'Y';
                            break;
                        }
                    }
                }
                //외부링크 허용 사이트 처리
                if($file_module_config->allow_outlink != 'Y') {
                    $referer = parse_url($_SERVER["HTTP_REFERER"]);
                    if($referer['host'] != $_SERVER['HTTP_HOST']) {
                        if($file_module_config->allow_outlink_site) {
                            $allow_outlink_site_array = array();
                            $allow_outlink_site_array = explode("\n", $file_module_config->allow_outlink_site);
                            if(!is_array($allow_outlink_site_array)) $allow_outlink_site_array[0] = $file_module_config->allow_outlink_site;

                            foreach($allow_outlink_site_array as $val) {
                                $site = parse_url(trim($val));
                                if($site['host'] == $referer['host']) {
                                    $file_module_config->allow_outlink = 'Y';
                                    break;
                                }
                            }
                        }
                    }
                    else $file_module_config->allow_outlink = 'Y';
                }
                if($file_module_config->allow_outlink != 'Y') return $this->stop('msg_not_allowed_outlink');
            }

            // 파일 다운로드 권한이 있는지 확인
            if(is_array($file_module_config->download_grant) && count($file_module_config->download_grant)>0) {
                if(!Context::get('is_logged')) return $this->stop('msg_not_permitted_download');
                $logged_info = Context::get('logged_info');
                if($logged_info->is_admin != 'Y') {

                    $oModuleModel =& getModel('module');
                    $module_info = $oModuleModel->getModuleInfoByModuleSrl($file_obj->module_srl);

                    if(!$oModuleModel->isSiteAdmin($logged_info, $module_info->site_srl))
                    {
                        $oMemberModel =& getModel('member');
                        $member_groups = $oMemberModel->getMemberGroups($logged_info->member_srl, $module_info->site_srl);

                        $is_permitted = false;
                        for($i=0;$i<count($file_module_config->download_grant);$i++) {
                            $group_srl = $file_module_config->download_grant[$i];
                            if($member_groups[$group_srl]) {
                                $is_permitted = true;
                                break;
                            }
                        }
                        if(!$is_permitted) return $this->stop('msg_not_permitted_download');
                    }
                }
            }

            // trigger 호출 (before)
            $output = ModuleHandler::triggerCall('file.downloadFile', 'before', $file_obj);
            if(!$output->toBool()) return $this->stop(($output->message)?$output->message:'msg_not_permitted_download');

            // 파일 출력
            if(strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
                $filename = rawurlencode($filename);
                $filename = preg_replace('/\./', '%2e', $filename, substr_count($filename, '.') - 1);
            }

            $uploaded_filename = $file_obj->uploaded_filename;
            if(!file_exists($uploaded_filename)) return $this->stop('msg_file_not_found');

            $fp = fopen($uploaded_filename, 'rb');
            if(!$fp) return $this->stop('msg_file_not_found');

            header("Cache-Control: "); 
            header("Pragma: "); 
            header("Content-Type: application/octet-stream"); 
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 

            header("Content-Length: " .(string)($file_obj->file_size)); 
            header('Content-Disposition: attachment; filename="'.$filename.'"'); 
            header("Content-Transfer-Encoding: binary\n"); 

            fpassthru($fp); 

            // 이상이 없으면 download_count 증가
            $args->file_srl = $file_srl;
            executeQuery('file.updateFileDownloadCount', $args);

            // trigger 호출 (after)
            $output = ModuleHandler::triggerCall('file.downloadFile', 'after', $file_obj);

            Context::close();

            exit();
        }

        /**
         * @brief 에디터에서 첨부 파일 삭제
         **/
        function procFileDelete() {
            // 기본적으로 필요한 변수인 upload_target_srl, module_srl을 설정
            $editor_sequence = Context::get('editor_sequence');
            $file_srl = Context::get('file_srl');
            $file_srls = Context::get('file_srls');
            if($file_srls) $file_srl = $file_srls;

            // 업로드 권한이 없거나 정보가 없을시 종료
            if(!$_SESSION['upload_info'][$editor_sequence]->enabled) exit();

            $upload_target_srl = $_SESSION['upload_info'][$editor_sequence]->upload_target_srl;
            if($upload_target_srl && $file_srl) $output = $this->deleteFile($file_srl);
        }

        /**
         * @brief 특정 upload_target_srl(document_srl)에 등록된 첨부파일의 갯수를 return하는 trigger
         **/
        function triggerCheckAttached(&$obj) {
            $document_srl = $obj->document_srl;
            if(!$document_srl) return new Object();

            // 첨부 파일의 갯수를 구함
            $oFileModel = &getModel('file');
            $obj->uploaded_count = $oFileModel->getFilesCount($document_srl);

            return new Object();
        }

        /**
         * @brief 특정 upload_target_srl(document_srl)에 등록된 첨부파일을 연결하는 trigger
         **/
        function triggerAttachFiles(&$obj) {
            $document_srl = $obj->document_srl;
            if(!$document_srl) return new Object();

            $output = $this->setFilesValid($document_srl);
            if(!$output->toBool()) return $output;

            return new Object();
        }

        /**
         * @brief 특정 upload_target_srl(document_srl)에 등록된 첨부파일을 삭제하는 trigger
         **/
        function triggerDeleteAttached(&$obj) {
            $document_srl = $obj->document_srl;
            if(!$document_srl) return new Object();

            $output = $this->deleteFiles($document_srl);
            return $output;
        }

        /**
         * @brief 특정 upload_target_srl(comment_srl)에 등록된 첨부파일의 갯수를 return하는 trigger
         **/
        function triggerCommentCheckAttached(&$obj) {
            $comment_srl = $obj->comment_srl;
            if(!$comment_srl) return new Object();

            // 첨부 파일의 갯수를 구함
            $oFileModel = &getModel('file');
            $obj->uploaded_count = $oFileModel->getFilesCount($comment_srl);

            return new Object();
        }

        /**
         * @brief 특정 upload_target_srl(comment_srl)에 등록된 첨부파일을 연결하는 trigger
         **/
        function triggerCommentAttachFiles(&$obj) {
            $comment_srl = $obj->comment_srl;
            $uploaded_count = $obj->uploaded_count;
            if(!$comment_srl || !$uploaded_count) return new Object();

            $output = $this->setFilesValid($comment_srl);
            if(!$output->toBool()) return $output;

            return new Object();
        }

        /**
         * @brief 특정 upload_target_srl(comment_srl)에 등록된 첨부파일을 삭제하는 trigger
         **/
        function triggerCommentDeleteAttached(&$obj) {
            $comment_srl = $obj->comment_srl;
            if(!$comment_srl) return new Object();

            $output = $this->deleteFiles($comment_srl);
            return $output;
        }

        /**
         * @brief module 삭제시 해당 첨부파일 모두 삭제하는 trigger
         **/
        function triggerDeleteModuleFiles(&$obj) {
            $module_srl = $obj->module_srl;
            if(!$module_srl) return new Object();

            $oFileController = &getAdminController('file');
            return $oFileController->deleteModuleFiles($module_srl);
        }

        /**
         * @brief 업로드 가능하다고 세팅
         **/
        function setUploadInfo($editor_sequence, $upload_target_srl=0) {
            $_SESSION['upload_info'][$editor_sequence]->enabled = true;
            $_SESSION['upload_info'][$editor_sequence]->upload_target_srl = $upload_target_srl;
        }

        /**
         * @brief 특정 upload_target_srl의 첨부파일들의 상태를 유효로 변경
         * 글이 등록될때 글에 첨부된 파일들의 상태를 유효상태로 변경함으로서 관리시 불필요 파일로 인식되지 않도록 함
         **/
        function setFilesValid($upload_target_srl) {
            $args->upload_target_srl = $upload_target_srl;
            return executeQuery('file.updateFileValid', $args);
        }

        /**
         * @brief 첨부파일 추가
         **/
        function insertFile($file_info, $module_srl, $upload_target_srl, $download_count = 0, $manual_insert = false) {
            // trigger 호출 (before)
            $trigger_obj->module_srl = $module_srl;
            $trigger_obj->upload_target_srl = $upload_target_srl;
            $output = ModuleHandler::triggerCall('file.insertFile', 'before', $trigger_obj);
            if(!$output->toBool()) return $output;

            if(!$manual_insert) {
                // 첨부파일 설정 가져옴
                $logged_info = Context::get('logged_info');
                if($logged_info->is_admin != 'Y') {
                    $oFileModel = &getModel('file');
                    $config = $oFileModel->getFileConfig($module_srl);
                    $allowed_filesize = $config->allowed_filesize * 1024 * 1024;
                    $allowed_attach_size = $config->allowed_attach_size * 1024 * 1024;

                    // 한 파일당 허용 용량 초과시 오류 출력
                    if($allowed_filesize < filesize($file_info['tmp_name'])) return new Object(-1, 'msg_exceeds_limit_size');


                    // 해당 문서에 첨부된 모든 파일의 용량을 가져옴 (DB에서 가져옴)
                    $size_args->upload_target_srl = $upload_target_srl;
                    $output = executeQuery('file.getAttachedFileSize', $size_args);
                    $attached_size = (int)$output->data->attached_size + filesize($file_info['tmp_name']);
                    if($attached_size > $allowed_attach_size) return new Object(-1, 'msg_exceeds_limit_size');
                }
            }

            // 이미지인지 기타 파일인지 체크하여 upload path 지정
            if(preg_match("/\.(jpg|jpeg|gif|png|wmv|wma|mpg|mpeg|avi|swf|flv|mp1|mp2|mp3|mp4|asf|wav|asx|mid|midi|asf|mov|moov|qt|rm|ram|ra|rmm|m4v)$/i", $file_info['name'])) {
                // direct 파일에 해킹을 의심할 수 있는 확장자가 포함되어 있으면 바로 삭제함
                $file_info['name'] = preg_replace('/\.(php|phtm|html|htm|cgi|pl|exe|jsp|asp|inc)/i', '$0-x',$file_info['name']);
                $file_info['name'] = str_replace(array('<','>'),array('%3C','%3E'),$file_info['name']);

                $path = sprintf("./files/attach/images/%s/%s", $module_srl,getNumberingPath($upload_target_srl,3));
                $filename = $path.$file_info['name'];
                $idx = 1;
                while(file_exists($filename)) {
                    $filename = $path.preg_replace('/\.([a-z0-9]+)$/i','_'.$idx.'.$1',$file_info['name']);
                    $idx++;
                }
                $direct_download = 'Y';
            } else {
                $path = sprintf("./files/attach/binaries/%s/%s", $module_srl, getNumberingPath($upload_target_srl,3));
                $filename = $path.md5(crypt(rand(1000000,900000), rand(0,100)));
                $direct_download = 'N';
            }

            // 디렉토리 생성
            if(!FileHandler::makeDir($path)) return new Object(-1,'msg_not_permitted_create');

            // 파일 이동
            if($manual_insert) {
                @copy($file_info['tmp_name'], $filename);
                if(!file_exists($filename)) {
                    $ext = substr(strrchr($file_info['name'],'.'),1);
                    $filename = $path. md5(crypt(rand(1000000,900000).$file_info['name'])).'.'.$ext;
                    @copy($file_info['tmp_name'], $filename);
                }
            } else {
                if(!@move_uploaded_file($file_info['tmp_name'], $filename)) {
                    $ext = substr(strrchr($file_info['name'],'.'),1);
                    $filename = $path. md5(crypt(rand(1000000,900000).$file_info['name'])).'.'.$ext;
                    if(!@move_uploaded_file($file_info['tmp_name'], $filename))  return new Object(-1,'msg_file_upload_error');
                }
            }

            // 사용자 정보를 구함
            $oMemberModel = &getModel('member');
            $member_srl = $oMemberModel->getLoggedMemberSrl();

            // 파일 정보를 정리
            $args->file_srl = getNextSequence();
            $args->upload_target_srl = $upload_target_srl;
            $args->module_srl = $module_srl;
            $args->direct_download = $direct_download;
            $args->source_filename = $file_info['name'];
            $args->uploaded_filename = $filename;
            $args->download_count = $download_count;
            $args->file_size = @filesize($filename);
            $args->comment = NULL;
            $args->member_srl = $member_srl;
            $args->sid = md5(rand(rand(1111111,4444444),rand(4444445,9999999)));

            $output = executeQuery('file.insertFile', $args);
            if(!$output->toBool()) return $output;

            // trigger 호출 (after)
            $trigger_output = ModuleHandler::triggerCall('file.insertFile', 'after', $args);
            if(!$trigger_output->toBool()) return $trigger_output;

            $output->add('file_srl', $args->file_srl);
            $output->add('file_size', $args->file_size);
            $output->add('sid', $args->sid);
            $output->add('direct_download', $args->direct_download);
            $output->add('source_filename', $args->source_filename);
            $output->add('upload_target_srl', $upload_target_srl);
            $output->add('uploaded_filename', $args->uploaded_filename);
            return $output;
        }

        /**
         * @brief 첨부파일 삭제
         **/
        function deleteFile($file_srl) {
            if(!$file_srl) return;

            $srls = explode(',',$file_srl);
            if(!count($srls)) return;

            for($i=0;$i<count($srls);$i++) {
                $srl = (int)$srls[$i];
                if(!$srl) continue;

                $args = null;
                $args->file_srl = $srl;
                $output = executeQuery('file.getFile', $args);
                if(!$output->toBool()) continue;

                $file_info = $output->data;
                if(!$file_info) continue;

                $source_filename = $output->data->source_filename;
                $uploaded_filename = $output->data->uploaded_filename;

                // trigger 호출 (before)
                $trigger_obj = $output->data;
                $output = ModuleHandler::triggerCall('file.deleteFile', 'before', $trigger_obj);
                if(!$output->toBool()) return $output;

                // DB에서 삭제
                $output = executeQuery('file.deleteFile', $args);
                if(!$output->toBool()) return $output;

                // trigger 호출 (after)
                $trigger_output = ModuleHandler::triggerCall('file.deleteFile', 'after', $trigger_obj);
                if(!$trigger_output->toBool()) return $trigger_output;

                // 삭제 성공하면 파일 삭제
                FileHandler::removeFile($uploaded_filename);
            }

            return $output;
        }

        /**
         * @brief 특정 문서의 첨부파일을 모두 삭제
         **/
        function deleteFiles($upload_target_srl) {
            // 첨부파일 목록을 받음
            $oFileModel = &getModel('file');
            $file_list = $oFileModel->getFiles($upload_target_srl);

            // 첨부파일이 없으면 성공 return
            if(!is_array($file_list)||!count($file_list)) return new Object();

            // DB에서 삭제 
            $args->upload_target_srl = $upload_target_srl;
            $output = executeQuery('file.deleteFiles', $args);
            if(!$output->toBool()) return $output;

            // 실제 파일 삭제
            $path = array();
            $file_count = count($file_list);
            for($i=0;$i<$file_count;$i++) {
                $uploaded_filename = $file_list[$i]->uploaded_filename;
                FileHandler::removeFile($uploaded_filename);
                $module_srl = $file_list[$i]->module_srl;

                $path_info = pathinfo($uploaded_filename);
                if(!in_array($path_info['dirname'], $path)) $path[] = $path_info['dirname'];
            }

            // 해당 글의 첨부파일 디렉토리 삭제
            for($i=0;$i<count($path);$i++) FileHandler::removeBlankDir($path[$i]);

            return $output;
        }

        /**
         * @brief 특정 글의 첨부파일을 다른 글로 이동
         **/
        function moveFile($source_srl, $target_module_srl, $target_srl) {
            if($source_srl == $target_srl) return;

            $oFileModel = &getModel('file');
            $file_list = $oFileModel->getFiles($source_srl);
            if(!$file_list) return;

            $file_count = count($file_list);

            for($i=0;$i<$file_count;$i++) {

                unset($file_info);
                $file_info = $file_list[$i];
                $old_file = $file_info->uploaded_filename;

                // 이미지인지 기타 파일인지 체크하여 이동할 위치 정함
                if(preg_match("/\.(jpg|jpeg|gif|png|wmv|wma|mpg|mpeg|avi|swf|flv|mp1|mp2|mp3|mp4|asf|wav|asx|mid|midi|asf|mov|moov|qt|rm|ram|ra|rmm|m4v)$/i", $file_info->source_filename)) {
                    $path = sprintf("./files/attach/images/%s/%s/", $target_module_srl,$target_srl);
                    $new_file = $path.$file_info->source_filename;
                } else {
                    $path = sprintf("./files/attach/binaries/%s/%s/", $target_module_srl, $target_srl);
                    $new_file = $path.md5(crypt(rand(1000000,900000), rand(0,100)));
                }

                // 이전 대상이 동일하면 그냥 패스
                if($old_file == $new_file) continue;

                // 디렉토리 생성
                FileHandler::makeDir($path);

                // 파일 이동
                FileHandler::rename($old_file, $new_file);

                // DB 정보도 수정
                unset($args);
                $args->file_srl = $file_info->file_srl;
                $args->uploaded_filename = $new_file;
                $args->module_srl = $file_info->module_srl;
                $args->upload_target_srl = $target_srl;
                executeQuery('file.updateFile', $args);
            }
        }

        /**
         * @brief upload_target_srl을 키로 하는 첨부파일을 찾아서 java script 코드로 return
         **/
        function printUploadedFileList($editor_sequence, $upload_target_srl) {
            return;
        }
    }
?>
