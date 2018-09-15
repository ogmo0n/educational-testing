
// change color of submit button
function submitBtnColor() {
	$('.submit').addClass('submitBtnColor');
}

// change color of button
function changeColor(myClass) {
	var flag = 0;
	// class 'a' is generated for every answer choice
	var a = document.querySelectorAll('#a'), i;
	for (i = 0; i < a.length; i++) {
		// highlight button if previously answered
		if (a[i].checked) {
			flag = 1;
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
					if (flag == 0) {
						$('.prompt').text('All questions have been answered');
					}
				} else {
					$(myClass).addClass('btnColor');
				}
				// change icon to 'check mark' on click
				$('.currentQuestion').siblings('.fa')
					.removeClass('fa fa-question-circle-o fa-lg')
					.addClass('fa fa-check fa-cg');
				//$('#individual.currentQuestion').addClass('answered');

				if (flag == 0) {
					// change text of .prompt dynamically
					var str = $('.prompt').text(); 	// get text
					if (str.substr(0, 2) > 9) { 		// if double digit
						var beg = str.substr(0, 2) - 1; // extract number portion and subtract one
						var end = str.substr(2); 		// extract non-number portion of text
					} else {							// if single digit
						var beg = str.substr(0, 1) - 1; // extract number portion and subtract one
						var end = str.substr(1); 		// extract non-number portion of text
					}
					if (beg > 1) $('.prompt').text(beg + end);
					else if (beg == 1) $('.prompt').text('1 question has not been answered');
					flag = 1;
				}
			});
		}
	}
}