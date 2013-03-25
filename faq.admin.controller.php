<?php
    /**
     * @class  faqAdminController
     * @author NHN (developers@nhn.com)
     * @brief  faq module admin controller class
     **/

    class faqAdminController extends faq {

        /**
         * @brief initialization
         **/
        function init() {
        }

        /**
         * @brief insert FAQ module
         **/
        function procFaqAdminInsertFaq($args = null) {
            // get module model/module controller
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            // get variables from admin page form
            if(!$args) $args = Context::getRequestVars();
            $args->module = 'faq';
            $args->mid = $args->faq_name;
            unset($args->faq_name);
 
			// set up addtional variables
			if($args->use_category!='Y') $args->use_category = 'N';
			if($args->allow_keywords!='Y') $args->allow_keywords = 'N';
			
			$args->faq_keywords = trim($args->faq_keywords);
	
			if(!in_array($args->order_target,$this->order_target)) $args->order_target = 'list_order';
            if(!in_array($args->order_type,array('asc','desc'))) $args->order_type = 'asc';

			// if module_srl exists
            if($args->module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }

            // insert/update faq module, depending on whether module_srl exists or not 
            if(!$args->module_srl) {
                $output = $oModuleController->insertModule($args);
                $msg_code = 'success_registed';
            } else {
                $output = $oModuleController->updateModule($args);
                $msg_code = 'success_updated';
            }

            if(!$output->toBool()) return $output;

            $this->add('page',Context::get('page'));
            $this->add('module_srl',$output->get('module_srl'));
            $this->setMessage($msg_code);
 
        	if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'module_srl', $output->get('module_srl'), 'act', 'dispFaqAdminFaqInfo');
				header('location:'.$returnUrl);
				return;
			}
        }

        /**
         * @brief delete FAQ module
         **/
        function procFaqAdminDeleteFaq() {
            $module_srl = Context::get('module_srl');

			$obj->module_srl = $module_srl;
			$oFaqModel = &getModel('faq');
			$oQuestionList = $oFaqModel->getQuestionList($obj);
			$oCategoryList = $oFaqModel->getAllCategoryList($obj->module_srl,0);

			// delete module's question
			$oFaqController = &getController('faq');
			if(count($oQuestionList->data)>0){
				foreach($oQuestionList->data as $oQuestion){
					$oFaqController->deleteQuestion($oQuestion->question_srl);
				}
			}
			
			//delete module's categories
			if(count($oCategoryList->data)>0){
				foreach($oCategoryList as $oCategory){
					$args->category_srl = $oCategory->category_srl;
					$output = executeQuery('faq.deleteCategory', $args);
					if(!$output->toBool()) return $output;
				}
			}

            $oModuleController = &getController('module');
            $output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) return $output;

            $this->add('module','faq');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_deleted');
        	if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'module_srl', $output->get('module_srl'), 'act', 'dispFaqAdminContent');
				header('location:'.$returnUrl);
				return;
			}
        }

    }
?>
