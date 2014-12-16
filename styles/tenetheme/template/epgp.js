$(document).ready(function() {
	$('table.clickable_row').on('click', 'tbody > tr > td', function() {
		if($(this).attr('class') != 'no_click') location.href = $(this).parent('tr').data('url');
	});
});
