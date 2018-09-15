<?php
/*
 *  Quiz - Quiz: Ues LTI too provider
 *  Copyright (C) 2016  Martin Carruth
 *
 *  Contact: mcarruth@ecpi.edu
 *
 *  Updated with pagination in March 2018 
 *    by Christopher Odden
 *  Modified in July 2018 
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
$num;
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
	if (isset($_REQUEST['individual'])) {
		$action = 'individual';
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
		 * flagged variable set; Contains all questions and numbers for the test for 
		 * flagging purposes.
		 *
		 * All values (answers) initialized to '0' until student selects an answer.
		 */
		$_SESSION['arrQuestion'] = [];
		$_SESSION['flagged'] = [];
	    $count = 0;
	    foreach ($questions as $question) {
	    	$_SESSION['arrQuestion'][$_SESSION['questions'][$count]['quesNumb']] = 0;
	    	$_SESSION['flagged'][$_SESSION['questions'][$count]['quesNumb']] = 0;
	    	$count++;
	    }
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
		print_r($_SESSION['arrQuestion']);
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
			// if 'flag' wasn't passed with click, change 1 to 0
			if (!isset($_REQUEST['flag'])) {
				$_SESSION['flagged'][$_SESSION['questions'][$_SESSION['count']]['quesNumb']] = 0;
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
			// if 'flag' wasn't passed with click, change 1 to 0
			if (!isset($_REQUEST['flag'])) {
				$_SESSION['flagged'][$_SESSION['questions'][$_SESSION['count']]['quesNumb']] = 0;
			}
		}

	/**
	 * If an 'individual' button/link was pressed,
	 * loop through the post array, compare keys,
	 * and assign the last question's selected value.
	 */
	} else if ($action == 'individual') {
		if ($_SESSION['isStudent'] == 1) {
			// Remove the string 'Question ' from input value and decrease returned value by 1
			$num = substr($_POST['individual'], 9) - 1;
			$action = 'print_test1';
			foreach ($_POST as $k => $v) {
				if ($k == $_SESSION['questions'][$_SESSION['count']]['quesNumb']) {
					$_SESSION['arrQuestion'][$k] = $v;
				}
			}
			// if 'flag' wasn't passed with click, change 1 to 0
			if (!isset($_REQUEST['flag'])) {
				$_SESSION['flagged'][$_SESSION['questions'][$_SESSION['count']]['quesNumb']] = 0;
			}
		}
	}
}

/**
 * Page header
 */
$title = APP_NAME;
$page = <<< EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="en" xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-language" content="EN" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta charset="UTF-8">
<title>{$title}</title>
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/no.inline.js"></script>
<script type="text/javascript" src="js/jquery.rateit.min.js"></script>
<script type="text/javascript">
	// disable refresh - messages no longer work in modern browsers
	var needToConfirm = true;
	window.onbeforeunload = function(e) {
		if (needToConfirm) {
			e.preventDefault;
			return 'You will receive a 0% for your test if you exit without submitting.';
		}
	}
	window.beforeunload = function(e) {
		if (needToConfirm) {
			e.preventDefault;
			return 'You will receive a 0% for your test if you exit without submitting.';
		}
	}

	// disable back button
	history.pushState(null, null, document.URL);
	window.addEventListener('popstate', function () {
	    return 'You will receive a 0% for your test if you exit without submitting.';
	    history.pushState(null, null, document.URL);
	}, false);

	// disable keys
	document.onkeydown = function (e) {
		if (e.which || e.keyCode == 13) { // prevent Enter
			return false;
		} else if (e.which || e.keyCode == 116) { // prevent f5
			return false;
		} else if (e.which || e.keyCode == 123) { // prevent f12
			return false;
		} else if (e.ctrlKey) {
			var c = e.which || e.keyCode;
			if (c == 82) { // prevent Ctrl+R
				return false;
			}
		}
	}
</script>
<link rel="stylesheet" type="text/css" href="css/styles.css">
<link rel="stylesheet" href="css/font-awesome/css/font-awesome.min.css">
<link href="https://fonts.googleapis.com/css?family=Lato" rel="stylesheet">
</head>
<body onkeydown="return TriggeredKey(this);">
EOD;

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


/**
 * Display test pre-post test
 */ 

