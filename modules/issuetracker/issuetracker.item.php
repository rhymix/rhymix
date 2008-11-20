<?php

    require_once("./modules/document/document.item.php");

    class issueItem extends documentItem {

        var $milestone = null; 
        var $priority = null;
        var $type = null;
        var $status = null;
        var $component = null;
        var $occured_version = null;
        var $closed_status = array('invalid', 'resolve');
        
        function issueItem($document_srl = 0) {
            parent::documentItem($document_srl);
        }

        function setIssue($document_srl) {
            $this->document_srl = $document_srl;
            $this->_loadFromDB();
        }

        function setProjectInfo($variables) {
            $this->adds($variables);

            $oIssuetrackerModel = &getModel('issuetracker');
            $project = &$oIssuetrackerModel->getProjectInfo($this->get('module_srl'));

            if($this->get('milestone_srl') && count($project->milestones)) {
                foreach($project->milestones as $val) {
                    if($this->get('milestone_srl')==$val->milestone_srl) {
                        $this->milestone = $val;
                        break;
                    }
                }
            }

            if($this->get('priority_srl') && count($project->priorities)) {
                foreach($project->priorities as $val) {
                    if($this->get('priority_srl')==$val->priority_srl) {
                        $this->priority = $val;
                        break;
                    }
                }
            }

            if($this->get('type_srl') && count($project->types)) {
                foreach($project->types as $val) {
                    if($this->get('type_srl')==$val->type_srl) {
                        $this->type = $val;
                        break;
                    }
                }
            }

            $this->status = $this->get('status');

            if($this->get('component_srl') && count($project->components)) {
                foreach($project->components as $val) {
                    if($this->get('component_srl')==$val->component_srl) {
                        $this->component = $val;
                        break;
                    }
                }
            }

            if($this->get('occured_version_srl') && count($project->releases)) {
                foreach($project->releases as $val) {
                    if($this->get('occured_version_srl')==$val->release_srl) {
                        $this->occured_version = $val;
                        break;
                    }
                }
            }

            if($this->occured_version) {
                foreach($project->packages as $val) {
                    if($this->occured_version->package_srl==$val->package_srl) {
                        $this->package = $val;
                        $this->add('package_srl', $val->package_srl);
                        break;
                    }
                }
            }
        }

        function _loadFromDB() {
            if(!$this->document_srl) return;
            parent::_loadFromDB();

            $obj->target_srl = $this->document_srl;
            $output = executeQuery("issuetracker.getIssue", $obj);
            if(!$output->toBool()) return;

            $this->setProjectInfo($output->data);
        }

        function getMilestoneTitle() {
            if($this->milestone) return $this->milestone->title;
        }

        function getTypeTitle() {
            if($this->type) return $this->type->title;
        }

        function getPriorityTitle() {
            if($this->priority) return $this->priority->title;
        }

        function getComponentTitle() {
            if($this->component) return $this->component->title;
        }

        function getResolutionTitle() {
            if($this->resolution) return $this->resolution->title;
        }

        function getStatus() {
            $status_lang = Context::getLang('status_list');
            return $status_lang[$this->status];
        }

        function getOccuredVersionTitle() {
            if($this->occured_version) return $this->occured_version->title;
        }

        function getReleaseTitle() {
            return $this->getOccuredVersionTitle();
        }

        function getPackageTitle() {
            if($this->package) return $this->package->title;
        }

        function getContent($add_popup_menu = true, $add_content_info = true, $resource_realpath = false) {
            $content = parent::getContent($add_content_info, $add_content_info, $resource_realpath);
            preg_match_all('/r([0-9]+)/',$content, $mat);
            for($k=0;$k<count($mat[1]);$k++) {
                $content = str_replace('r'.$mat[1][$k], sprintf('<a href="%s" onclick="window.open(this.href); return false;">%s</a>',getUrl('','mid',Context::get('mid'),'act','dispIssuetrackerViewSource','type','compare','erev',$mat[1][$k],'brev',''), 'r'.$mat[1][$k]), $content);
            }
            return $content;
        }

        function isClosed() {
            return in_array($this->status, $this->closed_status);
        }
    }
?>
