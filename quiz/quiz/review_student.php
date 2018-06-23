<?php
 require_once('lib.php');
// Initialise session and database
  $db = NULL;
  $ok = init($db, TRUE);
// Initialise parameters
  $id = 0;

  $testId = getQuizId($db, $_SESSION['courseCode']);
//$testId = $_SESSION['version'];
$questions = getQuestions($db, $_SESSION['courseCode'], $testId);
$numbQuestion = count($questions);
$numbers=range(0,$numbQuestion-1);

$attemptId = $_GET['attemptId'];
//var_dump($_SESSION);
$student_answers = getStudentAnswers($db, $attemptId);
//var_dump($student_answers);

$attemptData = getQuizDataAttemptId($db, $attemptId);

  
  if ($ok) {
  
//var_dump($_SESSION);

//page header
$page = <<< EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="en" xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-language" content="EN" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<meta charset="UTF-8">
<title>{$title}</title>
<script src="js/jquery.min.js" type="text/javascript"></script>
<script src="js/jquery.rateit.min.js" type="text/javascript"></script>
<script type="text/javascript">
    
</script>
<style>
.grade {
    border-collapse: collapse;
    border: 1px solid black;
    padding: 5px;
}
</style>
</head>

<body style="font-family: 'Arial', Helvetica, sans-serif; ">
EOD;

//body

//debug section
//show me the student answers to quetsions
//foreach($student_answers as $key => $value){
//    $page .= <<< EOD
//      {$key} => {$value} <br />        
//EOD;
//}
$type = strtoupper($attemptData['type']);
if($type == 'POST' || $type == 'PRE' ){
    $type .= ' TEST';
} else {
        $type .= ' QUIZ';
}
  $page .= <<< EOD
<p><strong>{$_SESSION['courseCode']} {$type}</strong></p>\n
<strong>Student</strong>: {$attemptData['username']} :: {$attemptData['first_name']} {$attemptData['last_name']}<br />\n
<strong>Quiz Start: </strong>{$attemptData['quiz_start']}<br />
<strong>Quiz Stop: </strong>{$attemptData['quiz_stop']}<br />
<strong>Quiz Grade: </strong>{$attemptData['grade']}<br />
<p>Questions and correct answers are <strong>bold</strong> font<br />
<img src="./images/tick.gif" alt="correct"> - student selected correct answer<br />
       <img src="./images/delete.png" alt="incorrect"> - student selected incorrect answer</p>

<p>All numbers reference the database. The students did not see ABCD selection, they had radio buttons to click.<br />\n
The questions on the student's view wereshuffled</p>
        <table>
EOD;
$image='';
foreach($numbers as $quesNumb){
    if(strlen($questions[$quesNumb]['qpic'])>1){
        $image = '<img src="'.$questions[$quesNumb]['qpic'].'"  alt="'.$questions[$quesNumb]['qalt'].'" /><br />';
    } else {
        $image='';
    }
    $page .= <<< EOD
       <tr><td style="font-weight: bold;">{$questions[$quesNumb]['quesNumb']}. {$questions[$quesNumb]['question']} {$image}</td></tr>\n
EOD;
    $answers = getAnswers($db, $questions[$quesNumb]['quesNumb']);
    $countb=1;
    foreach($answers as $answer){    
        $letter = chr(64+$countb);
        $ans=  html_entity_decode($answer['answer']);
        if($answer['fraction'] > 0){
            if($answer['answerId'] == $student_answers[$questions[$quesNumb]['quesNumb']]){
                $select = '<img src="./images/tick.gif" alt="correct">';
            } else {
                $select = '';
            }
        $page .= <<< EOD
            <tr><td style="font-weight: bold;">{$select}{$letter}. {$ans}</td></tr>\n
EOD;
        } else {
            if($answer['answerId'] == $student_answers[$questions[$quesNumb]['quesNumb']]){
                $select = '<img src="./images/delete.png" alt="incorrect">';
            } else {
                $select = '';
            }

        $page .= <<< EOD
            <tr><td>{$select}{$letter}. {$ans}</td></tr>\n
EOD;
        }
            
            $countb++;
    }
    $page .= <<< EOD
       <tr><td><br /></td></tr>\n
EOD;
    
}
    $page .= <<< EOD
        </table>\n
EOD;



// Page footer
  $page .= <<< EOD
</body>
</html>
EOD;

  }
// Display page
  echo $page;
  
?>