if ($action == 'print_test1') {

	if ($ok) {

		if ($_SESSION['isStudent'] == 1) {

			if (!isset($_SESSION['count'])){
				$_SESSION['count'] = 0;
			}

			$image = ''; // instantiate image variable and initialize to empty string

			/**
			 * Set the question based on number in array.
			 *
			 * If not set, set the question number to current within array.
			 */
			if (!isset($_SESSION['quesNumb'])) {
				$quesNumb = current($_SESSION['numbers']);
			}

			/**
			 * If user has clicked the 'next' button, increase count, 
			 * and move to next array position.
			 */
			if (array_key_exists('next', $_POST)) {
				$_SESSION['count']++;
				$quesNumb = next($_SESSION['numbers']);
			}

			/**
			 * If user has clicked the 'previous' button, increase count, 
			 * and move to previous array position.
			 */
			if (array_key_exists('previous', $_POST)) {
				$_SESSION['count']--;
				$quesNumb = prev($_SESSION['numbers']);
			}

			/**
			 * If user has clicked an 'individual' question button, change count, 
			 * and move to individual array position.
			 */
			if (array_key_exists('individual', $_POST)) {
				// $_SESSION['count'] = $_REQUEST['individual']; // array index by count
				$_SESSION['count'] = $num; // array index by count
				$quesNumb = $_SESSION['numbers'][$_REQUEST['individual']]; // I don't think this does anything ******
			}

			/**
			 * If user has 'flagged' a question, 
			 * change value in flagged array from 0 to 1.
			 */
			if (array_key_exists('flag', $_POST)) {
				foreach ($_POST as $k => $v) {
					if ($k == 'flag') {
						$_SESSION['flagged'][$v] = 1;
					}
				}
			}

			/**
			 * Determine if question contains an image. If so, retrieve it.
			 */
			if (strlen($_SESSION['questions'][$_SESSION['count']]['qpic']) > 1) { 
				$image = '<img src="https://test.ecpi.net/pretest/'.$_SESSION['questions'][$_SESSION['count']]['qpic'].'" alt="'.$_SESSION['questions'][$_SESSION['count']]['qalt'].'" /><br />'; // add URL for src like <img src="https://test.school.net/pretest/'.$_SESSION[]"
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
				<div class='main-content'>
					<form method='post' class='my-form'>
						<div class='second-container'>
						  <div class='question-container'>
						    <div class='input-table-container'>
						      <input type='checkbox' name='flag' value='".$_SESSION['questions'][$_SESSION['count']]['quesNumb']."' class='flag-question'";
			// if value in flagged array is 1, flag question
			if ($_SESSION['flagged'][$_SESSION['questions'][$_SESSION['count']]['quesNumb']] == 1) {
				$page .= "checked='checked'";
			}
			$page .= "
				   ><table>
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
					<tr><td class='answer'><input type='radio' id='a'";
				if ($_SESSION['arrQuestion'][$Quest] == $AnswerID) {
					$page .= " checked='checked' ";
				}
				$page .= "name='{$Quest}' value='".$answer['answerId']."'> {$ans}</td></tr> ";
        		$countb++;
			}
		    $page .= "
		       <tr><td><br /></td></tr>";

			/**
			 * Count number of unanswered questions in arrQuestion
			 */
		    if (!in_array(0, $_SESSION['arrQuestion'], true)) {
		    	$unanswered = null;
		    } else {
		    	$unanswered = array_count_values($_SESSION['arrQuestion'])[0];
		    }
// write code to find the closest '0' value to current question in
// arrQuestion to appropriately highlight next/previous button
echo "<br>Quest: {$Quest}<br>";
		    /**
			 * HTML and JavaScript String Variables
			 */
		    $js_changeBtnColor = "<script type='text/javascript'>window.onload = function(){changeColor";
		    $js_classPrevious = "('.previous');};</script>";
		    $js_classNext = "('.next');};</script>";
		    $js_classSubmit = "('.submit');};</script>";
		    $js_submitBtnColor = "<script type='text/javascript'>window.onload = function(){submitBtnColor();};</script>";
			$hiddenInput = "</table></div><input type='hidden' name='attemptId' value='{$_SESSION['attemptId']}' />";
			$divClassButtons = "<div class='buttons'>";
			$prevBtn = "<input type='submit' id='previous' class='previous' value='&#9666 Previous' name='previous' onclick='needToConfirm=false;'/>";
			$disabledPrevBtn = "<input type='submit' id='previous' class='previous' value='&#9666 Previous' name='previous' disabled/>";
			$nextBtn = "<input type='submit' id='next' class='next' value='Next &#9656' name='next' onclick='needToConfirm=false;'/>";
			$disabledNextBtn = "<input type='submit' id='next' class='next' value='Next &#9656' name='next' disabled/>";
			$closingDivTag = "</div>";
			$divClassBox = "<div class='box'>";
			$promptSpan1 = "<div class='prompt'>{$unanswered} question has not been answered</div>";
			$promptSpan2 = "<div class='prompt'>{$unanswered} questions have not been answered</div>";
			$promptSpanAll = "<div class='prompt'>All questions have been answered</div>";
			$submitBtn = "<input type='submit' id='submit' class='submit' value='Submit Quiz' name='grade' onclick='needToConfirm=false;return confirm('Are you sure you want to submit your answers?');'/>";

			/**
			 * Section for when all questions have NOT been answered.
			 *
			 * Hidden input to retain student's session.
			 */
			if (in_array(0, $_SESSION['arrQuestion'], true)) {

				// Form buttons for first test question
				if ($_SESSION['count'] < 1) {
					// if first question is last unanswered question
					if ($unanswered == 1) {
						$page .= 
							$js_changeBtnColor.$js_classSubmit.$hiddenInput.$divClassButtons.$disabledPrevBtn.$nextBtn.$closingDivTag.$closingDivTag.$divClassBox.$promptSpan1.$submitBtn.$closingDivTag.$closingDivTag;
					} else { // if there are other unanswered questions
						$page .= 
							$js_changeBtnColor.$js_classNext.$hiddenInput.$divClassButtons.$disabledPrevBtn.$nextBtn.$closingDivTag.$closingDivTag.$divClassBox.$promptSpan2.$submitBtn.$closingDivTag.$closingDivTag;
					}

				// Form buttons for last test question
				} else if ($_SESSION['count'] == $_SESSION['numberQuestions'] - 1) {
					// if last question is last unanswered question
					if ($unanswered == 1) {
						$page .= 
							$js_changeBtnColor.$js_classSubmit.$hiddenInput.$divClassButtons.$prevBtn.$disabledNextBtn.$closingDivTag.$closingDivTag.$divClassBox.$promptSpan1.$submitBtn.$closingDivTag.$closingDivTag;
					} else { // if there are other unanswered questions
						$page .= 
							$js_changeBtnColor.$js_classPrevious.$hiddenInput.$divClassButtons.$prevBtn.$disabledNextBtn.$closingDivTag.$closingDivTag.$divClassBox.$promptSpan2.$submitBtn.$closingDivTag.$closingDivTag;
					}

				// Form buttons for test questions other than first or last.
				} else {
					// if this question is last unanswered question
					if ($unanswered == 1) {
						$page .= 
							$js_changeBtnColor.$js_classSubmit.$hiddenInput.$divClassButtons.$prevBtn.$nextBtn.$closingDivTag.$closingDivTag.$divClassBox.$promptSpan1.$submitBtn.$closingDivTag.$closingDivTag;
					} else { // if there are other unanswered questions
						$page .= 
							$js_changeBtnColor.$js_classNext.$hiddenInput.$divClassButtons.$prevBtn.$nextBtn.$closingDivTag.$closingDivTag.$divClassBox.$promptSpan2.$submitBtn.$closingDivTag.$closingDivTag;
					}
				}

			/**
			 * Section for when all questions have been answered.
			 *
			 * Hidden input to retain student's session.
			 */
			} else {

				// Form buttons for first test question
				if ($_SESSION['count'] < 1) {
					$page .= 
						$js_submitBtnColor.$hiddenInput.$divClassButtons.$disabledPrevBtn.$nextBtn.$closingDivTag.$closingDivTag.$divClassBox.$promptSpanAll.$submitBtn.$closingDivTag.$closingDivTag;

				// Form buttons for last test question
				} else if ($_SESSION['count'] == $_SESSION['numberQuestions'] - 1) {
					$page .= 
						$js_submitBtnColor.$hiddenInput.$divClassButtons.$prevBtn.$disabledNextBtn.$closingDivTag.$closingDivTag.$divClassBox.$promptSpanAll.$submitBtn.$closingDivTag.$closingDivTag;

				// Form buttons for test questions other than first or last.
				} else {
					$page .= 
						$js_submitBtnColor.$hiddenInput.$divClassButtons.$prevBtn.$nextBtn.$closingDivTag.$closingDivTag.$divClassBox.$promptSpanAll.$submitBtn.$closingDivTag.$closingDivTag;
				}
			}

		    /**
			 * Section for question selection window.
			 *
			 * Hidden input to retain student's session.
			 */
	    	
		    // Add unanswered questions to array where key equals index.
		    $index = array_keys($_SESSION['arrQuestion'], 0); // The index compares to $_SESSION['count']

		    $indexFlag = array_keys($_SESSION['flagged'], 1);

// index array has question number as value
// arrQuestion array has question number as key
echo "array_diff: "; print_r(array_diff(array_keys($_SESSION['arrQuestion']), array_values($index)));

/*test*///echo "<br><br>Numbers Array: ";
/*test*///print_r($_SESSION['numbers']);
/*test*/echo "<br><br>Index Array: ";
/*test*/print_r($index);
/*test*/echo "<br><br>Request: ";
/*test*/print_r($_REQUEST);
/*test*/echo "<br>Request['id']: {$_REQUEST['id']}<br>arrQuestion: ";
/*test*/print_r($_SESSION['arrQuestion']);
/*test*/echo "<br>Count: {$_SESSION['count']}<br><br>Flagged: ";
/*test*/print_r($_SESSION['flagged']);

		    $questCount = 0;
		    $page .= "
		    	<div class='window-wrapper'>
		    		<div class='inner-container'>
		    			<h3 id='aside_questions'>Questions</h3>
		    			<ul id='question_list'>";
		    foreach ($_SESSION['questions'] as $question) {
			    $page .= "<li name='".$_SESSION['questions'][$questCount]['quesNumb']."'><input name='attemptId' type='hidden' value='{$_SESSION['attemptId']}'>
			    	<i class='small-flag";
			    // if previously flagged, change display to inline-block
			    if (in_array($_SESSION['questions'][$questCount]['quesNumb'], $indexFlag)) {
			    	$page .= "'></i>";
			    } else {
			    	$page .= " invisible'></i>";
			    }
		    	// change icon from 'question mark' to 'check mark' if question has been answered
		    	if (in_array($_SESSION['questions'][$questCount]['quesNumb'], $index)) {
			    	$page .= "<i class='fa fa-question-circle-o fa-lg'></i><input type='submit' id='individual' value='Question ".($questCount + 1)."' name='individual' class='icon";
		    	} else {
			    	$page .= "<i class='fa fa-check fa-cg'></i><input type='submit' id='individual' value='Question ".($questCount + 1)."' name='individual' class='icon answered";
		    	}

	    		if ($_SESSION['questions'][$questCount]['quesNumb'] == $_SESSION['questions'][$_SESSION['count']]['quesNumb']) {
	    			// make 'current question' bold
	    			$page .= " currentQuestion' aria-hidden='true' onclick='needToConfirm=false;'/></li>";
		    	} else {
		    		$page .= "' aria-hidden='true' onclick='needToConfirm=false;'/></li>";
		    	}
			    $questCount++;
		    }
		    $page .= "
				    			</ul>
				    		</div>
					    </div>
			    	</form>
		    	</div>
		    	<script type='text/javascript'>
		    	// script to scroll current question to center of scrolling window
		    		if ($(window).width() < 1133) { // number represents pixels
			    		// pixel spacing from top of scrolling window (48px)
		    			var posArray = $('.currentQuestion').position().top - 48; 
						$('#question_list').scrollTop(posArray);
					} else {
		    			var posArray = $('.currentQuestion').position().top - 96; 
						$('#question_list').scrollTop(posArray);
					}

					// click function for flagging questions on the front-end
					$('.flag-question').click(function() {
						if ($('.currentQuestion').siblings('.small-flag').hasClass('invisible')) {
							$('.currentQuestion').siblings('.small-flag')
							.removeClass('invisible');
						} else {
							$('.currentQuestion').siblings('.small-flag')
							.addClass('invisible');
						}
					});
		    	</script>"; // this script scrolls to the 'current question' in question selection window
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