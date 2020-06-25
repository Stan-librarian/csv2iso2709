$(window).scroll(function() {
	var limit = screen.height / 10 ;
	var height = $(window).scrollTop();
	if(height > limit) {
		document.getElementById("top").style.visibility = "visible" ;
	}
	if(height < limit) {
		document.getElementById("top").style.visibility = "hidden" ;
	}
});