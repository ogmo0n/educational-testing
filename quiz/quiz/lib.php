<?php
/*
 *  quiz - Quiz: uses an LTI tool provider
 *  Copyright (C) 2016  Martin Carruth
 *
 *
 *  Contact: mcarruth@ecpi.edu
 *
 *  Version history:
*/ 

/*
 * This page provides general functions to support the application.
//  */
// session_unset();
  ini_set('session.gc_maxlifetime', 8*60*60); //sets the sessin length to 4 hours
  require_once('db.php');
  //require_once ('/var/www/lib/mail/class.email.php'); // for linux
  require_once('class.email.php');

###  Uncomment the next line to log error messages
//  error_reporting(E_ALL);

###
###  Initialise application session and database connection
###
  function init(&$db, $checkSession = NULL) {

    $ok = TRUE;

// Set timezone
    if (!ini_get('date.timezone')) {
      date_default_timezone_set('America/New_York');
    }

// Set session cookie path
    ini_set('session.cookie_path', getAppPath());

// Open session
    session_name(SESSION_NAME);
    session_start();

    if (!is_null($checkSession) && $checkSession) {
      $ok = isset($_SESSION['consumer_key']) && isset($_SESSION['resource_id']) && isset($_SESSION['user_consumer_key']) &&
            isset($_SESSION['user_id']) && isset($_SESSION['isStudent']);
    }

    if (!$ok) {
      $_SESSION['error_message'] = 'Unable to open session.';
    } else {
// Open database connection
      $db = open_db(!$checkSession);
      $ok = $db !== FALSE;
      if (!$ok) {
        if (!is_null($checkSession) && $checkSession) {
// Display a more user-friendly error message to LTI users
          $_SESSION['error_message'] = 'Unable to open database.';
        }
      } else if (!is_null($checkSession) && !$checkSession) {
// Create database tables (if needed)
        $ok = init_db($db);  // assumes a MySQL/SQLite database is being used
        if (!$ok) {
          $_SESSION['error_message'] = 'Unable to initialise database.';
        }
      }
    }

    return $ok;

  }

###
### 
###
 function getQuizId($db, $course) {
    $sql = <<< EOD
    SELECT max(quizId) as 'quizId'
    FROM quiz_version
    WHERE (course_code = :course_code) AND (start_date <= :start_date)
EOD;

    $query = $db->prepare($sql);
    $query->bindValue('course_code', $course, PDO::PARAM_STR);
    $query->bindValue('start_date', date('Y-m-d'), PDO::PARAM_STR);
    $query->setFetchMode(PDO::FETCH_ASSOC);
    $query->execute();
    
    $row = $query->fetch(PDO::FETCH_ASSOC);
      if ($row !== FALSE) {
        $testId = $row['quizId'];
    }
    return $testId;
 }
 
 function getQuizVersion($db, $quizId){
    $sql = <<< EOD
    SELECT `version`
    FROM quiz_version
    WHERE quizId = :quizId
EOD;

    $query = $db->prepare($sql);
    $query->bindValue('quizId', $quizId, PDO::PARAM_INT);
    $query->setFetchMode(PDO::FETCH_ASSOC);
    $query->execute();
    
    $row = $query->fetch(PDO::FETCH_ASSOC);
      if ($row !== FALSE) {
        $version = $row['version'];
    }
    return $version;
     
 }
 
###
###  get questions for the quiz
###
 
 function getQuestions($db, $course, $quizId){
    $sql = <<< EOD
    SELECT q.`quesNumb`, q.`question`, q.`qpic`, q.`qalt`, q.`qtype`, q.`points` FROM `question` q
    JOIN `quiz` a on a.`quesNumb` =  q.`quesNumb`
    WHERE (`course_code` = :course_code) and (a.`quizId` = :quizId)
EOD;

    $query = $db->prepare($sql);
    $query->bindValue('course_code', $course, PDO::PARAM_STR);
    $query->bindValue('quizId', $quizId, PDO::PARAM_INT);
    $query->setFetchMode(PDO::FETCH_ASSOC);
    $query->execute();
    
    $row = $query->fetchall();
      if ($row !== FALSE) {
        $questions = $row;
    }
    //print_r($questions);
    return $questions;
 }
 
