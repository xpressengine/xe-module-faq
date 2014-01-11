<?php

require_once(_XE_PATH_.'modules/faq/faq.view.php');

class faqMobile extends faqView {
		function init()
		{
			if($this->module_info->list_count) $this->list_count = $this->module_info->list_count;
            if($this->module_info->search_list_count) $this->search_list_count = $this->module_info->search_list_count;
            if($this->module_info->page_count) $this->page_count = $this->module_info->page_count;

           /**
             * get skin template_path
             * if it is not found, default m.skin is blueFly
             **/
            $template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
            if(!is_dir($template_path)||!$this->module_info->skin) {
                $this->module_info->mskin = 'blueFly';
                $template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
            }
            $this->setTemplatePath($template_path);   

	         /**
             * get extra variables from xe_module_extra_vars table, context set
             **/
            $oModuleModel = getModel('module');
            $extra_keys = $oModuleModel->getModuleExtraVars($this->module_info->module_srl);
            Context::set('extra_keys', $extra_keys);

			// get search recommend keywords
			$search_keywords = explode(',',$this->module_info->faq_keywords);
			Context::set('search_keywords', $search_keywords);
		}

		 function dispFaqContent() {
			 $category_srl = Context::get('category');

			 $this->dispFaqCategoryList();
			 $category_list = Context::get('category_list');

			 if(!$category_srl && $this->module_info->use_category=='Y' && $category_list !=null){
				$this->setTemplateFile('category');
			 }else{
				$this->dispFaqContentView();
				
				$this->list_count = '5';
				$this->search_list_count = '5';
				
				$this->dispFaqContentList();

				Context::addJsFilter($this->module_path.'tpl/filter', 'search.xml');

				// set template_file to be list.html
				$this->setTemplateFile('list');
			 }
		 }

}


?>
