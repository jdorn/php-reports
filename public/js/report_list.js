$(document).ready(function() {
	function showReportList(h2) {
		//show all child reports
		$('.report',h2.next('.report_list')).show();
		
		h2.removeClass('collapsed').addClass('expanded');
		h2.parent('.report_list').show();
		h2.parent('h2').removeClass('collapsed').addClass('expanded');
	}
	function hideReportList(h2) {
		h2.removeClass('expanded').addClass('collapsed');
	}
	function toggleReportList(h2) {
		if(h2.hasClass('expanded')) hideReportList(h2);
		else showReportList(h2);
	}
	
	//toggle links
	$('#report_list').on('click','h2',function() {
		toggleReportList($(this),200);
		
		return false;
	});
	$('#hide_table_of_contents').on('click',function() {
		$('#table_of_contents').find('> .report_list').toggle(200);
		return false;
	});
	
	//when a link is clicked in table of contents, make sure the section is visible
	$('#table_of_contents').on('click','a',function() {
		var target = $($(this).attr('href'));
		showReportList(target);
	});
	
	//report search
    var last_value = '';
	$('#search').on('keyup search change',function() {
		var val = $(this).val();
        if(val === last_value) return;
        last_value = val;

        var report_list = $("#report_list");
		
		//if empty, show all reports
		if(!val) {
            report_list.find("> .report_list").show();
            report_list.find("h2.title").removeClass('collapsed').addClass('expanded');
            report_list.find("h2.no_title").removeClass('expanded').addClass('collapsed');
            report_list.find(".report").show().removeClass('selected');
		
			return;
		}

		//require at least 2 letters to search
		//if(val.length < 2) return;

		var re = new RegExp(val, "i");
		
		//get matching reports
		var matching = $.grep(reports,function(el) {
			return re.test(el.name);
		});
		
		//hide all reports
        report_list.find("> .report_list").hide();
        report_list.find(".report").hide().removeClass('selected');
        report_list.find("h2").removeClass('expanded').addClass('collapsed');
		
		//loop through all matches
		for(var i in matching) {
            var elem = $('#report_'+matching[i].id);

			//if a directory matches, show it and all the child reports
			if(!matching[i].report) {
				showReportList(elem);
			}
			//if a single report matches, show it and highlight it
			else {
                elem.parent().addClass('selected').show().parents('.report_list').last().show();
                elem.parents('.report_list').prev('h2').addClass('expanded').removeClass('collapsed');
			}
		}
	});
});
