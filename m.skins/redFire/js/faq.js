jQuery(document).ready(function($){

var btn_srch = $('.btn_srch');
var srch = $('form.srch');
var search_keyword = $('input[name=search_keyword]').val();
/* ray part */

    var more_btn = $('#more_view');
    
    var page = 2;
    var list_count = 5;
    var view_rage = 5;
    var total_dt = $('#p_list dt').length;
    var ori_total_dt = total_dt;

    more_btn.click(function(){
        var params = [];
        var response_tags = ['error','message', 'question_list', 'total_count'];
        params['list_count'] = list_count;
        params['page'] = page;
        params['mid'] = mid;
        params['category'] = category;
		params['search_keyword'] = search_keyword;
        exec_xml('faq','proGetQuesList',params, completeGetQuestionList, response_tags);

        page +=1;
    });

function completeGetQuestionList(ret_obj, response_tags)
{
    var htmlListBuffer = '';
    var question_list = ret_obj['question_list'];
    var total_count = ret_obj['total_count'];

    if(ret_obj['question_list'] != null){
        var question_list = ret_obj['question_list']['item'];
        if(!jQuery.isArray(question_list)) question_list = [question_list];

        for(var x in question_list){
            
            var objQuestion = question_list[x];
            htmlListBuffer = "<dt style='display:none' id='q_a_link_" + objQuestion.variables.question_srl +"' class='q clse'><a href='#' onclick='return false;'>" + objQuestion.variables.question +"</a></dt>"
                           + "<dd style='display:none' id='qAnswer_" + objQuestion.variables.question_srl + "' class='a'><div class='answer'> " + objQuestion.variables.answer +"</div></dd>";
            total_dt +=1;

            $('#p_list').append(htmlListBuffer);

            if(total_dt <= total_count){
                var dt_tog = '#q_a_link_'+ objQuestion.variables.question_srl;
                var dt_link = $(dt_tog);
                dt_link.find('a').click(function(){
                     lst_toggle($(this));
                });
            }
        }
    }
        
    if(total_dt <= total_count){
        view_rage +=list_count;
        test = '#p_list dt:lt('+(view_rage)+')';
        $(test).slideDown('fast');
    }
}


$('.p_list>dt>a').click(function(){
    lst_toggle($(this));
});
btn_srch.click(function(){
    srch.toggle();
});
function lst_toggle(e){
    if (e.parent().hasClass('opn')) {
        $('.p_list > dt').removeClass('opn').addClass('clse');
        e.parent().next().slideUp(100);
    }else if(e.parent().hasClass('clse')){
        $('.p_list > dt').removeClass('opn').addClass('clse');
        $('.p_list>dd').slideUp(100);
        e.parent().removeClass('clse').addClass('opn');
        e.parent().next().slideDown(100);
    }
}


}); // End of ready
