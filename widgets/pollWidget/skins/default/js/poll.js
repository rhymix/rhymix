function initRxDefaultTemplete(type, poll_srl)
{
	switch(type)
	{
		case 'poll':
			if (typeof window.template == 'undefined')
			{
				var source = jQuery("#entry-template-" + poll_srl).html();
				window.template = Handlebars.compile(source);
			}
			break;
		case 'result':
			if (typeof window.template_result == 'undefined')
			{
				var source = jQuery("#entry-template-result-" + poll_srl).html();
				window.template_result = Handlebars.compile(source);
			}
			break;
		case 'members':
			if (typeof window.template_member == 'undefined')
			{
				var source = jQuery("#entry-template-members-" + poll_srl).html();
				window.template_member = Handlebars.compile(source);
			}
			break;
	}
}
/* 설문 참여 함수 */
function doRxDefaultPoll(fo_obj) {
	var checkcount = new Array();
	var item = new Array();

	for(var i=0; i < fo_obj.length; i++) {
		var obj = fo_obj[i];
		if (obj.nodeName != 'INPUT') {
			continue;
		}

		var name = obj.name;
		if (name.indexOf('checkcount') > -1) {
			var t = name.split('_');
			var poll_srl_index = parseInt(t[1], 10);
			checkcount[poll_srl_index] = obj.value;
			item[poll_srl_index] = new Array();

		} else if (name.indexOf('item_') > -1) {
			var t = name.split('_');
			var poll_srl = parseInt(t[1], 10);
			var poll_srl_index = parseInt(t[2], 10);
			if (obj.checked == true) {
				item[poll_srl_index][item[poll_srl_index].length] = obj.value;
			}
		}
	}

	var poll_srl_indexes = "";
	for(var poll_srl_index in checkcount) {
		if(!checkcount.hasOwnProperty(poll_srl_index)) {
			continue;
		}
		var count = checkcount[poll_srl_index];
		var items = item[poll_srl_index];
		if(items.length < 1 || count < items.length) {
			alert(poll_alert_lang);
			return false;
		}

		poll_srl_indexes += items.join(',')+',';
	}
	fo_obj.poll_srl_indexes.value = poll_srl_indexes;

	jQuery.exec_json("poll.procPoll", {"poll_srl":poll_srl,"poll_srl_indexes":poll_srl_indexes}, function(data){
		if (data.error != 0) {
			alert(data.message);
		}
		else {
			loadRxDefaultPollResult(poll_srl);
			jQuery("#poll_" + poll_srl + "_gotoresult_button").css({
				display: "none"
			});

			jQuery("#poll_" + poll_srl + "_result_nobutton").css({
				display: "block"
			});

			jQuery("#poll_" + poll_srl + "_result_yesbutton").css({
				display: "none"
			});
		}
	});
	return false;
}

/* 항목 추가 함수 */
function addRxDefaultItem(poll_srl, poll_srl_indexes) {
	jQuery.exec_json("poll.procPollInsertItem", {"srl":poll_srl, "index_srl":poll_srl_indexes, "title":jQuery("#new_item_" + poll_srl_indexes).val()},  function(data){
		if (data.error!=0) {
			alert(data.message);
		}
		else {
			jQuery("#poll_" + poll_srl + "_result_button").css({
				display: "none"
			});

			jQuery("#poll_" + poll_srl + "_result_nobutton").css({
				display: "block"
			});

			jQuery("#poll_" + poll_srl + "_result_yesbutton").css({
				display: "none"
			});

			loadRxDefaultPoll(poll_srl);
		}
	});
	return false;
}

/* 항목 삭제 함수 */
function deleteRxDefaultItem(poll_srl, poll_srl_indexes, poll_item_srl) {
	jQuery.exec_json("poll.procPollDeleteItem", {"srl":poll_srl, "index_srl":poll_srl_indexes, "item_srl":poll_item_srl}, function(data){
		if (data.error!=0) {
			alert(data.message);
		}
		else {
			jQuery("#poll_" + poll_srl + "_result_button").css({
				display: "none"
			});

			jQuery("#poll_" + poll_srl + "_result_nobutton").css({
				display: "block"
			});

			jQuery("#poll_" + poll_srl + "_result_yesbutton").css({
				display: "none"
			});

			loadRxDefaultPoll(poll_srl);
		}
	});
	return false;
}

function loadRxDefaultPoll(poll_srl, data)
{
	if (typeof data == 'undefined') {
		jQuery.exec_json("poll.getPollinfo", {"poll_srl":poll_srl}, function(data){
			loadRxDefaultPoll(parseInt(data.poll.poll_srl), data);
		});
	}
	else {
		jQuery("#stop_date_"+poll_srl).html(data.poll.stop_date);

		initRxDefaultTemplete('poll', poll_srl);
		var template = window.template;
		var context = Object;
		var additem = data.caniadditem;
		context.questions = {};
		for (var i in data.poll.poll) {
			var poll = data.poll.poll[i];
			context.questions[i] = {};
			context.questions[i].poll_index_srl = poll.poll_index_srl;
			context.questions[i].checkcount = poll.checkcount;
			context.questions[i].title = poll.title;
			context.questions[i].items = poll.item;
			context.questions[i].poll_srl = poll_srl;
			context.questions[i].isMultipleChoice = (poll.checkcount > 1);
			context.questions[i].additem = additem;
		}
		var html = template(context);

		jQuery("#poll_content_" + poll_srl).html(html);

		jQuery("#poll_" + poll_srl).css({
			display: "block"
		});
		jQuery("#poll_" + poll_srl + '_result').css({
			display: "none"
		});
	}
}

