<?php
    /**
     * @class  faqView
     * @author NHN (developers@xpressengine.com)
     * @brief  faq module View class
     **/

    class faqView extends faq {

        /**
         * @brief initialize faq view class.
         **/
		function init() {

			if($this->module_info->list_count) $this->list_count = $this->module_info->list_count;
            if($this->module_info->search_list_count) $this->search_list_count = $this->module_info->search_list_count;
            if($this->module_info->page_count) $this->page_count = $this->module_info->page_count;

           /**
             * get skin template_path
             * if it is not found, default skin is xe_faq_official
             **/
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            if(!is_dir($template_path)||!$this->module_info->skin) {
                $this->module_info->skin = 'xe_faq_official';
                $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            }
            $this->setTemplatePath($template_path);

            /**
             * get extra variables from xe_module_extra_vars table, context set
             **/
            $oModuleModel = &getModel('module');
            $extra_keys = $oModuleModel->getModuleExtraVars($this->module_info->module_srl);
            Context::set('extra_keys', $extra_keys);

			// get search recommend keywords
			$search_keywords = explode(',',$this->module_info->faq_keywords);
			Context::set('search_keywords', $search_keywords);
		}


        /**
         * @brief display faq content
         **/
        function dispFaqContent() {
            if(!$this->grant->access) return $this->setTemplateFile('input_password_form');
			//get faq categories
            $this->dispFaqCategoryList();

            // set search option
            foreach($this->search_option as $opt) $search_option[$opt] = Context::getLang($opt);	
            Context::set('search_option', $search_option);

			$this->dispFaqContentView();

			$this->dispFaqContentList();

			Context::addJsFilter($this->module_path.'tpl/filter', 'search.xml');

			// set template_file to be list.html
            $this->setTemplateFile('list');
        }

        /**
         * @brief get faq category list
         **/
        function dispFaqCategoryList(){
			if($this->module_info->use_category=='Y') {
				$oFaqModel = &getModel('faq');
				$category_list = $oFaqModel->getTopCategoryList($this->module_srl);
				Context::set('category_list', $category_list);

				$oSecurity = new Security();
				$oSecurity->encodeHTML('category_list..');
			}
        }

		
        /**
         * @brief display faq content view 
         **/
        function dispFaqContentView(){
            if(!$this->grant->access) return $this->setTemplateFile('input_password_form');
            // get question_srl
            $question_srl = Context::get('question_srl');

			$page = Context::get('page');

            // faq model class
            $oFaqModel = &getModel('faq');

            /**
             * get question from faq model
             **/
			if($question_srl) {
				$oQuestion = $oFaqModel->getQuestion($question_srl);

				// if question exists
				if($oQuestion->isExists()) {
					// compare module_srl
					if($oQuestion->get('module_srl')!=$this->module_info->module_srl ) return $this->stop('msg_invalid_request');

				// if question notexists
				} else {
					Context::set('question_srl','',true);
					$this->alertMessage('msg_not_founded');
				}

            /**
             * if question_srl is null, get a blank question object (i.e. faq item)
             **/
            } else {
                $oQuestion = $oFaqModel->getQuestion(0);	
            }

			if($oQuestion->isExists()){
				Context::addBrowserTitle($oQuestion->getQuestionText());
			}

			// add module_srl to oQuestion
            $oQuestion->add('module_srl', $this->module_srl);
            Context::set('oQuestion', $oQuestion);
		}

		/**
         * @brief display faq content list
         **/
        function dispFaqContentList(){
            if(!$this->grant->access) return $this->setTemplateFile('input_password_form');

            // if you dot have permission, re-initialize the module variables
			/*if(!$this->grant->list) {
				Context::set('question_list', array());
				Context::set('total_count', 0);
				Context::set('total_page', 1);
				Context::set('page', 1);
				Context::set('page_navigation', new PageHandler(0,0,1,10));
			}*/
			if(!Context::get('page')) Context::set('page', 1);

            $oFaqModel = &getModel('faq');

            // set up basic args
            $args->module_srl = $this->module_srl; 
            $args->page = Context::get('page');
            $args->list_count = $this->list_count; 
            $args->page_count = $this->page_count; 

            // set up serach args 
            $args->search_target = Context::get('search_option'); 
            $args->search_keyword = Context::get('search_keyword'); 

            // set up category args
            if($this->module_info->use_category=='Y') $args->category_srl = intval(removeHackTag(Context::get('category'))); ///< 카테고리 사용시 선택된 카테고리

            // set up sorting args
            $args->sort_index = Context::get('sort_index');
            $args->order_type = Context::get('order_type');
			$this->order_target = array('question_srl','regdate');
            if(!in_array($args->sort_index, $this->order_target)) $args->sort_index = $this->module_info->order_target?$this->module_info->order_target:'list_order';
            if(!in_array($args->order_type, array('asc','desc'))) $args->order_type = $this->module_info->order_type?$this->module_info->order_type:'asc';

            // set up page args
            $_get = $_GET;
            if(!$args->page && ($_GET['question_srl'] || $_GET['entry'])) {
                $oQuestion = $oFaqModel->getQuestion(Context::get('question_srl'));
                if($oQuestion->isExists()) {
                    $page = $oFaqModel->getQuestionPage($oQuestion, $args);
                    Context::set('page', $page);
                    $args->page = $page;
                }
            }

            // set up list count, search_list_count
            if($args->category_srl || $args->search_keyword) $args->list_count = $this->search_list_count;

            // get user log info
            $logged_info = Context::get('logged_info');
            $args->member_srl = $logged_info->member_srl;

            // get Question list, context set
            $output = $oFaqModel->getQuestionList($args);
			$question_list = array();
			if($output->data){
				foreach($output->data as $question_item){
					$question_list[$question_item->question_srl] = $question_item;
					$question_list[$question_item->question_srl]->question = removeHackTag($question_item->getQuestion());
					$question_list[$question_item->question_srl]->answer = removeHackTag($question_item->getAnswer());
				}
			}


            Context::set('question_list', $question_list);
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('page_navigation', $output->page_navigation);

            // get list config
            $oFaqModel = &getModel('faq');
            Context::set('list_config', $oFaqModel->getListConfig($this->module_info->module_srl));
        }


        /**
         * @brief display faq write form
         **/
        function dispFaqWrite() {

			// only admin user can write faq
			if(!Context::get('is_logged'))  return $this->setTemplateFile('input_password_form');
            $logged_info = Context::get('logged_info');
            if(!$this->grant->manager) return $this->setTemplateFile('input_password_form');

			$oFaqModel = &getModel('faq');

			if(Context::get('is_logged')) {
				$logged_info = Context::get('logged_info');
				$group_srls = array_keys($logged_info->group_list);
			} else {
				$group_srls = array();
			}
			$group_srls_count = count($group_srls);

			if($this->module_info->use_category=='Y') {
				// get faq category list
				$normal_category_list = $oFaqModel->getAllCategoryList($this->module_srl,0);

				if(count($normal_category_list)) {
					foreach($normal_category_list as $category_srl => $category) {
						$is_granted = true;
						if($category->group_srls) {
							$category_group_srls = explode(',',$category->group_srls);
							$is_granted = false;
							if(count(array_intersect($group_srls, $category_group_srls))) $is_granted = true; 			
						}
						
						if($is_granted) $category_list[$category_srl] = $category;
					}
				}
				Context::set('category_list', $category_list);
			}

            //GET parameter question_srl
            $question_srl = Context::get('question_srl');

            $oQuestion = $oFaqModel->getQuestion(0, $this->grant->manager);
            $oQuestion->setQuestion($question_srl);


			if($oQuestion->get('module_srl') == $oQuestion->get('member_srl')) $savedDoc = true;
            $oQuestion->add('module_srl', $this->module_srl);

            // if faq is not editable, go to login page
            if($oQuestion->isExists()&&!$oQuestion->isEditable()) return $this->setTemplateFile('input_password_form');
            if(!$oQuestion->isExists()) {
                $oModuleModel = &getModel('module');		
                $logged_info = Context::get('logged_info');
            }

            Context::set('question_srl',$question_srl);
            Context::set('oQuestion', $oQuestion);

            /** 
             * add javascript filter file insert_question
             **/

            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_question.xml');

            $this->setTemplateFile('write_form');

		}

        /**
         * @brief faq delete form
         **/
        function dispFaqDelete() {
			// only admin user can write faq
			if(!Context::get('is_logged'))  return $this->setTemplateFile('input_password_form');
            $logged_info = Context::get('logged_info');
            if(!$this->grant->manager) return $this->setTemplateFile('input_password_form');

            // get question_srl
            $question_srl = Context::get('question_srl');

            // get question object
            if($question_srl) {
                $oFaqModel = &getModel('faq');
                $oQuestion = $oFaqModel->getQuestion($question_srl);
            }

            if($oQuestion){
				if(!$oQuestion->isExists()) return $this->dispFaqContent();
			}

            Context::set('oQuestion',$oQuestion);

            /** 
             * add javascript filter file delete_question
             **/
            Context::addJsFilter($this->module_path.'tpl/filter', 'delete_question.xml');

            $this->setTemplateFile('delete_form');

        }

		/**
		 * @brief the method for displaying the warning messages
		 * display an error message if it has not  a special design  
		 **/
		function alertMessage($message) {
			$script =  sprintf('<script type="text/javascript"> jQuery(function(){ alert("%s"); } );</script>', Context::getLang($message));
			Context::addHtmlFooter( $script );
		}
	}
?>
