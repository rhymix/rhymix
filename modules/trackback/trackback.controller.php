<?php
    /**
     * @class  trackbackController
     * @author zero (zero@nzeo.com)
     * @brief  trackback모듈의 Controller class
     **/

    class trackbackController extends Module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 엮인글 입력
         **/
        function insertTrackback($obj) {
            $document_srl = $obj->document_srl;
            if(!$document_srl) $this->dispMessage(-1, 'fail');

            // document model 객체 생성후 원본글을 가져옴
            $oDocumentModel = getModel('document');
            $document = $oDocumentModel->getDocument($document_srl);

            // 원본글이 없거나 트랙백 허용을 하지 않으면 오류 표시
            if(!$document_srl) $this->dispMessage(-1,'fail');
            if($document->allow_trackback=='N') $this->dispMessage(-1,'fail');

            // 엮인글 정리
            $obj = Context::convertEncoding($obj);
            if(!$obj->blog_name) $obj->blog_name = $obj->title;
            $obj->excerpt = strip_tags($obj->excerpt);

            // 엮인글를 입력
            $oDB = &DB::getInstance();
            $obj->list_order = $obj->trackback_srl = $oDB->getNextSequence();
            $obj->module_srl = $document->module_srl;
            $output = $oDB->executeQuery('trackback.insertTrackback', $obj);

            // 입력에 이상이 없으면 해당 글의 엮인글 수를 올림
            if(!$output->toBool()) $this->dispMessage(-1, 'fail');

            // trackback model 객체 생성
            $oTrackbackModel = getModel('trackback');

            // 해당 글의 전체 엮인글 수를 구해옴
            $trackback_count = $oTrackbackModel->getTrackbackCount($document_srl);

            // document controller 객체 생성
            $oDocumentController = getController('document');

            // 해당글의 엮인글 수를 업데이트
            $output = $oDocumentController->updateTrackbackCount($document_srl, $trackback_count);

            if(!$output->toBool()) $this->dispMessage(-1,'fail');
            else $this->dispMessage(0,'success');
        }

        /**
         * @brief 단일 엮인글 삭제
         **/
        function deleteTrackback($trackback_srl) {
            // trackback model 객체 생성
            $oTrackbackModel = getModel('trackback');

            // 삭제하려는 엮인글이 있는지 확인
            $trackback = $oTrackbackModel->getTrackback($trackback_srl);
            if($trackback->data->trackback_srl != $trackback_srl) return new Object(-1, 'msg_invalid_request');
            $document_srl = $trackback->data->document_srl;

            // document model 객체 생성
            $oDocumentModel = getModel('document');

            // 권한이 있는지 확인
            if(!$oDocumentModel->isGranted($document_srl)) return new Object(-1, 'msg_not_permitted');

            // 삭제
            $oDB = &DB::getInstance();
            $args->trackback_srl = $trackback_srl;
            $output = $oDB->executeQuery('trackback.deleteTrackback', $args);
            if(!$output->toBool()) return new Object(-1, 'msg_error_occured');

            // 엮인글 수를 구해서 업데이트
            $trackback_count = $oTrackbackModel->getTrackbackCount($document_srl);

            // document controller 객체 생성
            $oDocumentController = getController('document','controller');

            // 해당글의 엮인글 수를 업데이트
            $output = $oDocumentController->updateTrackbackCount($document_srl, $trackback_count);
            $output->add('document_srl', $document_srl);
            return $output;
        }

        /**
         * @brief 글에 속한 모든 트랙백 삭제
         **/
        function deleteTrackbacks($document_srl) {
            $oDB = &DB::getInstance();
            $args->document_srl = $document_srl;
            $output = $oDB->executeQuery('trackback.deleteTrackbacks', $args);
            return $output;
        }

        /**
         * @brief 모듈에 속한 모든 트랙백 삭제
         **/
        function deleteModuleTrackbacks($module_srl) {
            // 삭제
            $oDB = &DB::getInstance();
            $args->module_srl = $module_srl;
            $output = $oDB->executeQuery('trackback.deleteModuleTrackbacks', $args);
            return $output;
        }

        /**
         * @brief 엮인글을 발송
         **/
        function sendTrackback($document, $trackback_url, $charset) {
            // 발송할 정보를 정리
            $http = parse_url($trackback_url);
            $obj->blog_name = Context::getBrowserTitle();
            $obj->title = $document->title;
            $obj->excerpt = cut_str($document->content, 240);
            $obj->url = sprintf("%s?document_srl=%d", Context::getRequestUri(), $document->document_srl);

            // blog_name, title, excerpt, url의 문자열을 요청된 charset으로 변경
            if($charset && function_exists('iconv')) {
                foreach($obj as $key=>$val) {
                    $obj->{$key} = iconv('UTF-8',$charset,$val);
                }
            }

            // socket으로 발송할 내용 작성
            if($http['query']) $http['query'].="&";
            if(!$http['port']) $http['port'] = 80;

            $content =
                sprintf(
                    "title=%s&".
                    "url=%s&".
                    "blog_name=%s&".
                    "excerpt=%s",
                    urlencode($obj->title),
                    urlencode($obj->url),
                    urlencode($obj->blog_name),
                    urlencode($obj->excerpt)
                );
            if($http['query']) $content .= '&'.$http['query'];
            $content_length = strlen($content);

            $header =
            sprintf(
                "POST %s HTTP/1.1\r\n".
                "Host: %s\r\n".
                "Content-Type: %s\r\n".
                "Content-Length: %s\r\n\r\n".
                "%s\r\n",
                $http['path'],
                $http['host'],
                "application/x-www-form-urlencoded",
                $content_length,
                $content
            );
            if(!$http['host']||!$http['port']) return;

            $fp = @fsockopen($http['host'], $http['port'], $errno, $errstr, 5);
            if(!$fp) return;

            fputs($fp, $header);

            while(!feof($fp)) {
                $line = trim(fgets($fp, 4096));
                if(eregi("^<error>",$line)) break;
            }

            fclose($fp);
        }
    }
?>
