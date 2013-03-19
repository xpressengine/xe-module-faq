/**
 * @file   modules/faq/js/daq_admin.js
 * @author NHN (developers@xpressengine.com)
 * @brief  faq module template javascript
 **/

/* mass configuration*/
function doCartSetup(url) {
    var module_srl = new Array();
    jQuery('#fo_list input[name=cart]:checked').each(function() {
        module_srl[module_srl.length] = jQuery(this).val();
    });

    if(module_srl.length<1) return;

    url += "&module_srls="+module_srl.join(',');
    popopen(url,'modulesSetup');
}

function createCategory(obj){
	var title = jQuery("input[name=category_title]",obj.form).val();
	var module_srl = jQuery("input[name=module_srl]",obj.form).val();

	if(title == '') return false;

	var params = new Array();
	params['mid'] = current_mid;
	params['module_srl'] = module_srl;
	params['title'] = title;

	var response_tags = new Array('error','message','page','mid');
	exec_xml('faq', 'procFaqInsertCategory', params, completeInsertCategory, response_tags);

}

function completeInsertCategory(ret_obj, response_tags, args, fo_obj) {
	var error = ret_obj['error'];
	var message = ret_obj['message'];
	var mid = ret_obj['mid'];
	document.location.href=current_url.setQuery('category_srl','');
}

function updateCategory(obj){
	var title = jQuery("input[name=category_title]",obj.form).val();
	var module_srl = jQuery("input[name=module_srl]",obj.form).val();
	var category_srl = jQuery("input[name=category_srl]",obj.form).val();

	if(title == '' || category_srl == '') return false;

	var params = new Array();
	params['mid'] = current_mid;
	params['module_srl'] = module_srl;
	params['category_srl'] = category_srl;
	params['title'] = title;


	var response_tags = new Array('error','message','page','mid','selected_category');
	exec_xml('faq', 'procFaqInsertCategory', params, completeInsertCategory, response_tags);

}

function deleteCategory(obj){
	var category_srl = jQuery("input[name=category_srl]",obj.form).val();

	var params = new Array();
	params['mid'] = current_mid;
	params['category_srl'] = category_srl;

	var response_tags = new Array('error','message','page','mid');
	exec_xml('faq', 'procFaqDeleteCategory', params, completeDeleteCategory, response_tags);
}

function completeDeleteCategory(ret_obj, response_tags, args, fo_obj) {
	var error = ret_obj['error'];
	var message = ret_obj['message'];
	var mid = ret_obj['mid'];
	document.location.href=current_url.setQuery('category_srl','');
}

function createSubCategory(obj){
	var title = jQuery("input[name=sub_category_title]",obj.form).val();
	var module_srl = jQuery("input[name=module_srl]",obj.form).val();
	var parent_srl = jQuery("input[name=parent_srl]",obj.form).val();
	var depth = jQuery("input[name=depth]",obj.form).val();
	if(depth>2){
		alert('Sorry, you can not add a subcategory under this category level.')
		return;
	}

	if(title == '' || !parent_srl) return false;

	var params = new Array();
	params['mid'] = current_mid;
	params['module_srl'] = module_srl;
	params['title'] = title;
	params['parent_srl'] = parent_srl;
	params['depth'] = depth;

	var response_tags = new Array('error','message','page','mid');
	exec_xml('faq', 'procFaqInsertCategory', params, completeInsertCategory, response_tags);

}