<?php

/**
 * Description of class: the purpose of this class is to provide automated messages
 * to faculty based on changes in the various Curriculum development & authorization
 * systems
 *
 * @author mcarruth
 */
class Email {
    
    public function __construct(){
    }
    
    public static function mail($subject, $message, $to, $arCcc=null){
        
        $headers   =  array();
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=iso-8859-1";
        $headers[] = "From: Curriculum Operations <{$to}>";
        if(isset($arCcc)){
            $strCc = implode(',', $arCcc);
            $headers[] = "Cc: {$strCc}";
        } else {
            $headers[] = "Cc: <{$to}>";
        }
        $headers[] = "Reply-To: Curriculum Operations <{$to}>";
        $headers[] = "Subject: {$subject}";
        $headers[] = "X-Mailer: PHP/".phpversion();
        
        mail($to, $subject, $message, implode("\r\n", $headers));
    }
}
?>
