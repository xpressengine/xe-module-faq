<?php
    /**
     * @class  faqItem (question)
     * @author NHN (developers@xpressengine.com)
     * @brief  faq module faqItem class
     **/

    class faqItem extends Object {

        var $question_srl = 0;
        var $lang_code = null;


		function faqItem($question_srl = 0, $load_extra_vars = true) {
            $this->question_srl = $question_srl;
            $this->_loadFromDB($load_extra_vars);
        }

        function setQuestion($question_srl, $load_extra_vars = true) {
            $this->question_srl = $question_srl;
            $this->_loadFromDB($load_extra_vars);
        }

		function _loadFromDB($load_extra_vars=true) {
            if(!$this->question_srl) return;

            $args->question_srl = $this->question_srl;
            $output = executeQuery('faq.getQuestion', $args);
            $this->setAttribute($output->data,$load_extra_vars);
			
        }

        function isExists() {
            return $this->question_srl ? true : false;
        }

        function isEditable() {
			if($this->isGranted()) return true;
			else return false;
        }
        function isGranted() {
            if(!Context::get('is_logged')) return false;
            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin == 'Y') return true;

			$oModuleModel = getModel('module');
			$grant = $oModuleModel->getGrant($oModuleModel->getModuleInfoByModuleSrl($this->get('module_srl')), $logged_info);
			if($grant->manager) return true;
            if($this->get('member_srl') && $this->get('member_srl') == $logged_info->member_srl) return true;
            return false;
        }


        function getQuestion($cut_size = 0, $tail='...') {
            if(!$this->question_srl) return;

            $question = $this->getQuestionText($cut_size, $tail);

            $attrs = array();

            if(count($attrs)) return sprintf("<span style=\"%s\">%s</span>", implode(';',$attrs), htmlspecialchars($question));
            else return htmlspecialchars($question);
        }

        function getQuestionText($cut_size = 0, $tail='...') {
            if(!$this->question_srl) return;

            if($cut_size) $question = cut_str($this->get('question'), $cut_size, $tail);
            else $question = $this->get('question');

            return $question;
        }

        /**
         * @brief return get editor
         **/
        function getEditor() {
            $module_srl = $this->get('module_srl');
            if(!$module_srl) $module_srl = Context::get('module_srl');
			
			// do not use auto_save 
			//$GLOBALS['__editor_module_config__'][$module_srl]->enable_autosave = 'N';

            $oEditorModel = getModel('editor');
            return $oEditorModel->getModuleEditor('document', $module_srl, $this->question_srl, 'question_srl', 'answer');
        }

        function getAnswer() {
            if(!$this->question_srl) return;

            $answer = $this->get('answer');
            if(!$stripEmbedTagException) stripEmbedTagForAdmin($answer, $this->get('member_srl'));

            // rewrite answer
            $oContext = &Context::getInstance();
            if($oContext->allow_rewrite) {
                $content = preg_replace('/<a([ \t]+)href=("|\')\.\/\?/i',"<a href=\\2". Context::getRequestUri() ."?", $content);
            }

            return $answer;
        }

        function getAnswerText($strlen = 0) {
            if(!$this->question_srl) return;

            $_SESSION['accessible'][$this->question_srl] = true;

            $answer = $this->get('answer');

            if($strlen) return cut_str(strip_tags($answer),$strlen,'...');

            return htmlspecialchars($answer);
        }

        function setAttribute($attribute,$load_extra_vars=true) {
            if(!$attribute->question_srl) {
                $this->question_srl = null;
                return;
            }
            $this->question_srl = $attribute->question_srl;
            $this->lang_code = $attribute->lang_code;
            $this->adds($attribute);

            $oFaqModel = getModel('faq');
            $GLOBALS['XE_QUESTION_LIST'][$this->question_srl] = $this;

        }

     
    }
?>