###
###  Get the answers for a question
###
 function getAnswers($db, $quesNumb){
    $sql = <<< EOD
    SELECT `answerId`, `quesNumb`, `answer`, `fraction` 
    FROM `question_answers` 
    WHERE (quesNumb = :quesNumb)
EOD;

    $query = $db->prepare($sql);
    $query->setFetchMode(PDO::FETCH_ASSOC);
    $query->bindValue('quesNumb', $quesNumb, PDO::PARAM_INT);
    $query->execute();
    
    $row = $query->fetchall();
      if ($row !== FALSE) {
        $answers = $row;
    }
    return $answers;
 }
 
###
###  Get student answers
###

function getStudentAnswers($db, $attemptId){
    $sql = <<< EOD
    SELECT  `quesNumb`, `answer` 
    FROM `quiz_attempt_answers` 
    WHERE (attempt = :attemptId)
EOD;
    $query = $db->prepare($sql);
    $query->setFetchMode(PDO::FETCH_ASSOC);
    $query->bindValue('attemptId', $attemptId, PDO::PARAM_INT);
    $query->execute();
    
    $result = $query->fetchall();
      if ($result !== FALSE) {
         foreach($result as $row){
             $student_answers[$row['quesNumb']] = $row['answer'];
         } 
    }
    return $student_answers;

    
}
 
 
###
###  record starting quiz
###
function recordQuizStart($db, $quizId, $version, $type, $courseCode, $username, $firstName, $lastName, $courseId, $consumer_key, $resource_id, $user_id, $user_sis_id) {
    $sql = <<< EOD
     INSERT INTO quiz_attempt
     (`quizId`, `version`, `course_code`, `username`, `first_name`, `last_name`, `course_id`, `type`, `quiz_start`, `consumer_key`, `resource_id`, `user_id`, `user_sis_id`)
     VALUES (:quizId, :version, :course_code, :username, :first_name, :last_name,  :course_id, :type, :quiz_start, :consumer_key, :resource_id, :user_id, :user_sis_id)
EOD;
    
    $query = $db->prepare($sql);
    $query->bindValue('quizId', $quizId, PDO::PARAM_INT);
    $query->bindValue('version', $version, PDO::PARAM_INT);
    $query->bindValue('course_code', $courseCode, PDO::PARAM_STR);
    $query->bindValue('username', $username, PDO::PARAM_STR);
    $query->bindValue('first_name', $firstName, PDO::PARAM_STR);
    $query->bindValue('last_name', $lastName, PDO::PARAM_STR);
    $query->bindValue('type', $type, PDO::PARAM_STR);
    $query->bindValue('course_id', $courseId, PDO::PARAM_STR);
    $query->bindValue('user_id', $user_id, PDO::PARAM_STR);
    $query->bindValue('consumer_key', $consumer_key, PDO::PARAM_STR);
    $query->bindValue('resource_id', $resource_id, PDO::PARAM_STR);
    $query->bindValue('quiz_start', date('Y-m-d H:i:s'), PDO::PARAM_STR);
    $query->bindValue('user_sis_id', $user_sis_id, PDO::PARAM_STR);
    $query->execute();

    $attemptId = $db->lastInsertId(); 
    if ($attemptId == 0 || !isset($attemptId)){ 
        $attemptId = 0;
        $count = 0;
        $errors = "errors.log";
        // $errors = "/var/log/PrePost/errors.log";
        while($attemptId == 0 && $count < 5){
            $attemptId = getAttemptIdQuizStart($db, $username, $type, $courseId, $consumer_key, $resource_id );
            // changed path for errors.log from /var/log/PrePost/errors.log
            // see changes.txt for other occurrences 
            error_log("(lib.php-recordQuizStart:1 ERROR-lastInsertId) attemptId:".$attemptId." ". date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." courseCode=".$_SESSION['courseCode']." \n", 3, "errors.log");
            $count++;
        }
    }
    return $attemptId;
    
}

