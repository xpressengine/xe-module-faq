<?php
    /**
     * @class  faq
     * @author NHN (developers@xpressengine.com)
     * @brief  faq module high class
     **/

	require_once(_XE_PATH_.'modules/faq/faq.item.php');

    class faq extends ModuleObject {

        var $search_option = array('question','answer'); ///< search options
        var $order_target = array('question_srl','list_order', 'update_order'); //< order options

        var $skin = "default"; ///< skin name
        var $list_count = 20; ///< the question count shown in a page
        var $page_count = 10; ///< the page count shown in the page
        var $category_list = NULL; ///< category 


        /**
         * @brief module installation
         **/
		function moduleInstall()
		{
            // action forward get module controller and model
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');
			$oModuleController->insertTrigger('menu.getModuleListInSitemap', 'faq', 'model', 'triggerModuleListInSitemap', 'after');

            return new Object();
        }

        /**
         * @brief check update method
         **/
        function checkUpdate() {
			$oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');

			// 2012. 09. 11 when add new menu in sitemap, custom menu add
			if(!$oModuleModel->getTrigger('menu.getModuleListInSitemap', 'faq', 'model', 'triggerModuleListInSitemap', 'after')) return true;

			//2013.03.25 not need columns
			if(!$oDB->isColumnExists("faq_categories","depth")) return true;
			if($oDB->isColumnExists("faq_questions","positive")) return true;
			if($oDB->isColumnExists("faq_questions","negative")) return true;
			if($oDB->isColumnExists("faq_questions","votes")) return true;

            return false;
        }

        /**
         * @brief update module
         **/
        function moduleUpdate() {
			$oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

			if(!$oDB->isColumnExists("faq_categories","depth")) {
                $oDB->addColumn("faq_categories","depth", "number",11,0,true);
            }

			// 2012. 09. 11 when add new menu in sitemap, custom menu add
			if(!$oModuleModel->getTrigger('menu.getModuleListInSitemap', 'faq', 'model', 'triggerModuleListInSitemap', 'after'))
				$oModuleController->insertTrigger('menu.getModuleListInSitemap', 'faq', 'model', 'triggerModuleListInSitemap', 'after');

			//2013.03.25 not need columns
			if($oDB->isColumnExists("faq_questions","positive")) {
                $oDB->dropTable("faq_questions","positive");
            }
			if($oDB->isColumnExists("faq_questions","negative")) {
                $oDB->dropTable("faq_questions","negative");
            }
			if($oDB->isColumnExists("faq_questions","votes")) {
                $oDB->dropTable("faq_questions","votes");
            }

			$this->recompileCache();

            return new Object(0, 'success_updated');
        }

		function moduleUninstall() {
			$output = executeQueryArray("faq.getAllFaq");
			if(!$output->data) return new Object();
			set_time_limit(0);
			$oModuleController =& getController('module');
			foreach($output->data as $faq)
			{
				$oModuleController->deleteModule($faq->module_srl);
			}
			return new Object();
		}

        /**
         * @brief create cache file
         **/
        function recompileCache() {
			 $template_path = './files/cache/template_compiled';
			 $queries_path = './files/cache/queries';

			 FileHandler::removeDir($template_path);
			 FileHandler::removeDir($queries_path);
			 FileHandler::makeDir($template_path);
			 FileHandler::makeDir($queries_path);
        }

    }
?>
