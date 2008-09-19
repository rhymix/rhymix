<?php
    /**
     * @class  tccommentnotifier controller
     * @author haneul (haneul0318@gmail.com) 
     * @brief  tccommentnotifier 모듈의 controller class
     **/

    class tccommentnotifyModel extends tccommentnotify {

        function init() {
        }

        function checkShouldNotify()
        {
            if( file_exists($this->cachedir.$this->cachefile) )
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        function GetSite($url)
        {
            if(!$url) return new Object(-1, "parameter error");

            $args->url = $url;
            $output = executeQuery('tccommentnotify.getSite', $args);
            if(!$output->toBool())
            {
                return -2;
            }
            if(!$output->data)
            {
                return -1;
            }
                
            $siteid = $output->data->id;

            if( is_array($siteid) )
            {
                $siteid = array_shift($siteid);
            }
            
            return $siteid;
        }

        function GetCommentID( $parent_srl, $remoteid )
        {
            $args->parent_srl = $parent_srl;
            $args->remoteid = $remoteid;
            $output = executeQuery('tccommentnotify.getChildId', $args);
            if(!$output->data)
            {
                return -1;
            }

            $commentid = $output->data->notified_srl;
            if( is_array($commentid) )
            {
                $commentid = array_shift($commentid);
            }
            return $commentid;
        }

        function GetParentID( $entry, $siteid, $module_srl, $remoteid )
        {
            $args->entry = $entry;
            $args->siteid = $siteid;
            $args->module_srl = $module_srl;
            $args->remoteid = $remoteid;
            $output = executeQuery('tccommentnotify.getParentId', $args);

            if(!$output->toBool())
            {
                return -2;
            }
            if(!$output->data)
            {
                return -1;
            }
                
            $parentid = $output->data->notified_srl;

            if( is_array($parentid) )
            {
                $parentid = array_shift($parentid);
            }
            return $parentid;
        }

        function GetNotifiedList($args)
        {
            $args->parent_srl = 0;
            return executeQuery("tccommentnotify.getNotifiedList", $args);
        }

        function GetChildren($parentid)
        {
            $args->parent_srl = $parentid;
            $output = executeQueryArray("tccommentnotify.getChildren", $args);
            return $output->data;
        }

        function GetChild($notified_srl)
        {
            $args->notified_srl = $notified_srl;
            $output = executeQuery("tccommentnotify.getChild", $args);
            return $output;
        }

        function GetCommentsFromNotifyQueue()
        {
            $output = executeQueryArray("tccommentnotify.getFromQueue");
            return $output;
        }
    }
?>
