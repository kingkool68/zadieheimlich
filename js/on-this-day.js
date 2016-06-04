jQuery(document).ready(function($) {
	$onThisDaySwitcher = $('.on-this-day-date-switcher');
	$onThisDaySwitcher.on('change', '[name="zah-on-this-month"]', function() {
		// Adjust the number of days displayed in the select menu depending on what month is selected
		var monthVal = $(this).val();
		
		var daysInMonth = 31;
		switch( monthVal ) {
			// 30 days
			case '04':
			case '06':
			case '09':
			case '11':
				daysInMonth = 30;
			break;

			// 29 days because of leap years
			case '02':
				daysInMonth = 29;
			break;
		}
		$('[name="zah-on-this-day"]').attr('class', '').addClass('days-in-month-' + daysInMonth);
	}).find('[name="zah-on-this-month"]').trigger('change');

	$onThisDaySwitcher.on('submit', function(e) {
		// Skip a round trip to the server and set the URL directly to the pretty version we expect
		var url = $(this).attr('action');
		var monthVal = $('[name="zah-on-this-month"]', this).val();
		var dayVal = $('[name="zah-on-this-day"]', this).val();
		e.preventDefault();
		if ( ! monthVal && ! dayVal ) {
			return;
		}
		url += monthVal + '/' + dayVal + '/';
		if ( url == window.location ) {
			return;
		}
		window.location.href = url;
	});
});
