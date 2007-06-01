<?php
    /**
     * @class  blogController
     * @author zero (zero@nzeo.com)
     * @brief  blog 모듈의 Controller class
     **/

    class blogController extends blog {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 문서 입력
         **/
        function procBlogInsertDocument() {
            // 글작성시 필요한 변수를 세팅
            $obj = Context::getRequestVars();
            $obj->module_srl = $this->module_srl;
            if($obj->is_notice!='Y'||!$this->grant->manager) $obj->is_notice = 'N';

            // document module의 model 객체 생성
            $oDocumentModel = &getModel('document');

            // document module의 controller 객체 생성
            $oDocumentController = &getController('document');

            // 이미 존재하는 글인지 체크
            $document = $oDocumentModel->getDocument($obj->document_srl, $this->grant->manager);

            // 이미 존재하는 경우 수정
            if($document->document_srl == $obj->document_srl) {
                $output = $oDocumentController->updateDocument($document, $obj);
                $msg_code = 'success_updated';

            // 그렇지 않으면 신규 등록
            } else {
                $output = $oDocumentController->insertDocument($obj);
                $msg_code = 'success_registed';
                $obj->document_srl = $output->get('document_srl');
            }

            // 오류 발생시 멈춤
            if(!$output->toBool()) return $output;

            // 트랙백이 있으면 트랙백 발송
            $trackback_url = Context::get('trackback_url');
            $trackback_charset = Context::get('trackback_charset');
            if($trackback_url) {
                $oTrackbackController = &getController('trackback');
                $oTrackbackController->sendTrackback($obj, $trackback_url, $trackback_charset);
            }

            // 결과를 리턴
            $this->add('mid', Context::get('mid'));
            $this->add('document_srl', $output->get('document_srl'));

            // 성공 메세지 등록
            $this->setMessage($msg_code);
        }

        /**
         * @brief 문서 삭제
         **/
        function procBlogDeleteDocument() {
            // 문서 번호 확인
            $document_srl = Context::get('document_srl');

            // 문서 번호가 없다면 오류 발생
            if(!$document_srl) return $this->doError('msg_invalid_document');

            // document module model 객체 생성
            $oDocumentController = &getController('document');

            // 삭제 시도
            $output = $oDocumentController->deleteDocument($document_srl, $this->grant->manager);
            if(!$output->toBool()) return $output;

            // 성공 메세지 등록
            $this->add('mid', Context::get('mid'));
            $this->add('page', $output->get('page'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 코멘트 추가
         **/
        function procBlogInsertComment() {
            // 댓글 입력에 필요한 데이터 추출
            $obj = Context::gets('document_srl','comment_srl','parent_srl','content','password','nick_name','nick_name','member_srl','email_address','homepage');
            $obj->module_srl = $this->module_srl;

            // comment 모듈의 model 객체 생성
            $oCommentModel = &getModel('comment');

            // comment 모듈의 controller 객체 생성
            $oCommentController = &getController('comment');

            // comment_srl이 존재하는지 체크
            $comment = $oCommentModel->getComment($obj->comment_srl, $this->grant->manager);

            // comment_srl이 없을 경우 신규 입력
            if($comment->comment_srl != $obj->comment_srl) {

                // parent_srl이 있으면 답변으로
                if($obj->parent_srl) {
                    $parent_comment = $oCommentModel->getComment($obj->parent_srl);
                    if(!$parent_comment->comment_srl) return new Object(-1, 'msg_invalid_request');

                    $output = $oCommentController->insertComment($obj);

                // 없으면 신규
                } else {
                    $output = $oCommentController->insertComment($obj);
                }

            // comment_srl이 있으면 수정으로
            } else {
                $obj->parent_srl = $comment->parent_srl;
                $output = $oCommentController->updateComment($obj);
                $comment_srl = $obj->comment_srl;
            }

            if(!$output->toBool()) return $output;

            $this->setMessage('success_registed');
            $this->add('mid', Context::get('mid'));
            $this->add('document_srl', $obj->document_srl);
            $this->add('comment_srl', $comment_srl);
        }

        /**
         * @brief 코멘트 삭제
         **/
        function procBlogDeleteComment() {
            // 댓글 번호 확인
            $comment_srl = Context::get('comment_srl');
            if(!$comment_srl) return $this->doError('msg_invalid_request');

            // comment 모듈의 controller 객체 생성
            $oCommentController = &getController('comment');

            $output = $oCommentController->deleteComment($comment_srl, $this->grant->manager);
            if(!$output->toBool()) return $output;

            $this->add('mid', Context::get('mid'));
            $this->add('page', Context::get('page'));
            $this->add('document_srl', $output->get('document_srl'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 엮인글 삭제
         **/
        function procBlogDeleteTrackback() {
            $trackback_srl = Context::get('trackback_srl');

            // trackback module의 controller 객체 생성
            $oTrackbackController = &getController('trackback');
            $output = $oTrackbackController->deleteTrackback($trackback_srl, $this->grant->manager);
            if(!$output->toBool()) return $output;

            $this->add('mid', Context::get('mid'));
            $this->add('page', Context::get('page'));
            $this->add('document_srl', $output->get('document_srl'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 문서와 댓글의 비밀번호를 확인
         **/
        function procBlogVerificationPassword() {
            // 비밀번호와 문서 번호를 받음
            $password = md5(Context::get('password'));
            $document_srl = Context::get('document_srl');
            $comment_srl = Context::get('comment_srl');

            // comment_srl이 있을 경우 댓글이 대상
            if($comment_srl) {
                // 문서번호에 해당하는 글이 있는지 확인
                $oCommentModel = &getModel('comment');
                $data = $oCommentModel->getComment($comment_srl);
                // comment_srl이 없으면 문서가 대상
            } else {
                // 문서번호에 해당하는 글이 있는지 확인
                $oDocumentModel = &getModel('document');
                $data = $oDocumentModel->getDocument($document_srl);
            }

            // 글이 없을 경우 에러
            if(!$data) return new Object(-1, 'msg_invalid_request');

            // 문서의 비밀번호와 입력한 비밀번호의 비교
            if($data->password != $password) return new Object(-1, 'msg_invalid_password');

            // 해당 글에 대한 권한 부여
            if($comment_srl) {
                $oCommentController = &getController('comment');
                $oCommentController->addGrant($comment_srl);
            } else {
                $oDocumentController = &getController('document');
                $oDocumentController->addGrant($document_srl);
            }
        }

        /**
         * @brief 권한 추가
         **/
        function procBlogAdminInsertGrant() {
            $module_srl = Context::get('module_srl');

            // 현 모듈의 권한 목록을 가져옴
            $grant_list = $this->xml_info->grant;

            if(count($grant_list)) {
                foreach($grant_list as $key => $val) {
                    $group_srls = Context::get($key);
                    if($group_srls) $arr_grant[$key] = explode('|@|',$group_srls);
                }
                $grants = serialize($arr_grant);
            }

            $oModuleController = &getController('module');
            $oModuleController->updateModuleGrant($module_srl, $grants);

            $this->add('module_srl',Context::get('module_srl'));
            $this->setMessage('success_registed');
        }

        /**
         * @brief 스킨 정보 업데이트
         **/
        function procBlogAdminUpdateSkinInfo() {
            // module_srl에 해당하는 정보들을 가져오기
            $module_srl = Context::get('module_srl');
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            $skin = $module_info->skin;

            // 스킨의 정보르 구해옴 (extra_vars를 체크하기 위해서)
            $skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);

            // 입력받은 변수들을 체크 (mo, act, module_srl, page등 기본적인 변수들 없앰)
            $obj = Context::getRequestVars();
            unset($obj->act);
            unset($obj->module_srl);
            unset($obj->page);

            // 원 skin_info에서 extra_vars의 type이 image일 경우 별도 처리를 해줌
            if($skin_info->extra_vars) {
                foreach($skin_info->extra_vars as $vars) {
                    if($vars->type!='image') continue;

                    $image_obj = $obj->{$vars->name};

                    // 삭제 요청에 대한 변수를 구함
                    $del_var = $obj->{"del_".$vars->name};
                    unset($obj->{"del_".$vars->name});
                    if($del_var == 'Y') {
                        @unlink($module_info->{$vars->name});
                        continue;
                    }

                    // 업로드 되지 않았다면 이전 데이터를 그대로 사용
                    if(!$image_obj['tmp_name']) {
                        $obj->{$vars->name} = $module_info->{$vars->name};
                        continue;
                    }

                    // 정상적으로 업로드된 파일이 아니면 무시
                    if(!is_uploaded_file($image_obj['tmp_name'])) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 이미지 파일이 아니어도 무시
                    if(!eregi("\.(jpg|jpeg|gif|png)$", $image_obj['name'])) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 경로를 정해서 업로드
                    $path = sprintf("./files/attach/images/%s/", $module_srl);

                    // 디렉토리 생성
                    if(!FileHandler::makeDir($path)) return false;

                    $filename = $path.$image_obj['name'];

                    // 파일 이동
                    if(!move_uploaded_file($image_obj['tmp_name'], $filename)) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 변수를 바꿈
                    unset($obj->{$vars->name});
                    $obj->{$vars->name} = $filename;
                }
            }

            // 메뉴 관리
            $menus = get_object_vars($skin_info->menu);
            if(count($menus)) {
                foreach($menus as $menu_id => $val) {
                    $menu_srl = Context::get($menu_id);
                    if($menu_srl) {
                        $obj->menu->{$menu_id} = $menu_srl;
                        $obj->{$menu_id} = $menu_srl;
                        $menu_srl_list[] = $menu_srl;
                    }
                }

                // 정해진 메뉴가 있으면 모듈 및 메뉴에 대한 레이아웃 연동
                if(count($menu_srl_list)) {
                    // 해당 메뉴와 레이아웃 값을 매핑
                    $oMenuController = &getController('menu');
                    $oMenuController->updateMenuLayout($module_srl, $menu_srl_list);

                    // 해당 메뉴에 속한 mid의 layout값을 모두 변경
                    $oModuleController = &getController('module');
                    $oModuleController->updateModuleLayout($module_srl, $menu_srl_list);
                }
            }

            // serialize하여 저장
            $obj->category_xml_file = sprintf("./files/cache/blog_category/%s.xml.php", $module_srl);
            $obj->mid = $module_info->mid;
            $skin_vars = serialize($obj);

            $oModuleController = &getController('module');
            $oModuleController->updateModuleSkinVars($module_srl, $skin_vars);

            // 레이아웃 확장변수 수정
            $layout_args->extra_vars = $skin_vars;
            $layout_args->layout_srl = $module_srl;
            $oLayoutController = &getController('layout');
            $output = $oLayoutController->updateLayout($layout_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            $this->setLayoutPath('./common/tpl');
            $this->setLayoutFile('default_layout.html');
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile("top_refresh.html");
        }

        /**
         * @brief 블로그 추가
         **/
        function procBlogAdminInsertBlog() {
            // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
            $args = Context::gets('module_srl','module_category_srl','blog_name','skin','browser_title','description','is_default','header_text','footer_text','admin_id','open_rss');
            $args->module = 'blog';
            $args->mid = $args->blog_name;
            unset($args->blog_name);
            if($args->is_default!='Y') $args->is_default = 'N';

            // 기본 값외의 것들을 정리
            $extra_var = delObjectVars(Context::getRequestVars(), $args);
            unset($extra_var->act);
            unset($extra_var->page);
            unset($extra_var->blog_name);

            $oDB = &DB::getInstance();
            $oDB->begin();

            // module_srl이 넘어오면 원 모듈이 있는지 확인
            if($args->module_srl) {
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);

                // 만약 원래 모듈이 없으면 새로 입력하기 위한 처리
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }

            // $extra_var를 serialize
            $args->extra_vars = serialize($extra_var);

            // module 모듈의 controller 객체 생성
            $oModuleController = &getController('module');

            // is_default=='Y' 이면
            if($args->is_default=='Y') $oModuleController->clearDefaultModule();

            // module_srl의 값에 따라 insert/update
            if(!$args->module_srl) {
                // 블로그 등록
                $output = $oModuleController->insertModule($args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                // 글작성, 파일첨부, 댓글 파일첨부, 관리에 대한 권한 지정
                if($output->toBool()) {
                    $oMemberModel = &getModel('member');
                    $admin_group = $oMemberModel->getAdminGroup();
                    $admin_group_srl = $admin_group->group_srl;

                    $module_srl = $output->get('module_srl');
                    $grants = serialize(array('write_document'=>array($admin_group_srl), 'fileupload'=>array($admin_group_srl), 'comment_fileupload'=>array($admin_group_srl), 'manager'=>array($admin_group_srl)));

                    $output = $oModuleController->updateModuleGrant($module_srl, $grants);
                    if(!$output->toBool()) {
                        $oDB->rollback();
                        return $output;
                    }
                }

                // 레이아웃 등록
                $layout_args->layout_srl = $layout_args->module_srl = $module_srl;
                $layout_args->layout = 'blog';
                $layout_args->title = sprintf('%s - %s',$args->browser_title, $args->mid);
                $layout_args->layout_path = sprintf('./modules/blog/skins/%s/layout.html', $args->skin);

                $oLayoutController = &getController('layout');
                $output = $oLayoutController->insertLayout($layout_args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                // 기본 카테고리 등록
                $category_args->module_srl = $module_srl;
                $category_args->category_srl = getNextSequence();
                $category_args->name = 'Story';
                $category_args->expand = 'N';
                $this->procBlogAdminInsertCategory($category_args);

                $msg_code = 'success_registed';
            } else {
                // 블로그 데이터 수정
                $output = $oModuleController->updateModule($args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                // 레이아웃 수정
                $layout_args->layout_srl = $layout_args->module_srl = $module_srl;
                $layout_args->title = $args->browser_title;
                $layout_args->layout_path = sprintf('./modules/blog/skins/%s/layout.html', $args->skin);

                $oLayoutController = &getController('layout');
                $output = $oLayoutController->updateLayout($layout_args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                $msg_code = 'success_updated';
            }

            $oDB->commit();

            $this->add('page',Context::get('page'));
            $this->add('module_srl',$output->get('module_srl'));
            $this->setMessage($msg_code);
        }

        /**
         * @brief 블로그 삭제
         **/
        function procBlogAdminDeleteBlog() {
            $module_srl = Context::get('module_srl');

            $oDB = &DB::getInstance();
            $oDB->begin();

            // 블로그 모듈 삭제
            $oModuleController = &getController('module');
            $output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 레이아웃 삭제
            $layout_args->layout_srl = $layout_args->module_srl = $module_srl;

            $oLayoutController = &getController('layout');
            $output = $oLayoutController->deleteLayout($layout_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 블로그 카테고리 삭제
            $category_args->module_srl = $module_srl;
            $output = executeQuery('blog.deleteCategories', $category_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }
            @unlink( sprintf('./files/cache/blog_category/%d.xml.php', $module_srl) );

            $oDB->commit();

            $this->add('module','blog');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 카테고리 추가
         **/
        function procBlogAdminInsertCategory($args = null) {
            // 입력할 변수 정리
            if(!$args) $args = Context::gets('module_srl','category_srl','parent_srl','name','expand','group_srls');

            if($args->expand !="Y") $args->expand = "N";
            $args->group_srls = str_replace('|@|',',',$args->group_srls);
            $args->parent_srl = (int)$args->parent_srl;

            $oDB = &DB::getInstance();
            $oDB->begin();

            // 이미 존재하는지를 확인
            $oBlogModel = &getModel('blog');
            $category_info = $oBlogModel->getCategoryInfo($args->category_srl);

            // 존재하게 되면 update를 해준다
            if($category_info->category_srl == $args->category_srl) {
                $output = executeQuery('blog.updateCategory', $args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                $oDocumentController = &getController('document');
                $document_args->category_srl = $args->category_srl;
                $document_args->title = $args->name ;
                $output = $oDocumentController->updateCategory($document_args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

            // 존재하지 않으면 insert를 해준다
            } else {
                $args->listorder = -1*$args->category_srl;
                $output = executeQuery('blog.insertCategory', $args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                $oDocumentController = &getController('document');
                $output = $oDocumentController->insertCategory($args->module_srl, $args->name, $args->category_srl);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }
            }

            // XML 파일을 갱신하고 위치을 넘겨 받음
            $xml_file = $this->makeXmlFile($args->module_srl);

            $oDB->commit();

            $this->add('xml_file', $xml_file);
            $this->add('module_srl', $args->module_srl);
            $this->add('category_srl', $args->category_srl);
            $this->add('parent_srl', $args->parent_srl);
        }

        /**
         * @brief 카테고리 삭제
         **/
        function procBlogAdminDeleteCategory() {
            // 변수 정리 
            $args = Context::gets('module_srl','category_srl');

            $oDB = &DB::getInstance();
            $oDB->begin();

            $oBlogModel = &getModel('blog');

            // 원정보를 가져옴 
            $category_info = $oBlogModel->getCategoryInfo($args->category_srl);
            if($category_info->parent_srl) $parent_srl = $category_info->parent_srl;

            // 자식 노드가 있는지 체크하여 있으면 삭제 못한다는 에러 출력
            $output = executeQuery('blog.getChildCategoryCount', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            if($output->data->count>0) {
                $oDB->rollback();
                return new Object(-1, 'msg_cannot_delete_for_child');
            }

            // DB에서 삭제
            $output = executeQuery("blog.deleteCategory", $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            $oDocumentController = &getController('document');
            $output = $oDocumentController->deleteCategory($args->category_srl);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // XML 파일을 갱신하고 위치을 넘겨 받음
            $xml_file = $this->makeXmlFile($args->module_srl);

            $oDB->commit();

            $this->add('xml_file', $xml_file);
            $this->add('category_srl', $parent_srl);
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 카테고리 이동
         **/
        function procBlogAdminMoveCategory() {
            $source_category_srl = Context::get('source_category_srl');
            $target_category_srl = Context::get('target_category_srl');

            $oBlogModel = &getModel('blog');
            $target_category = $oBlogModel->getCategoryInfo($target_category_srl);
            $source_category = $oBlogModel->getCategoryInfo($source_category_srl);

            // source_category에 target_category_srl의 parent_srl, listorder 값을 입력
            $source_args->category_srl = $source_category_srl;
            $source_args->parent_srl = $target_category->parent_srl;
            $source_args->listorder = $target_category->listorder;
            $output = executeQuery('blog.updateCategoryParent', $source_args);
            if(!$output->toBool()) return $output;

            // target_category의 listorder값을 +1해 준다
            $target_args->category_srl = $target_category_srl;
            $target_args->parent_srl = $target_category->parent_srl;
            $target_args->listorder = $target_category->listorder -1;
            $output = executeQuery('blog.updateCategoryParent', $target_args);
            if(!$output->toBool()) return $output;

            // xml파일 재생성 
            $xml_file = $this->makeXmlFile($target_category->module_srl);

            // return 변수 설정
            $this->add('xml_file', $xml_file);
            $this->add('source_category_srl', $source_category_srl);
        }

        /**
         * @brief xml 파일을 갱신
         * 관리자페이지에서 메뉴 구성 후 간혹 xml파일이 재생성 안되는 경우가 있는데\n
         * 이럴 경우 관리자의 수동 갱신 기능을 구현해줌\n
         * 개발 중간의 문제인 것 같고 현재는 문제가 생기지 않으나 굳이 없앨 필요 없는 기능
         **/
        function procBlogAdminMakeXmlFile() {
            // 입력값을 체크 
            $module_srl = Context::get('module_srl');

            // xml파일 재생성 
            $xml_file = $this->makeXmlFile($module_srl);

            // return 값 설정 
            $this->add('xml_file',$xml_file);
        }

        /**
         * @brief 블로그 카테고리를 xml파일로 저장
         **/
        function makeXmlFile($module_srl) {
            // xml파일 생성시 필요한 정보가 없으면 그냥 return
            if(!$module_srl) return;

            // 캐시 파일의 이름을 지정
            $xml_file = sprintf("./files/cache/blog_category/%s.xml.php", $module_srl);

            // 모듈정보를 구해옴
            $oModuleModel = &getModel('module');
            $this->module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);

            // DB에서 module_srl 에 해당하는 메뉴 아이템 목록을 listorder순으로 구해옴 
            $oBlogModel = &getModel('blog');
            $list = $oBlogModel->getCategoryList($module_srl);

            // 구해온 데이터가 없다면 노드데이터가 없는 xml 파일만 생성
            if(!$list) {
                $xml_buff = "<root />";
                FileHandler::writeFile($xml_file, $xml_buff);
                return $xml_file;
            }

            // 구해온 데이터가 하나라면 array로 바꾸어줌
            if(!is_array($list)) $list = array($list);

            // 루프를 돌면서 tree 구성
            $list_count = count($list);
            for($i=0;$i<$list_count;$i++) {
                $node = $list[$i];
                $category_srl = $node->category_srl;
                $parent_srl = $node->parent_srl;

                $tree[$parent_srl][$category_srl] = $node;
            }

            // xml 캐시 파일 생성
            $xml_buff = sprintf('<?php header("Content-Type: text/xml; charset=UTF-8"); header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); header("Cache-Control: no-store, no-cache, must-revalidate"); header("Cache-Control: post-check=0, pre-check=0", false); header("Pragma: no-cache"); @session_start(); ?><root>%s</root>', $this->getXmlTree($tree[0], $tree));

            // 파일 저장
            FileHandler::writeFile($xml_file, $xml_buff);
            return $xml_file;
        }

        /**
         * @brief array로 정렬된 노드들을 parent_srl을 참조하면서 recursive하게 돌면서 xml 데이터 생성
         * 메뉴 xml파일은 node라는 tag가 중첩으로 사용되며 이 xml doc으로 관리자 페이지에서 메뉴를 구성해줌\n
         * (tree_menu.js 에서 xml파일을 바로 읽고 tree menu를 구현)
         **/
        function getXmlTree($source_node, $tree) {
            if(!$source_node) return;
            foreach($source_node as $category_srl => $node) {
                $child_buff = "";

                // 자식 노드의 데이터 가져옴
                if($category_srl && $tree[$category_srl]) $child_buff = $this->getXmlTree($tree[$category_srl], $tree);

                // 변수 정리 
                $name = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->name);
                $expand = $node->expand;
                $group_srls = $node->group_srls;

                // node->group_srls값이 있으면 
                if($group_srls) $group_check_code = sprintf('($_SESSION["is_admin"]==true||(is_array($_SESSION["group_srls"])&&count(array_intersect($_SESSION["group_srls"], array(%s)))))',$group_srls);
                else $group_check_code = "true";

                $attribute = sprintf(
                        'node_srl="%s" text="<?=(%s?"%s":"")?>" url="%s" expand="%s" ',
                        $category_srl,
                        $group_check_code,
                        $name,
                        getUrl('','mid',$this->module_info->mid,'category',$category_srl),
                        $expand
                );
                
                if($child_buff) $buff .= sprintf('<node %s>%s</node>', $attribute, $child_buff);
                else $buff .=  sprintf('<node %s />', $attribute);
            }
            return $buff;
        }
    }
?>
