<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  faqModel
 * @author NAVER (developers@xpressengine.com)
 * @brief  faq module Model class
 **/
class faqModel extends faq
{
	/**
	 * @brief initialization
	 **/
	function init()
	{
	}

	/**
	 * @brief getListConfig
	 **/
	function getListConfig($module_srl)
	{
		$oModuleModel = getModel('module');
		$oFaqModel = getModel('faq');

		$list_config = $oModuleModel->getModulePartConfig('faq', $module_srl);
		if(!$list_config || !count($list_config)) $list_config = array( 'no', 'title', 'nick_name','regdate','readed_count');

		$output = array();
		foreach($list_config as $key)
		{
			if(preg_match('/^([0-9]+)$/',$key))
			{
				$output['extra_vars'.$key] = $inserted_extra_vars[$key];
			}
			else
			{
				$output[$key] = new ExtraItem($module_srl, -1, Context::getLang($key), $key, 'N', 'N', 'N', null);
			}
		}

		return $output;
	}


	/**
	 * @brief get faq question object (faq item)
	 **/
	function getQuestion($question_srl=0, $is_admin = false, $load_extra_vars=true)
	{
		if(!$question_srl) return new faqItem();

		if(!isset($GLOBALS['XE_QUESTION_LIST'][$question_srl]))
		{
			$oQuestion = new faqItem($question_srl, $load_extra_vars);
			$GLOBALS['XE_QUESTION_LIST'][$question_srl] = $oQuestion;
		}

		if($is_admin) $GLOBALS['XE_QUESTION_LIST'][$question_srl]->setGrant();

		return $GLOBALS['XE_QUESTION_LIST'][$question_srl];
	}

	/**
	 * @brief get category question count
	 **/
	function getCategoryQuestionCount($module_srl, $category_srl)
	{
		$args = new stdClass;
		$args->module_srl = $module_srl;
		$args->category_srl = $category_srl;
		$output = executeQuery('faq.getCategoryQuestionCount', $args);
		return (int)$output->data->count;
	}

	/**
	 * @brief get faq category infor
	 **/
	function getCategory($category_srl)
	{
		$args = new stdClass;
		$args->category_srl = $category_srl;
		$output = executeQuery('faq.getCategory', $args);

		$node = $output->data;
		if(!$node) return;

		if($node->group_srls)
		{
			$group_srls = explode(',',$node->group_srls);
			unset($node->group_srls);
			$node->group_srls = $group_srls;
		}
		else
		{
			unset($node->group_srls);
			$node->group_srls = array();
		}

		return $node;
	}


