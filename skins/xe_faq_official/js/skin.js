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
