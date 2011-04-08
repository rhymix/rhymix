<?php
    /**
     * @class  pollAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief The admin controller class of the poll module
     **/

    class pollAdminController extends poll {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Save the configurations
         **/
        function procPollAdminInsertConfig() {
            $config->skin = Context::get('skin');
            $config->colorset = Context::get('colorset');

            $oModuleController = &getController('module');
            $oModuleController->insertModuleConfig('poll', $config);

            $this->setMessage('success_updated');
        }

        /**
         * @brief Delete the polls selected in the administrator's page
         **/
        function procPollAdminDeleteChecked() {
            // Display an error no post is selected
            $cart = Context::get('cart');
            if(!$cart) return $this->stop('msg_cart_is_null');

            $poll_srl_list= explode('|@|', $cart);
            $poll_count = count($poll_srl_list);
            if(!$poll_count) return $this->stop('msg_cart_is_null');
            // Delete the post
            for($i=0;$i<$poll_count;$i++) {
                $poll_index_srl = trim($poll_srl_list[$i]);
                if(!$poll_index_srl) continue;

                $output = $this->deletePollTitle($poll_index_srl, true);
                if(!$output->toBool()) return $output;
            }

            $this->setMessage( sprintf(Context::getLang('msg_checked_poll_is_deleted'), $poll_count) );
        }

        /**
         * @brief Delete the poll (when several questions are registered in one poll, delete this question)
         **/
        function deletePollTitle($poll_index_srl) {
            $args->poll_index_srl = $poll_index_srl;

            $oDB = &DB::getInstance();
            $oDB->begin();

            $output = $oDB->executeQuery('poll.deletePollTitle', $args);
            if(!$output) {
                $oDB->rollback();
                return $output;
            }

            $output = $oDB->executeQuery('poll.deletePollItem', $args);
            if(!$output) {
                $oDB->rollback();
                return $output;
            }

            $oDB->commit();

            return new Object();
        }

        /**
         * @brief Delete the poll (delete the entire poll)
         **/
        function deletePoll($poll_srl) {
            $args->poll_srl = $poll_srl;

            $oDB = &DB::getInstance();
            $oDB->begin();

            $output = $oDB->executeQuery('poll.deletePoll', $args);
            if(!$output) {
                $oDB->rollback();
                return $output;
            }

            $output = $oDB->executeQuery('poll.deletePollTitle', $args);
            if(!$output) {
                $oDB->rollback();
                return $output;
            }

            $output = $oDB->executeQuery('poll.deletePollItem', $args);
            if(!$output) {
                $oDB->rollback();
                return $output;
            }

            $oDB->commit();

            return new Object();
        }
    }
?>
