
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

// change color of submit button when all answers have been selected
// function changeColor() {
// 	$('#submit').addClass('summit');
// 	console.log("JavaScript");
// }