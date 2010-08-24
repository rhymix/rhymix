<?php
    /**
     * @class  getSyndicationList
     * @author zero (skklove@gmail.com)
     * @brief  syndication 모듈의 model class
     **/

    class syndicationModel extends syndication {

        var $site_url = null;
        var $target_services = array();
        var $year = null;
        var $langs = array();
        var $granted_modules = array();

        function init() {
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('syndication');
            $this->site_url = preg_replace('/\/+$/is','',$config->site_url);
            $this->target_services = $config->target_services;
            $this->year = $config->year;

            $output = executeQueryArray('syndication.getGrantedModules');
            if($output->data) {
                foreach($output->data as $key => $val) {
                    $this->granted_modules[] = $val->module_srl;
                }
            }
        }

        function isExceptedModules($module_srl) {
            $args->module_srl = $module_srl;
            $output = executeQuery('syndication.getExceptModule', $args);
            if($output->data->count) return true;
            $output = executeQuery('syndication.getGrantedModule', $args);
            if($output->data->count) return true;
            return false;

        }

        function getLang($key, $site_srl)
        {
            if(!$this->langs[$site_srl])
            {
                $this->langs[$site_srl] = array();
                $args->site_srl = $site_srl;
                $args->lang_code = Context::getLangType();
                $output = executeQueryArray("syndication.getLang", $args);
                if(!$output->toBool() || !$output->data) return $key;
                foreach($output->data as $value)
                {
                    $this->langs[$site_srl][$value->name] = $value->value;
                }
            }
            if($this->langs[$site_srl][$key])
            {
                return $this->langs[$site_srl][$key];
            }
            else return $key;
        }

        function handleLang($title, $site_srl)
        {
            $matches = null;
            if(!preg_match("/\\\$user_lang->(.+)/",$title, $matches)) return $title;
            else 
            {
                return $this->getLang($matches[1], $site_srl); 
            }
        }

        function getSyndicationList() {
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('syndication');
			if(!$config->year || !$config->site_url) return new Object(-1,'msg_check_syndication_config');

            $id = Context::get('id');
            $type = Context::get('type');
            $page = Context::get('page');
            if(!$id || !$type) return new Object(-1,'msg_invalid_request');

			if(!preg_match('/^tag:([^,]+),([0-9]+):(site|channel|article)(.*)$/i',$id,$matches)) return new Object(-1,'msg_invalid_request');

            $url = $matches[1];
            $year = $matches[2];
			$target = $matches[3];
			$id = $matches[4];
			if($id && $id{0}==':') $id = substr($id, 1);

            if($id && strpos($id,'-')!==false) list($module_srl, $document_srl) = explode('-',$id);
            elseif($id) $module_srl = $id;
            if(!$url || !$year || !$target) return new Object(-1,'msg_invalid_request');

            $startTime = Context::get('start-time');
            $endTime = Context::get('end-time');

            $time_zone = substr($GLOBALS['_time_zone'],0,3).':'.substr($GLOBALS['_time_zone'],3);
            Context::set('time_zone', $time_zone);

            $site_module_info = Context::get('site_module_info');

            if($target == 'channel' && !$module_srl) $target = 'site';
            if($target == 'channel' && $module_srl) {
                $args->module_srls = $module_srl;
                $output = executeQuery('syndication.getModules', $args);
                $module_info = $output->data;
                if($module_info) {
                    $args->module_srl = $module_srl;
                    $output = executeQuery('syndication.getExceptModules', $args);
                    if($output->data->count) $error = 'target is not founded';
                } else $error = 'target is not founded';

                unset($args);
            }

            if(!$error) {
                Context::set('target', $target);
                Context::set('type', $type);
                switch($target) {
                    case 'site' :
                            $site_info->id = $this->getID('site');
                            $site_info->title = $this->handleLang($site_module_info->browser_title, $site_module_info->site_srl);

                            $output = executeQuery('syndication.getSiteUpdatedTime');
                            if($output->data) $site_info->updated = date("Y-m-d\\TH:i:s", ztime($output->data->last_update)).$time_zone;
                            $site_info->self_href = $this->getSelfHref($site_info->id,$type);
                            $site_info->alternative_href =$this->getAlternativeHref();
                            Context::set('site_info', $site_info);

                            $this->setTemplateFile('site');
                            switch($type) {
                                case 'channel' :
                                        Context::set('channels', $this->getChannels());
                                    break;
                                case 'article' :
                                        Context::set('articles', $this->getArticles(null, $page, $startTime, $endTime, 'article',$site_info->id));
                                    break;
                                case 'deleted' :
                                    Context::set('deleted', $this->getDeleted(null, $page, $startTime, $endTime, 'deleted',$site_info->id));
                                    break;
                                default :
                                        $this->setTemplateFile('site.info');
                                    break;
                            }
                        break;
                    case 'channel' :
                            $channel_info->id = $this->getID('channel', $module_info->module_srl);
                            $channel_info->title = $this->handleLang($module_info->browser_title, $module_info->site_srl);
                            $channel_info->updated = date("Y-m-d\\TH:i:s").$time_zone;
                            $channel_info->self_href = $this->getSelfHref($channel_info->id, $type);
                            $channel_info->alternative_href = $this->getAlternativeHref($module_info);
                            $channel_info->summary = $module_info->description;
                            if($module_info->module == "textyle")
                            {
                                $channel_info->type = "blog";
                                $channel_info->rss_href = getFullSiteUrl($module_info->domain, '', 'mid', $module_info->mid, 'act', 'rss');
                            }
                            else
                            {
                                $channel_info->type = "web";
                            }
                            $output = executeQuery('syndication.getSiteUpdatedTime');
                            if($output->data) $channel_info->updated = date("Y-m-d\\TH:i:s", ztime($output->data->last_update)).$time_zone;
                            Context::set('channel_info', $channel_info);

                            $this->setTemplateFile('channel');
                            switch($type) {
                                case 'article' :
                                        Context::set('articles', $this->getArticles($module_srl, $page, $startTime, $endTime, 'article', $channel_info->id));
                                    break;
                                case 'deleted' :
                                        Context::set('deleted', $this->getDeleted($module_srl, $page, $startTime, $endTime, 'deleted', $channel_info->id));
                                    break;
                                default :
                                        $this->setTemplateFile('channel.info');
                                    break;
                            }
                        break;
						
						case 'article':
							Context::set('article', $this->getArticle($document_srl));
							$this->setTemplateFile('include.articles');
						break;
                }
            } else {
                Context::set('message', $error);
                $this->setTemplateFile('error');
            }

            $this->setTemplatePath($this->module_path.'tpl');
            Context::setResponseMethod('XMLRPC');
        }

        function getChannels() {
            if($module_srls) $args->module_srls = $module_srls;
            if(count($this->granted_modules)) $args->except_module_srls = implode(',',$this->granted_modules);
            $output = executeQueryArray('syndication.getModules', $args);
            if($output->data) {
                foreach($output->data as $module_info) {
                    unset($obj);
                    $obj->id = $this->getID('channel', $module_info->module_srl);
                    $obj->title = $this->handleLang($module_info->browser_title, $module_info->site_srl);
                    $obj->updated = date("Y-m-d\\TH:i:s").$time_zone;
                    $obj->self_href = $this->getSelfHref($obj->id, 'channel');
                    $obj->alternative_href = $this->getAlternativeHref($module_info);
                    $obj->summary = $module_info->description;
                    if($module_info->module == "textyle")
                    {
                        $obj->type = "blog";
                        $obj->rss_href = getFullSiteUrl($module_info->domain, '', 'mid', $module_info->mid, 'act', 'rss');
                    }
                    else
                    {
                        $obj->type = "web";
                    }

                    $list[] = $obj;
                }
            }
            return $list;
        }

        function getArticle($document_srl) {
            if($this->site_url==null) $this->init();

			$oDocumentModel = &getModel('document');
			$oDocument = $oDocumentModel->getDocument($document_srl,false,false);
			if(!$oDocument->isExists()) return;

			$val = $oDocument->getObjectVars();		

			$val->id = $this->getID('article', $val->module_srl.'-'.$val->document_srl);
			$val->updated = date("Y-m-d\\TH:i:s", ztime($val->last_update)).$GLOBALS['_time_zone'];
			$val->alternative_href = getFullSiteUrl($this->site_url, '', 'document_srl', $val->document_srl);
			$val->channel_alternative_href = $this->getChannelAlternativeHref($val->module_srl);
			$val->channel_id = $this->getID('channel', $val->module_srl.'-'.$val->document_srl);
			if(!$val->nick_name) $val->nick_name = $val->user_name;

            return $val;
        }

        function getArticles($module_srl = null, $page=1, $startTime = null, $endTime = null, $type = null, $id = null) {
            if($this->site_url==null) $this->init();

            if($module_srl) $args->module_srl = $module_srl;
            if($startTime) $args->start_date = $this->getDate($startTime);
            if($endTime) $args->end_date = $this->getDate($endTime);
            if(count($this->granted_modules)) $args->except_module_srls = implode(',',$this->granted_modules);
            $args->page = $page;
            $output = executeQueryArray('syndication.getDocumentList', $args);
            $cur_page = $output->page_navigation->cur_page;
            $total_page = $output->page_navigation->last_page;

            $result->next_url = null;
            $result->list = array();

            if($cur_page<$total_page) {
                $next_url = $this->getSelfHref($id, $type);
                if($startTime) $next_url .= '&startTime='.$startTime;
                if($endTime) $next_url .= '&endTime='.$endTime;
                $result->next_url = $next_url.'&page='.($cur_page+1);
            }

            if($output->data) {
                foreach($output->data as $key => $val) {
                    $val->id = $this->getID('article', $val->module_srl.'-'.$val->document_srl);
                    $val->updated = date("Y-m-d\\TH:i:s", ztime($val->last_update)).$GLOBALS['_time_zone'];
                    $val->alternative_href = getFullSiteUrl($this->site_url, '', 'document_srl', $val->document_srl);
                    $val->channel_alternative_href = $this->getChannelAlternativeHref($val->module_srl);
                    $val->channel_id = $this->getID('channel', $val->module_srl.'-'.$val->document_srl);
                    if(!$val->nick_name) $val->nick_name = $val->user_name;
                    $output->data[$key] = $val;
                }
                $result->list = $output->data;
            }
            return $result;
        }

        function getDeleted($module_srl = null, $page = 1, $startTime = null, $endTime = null, $type = null, $id = null) {
            if($this->site_url==null) $this->init();

            if($module_srl) $args->module_srl= $module_srl;
            if($startTime) $args->start_date = $this->getDate($startTime);
            if($endTime) $args->end_date = $this->getDate($endTime);
            $args->page = $page;

            $output = executeQueryArray('syndication.getDeletedList', $args);

            $cur_page = $output->page_navigation->cur_page;
            $total_page = $output->page_navigation->last_page;

            $result->next_url = null;
            $result->list = array();

            if($cur_page<$total_page) {
                $next_url = $this->getSelfHref($id, $type);
                if($startTime) $next_url .= '&startTime='.$startTime;
                if($endTime) $next_url .= '&endTime='.$endTime;
                $result->next_url = $next_url . '&page='.($cur_page+1);
            }

            if($output->data) {
                foreach($output->data as $key => $val) {
                    $val->id = $this->getID('article', $val->module_srl.'-'.$val->document_srl);
                    $val->deleted = date("Y-m-d\\TH:i:s", ztime($val->regdate)).$GLOBALS['_time_zone'];
                    $val->alternative_href = getFullSiteUrl($this->site_url, '', 'document_srl', $val->document_srl);
                    $val->channel_id = $this->getID('channel', $val->module_srl.'-'.$val->document_srl);
                    $output->data[$key] = $val;
                }
                $result->list = $output->data;
            }
            return $result;
        }

        function getID($type, $target_id = null) {
            if($this->site_url==null) $this->init();

            return sprintf('tag:%s,%d:%s', $this->site_url, $this->year, $type) . ($target_id?':'.$target_id:'');
        }

        function getChannelAlternativeHref($module_srl) {
            static $module_info = array();
            if(!isset($module_info[$module_srl])) {
                $args->module_srl = $module_srl;
                $output = executeQuery('syndication.getModuleSiteInfo', $args);
                if($output->data) $module_info[$module_srl] = $output->data;
                else $module_info[$module_srl] = null;
            }

            if(is_null($module_info[$module_srl])) return $this->site_url;

            $domain = $module_info[$module_srl]->domain;
            $url = getFullSiteUrl($domain, '', 'mid', $module_info[$module_srl]->mid);
            if(substr($url,0,1)=='/') $domain = 'http://'.$this->site_url.$url;
            return $url;
        }

        function getSelfHref($id, $type = null) {
            if($this->site_url==null) $this->init();

            return  sprintf('http://%s/?module=syndication&act=getSyndicationList&id=%s&type=%s', $this->site_url, $id, $type);
        }

        function getAlternativeHref($module_info = null) {
            if($this->site_url==null) $this->init();

            if(!$module_info) return sprintf('http://%s', $this->site_url);
            if(!$module_info->site_srl) return getFullUrl('', 'mid', $module_info->mid);

            $domain = $module_info->domain;
            $url = getFullSiteUrl($domain, '', 'mid', $module_info->mid);

            if(substr($url,0,1)=='/') $domain = 'http://'.$this->site_url.$url;
            return $url;
        }

        function getDate($date) {
            $time = strtotime($date);
            if($time == -1) $time = ztime(str_replace(array('-','T',':'),'',$date));
            return date('YmdHis', $time);
        }
    }
?>
