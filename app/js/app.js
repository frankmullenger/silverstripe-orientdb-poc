(function($) {
	$(document).ready(function() {

		//Add up the queries and total time
		var queryCount = 0;
		var totalTime = 0;

		$('p.message').each(function(el, val){

			queryCount++;

			var ms = $(val).html().match(/\n(.*?)ms/);
			totalTime += parseFloat(ms[1]);

			$(val).appendTo('#queries');
		});

		$('#query-count').html(queryCount);
		$('#query-time').html(totalTime.toFixed(4) + ' seconds');
	});
})(jQuery);