function showRxDefaultPollMemberNext(poll_srl, poll_item_srl)
{
	if (typeof window.cur_page == 'undefined') {
		window.cur_page = 1;
	}

	window.cur_page++;

	jQuery.exec_json("poll.getPollitemInfo", {"poll_srl":poll_srl, "poll_item":poll_item_srl, "page":window.cur_page}, function(data){
		initRxDefaultTemplete('members', poll_srl);
		var template = window.template_member;
		var context = Object;

		context.poll_srl = poll_srl;
		context.poll_item_srl = poll_item_srl;
		context.page = window.cur_page;
		context.isPage = ((data.page.total_count > 5) && (window.cur_page < data.page.total_page));

		context.members = {};

		for (var i in data.item.member) {
			var member = data.item.member[i];

			context.members[i] = {};
			context.members[i].profile_image = member.profile_image;
			context.members[i].member_srl = member.member_srl;
			context.members[i].nick_name = member.nick_name;
			context.members[i].isImage = (member.profile_image != '');
			context.members[i].dummy_profile = data.dummy_profile;
		}
		var html = template(context);

		jQuery("#btn_load_more_" + poll_item_srl).replaceWith(html);
	});

	return false;
}

function showRxDefaultPollMember(poll_srl, poll_item_srl)
{
	window.cur_page = 1;

	jQuery.exec_json("poll.getPollitemInfo", {"poll_srl":poll_srl, "poll_item":poll_item_srl, "page":window.cur_page}, function(data){
		initRxDefaultTemplete('members', poll_srl);
		var template = window.template_member;
		var context = Object;
		var title = poll_member_lang;
		title = title.replace("%s", data.item.title);
		var html = '<div class="title">' + title + '</div><ul>';
		context.poll_srl = poll_srl;
		context.poll_item_srl = poll_item_srl;
		context.page = window.cur_page;
		context.isPage = ((data.page.total_count > 5) && (window.cur_page < data.page.total_count));

		context.members = {};

		for (var i in data.item.member) {
			var member = data.item.member[i];

			context.members[i] = {};
			context.members[i].profile_image = member.profile_image;
			context.members[i].member_srl = member.member_srl;
			context.members[i].nick_name = member.nick_name;
			context.members[i].isImage = (member.profile_image != '');
			context.members[i].dummy_profile = data.dummy_profile;
		}
		html = html + template(context) + '</ul>';

		jQuery("#poll_content_" + poll_srl + "_result").html(html);

		jQuery("#poll_" + poll_srl + '_result_button').css({
			display: "none"
		});
		jQuery("#poll_" + poll_srl + '_gotoresult_button').css({
			display: "block"
		});
	});

	return false;
}

function loadRxDefaultPollResult(poll_srl, data)
{
	if (typeof data == 'undefined') {
		jQuery.exec_json("poll.getPollinfo", {"poll_srl":poll_srl}, function(data){
			loadRxDefaultPollResult(parseInt(data.poll.poll_srl), data);
		});
	}
	else {
		jQuery("#stop_date_result_" + poll_srl).html(data.poll.stop_date);
		jQuery("#poll_count_result_" + poll_srl).html(data.poll.poll_count);

		initRxDefaultTemplete('result', poll_srl);
		var template = window.template_result;
		var context = Object;
		var showMembers = (data.poll.poll_type==1 || data.poll.poll_type==3);
		context.questions = {};
		for (var i in data.poll.poll) {
			var poll = data.poll.poll[i];
			context.questions[i] = {};
			context.questions[i].poll_index_srl = poll.poll_index_srl;
			context.questions[i].checkcount = poll.checkcount;
			context.questions[i].title = poll.title;
			context.questions[i].poll_count = poll.poll_count;
			context.questions[i].showMembers = showMembers;
			context.questions[i].items = poll.item;
			var count = 0;
			for (var j in poll.item) {
				var item = poll.item[j];
				count++;
				if (poll.poll_count > 0) {
					context.questions[i].items[j].per = Math.round((item.poll_count / poll.poll_count)*100);
					context.questions[i].items[j].isVote = true;
				}
				else {
					context.questions[i].items[j].per = 0;
					context.questions[i].items[j].isVote = false;
				}
				context.questions[i].items[j].number = count;
			}
			context.questions[i].items = poll.item;
			context.questions[i].poll_srl = poll_srl;
			context.questions[i].isMultipleChoice = (poll.checkcount > 1);
		}
		var html = template(context);

		jQuery("#poll_content_" + poll_srl + "_result").html(html);
		jQuery("#poll_" + poll_srl).css({
			display: "none"
		});
		jQuery("#poll_" + poll_srl + '_result').css({
			display: "block"
		});

		// do not display back to result button, because, this is that page.
		jQuery("#poll_" + poll_srl + '_gotoresult_button').css({
			display: "none"
		});

		// Check if the user have voted or not. If xe (he or she) have done, do not display back to the poll button
		if (data.poll.is_polled == 0) {
			jQuery("#poll_" + poll_srl + '_result_button').css({
				display: "block"
			});
		}
		else {
			jQuery("#poll_" + poll_srl + '_result_button').css({
				display: "none"
			});
		}
	}
}