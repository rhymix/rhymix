<?php
    /**
     * @class  trackbackController
     * @author zero (zero@nzeo.com)
     * @brief  trackback모듈의 Controller class
     **/

    class trackbackController extends trackback {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 엮인글 입력
         **/
        function trackback() {
            Context::setRequestMethod("XMLRPC");

            $obj = Context::gets('document_srl','blog_name','url','title','excerpt');

            if(!$obj->document_srl || !$obj->url || !$obj->title || !$obj->excerpt) return $this->stop('fail');

            return $this->insertTrackback($obj);
        }

        function insertTrackback($obj, $manual_inserted = false) {
            // GET으로 넘어온 document_srl을 참조, 없으면 오류~
            $document_srl = $obj->document_srl;

            if(!$manual_inserted) {
                // document model 객체 생성후 원본글을 가져옴
                $oDocumentModel = &getModel('document');
                $oDocument = $oDocumentModel->getDocument($document_srl);

                // 원본글이 없거나 트랙백 허용을 하지 않으면 오류 표시
                if(!$oDocument->isExists()) return $this->stop('fail');
                if(!$oDocument->allowTrackback()) return new Object(-1,'fail');

                $obj->module_srl = $oDocument->get('module_srl');
            }

            // 엮인글 정리
            $obj = Context::convertEncoding($obj);
            if(!$obj->blog_name) $obj->blog_name = $obj->title;
            $obj->excerpt = strip_tags($obj->excerpt);

            // 엮인글를 입력
            $obj->trackback_srl = getNextSequence();
            $obj->list_order = $obj->trackback_srl*-1;
            $output = executeQuery('trackback.insertTrackback', $obj);
            if(!$output->toBool()) return $output;

            // 입력에 이상이 없으면 해당 글의 엮인글 수를 올림
            if(!$manual_inserted) {
                // trackback model 객체 생성
                $oTrackbackModel = &getModel('trackback');

                // 해당 글의 전체 엮인글 수를 구해옴
                $trackback_count = $oTrackbackModel->getTrackbackCount($document_srl);

                // document controller 객체 생성
                $oDocumentController = &getController('document');

                // 해당글의 엮인글 수를 업데이트
                $output = $oDocumentController->updateTrackbackCount($document_srl, $trackback_count);

                // 결과 return
                if(!$output->toBool()) return $output;
            }

            // 원본글에 알림(notify_message)가 설정되어 있으면 메세지 보냄
            if(!$manual_inserted) $oDocument->notify(Context::getLang('trackback'), $obj->excerpt);


            return new Object();
        }

        /**
         * @brief 단일 엮인글 삭제
         **/
        function deleteTrackback($trackback_srl, $is_admin = false) {
            // trackback model 객체 생성
            $oTrackbackModel = &getModel('trackback');

            // 삭제하려는 엮인글이 있는지 확인
            $trackback = $oTrackbackModel->getTrackback($trackback_srl);
            if($trackback->data->trackback_srl != $trackback_srl) return new Object(-1, 'msg_invalid_request');
            $document_srl = $trackback->data->document_srl;

            // document model 객체 생성
            $oDocumentModel = &getModel('document');

            // 권한이 있는지 확인
            if(!$is_admin && !$oDocumentModel->isGranted($document_srl)) return new Object(-1, 'msg_not_permitted');

            $args->trackback_srl = $trackback_srl;
            $output = executeQuery('trackback.deleteTrackback', $args);
            if(!$output->toBool()) return new Object(-1, 'msg_error_occured');

            // 엮인글 수를 구해서 업데이트
            $trackback_count = $oTrackbackModel->getTrackbackCount($document_srl);

            // document controller 객체 생성
            $oDocumentController = &getController('document','controller');

            // 해당글의 엮인글 수를 업데이트
            $output = $oDocumentController->updateTrackbackCount($document_srl, $trackback_count);
            $output->add('document_srl', $document_srl);

            return $output;
        }

        /**
         * @brief 글에 속한 모든 트랙백 삭제
         **/
        function deleteTrackbacks($document_srl) {
            // 삭제
            $args->document_srl = $document_srl;
            $output = executeQuery('trackback.deleteTrackbacks', $args);

            return $output;
        }

        /**
         * @brief 엮인글을 발송
         *
         * 발송 후 결과처리는 하지 않는 구조임
         **/
        function sendTrackback($document, $trackback_url, $charset) {
            // 발송할 정보를 정리
            $http = parse_url($trackback_url);
            $obj->blog_name = Context::getBrowserTitle();
            $obj->title = $document->title;
            $obj->excerpt = cut_str(strip_tags($document->content), 240);
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

            // header 정리
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

            // 발송하려는 대상 서버의 socket을 연다
            $fp = @fsockopen($http['host'], $http['port'], $errno, $errstr, 5);
            if(!$fp) return;

            // 작성한 헤더 정보를 발송
            fputs($fp, $header);

            // 결과를 기다림 (특정 서버의 경우 EOF가 떨어지지 않을 수가 있음
            while(!feof($fp)) {
                $line = trim(fgets($fp, 4096));
                if(eregi("^<error>",$line)) break;
            }

            // socket 닫음
            fclose($fp);
        }
    }
?>
