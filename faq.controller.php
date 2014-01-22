<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  faqController
 * @author NAVER (developers@xpressengine.com)
 * @brief  faq module Controller class
 **/
class faqController extends faq
{
	/**
	 * @brief initialization
	 **/
	function init()
	{
		//check permission
		if(!$this->grant->access) return $this->stop("msg_not_permitted");
		if(!in_array(Context::get('act'),array('proGetQuesList')) && !$this->grant->manager) return $this->stop("msg_not_permitted");
	}

	/**
	 * @brief insert/update question (faq_item)
	 **/
	function procFaqInsertQuestion()
	{
		// check permission
		if($this->module_info->module != "faq")
		{
			return new Object(-1, "msg_invalid_request");
		}
		$logged_info = Context::get('logged_info');

		// get form variables submitted
		$obj = Context::getRequestVars();
		$obj->module_srl = $this->module_info->module_srl;

		if($obj->question == '') $obj->question = cut_str(strip_tags($obj->answer),20,'...');
		//Question Undefined
		if($obj->question == '') $obj->question = 'Question Undefined';

		// get faq module model
		$oFaqtModel = getModel('faq');

		// get faq module controller
		$oFaqController = getController('faq');

		// get question object
		$oQuestion = $oFaqtModel->getQuestion($obj->question_srl);;

		// if question exists, then update question
		if($oQuestion->isExists() && $oQuestion->question_srl == $obj->question_srl)
		{
			$output = $oFaqController->updateQuestion($oQuestion, $obj);
			$msg_code = 'success_updated';
		}
		else
		{
			// if question not exists, then insert question
			$output = $oFaqController->insertQuestion($obj);
			$msg_code = 'success_registed';
			$obj->question_srl = $output->get('question_srl');
		}

		// if there is an error, then stop
		if(!$output->toBool()) return $output;

		// return result
		$this->add('mid', Context::get('mid'));
		$this->add('question_srl', $output->get('question_srl'));

		// output success inserted/updated message
		$this->setMessage($msg_code);

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispFaqContent');
			header('location:'.$returnUrl);
			return;
		}
	}

	/**
	 * @brief delete question
	 **/
	function procFaqDeleteQuestion()
	{
		// get question_srl
		$question_srl = Context::get('question_srl');

		// if question not exists, then alert an error
		if(!$question_srl) return $this->doError('msg_invalid_document');

		// get faq module model
		$oFaqController = getController('faq');

		// delete question
		$output = $oFaqController->deleteQuestion($question_srl);
		if(!$output->toBool()) return $output;

		// alert success deleted message
		$this->add('mid', Context::get('mid'));
		$this->add('page', $output->get('page'));
		$this->setMessage('success_deleted');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', $this->module_info->mid, 'act', 'dispFaqContent');
			header('location:'.$returnUrl);
			return;
		}
	}

	/**
	 * @brief insert question
	 **/
	function insertQuestion($obj, $manual_inserted = false)
	{
		// begin transaction
		$oDB = &DB::getInstance();
		$oDB->begin();

		$obj->ipaddress = $_SERVER['REMOTE_ADDR'];	//get client ip, or remote proxy server ip address

		// $extra_vars serialize
		$obj->extra_vars = serialize($obj->extra_vars);

		// unset auto save
		unset($obj->_saved_doc_srl);
		unset($obj->_saved_doc_question);
		unset($obj->_saved_doc_answer);
		unset($obj->_saved_doc_message);

		// create a question_srl
		if(!$obj->question_srl) $obj->question_srl = getNextSequence();

		$oFaqModel = getModel('faq');

		// get category list
		if($obj->question_srl)
		{
			$oCategory = $oFaqModel->getCategory($obj->category_srl);
			if(!$oCategory || ($oCategory->module_srl != $obj->module_srl))
			{
				$obj->category_srl = 0;
			}
		}

		// set read_count, update_order&list_order
		if(!$obj->readed_count) $obj->readed_count = 0;
		$obj->update_order = $obj->list_order = getNextSequence() * -1;

		// md5 user password
		if($obj->password && !$obj->password_is_hashed)
		{
			$obj->password = md5($obj->password);
		}

		// set up log user inforamtion
		if(Context::get('is_logged') && !$manual_inserted)
		{
			$logged_info = Context::get('logged_info');
			$obj->member_srl = $logged_info->member_srl;
			$obj->user_id = $logged_info->user_id;
			$obj->user_name = $logged_info->user_name;
			$obj->nick_name = $logged_info->nick_name;
			$obj->email_address = $logged_info->email_address;
			$obj->homepage = $logged_info->homepage;
		}

		// set up question
		settype($obj->question, "string");
		if($obj->question == '') $obj->question = cut_str(strip_tags($obj->anwser),20,'...');
		// Question Undefined
		if($obj->question == '') $obj->question = 'Question Undefined';

		if($logged_info->is_admin != 'Y') $obj->anwser = removeHackTag($obj->anwser);

		// if user is not a member, return error
		if(!$logged_info->member_srl && !$obj->nick_name) return new Object(-1,'msg_invalid_request');

		$obj->lang_code = Context::getLangType();

		// DB quesry
		$output = executeQuery('faq.insertQuestion', $obj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// update category count
		if($obj->category_srl) $this->updateCategoryCount($obj->module_srl, $obj->category_srl);

		// DB commit
		$oDB->commit();

		$output->add('question_srl',$obj->document_srl);
		$output->add('category_srl',$obj->category_srl);

		return $output;
	}


	/**
	 * @brief update question
	 **/
	function updateQuestion($source_obj, $obj)
	{
		if(!$source_obj->question_srl || !$obj->question_srl)
		{
			return new Object(-1,'msg_invalied_request');
		}

		// begin transaction
		$oDB = &DB::getInstance();
		$oDB->begin();

		$oModuleModel = getModel('module');
		if(!$obj->module_srl) $obj->module_srl = $source_obj->get('module_srl');
		$module_srl = $obj->module_srl;

		// unset auto save
		unset($obj->_saved_doc_srl);
		unset($obj->_saved_doc_question);
		unset($obj->_saved_doc_answer);
		unset($obj->_saved_doc_message);

		$oFaqModel = getModel('faq');

		// get updated category
		if($source_obj->get('category_srl')!=$obj->category_srl)
		{
			$oCategory = $oFaqModel->getCategory($obj->category_srl);
			if(!$oCategory || ($oCategory->module_srl != $obj->module_srl)) $obj->category_srl = 0;
		}

		// change update_order
		$obj->update_order = getNextSequence() * -1;

		// set up log user information
		if(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');
			if($source_obj->get('member_srl')==$logged_info->member_srl)
			{
				$obj->member_srl = $logged_info->member_srl;
			}
		}

		// then only question provider can update question
		if($source_obj->get('member_srl')&& !$obj->nick_name)
		{
			$obj->member_srl = $source_obj->get('member_srl');
		}

		// set up question
		settype($obj->question, "string");
		if($obj->question == '') $obj->question = cut_str(strip_tags($obj->anwser),20,'...');
		// Question Undefined
		if($obj->question == '') $obj->question = 'Question Undefined';

		if($logged_info->is_admin != 'Y') $obj->answer = removeHackTag($obj->answer);

		// DB update question
		$output = executeQuery('faq.updateQuestion', $obj);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// update category count when the question's category changed
		if($source_obj->get('category_srl') != $obj->category_srl || $source_obj->get('module_srl') == $logged_info->member_srl)
		{
			if($source_obj->get('category_srl') != $obj->category_srl)
			{
				$this->updateCategoryCount($obj->module_srl, $source_obj->get('category_srl'));
			}

			if($obj->category_srl) $this->updateCategoryCount($obj->module_srl, $obj->category_srl);
		}

		// DB commit
		$oDB->commit();

		// remove thumbnail
		FileHandler::removeDir(sprintf('files/thumbnails/%s',getNumberingPath($obj->question_srl, 3)));

		$output->add('question_srl',$obj->question_srl);
		return $output;
	}

	/**
	 * @brief delete question
	 **/
	function deleteQuestion($question_srl, $is_admin = FALSE)
	{
		// begin transaction
		$oDB = &DB::getInstance();
		$oDB->begin();

		// get faq model
		$oFaqModel = getModel('faq');

		// get question object
		$oQuestion = $oFaqModel->getQuestion($question_srl, $is_admin);
		if(!$oQuestion->isExists() || $oQuestion->question_srl != $question_srl) return new Object(-1, 'msg_invalid_document');

		$args->question_srl = $question_srl;
		$output = executeQuery('faq.deleteQuestion', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// update category count when the question has beeen deleted
		if($oQuestion->get('category_srl')) $this->updateCategoryCount($oQuestion->get('module_srl'),$oQuestion->get('category_srl'));

		// remove thumbnail
		FileHandler::removeDir(sprintf('files/thumbnails/%s',getNumberingPath($question_srl, 3)));

		// commit
		$oDB->commit();

		return $output;
	}


	/**
	 * @brief update category count
	 **/
	function updateCategoryCount($module_srl, $category_srl, $question_count = 0)
	{
		// get faq model
		$oFaqModel = getModel('faq');
		if(!$question_count)
		{
			$question_count = $oFaqModel->getCategoryQuestionCount($module_srl,$category_srl);
		}
		$args->category_srl = $category_srl;
		$args->question_count = $question_count;
		$output = executeQuery('faq.updateCategoryCount', $args);

		return $output;
	}

	/**
	 * @brief insert faq category
	 **/
	function insertCategory($obj)
	{
		// set category list order
		if($obj->parent_srl)
		{
			// when insert a subcategory
			$oFaqModel = getModel('faq');
			$parent_category = $oFaqModel->getCategory($obj->parent_srl);
			$obj->list_order = $parent_category->list_order;
			$this->updateCategoryListOrder($parent_category->module_srl, $parent_category->list_order+1);
			if(!$obj->category_srl) $obj->category_srl = getNextSequence();
		}
		else
		{
			$obj->list_order = $obj->category_srl = getNextSequence();
		}

		$output = executeQuery('faq.insertCategory', $obj);
		if($output->toBool())
		{
			$output->add('category_srl', $obj->category_srl);
		}

		return $output;
	}

	/**
	 * @brief update category list order
	 **/
	function updateCategoryListOrder($module_srl, $list_order)
	{
		$args = new stdClass;
		$args->module_srl = $module_srl;
		$args->list_order = $list_order;
		return executeQuery('faq.updateCategoryOrder', $args);
	}

	/**
	 * @brief update category
	 **/
	function updateCategory($obj)
	{
		$output = executeQuery('faq.updateCategory', $obj);
		return $output;
	}

	/**
	 * @brief delete category
	 **/
	function deleteCategory($category_srl)
	{
		$args = new stdClass;
		$args->category_srl = $category_srl;
		$oFaqModel = getModel('faq');
		$category_info = $oFaqModel->getCategory($category_srl);

		// if the category has any child, then return an error
		$output = executeQuery('faq.getChildCategoryCount', $args);
		if(!$output->toBool()) return $output;
		if($output->data->count>0) return new Object(-1, 'msg_cannot_delete_for_child');

		// execute delete query
		$output = executeQuery('faq.deleteCategory', $args);
		if(!$output->toBool()) return $output;

		unset($args);

		$args->target_category_srl = 0;
		$args->source_category_srl = $category_srl;
		$output = executeQuery('faq.updateQuestionCategory', $args);

		return $output;
	}

	/**
	 * @brief proc insert/update category
	 **/
	function procFaqInsertCategory($args = null)
	{
		if(!$args) $args = Context::gets('category_srl','module_srl','parent_srl','title','group_srls','color','mid','depth');

		if(!$args->module_srl && $args->mid){
			$mid = $args->mid;
			unset($args->mid);
			$args->module_srl = $this->module_srl;
		}

		// get module information, check permission
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
		$grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'));
		if(!$grant->manager) return new Object(-1,'msg_not_permitted');

		$args->group_srls = str_replace('|@|',',',$args->group_srls);
		$args->parent_srl = intval($args->parent_srl);
		$args->depth = intval($args->depth);

		$oFaqModel = getModel('faq');

		$oDB = &DB::getInstance();
		$oDB->begin();

		// check whether the category exists
		if($args->category_srl)
		{
			$category_info = $oFaqModel->getCategory($args->category_srl);
			$args->parent_srl = intval($category_info->parent_srl);
			$args->depth = intval($category_info->depth);
			if($category_info->category_srl != $args->category_srl) $args->category_srl = null;
		}

		if($args->category_srl)
		{
			// update category
			$output = $this->updateCategory($args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}
		else
		{
			// insert category
			$output = $this->insertCategory($args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}

		$oDB->commit();

		$this->add('module_srl', $args->module_srl);
		$this->add('category_srl', $args->category_srl);
		$this->add('parent_srl', $args->parent_srl);

	}

	/**
	 * @brief proc delete category
	 **/
	function procFaqDeleteCategory()
	{
		$args = Context::gets('module_srl','category_srl');

		$oDB = &DB::getInstance();
		$oDB->begin();

		// check permission
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
		$grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'));
		if(!$grant->manager) return new Object(-1,'msg_not_permitted');

		$oFaqModel = getModel('faq');

		// get category information
		$category_info = $oFaqModel->getCategory($args->category_srl);
		if($category_info->parent_srl) $parent_srl = $category_info->parent_srl;

		// if the category has any child, then return an error
		if($oFaqModel->getCategoryChlidCount($args->category_srl))
		{
			return new Object(-1, 'msg_cannot_delete_for_child');
		}

		// delete category
		$output = $this->deleteCategory($args->category_srl);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$oDB->commit();

		$this->add('category_srl', $parent_srl);
		$this->setMessage('success_deleted');
	}

	function proGetQuesList()
	{
		$category_srl = Context::get('category');
		$mid = Context::get('mid');
		$list_count = Context::get('list_count');
		$page = intval(Context::get('page'));

		$obj = new stdClass;
		$obj->mid = $mid;
		$obj->page = $page?$page:1;
		$obj->list_count = $list_count?$list_count:5;
		$obj->category_srl = $category_srl?$category_srl:null;
		if($category_srl == 'all') $obj->category_srl = null;
		$obj->search_keyword = Context::get('search_keyword');
		$obj->sort_index = $this->module_info->order_target?$this->module_info->order_target:'list_order';
		$obj->order_type = $this->module_info->order_type?$this->module_info->order_type:'asc';

		$oQuestionModel = getModel('faq');
		$questionList = $oQuestionModel->getQuestionList($obj);
		$total_count = $questionList->total_count;
		$questionList = $questionList->data;

		$this->add('question_list', $questionList);
		$this->add('total_count', $total_count);
	}
}
/* End of file */
