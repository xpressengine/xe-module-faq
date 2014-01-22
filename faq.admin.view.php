<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  faqAdminView
 * @author NAVER (developers@xpressengine.com)
 * @brief  faq module admin view class
 **/
class faqAdminView extends faq
{
	function init()
	{
		// get module_srl if it exists
		$module_srl = Context::get('module_srl');
		if(!$module_srl && $this->module_srl)
		{
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}

		// module model class
		$oModuleModel = getModel('module');

		// get module_info based on module_srl
		if($module_srl)
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if(!$module_info)
			{
				Context::set('module_srl','');
				$this->act = 'list';
			}
			else
			{
				ModuleModel::syncModuleToSite($module_info);
				$this->module_info = $module_info;
				Context::set('module_info',$module_info);
			}
		}

		if($module_info && $module_info->module != 'faq')
		{
			return $this->stop("msg_invalid_request");
		}

		// get module category
		$module_category = $oModuleModel->getModuleCategories();
		Context::set('module_category', $module_category);

		// set the module template path (modules/faq/tpl)
		$template_path = sprintf("%stpl/",$this->module_path);
		$this->setTemplatePath($template_path);

		// set order target
		$order_target = array();
		foreach($this->order_target as $key)
		{
			$order_target[$key] = Context::getLang($key);
		}
		$order_target['list_order'] = Context::getLang('regdate');
		$order_target['update_order'] = Context::getLang('last_update');
		Context::set('order_target', $order_target);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('module_info.');
		$oSecurity->encodeHTML('module_category..');
	}

	function dispFaqAdminContent()
	{
		$args = new stdClass;
		$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$args->s_module_category_srl = Context::get('module_category_srl');

		$s_mid = Context::get('s_mid');
		if($s_mid) $args->s_mid = $s_mid;

		$s_browser_title = Context::get('s_browser_title');
		if($s_browser_title) $args->s_browser_title = $s_browser_title;

		$output = executeQueryArray('faq.getFaqList', $args);
		ModuleModel::syncModuleToSite($output->data);

		// setup module variables, context::set
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('faq_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('faq_list..');

		// set template file
		$this->setTemplateFile('index');
	}

	function dispFaqAdminFaqInfo()
	{
		$this->dispFaqAdminInsertFaq();
	}

	/**
	* @brief display insert faq admin page
	**/
	function dispFaqAdminInsertFaq()
	{
		if(!in_array($this->module_info->module, array('admin','faq')))
		{
			return $this->alertMessage('msg_invalid_request');
		}

		//get skin list
		$oModuleModel = getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);

		$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
		Context::set('mskin_list', $mskin_list);

		//get layout list
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('skin_list..', 'mskin_list..');
		$oSecurity->encodeHTML('layout_list..', 'mlayout_list..');

		$this->setTemplateFile('faq_insert');
	}

	/**
	* @brief display faq category admin page
	**/
	function dispFaqAdminCategoryInfo()
	{
		$module_srl = Context::get('module_srl');
		$category_srl = Context::get('category_srl');
		$parent_srl = 0;

		$oFaqModel = getModel('faq');
		$output = $oFaqModel->getAllCategoryList($module_srl,$parent_srl);
		Context::set('faq_category_list', $output);
		Context::set('category_list_count',count($output));
		if($category_srl)
		{
			$output = $oFaqModel->getCategory($category_srl);
			Context::set('selected_category',$output);
		}

		$oSecurity = new Security();
		$oSecurity->encodeHTML('faq_category_list..', 'selected_category.');

		$this->setTemplateFile('PostManageCategory');
	}

	/**
	* @brief display faq AdditionSetup admin page
	**/
	function dispFaqAdminFaqAdditionSetup()
	{

		$content = '';
		/*$oModuleModel = getModel('module');
		$triggers = $oModuleModel->getTriggers('module.dispAdditionSetup', 'before');

		var_dump($triggers);

		foreach($triggers as $item)
		{
			$module = $item->module;
			$type = $item->type;
			$called_method = $item->called_method;
			if($module == 'editor')
			{ //only display edtior
				$oModule = null;
				$oModule = getModule($module, $type);
				if(!$oModule || !method_exists($oModule, $called_method)) continue;

				$output = $oModule->{$called_method}($content);
				if(is_object($output) && method_exists($output, 'toBool') && !$output->toBool()) return $output;
				unset($oModule);
			}

		}*/
		$oEditorView = getView('editor');
		$oEditorView->triggerDispEditorAdditionSetup($content);

		Context::set('setup_content', $content);
		$this->setTemplateFile('addition_setup');

		$security = new Security();
		$security->encodeHTML('module_info.');
	}

	/**
	 * @brief delete faq module
	 **/
	function dispFaqAdminDeleteFaq()
	{
		if(!Context::get('module_srl')) return $this->dispFaqAdminContent();
		if(!in_array($this->module_info->module, array('admin', 'faq','blog','guestbook')))
		{
			return $this->alertMessage('msg_invalid_request');
		}

		$module_info = Context::get('module_info');

		$oFaqModel = getModel('faq');
		$question_count = $oFaqModel->getQuestionCount($module_info->module_srl);
		$module_info->question_count = $question_count;

		Context::set('module_info',$module_info);

		// set template file
		$this->setTemplateFile('faq_delete');
	}

	/**
	 * @brief display the grant information
	 **/
	function dispFaqAdminGrantInfo()
	{
		// get the grant infotmation from admin module
		$oModuleAdminModel = getAdminModel('module');
		$grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $grant_content);

		$this->setTemplateFile('grant_list');
	}
	/**
	* @brief faq module alert message
	**/
	function alertMessage($message)
	{
		$script =  sprintf('<script> xAddEventListener(window,"load", function()
			{ alert("%s"); } );</script>', Context::getLang($message));
		Context::addHtmlHeader( $script );
	}
}
/* End of file */
