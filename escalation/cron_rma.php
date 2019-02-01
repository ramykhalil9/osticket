<?php

// Importing database class 
require __DIR__ . '/db.php';
$db = new Db();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Importing mail class
require __DIR__ . "/vendor/autoload.php";

// Fetching all the tickets that are open
// Closed means status_id = 3, so checking for anything different than 3
// Also only return if the ticket is RMA required and the email for RMA required is not already sent
$openTickets_query = $db->dbh->query("
    SELECT `ost_ticket`.*, `ost_ticket__cdata`.*, `ost_ticket_priority`.`priority_desc`
    FROM `ost_ticket`
    LEFT JOIN `ost_ticket__cdata` ON `ost_ticket__cdata`.`ticket_id` = `ost_ticket`.`ticket_id`
    LEFT JOIN `ost_ticket_priority` ON `ost_ticket__cdata`.`priority` = `ost_ticket_priority`.`priority_id`
    WHERE `status_id` != 3 AND `status_id` != 2 AND (`RMARequiredEmailSent` != 1 OR `RMARequiredEmailSent` IS NULL) AND (`RMARequired` = 1 OR `RMARequired` IS NULL)");
$openTickets_query->execute();
$openTickets_results = $openTickets_query->fetchAll(PDO::FETCH_ASSOC);

// Looping through open tickets
foreach($openTickets_results as $openTicket) {
    
    // Fetch ticket owner
    $user_id = $openTicket['user_id'];
    $user_query = $db->dbh->prepare("SELECT * FROM `ost_user` WHERE `id` = :id");
    $user_query->bindParam(":id", $user_id);
    $user_query->execute();
    $user_result = $user_query->fetchAll(PDO::FETCH_ASSOC);
    $user = $user_result[0];
    
    // Fetch Staff Assigned
    $staff_id = $openTicket['staff_id'];
    $staff_query = $db->dbh->prepare("SELECT * FROM `ost_staff` WHERE `staff_id` = :id");
    $staff_query->bindParam(":id", $staff_id);
    $staff_query->execute();
    $staff_result = $staff_query->fetchAll(PDO::FETCH_ASSOC);
    $staff = !empty($staff_result) ? $staff_result[0] : array('firstname' => 'Not', 'lastname' => 'Assigned');

    // Template Variables
    $_emailVariables = array(
        '{_userName}' => $user['name'],
        '{_ticketNumber}' => $openTicket['number'],
        '{_ticketID}' => $openTicket['ticket_id'],
        '{_ticketSubject}' => $openTicket['subject'],
        '{_ticketPriority}' => $openTicket['priority_desc'],
        '{_assignedName}' => $staff['firstname'] . ' ' . $staff['lastname'],
    );

    sendMail($_emailVariables);
    $db->updateValue(array('RMARequiredEmailSent' => 1), array('ticket_id' => $openTicket['ticket_id']), 'ost_ticket__cdata');
    die;
}

function sendMail($_ticketVariables) {
    $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
    try {
        //Server settings
        $mail->SMTPDebug = 2;                                 // Enable verbose debug output
        $mail->isSMTP();                                    // Set mailer to use SMTP
        $mail->Host = 'lbfcmail.bmbgroup.com';  // Specify main and backup SMTP servers
        $mail->Port = 25;                                    // TCP port to connect to
        $mail->SMTPSecure = false;
        $mail->SMTPAutoTLS = false;

        //Recipients
        $mail->setFrom('tac@bmbgroup.com');
        $mail->isHTML(true);                       

        $tpl = file_get_contents(__DIR__ . "/templates/rma.tpl");
        
        foreach($_ticketVariables as $variable => $value) {
            $tpl = str_replace($variable, $value, $tpl);
        }

        $tpl = nl2br($tpl);


        $mail->Subject = "New ticket #{$_ticketVariables['{_ticketNumber}']} is open with RMA Required.";
        $mail->Body    = $tpl;
        $mail->AltBody = $tpl;
            
        $mail->addAddress("andy@yllw.com");

        $mail->send();
        echo "Message has been sent for Ticket ID #{$_ticketVariables['{_ticketNumber}']}";
    } catch (Exception $e) {
        echo "Message could not be sent for Ticket ID #{$_ticketVariables['{_ticketNumber}']} Mailer Error: ", $mail->ErrorInfo;
    }

}