###
### determine attemptId after insert  lastInserId() does not always provide a response
###
function getAttemptIdQuizStart($db, $username, $type, $courseId, $consumer_key, $resource_id ){
    $sql = <<< EOD
     select `attemptId` from quiz_attempt where (username = :username) and (course_id = :course_id) 
         and (type = :type) and (consumer_key = :consumer_key) and (resource_id = :resource_id)
EOD;
    $query = $db->prepare($sql);
    $query->bindValue('username', $username, PDO::PARAM_STR);
    $query->bindValue('type', $type, PDO::PARAM_STR);
    $query->bindValue('course_id', $courseId, PDO::PARAM_STR);
    $query->bindValue('consumer_key', $consumer_key, PDO::PARAM_STR);
    $query->bindValue('resource_id', $resource_id, PDO::PARAM_STR);
    
    
    try {
       $query->execute();
       $row = $query->fetch(PDO::FETCH_ASSOC);
       $attemptId = $row['attemptId'];
    } 
    catch(PDOException $ex) {
        error_log("(lib.php-getAttemptIdQuizStart:3 ERROR) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." attemptId=".$attemptId." courseCode=".$_SESSION['courseCode']." PDO error message:".$ex->getMessage()." \n", 3, "errors.log");
        $attemptId = 0;
    }
    return $attemptId;
}


###
###  log delete attempt
###
function logQuizAttemptDeletion($db, $attemptId, $facultyUsername, $version, $courseCode, $username, $firstName, $lastName, $courseId, 
        $type, $quiz_start, $quiz_stop, $grade, $consumer_key, $resource_id, $user_id) {
    $sql = <<< EOD
     INSERT INTO quiz_delete
     (`attemptID`, `faculty_username`, `version`, `course_code`, `username`, `first_name`, `last_name`, `course_id`, `type`, `quiz_start`, `quiz_stop`, `grade`, `consumer_key`, `resource_id`, user_id)
     VALUES (:attemptID, :faculty_username, :version, :course_code, :username, :first_name, :last_name,  :course_id, :type, :quiz_start, :quiz_stop, :grade, :consumer_key, :resource_id, :user_id)
EOD;
    
    $query = $db->prepare($sql);
    $query->bindValue('attemptID', $attemptId, PDO::PARAM_INT);
    $query->bindValue('faculty_username', $facultyUsername, PDO::PARAM_STR);
    $query->bindValue('version', $version, PDO::PARAM_INT);
    $query->bindValue('course_code', $courseCode, PDO::PARAM_STR);
    $query->bindValue('username', $username, PDO::PARAM_STR);
    $query->bindValue('first_name', $firstName, PDO::PARAM_STR);
    $query->bindValue('last_name', $lastName, PDO::PARAM_STR);
    $query->bindValue('course_id', $courseId, PDO::PARAM_STR);
    $query->bindValue('type', $type, PDO::PARAM_STR);
    $query->bindValue('quiz_start', $quiz_start, PDO::PARAM_STR);
    $query->bindValue('quiz_stop', $quiz_stop, PDO::PARAM_STR);
    $query->bindValue('grade', $grade, PDO::PARAM_STR);
    $query->bindValue('user_id', $user_id, PDO::PARAM_STR);
    $query->bindValue('consumer_key', $consumer_key, PDO::PARAM_STR);
    $query->bindValue('resource_id', $resource_id, PDO::PARAM_STR);
    
    $query->execute();

    return $db->lastInsertId(); 
}


###
###  record completion time for quiz
###
function recordQuizCompletion($db, $attemptId) {
    $sql = <<< EOD
     UPDATE quiz_attempt          
     SET quiz_stop = :quiz_stop
     WHERE (attemptId = :attemptId)
EOD;
    
    $query = $db->prepare($sql);
    $query->bindValue('attemptId', $attemptId, PDO::PARAM_INT);
    $query->bindValue('quiz_stop', date('Y-m-d H:i:s'), PDO::PARAM_STR);
    try {
       $result = $query->execute();
       if($result ===FALSE){
           //query failed - means that the updated did not happen
          error_log("(lib.php-recordQuizCompletion:2 ERROR) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." attemptId=".$attemptId." courseCode=".$_SESSION['courseCode']." error message: Update failed \n", 3, "errors.log"); 
       }
    } 
    catch(PDOException $ex) {
        error_log("(lib.php-recordQuizCompletion:1 ERROR) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." attemptId=".$attemptId." courseCode=".$_SESSION['courseCode']." PDO error message:".$ex->getMessage()." \n", 3, "errors.log");
    }

}

###
###  determine value for quiz answer 
###
function getAnswerValue($db, $answerId) {
    $sql = <<< EOD
    SELECT `fraction` 
    FROM `question_answers` 
    WHERE (answerId = :answerId)
EOD;

    $query = $db->prepare($sql);
    $query->setFetchMode(PDO::FETCH_ASSOC);
    $query->bindValue('answerId', $answerId, PDO::PARAM_INT);
    $query->execute();
    
    $row = $query->fetchall();
    foreach($row as $key => $value){
        return $value['fraction'];
    }
}


###
###  record the quiz answers inputted by the student 
###
function recordQuizAnswers($db, $attemptId, $quesNumb, $answer, $points){
    $sql = <<< EOD
    INSERT INTO quiz_attempt_answers
    (`attempt`, `quesNumb`, `answer`, `grade`)
    VALUES 
    (:attempt, :quesNumb, :answer, :points)
EOD;
    
    $query = $db->prepare($sql);
    $query->bindValue('attempt', $attemptId, PDO::PARAM_INT);
    $query->bindValue('quesNumb', $quesNumb, PDO::PARAM_INT);
    $query->bindValue('answer', $answer, PDO::PARAM_INT);
    $query->bindValue('points', $points, PDO::PARAM_INT);
    try {
        $result = $query->execute();
        if($result ===FALSE){
            //query failed - means that the updated did not happen
           error_log("(lib.php-recordQuizAnswers:1 ERROR) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']."  attemptId=".$attemptId." courseCode=".$_SESSION['courseCode']." quesNumb:".$quesNumb." answer:".$answer." points:".$points."Error message:Insert failed \n", 3, "errors.log"); 
        }
    } 
    catch(PDOException $ex) {
        error_log("(lib.php-recordQuizAnswers:2 ERROR) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." attemptId=".$attemptId." courseCode=".$_SESSION['courseCode']." PDO error message:".$ex->getMessage()." \n", 3, "errors.log");
    }
}

###
###  determine the quiz grade 
###
function sumQuizScores($db, $attemptId) {
    $sql = <<< EOD
    SELECT sum(`grade`) as grade
    FROM quiz_attempt_answers
    WHERE (attempt = :attemptId)
EOD;
    $query = $db->prepare($sql);
    $query->bindValue('attemptId', $attemptId, PDO::PARAM_INT);
    $query->execute();
    $row = $query->fetchall();
    foreach($row as $key => $value){
        return $value['grade'];
    }
}

###
###  update the quiz grade 
###
function updateQuizGrade($db, $grade, $attemptId) {
    $request='';
        foreach ($_REQUEST as $keys => $values) {
            $request .= "{$keys} = {$values} <br />";
        }
        $session='';
        foreach ($_SESSION as $keys => $values) {
            $session .= "{$keys} = {$values} <br />";
        }
        $server='';
        foreach ($_SERVER as $keys => $values) {
            $server .= "{$keys} = {$values} <br />";
        }
    if ($grade == 0 || !isset($grade)){
        $subject = 'Zero score for '.$_SESSION['username'].' '.$_SESSION['courseCode']. ' '.$_SESSION['course_SISID'] ;
        $message = "Zero passed to QuizGrade database update<br />Session:".$session ."<br /><br />QuizGrade: {$grade} attemptID: {$attemptId}"
        . "<br /><br />Server: {$server}<br /><br />Request: {$request}";
        $to = $email; 
        Email::mail($subject, $message, $to);
    } 
    
    error_log("(lib.php-updateQuizGrade:1) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." grade=".$grade." attemptId=".$attemptId." courseCode=".$_SESSION['courseCode']." \n", 3, "/var/log/PrePost/errors.log"); 
    $sql = <<< EOD
     UPDATE quiz_attempt          
     SET grade = :grade
     WHERE (attemptId = :attemptId)
EOD;
    
    $query = $db->prepare($sql);
    $query->bindValue('attemptId', $attemptId, PDO::PARAM_INT);
    $query->bindValue('grade', $grade, PDO::PARAM_INT);
    
    try {
       $result = $query->execute();
       if($result ===FALSE){
            error_log("(lib.php-updateQuizGrade:3 ERROR) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." attemptId=".$attemptId." courseCode=".$_SESSION['courseCode']." Error message: Update of grade failed \n", 3, "errors.log");
       }
    } catch(PDOException $ex) {
        error_log("(lib.php-updateQuizGrade:2 ERROR) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." attemptId=".$attemptId." courseCode=".$_SESSION['courseCode']." PDO error message:".$ex->getMessage()." \n", 3, "errors.log");
    }
    
}

###
###  get quiz grade for student
###
function getQuizGrade($db, $type, $course_id, $username, $consumer_key, $resource_id) {
    $sql = <<< EOD
    SELECT `grade` 
    FROM quiz_attempt
    WHERE (type = :type) AND (course_id = :course_id) AND (username = :username)
    AND (consumer_key = :consumer_key) AND (resource_id = :resource_id)

EOD;
    
    $query = $db->prepare($sql);
    $query->setFetchMode(PDO::FETCH_ASSOC);
    $query->bindValue('type', $type, PDO::PARAM_INT);
    $query->bindValue('course_id', $course_id, PDO::PARAM_STR);
    $query->bindValue('username', $username, PDO::PARAM_STR);
    $query->bindValue('consumer_key', $consumer_key, PDO::PARAM_STR);
    $query->bindValue('resource_id', $resource_id, PDO::PARAM_STR);
    $query->execute();
    $row = $query->fetchall();
    foreach($row as $key => $value){
        if ($row !== FALSE) {
            return $value['grade'];
        } else {
           error_log("(lib.php-getQuizGrade:1 ERROR) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." attemptId=".$attemptId." courseCode=".$_SESSION['courseCode']." Error message: Unable to getquizGrade \n", 3, "errors.log"); 
        }
    }
}

###
###  get quiz grade for gradebook
###
function getQuizGradeUser($db, $user_id, $consumer_key, $resource_id) {
    $sql = <<< EOD
    SELECT `grade` 
    FROM quiz_attempt
    WHERE (user_id = :user_id) AND (consumer_key = :consumer_key) 
    AND (resource_id = :resource_id)

EOD;
    
    $query = $db->prepare($sql);
    $query->setFetchMode(PDO::FETCH_ASSOC);
    $query->bindValue('user_id', $user_id, PDO::PARAM_STR);
    $query->bindValue('consumer_key', $consumer_key, PDO::PARAM_STR);
    $query->bindValue('resource_id', $resource_id, PDO::PARAM_STR);
    $query->execute();
    $row = $query->fetchall();
    foreach($row as $key => $value){
        if ($row !== FALSE) {
            return $value['grade'];
        } else {
           error_log("(lib.php-getQuizGradeUser:1 ERROR) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." attemptId=".$attemptId." courseCode=".$_SESSION['courseCode']." Error message: Unable to getquizGrade \n", 3, "errors.log"); 
        }
    }
}

###
###  get quiz attempt data baed on attemptID for deletion log
###
function getQuizAttempt($db, $attemptId) {
    $sql = <<< EOD
    SELECT `attemptId`, `version`, `course_code`, `type`, `username`, `first_name`, `last_name`, `course_id`, `quiz_start`, `quiz_stop`, `grade`, `consumer_key`, `user_id`, `resource_id`
    FROM quiz_attempt
    WHERE (attemptID = :attemptID) 

EOD;
    
    $query = $db->prepare($sql);
    $query->setFetchMode(PDO::FETCH_ASSOC);
    $query->bindValue('attemptID', $attemptId, PDO::PARAM_INT);
    $query->execute();
    $row = $query->fetch();
    if ($row !== FALSE) {
        return $row;
    } else {
        error_log("(lib.php-getQuizAttempt:1 ERROR) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." attemptId=".$attemptId." courseCode=".$_SESSION['courseCode']." Error message: Unable to getQuizAttempt \n", 3, "errors.log"); 
    }
}

###
###  deletes the quiz attempt 
###
function deleteQuizAttemptId($db, $attemptId) {
    $sql = <<< EOD
    DELETE FROM quiz_attempt
    WHERE (attemptID = :attemptID) 
EOD;
    
    $query = $db->prepare($sql);
//    $query->setFetchMode(PDO::FETCH_ASSOC);
    $query->bindValue('attemptID', $attemptId, PDO::PARAM_INT);
    $query->execute();
//    $row = $query->fetch();
//    return $row;
}

function deleteQuizAttempt($db, $attemptId, $facultyUsername){
    $attempt = getQuizAttempt($db, $attemptId);
    $deleteID = logQuizAttemptDeletion($db, $attempt['attemptId'], $facultyUsername, $attempt['version'], $attempt['course_code'], 
            $attempt['username'], $attempt['first_name'], $attempt['last_name'], $attempt['course_id'], $attempt['type'], $attempt['quiz_start'], 
            $attempt['quiz_stop'], $attempt['grade'], $attempt['consumer_key'], $attempt['resource_id'], $attempt['user_id']);
    deleteQuizAttemptId($db, $attemptId);
    return $deleteID;
}



###
###  get quiz grades for class 
###
function getQuizGrades($db, $type, $course_id, $consumer_key, $resource_id) {
    $sql = <<< EOD
    SELECT `attemptId`, `username`, `first_name`, `last_name`, `quiz_start`, `quiz_stop`,`grade` 
    FROM quiz_attempt
    WHERE (type = :type) AND (course_id = :course_id) 
    AND (consumer_key = :consumer_key) AND (resource_id = :resource_id)

EOD;
    
    $query = $db->prepare($sql);
    $query->setFetchMode(PDO::FETCH_ASSOC);
    $query->bindValue('type', $type, PDO::PARAM_INT);
    $query->bindValue('course_id', $course_id, PDO::PARAM_STR);
    $query->bindValue('consumer_key', $consumer_key, PDO::PARAM_STR);
    $query->bindValue('resource_id', $resource_id, PDO::PARAM_STR);
    $query->execute();
    $result = $query->fetchall();
    $grade = array();
    foreach($result as $row){
        $grade[]=$row;
    }
    return $grade;
}

###
###  get quiz data for an attemptID 
###
function getQuizDataAttemptId($db, $attemptId) {
    $sql = <<< EOD
    SELECT `username`, `first_name`, `last_name`, `quiz_start`, `quiz_stop`,`grade`, `type`
    FROM quiz_attempt
    WHERE (attemptId = :attemptId) 

EOD;
    
    $query = $db->prepare($sql);
    $query->setFetchMode(PDO::FETCH_ASSOC);
    $query->bindValue('attemptId', $attemptId, PDO::PARAM_INT);
    $query->execute();
    $result = $query->fetch();
    return $result;
}


###
###  Update the gradebook quiz grade
###
  function updateGradebook($db, $user_consumer_key = NULL, $user_user_id = NULL) {
      $request='';
        foreach ($_REQUEST as $keys => $values) {
            $request .= "{$keys} = {$values} <br />";
        }
        $session='';
        foreach ($_SESSION as $keys => $values) {
            $session .= "{$keys} = {$values} <br />";
        }
        $server='';
        foreach ($_SERVER as $keys => $values) {
            $server .= "{$keys} = {$values} <br />";
        }
    error_log("(lib.php-updateGradebook: Entry) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." courseCode=".$_SESSION['courseCode']." user_user_id=".$user_user_id."\n", 3, "errors.log");

    $data_connector = LTI_Data_Connector::getDataConnector(DB_TABLENAME_PREFIX, $db);
    //identified 
    $consumer = new LTI_Tool_Consumer($_SESSION['consumer_key'], $data_connector);
    $resource_link = new LTI_Resource_Link($consumer, $_SESSION['resource_id']);
    
    //get an array of users that are using this resource
    $users = $resource_link->getUserResultSourcedIDs();
    //return $users;
    foreach ($users as $user) {
      $consumer_key = $user->getResourceLink()->getKey();
      $user_id = $user->getId();
      $grade = getQuizGradeUser($db, $user_id, $_SESSION['consumer_key'], $_SESSION['resource_id']);
      //$update = is_null($user_consumer_key) || is_null($user_user_id) || (($user_consumer_key == $consumer_key) && ($user_user_id == $user_id));
      $update = ($user_consumer_key == $consumer_key && $grade>0);
      error_log("(lib.php-updateGradebook:1) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." update=".$update
              . " grade=".$grade." user_id=".$user_id." user_user_id=".$user_user_id." consumer_key=".$consumer_key." user_consumer_key=".$user_consumer_key." courseCode=".$_SESSION['courseCode']."\n", 3, "errors.log");
      if ($update) {
          error_log("(lib.php-updateGradebook:2) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." grade=".$grade." userid=".$user_id." consumer_key=".$consumer_key." courseCode=".$_SESSION['courseCode']."\n", 3, "errors.log");
          if ($grade == 0 && isset($grade) ){
            $subject = 'Zero score for '.$_SESSION['username'].' '.$_SESSION['courseCode']. ' '.$_SESSION['course_SISID'] ;
            $message = "QuizGrade LMSGradebook update <br />Session:".$session ."<br /><br />UserId: {$user_id}<br /><br />QuizGrade: {$grade} "
            . "<br /><br />Server: {$server}<br /><br />Request: {$request}";
            $to = $email; 
            Email::mail($subject, $message, $to);
            } elseif (!isset($grade)) {
                $subject = 'No Record for '.$_SESSION['username'].' '.$_SESSION['courseCode']. ' '.$_SESSION['course_SISID'] ;
            $message = "No record fonnd when attmpted to update QuizGrade in LMSGradebook  <br />><br />Yes the teacher probably deleted the attempt before the student finished the test<br />Session:".$session ."<br /><br />UserId: {$user_id}<br /><br />QuizGrade: {$grade} "
            . "<br /><br />Server: {$server}<br /><br />Request: {$request}";
            $to = $email;
            Email::mail($subject, $message, $to);
            } else {
                $lti_outcome = new LTI_Outcome(NULL, $grade/100);
                $response = $resource_link->doOutcomesService(LTI_Resource_Link::EXT_WRITE, $lti_outcome, $user);
                error_log("(lib.php-updateGradebook:3) ".date('Y-m-d:H.i.s')." username=".$_SESSION['username']." type=".$_SESSION['type']." grade=".$grade." userid=".$user_id." consumer_key=".$consumer_key." courseCode=".$_SESSION['courseCode']."\n", 3, "errors.log");
            } 
        }
    }
  }

 
###
###  Get the web path to the application
###
  function getAppPath() {

    $root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $dir = str_replace('\\', '/', dirname(__FILE__));

    $path = str_replace($root, '', $dir) . '/';

    return $path;

  }


###
###  Get the application domain URL
###
  function getHost() {

    $scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")
              ? 'http'
              : 'https';
    $url = $scheme . '://' . $_SERVER['HTTP_HOST'];

    return $url;

  }


###
###  Get the URL to the application
###
  function getAppUrl() {

    $url = getHost() . getAppPath();

    return $url;

  }


###
###  Return a string representation of a float value
###
  function floatToStr($num) {

    $str = sprintf('%f', $num);
    $str = preg_replace('/0*$/', '', $str);
    if (substr($str, -1) == '.') {
      $str = substr($str, 0, -1);
    }

    return $str;

  }


###
###  Return the value of a POST parameter
###
  function postValue($name, $defaultValue = NULL) {

    $value = $defaultValue;
    if (isset($_POST[$name])) {
      $value = $_POST[$name];
    }

    return $value;

  }


/**
 * Returns a string representation of a version 4 GUID, which uses random
 * numbers.There are 6 reserved bits, and the GUIDs have this format:
 *     xxxxxxxx-xxxx-4xxx-[8|9|a|b]xxx-xxxxxxxxxxxx
 * where 'x' is a hexadecimal digit, 0-9a-f.
 *
 * See http://tools.ietf.org/html/rfc4122 for more information.
 *
 * Note: This function is available on all platforms, while the
 * com_create_guid() is only available for Windows.
 *
 * Source: https://github.com/Azure/azure-sdk-for-php/issues/591
 *
 * @return string A new GUID.
 */
  function getGuid() {

    return sprintf('%04x%04x-%04x-%04x-%02x%02x-%04x%04x%04x',
       mt_rand(0, 65535),
       mt_rand(0, 65535),        // 32 bits for "time_low"
       mt_rand(0, 65535),        // 16 bits for "time_mid"
       mt_rand(0, 4096) + 16384, // 16 bits for "time_hi_and_version", with
                                 // the most significant 4 bits being 0100
                                 // to indicate randomly generated version
       mt_rand(0, 64) + 128,     // 8 bits  for "clock_seq_hi", with
                                 // the most significant 2 bits being 10,
                                 // required by version 4 GUIDs.
       mt_rand(0, 256),          // 8 bits  for "clock_seq_low"
       mt_rand(0, 65535),        // 16 bits for "node 0" and "node 1"
       mt_rand(0, 65535),        // 16 bits for "node 2" and "node 3"
       mt_rand(0, 65535)         // 16 bits for "node 4" and "node 5"
      );

  }

?>