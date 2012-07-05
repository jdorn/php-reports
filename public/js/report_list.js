$(document).ready(function() {
	function showReportList(h2,delay) {
		//show all child reports
		$('.report',h2.next('.report_list')).show();
		
		h2.removeClass('collapsed').addClass('expanded');
		h2.parent('.report_list').show();
		h2.parent('h2').removeClass('collapsed').addClass('expanded');
	}
	function hideReportList(h2,delay) {
		h2.removeClass('expanded').addClass('collapsed');
	}
	function toggleReportList(h2,delay) {
		if(h2.hasClass('expanded')) hideReportList(h2,delay);
		else showReportList(h2,delay);
	}
	
	//toggle links
	$('#report_list').on('click','h2',function() {
		toggleReportList($(this),200);
		
		return false;
	});
	$('#hide_table_of_contents').on('click',function() {
		$('#table_of_contents > .report_list').toggle(200);
		return false;
	});
	
	//when a link is clicked in table of contents, make sure the section is visible
	$('#table_of_contents').on('click','a',function() {
		var target = $($(this).attr('href'));
		showReportList(target);
	});
	
	//report search
	$('#search').keyup(function(e) {
		var val = $(this).val();
		
		//if empty, show all reports
		if(!val) {
			$("#report_list > .report_list").show();
			$("#report_list h2.title").removeClass('collapsed').addClass('expanded');
			$("#report_list h2.no_title").removeClass('expanded').addClass('collapsed');
			$("#report_list .report").show().removeClass('selected');
		
			refresh_report_list();
		
			return;
		}
		
		var re = new RegExp(val, "i");
		
		//get matching reports
		var matching = $.grep(reports,function(el) {
			return re.test(el.name);
		});
		
		//hide all reports
		$("#report_list > .report_list").hide();
		$("#report_list .report").hide().removeClass('selected');
		$("#report_list h2").removeClass('expanded').addClass('collapsed');
		
		//loop through all matches
		for(var i in matching) {
			//if a directory matches, show it and all the child reports
			if(!matching[i].report) {
				showReportList($('#report_'+matching[i].id));
			}
			//if a single report matches, show it and highlight it
			else {
				$('#report_'+matching[i].id).parent().addClass('selected').show().parents('.report_list').last().show();
				$('#report_'+matching[i].id).parents('.report_list').prev('h2').addClass('expanded').removeClass('collapsed');
			}
		}
		
		//make sure we aren't scrolled above the report list
		if($(window).scrollTop() < ($('#report_list').offset().top - 50)) {
			$(window).scrollTop($('#report_list').offset().top - 50);
		}
		
		refresh_report_list();
	});
	
	//there is a bug with webkit where the page isn't redrawn
	//when changing the innerhtml
	var refresh_report_list = function() {
		var height = $('#report_list').height('height').height();
		$('#report_list').height(height+1);
	};
	
	//make the search bar fixed when scolling down through reports
	var original_search_offset = $('#searchbar').next().offset().top;
	var search_bar_fixed = false;
	$(window).scroll(function(e){
		var $el = $('#searchbar');
		
		if(!search_bar_fixed) {
			original_search_offset = $el.next().offset().top - 55;
		}
		
		if ($(this).scrollTop() >= original_search_offset){
			if(!search_bar_fixed) {
				$el.addClass('fixed');
				search_bar_fixed = true;
			}
		}
		else if(search_bar_fixed) {
			$el.removeClass('fixed');
			search_bar_fixed = false;
		}
	});

});
