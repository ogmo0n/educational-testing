<?php
/*
 *  Quiz - Quiz: Ues LTI too provider
 *  Copyright (C) 2016  Martin Carruth
 *
 *  Contact: mcarruth@ecpi.edu
 *
 *  Updated with pagination in March 2018 
 *    by Christopher Odden
 *  Contact: chrodd9604@students.ecpi.edu
 * 
*/

/*
 * This page displays a list of items for a resource link.  Students are able to rate
 * each item; staff may add, edit, re-order and delete items.
 */

require_once('lib.php');

/**
 * Initialise session and database
 */
$db = NULL;
$ok = init($db, TRUE);
/**
 * Initialise parameters
 */
$id = 0;
if ($ok) {

	$action = '';
	/** 
	 * Check for item id and action parameters
	 */
	if (isset($_REQUEST['id'])) {
		$id = intval($_REQUEST['id']);
	}
	if (isset($_REQUEST['do'])) {
		$action = $_REQUEST['do'];
	}
	if (isset($_REQUEST['grade'])) {
		$action = 'grade';
	}
	if (isset($_REQUEST['next'])) {
		$action = 'next';
	}
	if (isset($_REQUEST['previous'])) {
		$action = 'previous';
	}

	if (isset($_REQUEST['delete'])) {
		$attemptId = $_GET['delete'];
		$facultyUsername = $_SESSION['username'];
		$deleteId = deleteQuizAttempt($db, $attemptId, $facultyUsername);
	}

	if (isset($_REQUEST['review'])) {
		$attemptId = $_GET['review'];
		$facultyUsername = $_SESSION['username'];
	}

	if (isset($_REQUEST['print_test'])) {
		$action = 'print_test';
	}

	if ($action =='' && $_SESSION['isStudent'] == 1) {
		$grade = getQuizGrade($db, $_SESSION['type'], $_SESSION['course_SISID'], $_SESSION['username'], $_SESSION['consumer_key'], $_SESSION['resource_id']);
		if ($grade) {
        /**
         * If test grade previously recorded, prints test grade
         */
			$action = 'print_grade';
		} else {
        /**
         * Initial launch
         */
			$action = 'intro';
		} 
	} else if ($_SESSION['isTeacher'] == 1 || $_SESSION['isAdmin'] == 1 ) {
    /**
     * Print student grades for class for teacher
     */
		$grades = getQuizGrades($db, $_SESSION['type'], $_SESSION['course_SISID'], $_SESSION['consumer_key'], $_SESSION['resource_id']);
    /**
     * Extracts needed info from database to build test.
     */
	} else if ($action == 'print_test' ) {
		$action = 'print_test1';
		$quizId = getQuizId($db, $_SESSION['courseCode']);
		$_SESSION['quizId'] = $quizId;
		$questions = getQuestions($db, $_SESSION['courseCode'], $quizId);
		$_SESSION['questions'] = $questions;
		/** 
		 * arrQuestion variable set; Contains all questions and numbers for the test.
		 *
		 * All values (answers) initialized to '0' until student selects an answer.
		 */
		$_SESSION['arrQuestion'] = [];
	    $count = 0;
	    foreach ($questions as $question) {
	    	$_SESSION['arrQuestion'][$_SESSION['questions'][$count]['quesNumb']] = 0;
	    	$count++;
	    }
	    /** 
		 * Pass arrQuestion to frontend w/0 values and replace
		 *
		 */
	    $_SESSION['jsArray'] = array_values($_SESSION['arrQuestion']);
	    print_r($jsArray);
	    // echo json_encode($jsArray);
	    
		$numbQuestion = count($_SESSION['questions']);
		$_SESSION['numberQuestions'] = $numbQuestion;
		$numbers = range(0,$_SESSION['numberQuestions'] - 1); 
		shuffle($numbers);
		$_SESSION['numbers'] = $numbers;
		$version = getQuizVersion($db, $quizId);
    	/**
    	 * Database entry to signify starting test
    	 */
		$attemptId = recordQuizStart($db, $quizId,$version, $_SESSION['type'], $_SESSION['courseCode'], 
			$_SESSION['username'], $_SESSION['first_name'], $_SESSION['last_name'], $_SESSION['course_SISID'], $_SESSION['consumer_key'], 
			$_SESSION['resource_id'], $_SESSION['user_id'], $_SESSION['user_sis_id']);
		$_SESSION['attemptId'] = $attemptId;
    	/**
    	 * This is error checking.
    	 *
    	 * Will prevent student from taking test with no way to record answers
    	 * This should not occur.
    	 */
		if ($attemptId == 0) {
			error_log("index.php - Record Attempt - AttemptId:0 username=".$_SESSION['username']." type=".$_SESSION['type']." course_SISID=".$_SESSION['course_SISID']." courseCode=".$_SESSION['courseCode']." attemptId=".$attemptId." action=".$action."\n", 3, "errors.log");
			$action = 'restart';
		}

    /**
     * Grades test and updates LMS gradebook
     */
	}  else if ($action == 'grade') {
    /** 
     * Grade test
     */
		$action = 'grade1';
		foreach ($_POST as $k => $v) {
			if ($k == $_SESSION['questions'][$_SESSION['count']]['quesNumb']) {
				$_SESSION['arrQuestion'][$k] = $v;
			}
		}
		$attemptId = postValue('attemptId');
		recordQuizCompletion($db, $attemptId);
		error_log("(index.php-grade  action:1) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." course_SISID=".$_SESSION['course_SISID']." courseCode=".$_SESSION['courseCode']." attemptId=".$attemptId." action=".$action." REQUEST=".$_REQUEST['grade']."\n", 3, "errors.log");
		$pointsEarned = 0;
		foreach ($_SESSION['arrQuestion'] as $questionNumb => $answerId) {
			$answerId = str_replace(' ', '', $answerId);
			$questionNumb = str_replace(' ','', $questionNumb);
			if (is_numeric($questionNumb)) {
				$points = getAnswerValue($db, $answerId);
				$pointsEarned += $points;
				recordQuizAnswers($db, $attemptId, $questionNumb, $answerId, $points);
			}
		}
		$grade = round(($pointsEarned * 100 )/$_SESSION['numberQuestions'] , 1);
		if ($grade > 0) {
			error_log("(index.php-grade  action:2) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." grade=".$grade." course_SISID=".$_SESSION['course_SISID']." courseCode=".$_SESSION['courseCode']." attemptId=".$attemptId." action=".$action."\n", 3, "errors.log");
			updateQuizGrade($db, $grade, $attemptId);
			if ($_SESSION['type'] != 'pre') {
				updateGradebook($db, $_SESSION['consumer_key'], $_SESSION['user_id'] );
			}
		}

	/**
	 * If the 'next' button was pressed,
	 * loop through the post array, compare keys,
	 * and assign the previous question's selected value.
	 */
	} else if ($action == 'next') {
		if ($_SESSION['isStudent'] == 1) {
			$action = 'print_test1';
			foreach ($_POST as $k => $v) {
				if ($k == $_SESSION['questions'][$_SESSION['count']]['quesNumb']) {
					$_SESSION['arrQuestion'][$k] = $v;
				}
			}
		}
	/**
	 * If the 'previous' button was pressed,
	 * loop through the post array, compare keys,
	 * and assign the former question's selected value.
	 */
	} else if ($action == 'previous') {
		if ($_SESSION['isStudent'] == 1) {
			$action = 'print_test1';
			foreach ($_POST as $k => $v) {
				if ($k == $_SESSION['questions'][$_SESSION['count']]['quesNumb']) {
					$_SESSION['arrQuestion'][$k] = $v;
				}
			}
		}
	}

}

/**
 * Page header
 */
$title = APP_NAME;

/**
 * Attempted PHP Cache-Control. See meta tags in document head
 */
	// header("Cache-Control: no-cache, must-revalidate"); //HTTP 1.1
	// header("Pragma: no-cache"); //HTTP 1.0
	// header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
/* Removed from head
	<!-- <meta http-equiv="cache-control" content="no-cache" />
	<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
	<meta http-equiv="pragma" content="no-cache" /> -->
*/

$page = <<< EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="en" xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-language" content="EN" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta charset="UTF-8">
<title>{$title}</title>
<script type="text/javascript" src="js/noinline.js"></script>
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery.rateit.min.js"></script>
<script type="text/javascript">
	var needToConfirm = false;
	window.onbeforeunload = confirmExit;
	(function confirmExit() {
		if (needToConfirm)
			return "You will receive a 0% for your test if you exit without submitting.";
	})(); //****** NOT WORKING!! #######################
	document.onkeydown = function (e) {
		if (e.which || e.keyCode == 13) {
			return false;
		} else if (e.which || e.keyCode == 116) {
			return false;
		}
	}	
</script>
<script type="text/javascript">
	var js_array = <?php echo json_encode({$_SESSION['jsArray']}, JSON_PRETTY_PRINT); ?>;
	console.log(js_array);
</script>
<link rel="stylesheet" type="text/css" href="css/styles.css">
</head>
<body onkeydown="return TriggeredKey(this);">
EOD;

/**
 * Removed the following from stylesheet: ?<?php echo time(); ?>
 */


/**
 * Check for any messages to be displayed
 */
if (isset($_SESSION['error_message'])) {
	$page .= "
		<p style='font-weight: bold; color: #f00;'>ERROR: {$_SESSION['error_message']}</p>";
	unset($_SESSION['error_message']);
}

if (isset($_SESSION['message'])) {
	$page .= "
		<p style='font-weight: bold; color: #00f;'>{$_SESSION['message']}</p>";
	unset($_SESSION['message']);
}

/**
 * Page body  
 */
if ($action == 'intro') {

    /**
     * Pretest header
     */
	if ($_SESSION['type'] == 'pre') {
		$page .= '
			<form action="./" method="post">
			<h4>Directions</h4>
			<p>The goal of the pretest is to see how much you know going into the course 
			and compare it to how much you learned by the end. It is entirely possible 
			that you will know the answers to very few of the questions posed here, 
			and that is okay; you have never taken the course before.</p>
			<p>Please <strong>do not</strong> let the word <strong>"test"</strong> intimidate 
			you. <strong>This pre-test will not be graded</strong> as an exam in the 
			course, but taking the test may be counted as part of your course participation.</p>
			<p>Since we need an accurate measure of what you know going into the course, 
			please avoid using your textbook or any supplemental sources to complete the exam. 
			Do not guess answers. Instead skip the questions you do not know.</p>
			<p><strong>After you start the test you will not be able to leave the test.</strong>  </p>
			<p><strong>After you have taken the test, please be sure you click Submit.</strong> 
			This allows your grade to be recorded.</p><br />
			<p><strong>Do not use the "Enter" key during the test, it may prematurely submit the test.</strong></p>
			<input type="submit" class="next" value="Start Test" name="print_test" onclick="needToConfirm = false;" />';
	}

    /**
     * Post test header
     */
	if ($_SESSION['type'] == 'post') {
		$page .= '
			<form action="./" method="post">
			<h4>Directions</h4>
			<p>The results of this test may be counted as part of your overall course grade. 
			Please consult your course materials and/or instructor for the policy on grading this test.</p>
			<p>Since we need an accurate measure of knowledge gained during the course, 
			please avoid using your textbook or any supplemental sources to complete the exam.</p>
			<p><strong>After you start the test you will not be able to leave the test.  
			Leaving the test without clicking "Submit" will result in a grade of 0 for this test.</strong></p>
			<p><strong>After leaving the test, you will not be allowed back into the test. Please ensure you complete the test prior to clicking Submit.</strong></p>
			<p><b>After you have taken the test, please be sure you click Submit</b>. This allows your grade to be recorded.</p><br />
			<p><strong>Do not use the "Enter" key during the test, it may prematurely submit the test.</strong></p>
			<input type="submit" class="next" value="Start Test" name="print_test" onclick="needToConfirm = false;" />';
	}

    /**
     * Library Orientation test header.
     */
	if ($_SESSION['type'] == 'quiz' || $_SESSION['type'] == 'lit') {
		$page .= '
			<form action="./" method="post">
			<h4>Directions</h4>
			<p>The results of this test may be counted as part of your overall course grade. 
			Please consult your course materials and/or instructor for the policy on grading this test.</p>
			<p><strong>After you start the test you will not be able to leave the test.  
			Leaving the test without clicking "Submit" will result in a grade of 0 for this test.</strong></p>
			<p><strong>After leaving the test, you will not be allowed back into the test. Please ensure you complete the test prior to clicking Submit.</strong></p>
			<p><b>After you have taken the test, please be sure you click Submit</b>. This allows your grade to be recorded.</p><br />
			<p><strong>Do not use the "Enter" key during the test, it may prematurely submit the test.</strong></p>
			<input type="submit" class="next" value="Start Test" name="print_test" onclick="needToConfirm = false;" />';
	}
}

if ($action == 'print_test1') {
	/**
	 * Display test pre-post test
	 */ 
	if ($ok) {

		if ($_SESSION['isStudent'] == 1) {

			if (!isset($_SESSION['count'])){
				$_SESSION['count'] = 0;
			}

			$image = ''; 

			/**
			 * Set the question based on number in array.
			 *
			 * If not set, set the question number to current within array.
			 */
			if (!isset($_SESSION['quesNumb'])) {
				$quesNumb = current($_SESSION['numbers']);
			}

			/**
			 * If user has pushed the 'next' button, increase count, 
			 * and move to next array position.
			 */
			if (array_key_exists('next', $_POST)) {
				$_SESSION['count']++;
				$quesNumb = next($_SESSION['numbers']);
			}

			/**
			 * If user has pushed the 'previous' button, increase count, 
			 * and move to previous array position.
			 */
			if (array_key_exists('previous', $_POST)) {
				$_SESSION['count']--;
				$quesNumb = prev($_SESSION['numbers']);
			}

			/**
			 * Determine if question contains an image. If so, retrieve it.
			 */
			if (strlen($_SESSION['questions'][$_SESSION['count']]['qpic']) > 1) { 
				$image = '<img src="https://test.ecpi.net/pretest/'.$_SESSION['questions'][$_SESSION['count']]['qpic'].'"  alt="'.$_SESSION['questions'][$_SESSION['count']]['qalt'].'" /><br />'; // add URL for src like <img src="https://test.school.net/pretest/'.$_SESSION[]"
			} else {
				$image = '';
			}

			/**
			 * Pull question from database.
			 */
			$quest_string = html_entity_decode($_SESSION['questions'][$_SESSION['count']]['question']);

			/**
			 * Print question with optional image to page.
			 */
			$page .= "
				<form method='post'>
				<table>
				<tr><th class='number'>Question ".($_SESSION['count'] + 1)."<span class='total'> / ".$_SESSION['numberQuestions']."</span></th></tr>
				<tr><td class='question'>".$quest_string . $image."</td></tr>";

			/**
			 * Pull answers from database.
			 */
			$answers = getAnswers($db, $_SESSION['questions'][$_SESSION['count']]['quesNumb']);
			$countb = 1; // answer count
			foreach ($answers as $answer) {    
				$letter = chr(64 + $countb);
				$ans=  html_entity_decode($answer['answer']);
				$Quest = $_SESSION['questions'][$_SESSION['count']]['quesNumb'];
				$AnswerID = $answer['answerId'];

				/**
			 	* Print possible answers to page.
			 	*
			 	* If an answer has been previously selected,
			 	* allow it to be of the checked state.
			 	*/
				$page .= "
					<tr><td><hr></td></tr>
					<tr><td class='answer'><input type='radio' class='a'";
				if ($_SESSION['arrQuestion'][$Quest] == $AnswerID) {
					$page .= " checked='checked' ";
				}
				$page .= "name='".$_SESSION['questions'][$_SESSION['count']]['quesNumb']."' value='".$answer['answerId']."'> {$ans}</td></tr> ";
        		$countb++;
			}
		    $page .= "
		       <tr><td><br /></td></tr>";

			/**
			 * Highlight 'Submit Quiz' button if all questions have been answered.
			 */
			if (!in_array(0, $_SESSION['arrQuestion'], true)) {
				echo "<script type='text/javascript'>
					//setTimeout(function(){submitBtnColor();}, 0);
					window.onload = function(){submitBtnColor();};
					</script>";
			}

			/**
			 * Form buttons for last test question
			 * when all questions have NOT been answered.
			 *
			 * Hidden input to retain student's session.
			 */
			if ($_SESSION['count'] == $_SESSION['numberQuestions'] - 1 && in_array(0, $_SESSION['arrQuestion'], true)) {
				echo "<script type='text/javascript'>
					//setTimeout(function(){changeColor('.previous');}, 0);
					window.onload = function(){changeColor('.previous');};
					</script>";
				$page .= "
					</table>
					<input type='hidden' name='attemptId' value='".$_SESSION['attemptId']."' />
					<div class='buttons'>
						<input type='submit' id='previous' class='previous' value='&#9666 Previous' name='previous' /> 
						<input type='submit' id='next' class='next' value='Next &#9656' name='next' disabled/>
					</div>
					<div class='buttons box'>
						<span class='prompt'>x question(s) have not been answered</span>
						<input type='submit' id='submit' class='submit' value='Submit Quiz' name='grade' onclick='needToConfirm = true;return confirm('Are you sure you want to submit your answers?');' /> 
					</div>
					</form>";

			// /**
			//  * Form buttons for last test question
			//  * when all questions have NOT been answered.
			//  *
			//  * Hidden input to retain student's session.
			//  */
			// } else if ($_SESSION['count'] == $_SESSION['numberQuestions'] - 1 && in_array(0, $_SESSION['arrQuestion'], true)) {
			// 	echo "<script type='text/javascript'>
			// 		setTimeout(function(){changeColor('.submit');}, 0);
			// 		</script>";
			// 	$page .= "
			// 		</table>
			// 		<input type='hidden' name='attemptId' value='".$_SESSION['attemptId']."' />
			// 		<div class='buttons'>
			// 			<input type='submit' id='previous' class='previous' value='&#9666 Previous' name='previous' /> 
			// 			<input type='submit' id='next' class='next' value='Next &#9656' name='next' disabled/>
			// 		</div>
			// 		<div class='box'>
			// 			<input type='submit' id='submit' class='submit' value='Submit Quiz' name='grade' onclick='needToConfirm = true;return confirm('Are you sure you want to submit your answers?');' /> 
			// 		</div>
			// 		</form>";

			/**
			 * Form buttons for first test question.
			 *
			 * Hidden input to retain student's session.
			 */
			} else if ($_SESSION['count'] < 1) { 
				echo "<script type='text/javascript'>
					window.onload = function(){changeColor('.next');};
					//(function(){changeColor('.next');})();
					</script>";
				$page .= "
					</table>
					<input type='hidden' name='attemptId' value='".$_SESSION['attemptId']."' />
					<div class='buttons'>
						<input type='submit' id='previous' class='previous' value='&#9666 Previous' name='previous' disabled/> 
						<input type='submit' id='next' class='next' value='Next &#9656' name='next'/>
					</div>
					<div class='box'>
						<input type='submit' id='submit' class='submit' value='Submit Quiz' name='grade' onclick='needToConfirm = true;return confirm('Are you sure you want to submit your answers?');' /> 
					</div>
					</form>";
			
			/**
			 * Form buttons for test questions other than first or last.
			 *
			 * Hidden input to retain student's session.
			 */
			} else {
				echo "<script type='text/javascript'>
					//setTimeout(function(){changeColor('.next');}, 0);
					window.onload = function(){changeColor('.next');};
					</script>";
				$page .= "
					</table>
					<input type='hidden' name='attemptId' value='".$_SESSION['attemptId']."'/>
					<div class='buttons'>
						<input type='submit' id='previous' class='previous' value='&#9666 Previous' name='previous'/> 
						<input type='submit' id='next' class='next' value='Next &#9656' name='next'/>
					</div>
					<div class='box'>
						<input type='submit' id='submit' class='submit' value='Submit Quiz' name='grade' onclick='needToConfirm = true;return confirm('Are you sure you want to submit your answers?');' /> 
					</div>
					</form>";
			}
		}
	}
}

if ($action == 'print_grade') {
	$grade = round($grade,0);
	$page .= "
		<p>You have previously completed this assessment.  The grade you earned is {$grade}</p>";
	if ($grade == 0) {
		$page .= "
			<p>You will need to speak with your instructor about starting the quiz and then leaving the quiz before submitting.<p>";
	}
}


if ($action == 'restart') {
	$page .= "
		<p>An issue occurred during the test launch, please close your browser window and then log back in your course again.</p>";
}

if ($action == 'grade1') {
	$page .= "
		<p>Results of the assessment</p>
		<p>Grade: {$grade}</p>";
}

/**
 * Teacher part of page starts
 */  
if ($_SESSION['isTeacher'] == 1 || $_SESSION['isAdmin'] == 1 ) {
	$page .= "
		<div style='margin:auto;width:80%;'>
		<p><a target='_blank' href='./previewTest.php' onclick='needToConfirm = false;'>Preview test</a></p>
		<p>Grades earned by students.</p>
		<table class='grade'><tr class='grade'><th class='grade'>Delete Attempt *</th><th class='grade'>Student ID</th><th class='grade'>First Name</th><th class='grade'>Last Name</th>
		<th class='grade'>Grade</th><th  class='grade'>Start</th><th class='grade'>Stop</th></tr>";
	foreach ($grades as $grade) {
		$link = "<a href='./index.php?delete=".$grade['attemptId']."' onclick='needToConfirm = false;' >delete attempt</a>";  
		$_SESSION['quiz_attemptId'] = $grade['attemptId'];
		$review = "<a target='_blank' href='./review_student.php?attemptId=".$grade['attemptId']."'>{$grade['grade']}</a>";
		$page .= "
			<tr><td class='grade'>{$link}</td><td class='grade'>{$grade['username']}</td><td class='grade'>{$grade['first_name']}</td><td class='grade'>{$grade['last_name']}</td><td class='grade'>{$review}</td><td class='grade'>{$grade['quiz_start']}</td><td class='grade'>{$grade['quiz_stop']}</td></tr>";
	}
	$page .= "
		</table>
		<p>* each student attempt that is deleted is logged indicating the faculty that performed the deletion</p>
		</div>";
}


/**
 * Page footer
 */
$page .= "
	</body>
	</html>";

/**
 * Display page
 */
echo $page;

?>