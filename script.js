jQuery(function() {
    var $images = jQuery('main section.stage-big img');
    if ($images.length > 1) {
	$images.slice(1).hide();
	var $current = $images.slice(0, 1);
	var $first = $current;

	var swapImages = function() {
	    var $next = ($current.next('img').length > 0) ? $current.next('img') : $first;
	    $next.css({'position': 'absolute', 'top': 0, 'left': 0});
	    $next.fadeIn(1000, function() {
		$current.hide();
		$next.css('position', 'static');
		$current = $next;
	    });
	};

	window.setInterval(swapImages, 5000);
    }
});