	/**
	 * @brief get category child count
	 **/
	function getCategoryChlidCount($category_srl)
	{
		$args = new stdClass;
		$args->category_srl = $category_srl;
		$output = executeQuery('faq.getChildCategoryCount',$args);
		if($output->data->count > 0)
		{
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @brief get category list from cached file
	 **/
	function getAllCategoryList($module_srl,$parent_srl)
	{
		$faq_category = array();
		$args = new stdClass;
		$args->module_srl = $module_srl;
		$args->parent_srl = $parent_srl;
		$output = executeQueryArray('faq.getCategoryList',$args);

		$category_list = $output ->data;

		foreach($category_list as $val)
		{
			$faq_category[] = $val;
			$args->module_srl = $val->module_srl;
			$args->parent_srl = $val->category_srl;
			$faq_category = array_merge($faq_category, $this->getAllCategoryList($args->module_srl,$args->parent_srl));
		}

		return $faq_category;
		/*if($output->data->count > 0)
			$faq_category = null;
		else
			$faq_category = $output->data;

		return $faq_category;*/
	}

	function getTopCategoryList($module_srl)
	{
		$top_category = array();
		$args = new stdClass;
		$args->module_srl = $module_srl;
		$args->parent_srl = 0;
		$output = executeQueryArray('faq.getCategoryList',$args);

		$top_category = $output ->data;

		return $top_category;
	}

	function getSubCategoryListByDepth($module_srl,$parent_srl,$depth)
	{
		$sub_category = array();
		$args = new stdClass;
		$args->module_srl = $module_srl;
		$args->parent_srl = $parent_srl;
		$args->depth = $depth;
		$output = executeQueryArray('faq.getSubCategoryListByDepth',$args);

		$sub_category = $output ->data;

		return $sub_category;
	}

	function getHighestCategoryDepth($module_srl,$category_srl)
	{
		$depth = 0;
		$allCategoryList = $this->getAllCategoryList($module_srl,$category_srl);

		if($allCategoryList )
		{
			foreach($allCategoryList  as $val)
			{
				if(intval($val->depth)>$depth) $depth = intval($val->depth);
			}
		}

		return $depth;
	}

	/**
	 * @brief get question list
	 **/
	function getQuestionList($obj, $load_extra_vars=true)
	{
		$args = new stdClass;

		// set sorting infor
		if(!in_array($obj->sort_index, array('question_srl','list_order','update_order','regdate')))
		{
			$obj->sort_index = 'question_srl';
		}

		if(!in_array($obj->order_type, array('desc','asc')))
		{
			$obj->order_type = 'asc';
		}

		// set module_srl
		if($obj->mid)
		{
			$oModuleModel = getModel('module');
			$obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
			unset($obj->mid);
		}

		// is module_srl is an array
		if(is_array($obj->module_srl))
		{
			$args->module_srl = implode(',', $obj->module_srl);
		}
		else
		{
			$args->module_srl = $obj->module_srl;
		}

		// test exclude_module_srl
		if(is_array($obj->exclude_module_srl))
		{
			$args->exclude_module_srl = implode(',', $obj->exclude_module_srl);
		}
		else
		{
			$args->exclude_module_srl = $obj->exclude_module_srl;
		}

		// set up args
		$args->category_srl = $obj->category_srl?$obj->category_srl:null;
		$args->sort_index = $obj->sort_index;
		$args->order_type = $obj->order_type;
		$args->page = $obj->page?$obj->page:1;
		$args->list_count = $obj->list_count?$obj->list_count:20;
		$args->page_count = $obj->page_count?$obj->page_count:10;
		$args->start_date = $obj->start_date?$obj->start_date:null;
		$args->end_date = $obj->end_date?$obj->end_date:null;
		$args->member_srl = $obj->member_srl;

		// if it has category, add its all sub-categories
		if($args->category_srl)
		{
			$category_list = $this->getAllCategoryList($args->module_srl,$args->category_srl);
			$category_info = $category_list[$args->category_srl];
			$category_info->childs[] = $args->category_srl;
			foreach($category_list as $subCategory)
			{
				$category_info->childs[] = $subCategory->category_srl;
			}
			$args->category_srl = implode(',',$category_info->childs);
		}

		// set up query id
		$query_id = 'faq.getQuestionList';

		// set up question division
		$use_division = false;

		// set search args
		$searchOpt->search_target = $obj->search_target;
		$searchOpt->search_keyword = $obj->search_keyword;

		if($obj->search_keyword)
		{
			$args->s_question = $obj->search_keyword;
			$args->s_answer = $obj->search_keyword;
		}

		/**
		 * do not use division if sorting index=list_order or order!=asc
		 **/
		if($args->sort_index != 'list_order' || $args->order_type != 'asc') $use_division = false;

		$output = executeQueryArray($query_id, $args);

		// if there is no data return
		if(!$output->toBool()||!count($output->data)) return $output;

		$idx = 0;
		$data = $output->data;
		unset($output->data);

		if(!isset($virtual_number))
		{
			$keys = array_keys($data);
			$virtual_number = $keys[0];
		}

		foreach($data as $key => $attribute)
		{
			$question_srl = $attribute->question_srl;
			if(!$GLOBALS['XE_QUESTION_LIST'][$question_srl])
			{
				$oQuestion = null;
				$oQuestion = new faqItem();
				$oQuestion->setAttribute($attribute, false);
				$GLOBALS['XE_QUESTION_LIST'][$question_srl] = $oQuestion;
			}

			$output->data[$virtual_number] = $GLOBALS['XE_QUESTION_LIST'][$question_srl];
			$virtual_number--;
		}

		if(count($output->data))
		{
			foreach($output->data as $number => $question)
			{
				$output->data[$number] = $GLOBALS['XE_QUESTION_LIST'][$question->question_srl];
			}
		}

		return $output;
	}


	/**
	 * @brief get question count based on module_srl
	 **/
	function getQuestionCount($module_srl, $search_obj = NULL)
	{
		$args = new stdClass;
		$args->module_srl = $module_srl;
		$args->s_question = $search_obj->s_question;
		$args->s_answer = $search_obj->s_answer;
		$args->s_member_srl = $search_obj->s_member_srl;
		$args->s_regdate = $search_obj->s_regdate;
		$args->category_srl = $search_obj->category_srl;

		$output = executeQuery('faq.getQuestionCount', $args);

		// return total count
		$total_count = $output->data->count;

		return (int)$total_count;
	}

	/**
	 * @brief return module name in sitemap
	 **/
	function triggerModuleListInSitemap(&$obj)
	{
		array_push($obj, 'faq');
	}
}
/* End of file */
