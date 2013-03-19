jQuery(function($){
	$('ul.faq_lst').find('li').each(function(index){
		$(this).attr('class','off')
		$(this).find('.btn_show,.title').click(function(){
			$(this).parent().toggleClass('on off');
			return false;
		});
	});
	$('ul#top_category li').mouseover(function(){
			if($(this).attr('data')){
				var popup = '#popup'+$(this).attr('data');
				/*basicOff = $('.faqTitle').offset().left;
				curOff = $(this).offset().left;
				offset = $(this).width()/2;
				leftAttr = curOff - basicOff+offset-4;*/
				offset = $(this).width()/2;
				leftAttr = offset-4;
				$(popup).find('.arr').css('left',leftAttr);
				$(popup).show();
			}
	});
	$('ul#top_category li').mouseleave(function(){
		    $('div.popover_cate').hide();
	});

	$('ul.subcate_ul_v li').mouseover(function(){
		$(this).find('dl').css('background-color','#D6D9E2');
		$(this).find('dl').find('dd').css('background-color','#EBEDF4');
	});

	$('ul.subcate_ul_v li').mouseleave(function(){
		$(this).find('dl').css('background-color','#F6F6F6');
		$(this).find('dl').find('dd').css('background-color','#FFFFFF');
	});
});

function voteFaq(question_srl,status){
	var params = new Array();
	params['question_srl'] = question_srl;
	params['status'] = status;

	var completeVote = function(ret_obj, response_tags){
			var voteExist = parseInt(ret_obj['voteExist']);

			if(voteExist == 1){
				alert(vote_failed);
			}else{
				if(status == 'positive'){
					var item = '#btn_useful'+question_srl;
					var positive = parseInt(jQuery(item).attr('data')) + 1;
					jQuery(item).attr('data',positive);
					var value = '<i class="up"></i>Helpful('+positive+')';
					jQuery(item).html(value);
				}else if(status == 'negative'){
					var item = '#btn_useless'+question_srl;
					var negative = parseInt(jQuery(item).attr('data')) + 1;
					jQuery(item).attr('data',negative);
					var value = '<i class="down"></i>Helpless('+negative+')';
					jQuery(item).html(value);
				}
				alert(vote_success);
			}
	 };
	var response_tags = new Array('error','message','page','mid','voteExist');
	exec_xml('faq', 'procFaqVote', params, completeVote, response_tags);
}

