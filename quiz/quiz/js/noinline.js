
// confirm submission message
var needToConfirm = false;
window.onbeforeunload = confirmExit;
function confirmExit() {
	if (needToConfirm)
		return "You will receive a 0% for your test if you exit without submitting.";
}

// remove functionality of Enter and F5 keys
document.onkeydown = function (e) {
	if (e.which || e.keyCode == 13) {
		return false;
	} else if (e.which || e.keyCode == 116) {
		return false;
	}
}	

// change color of submit button
function submitBtnColor() {
	$('.submit').addClass('submitBtnColor');
}

// change color of button
function changeColor(myClass) {
	var a = document.querySelectorAll('.a'), i;
	for (i = 0; i < a.length; i++) {
		// highlight button if previously answered
		if (a[i].checked) {
			if (myClass == '.submit') {
				$('.next').addClass('btnColor');
			} else {
				$(myClass).addClass('btnColor');
			}
		} else {
			// highlight button upon 'click' event
			a[i].addEventListener('click', () => {
				if (myClass == '.submit') {
					$('.submit').addClass('submitBtnColor');
				} else {
					$(myClass).addClass('btnColor');
				}
			});
		}
	}
}