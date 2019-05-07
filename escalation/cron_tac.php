<?php
date_default_timezone_set("Asia/Beirut");

// Importing database class 
require __DIR__ . '/db.php';
$db = new Db();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Importing mail class
require __DIR__ . "/vendor/autoload.php";

// Fetching all the tickets that are open
// Closed means status_id = 3, so checking for anything different than 3
// Also only return if the ticket is TAC required and the email for TAC required is not already sent


// MAKE SURE ID 40 IS TAC REQUIRED
$openTickets_query = $db->dbh->query("
    SELECT `ost_ticket`.*, `ost_ticket__cdata`.*, `ost_ticket_priority`.`priority_desc`, `ost_form_entry`.`id` as `form_id`
    FROM `ost_ticket`
    LEFT JOIN `ost_ticket__cdata` ON `ost_ticket__cdata`.`ticket_id` = `ost_ticket`.`ticket_id`
    LEFT JOIN `ost_form_entry` ON `ost_form_entry`.`object_id` = `ost_ticket`.`ticket_id` AND `ost_form_entry`.`object_type` = 'T'
    LEFT JOIN `ost_form_entry_values` ON `ost_form_entry_values`.`entry_id` = `ost_form_entry`.`id` AND `field_id` = 40
    LEFT JOIN `ost_ticket_priority` ON `ost_ticket__cdata`.`priority` = `ost_ticket_priority`.`priority_id`
    WHERE `status_id` != 3 AND `status_id` != 2  AND (`ost_form_entry_values`.`value` = 1)");
$openTickets_query->execute();
$openTickets_results = $openTickets_query->fetchAll(PDO::FETCH_ASSOC);

// Looping through open tickets
foreach($openTickets_results as $openTicket) {
    // Checking if the email has already been sent.
    $reminderSent_query = $db->dbh->query("
        SELECT `ost_form_entry_values`.*
        FROM `ost_ticket`
        LEFT JOIN `ost_form_entry` ON `ost_form_entry`.`object_id` = `ost_ticket`.`ticket_id` AND `ost_form_entry`.`object_type` = 'T'
        LEFT JOIN `ost_form_entry_values` ON `ost_form_entry_values`.`entry_id` = `ost_form_entry`.`id`
        WHERE `ost_form_entry_values`.`field_id` = 49 AND `ost_ticket`.`ticket_id` = {$openTicket['ticket_id']}
    ");
    $reminderSent_query->execute();
    $reminderSent_results = $reminderSent_query->fetchAll(PDO::FETCH_ASSOC);

    if(!empty($reminderSent_results) && $reminderSent_results[0]['value'] == 1) continue;

    echo "<pre>";
    print_r($openTicket);
    
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

    if(empty($reminderSent_results)) {
        $db->dbh->query("
            INSERT INTO `ost_form_entry_values`
            SET `entry_id` = {$openTicket['form_id']}, `field_id` = 49, `value` = '1'
        ");  
    } else {
        $db->dbh->query("
            UPDATE `ost_form_entry_values`
            SET `value` = 1
            WHERE `ost_form_entry_values`.`entry_id` = {$openTicket['form_id']} AND `ost_form_entry_values`.`field_id` = 49
        ");
    }
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

        $tpl = file_get_contents(__DIR__ . "/templates/tac.tpl");
        
        foreach($_ticketVariables as $variable => $value) {
            $tpl = str_replace($variable, $value, $tpl);
        }

        $tpl = nl2br($tpl);


        $mail->Subject = "New ticket #{$_ticketVariables['{_ticketNumber}']} is open with TAC Required.";
        $mail->Body    = $tpl;
        $mail->AltBody = $tpl;
            
        $mail->addAddress("andy@yllw.com");

        $mail->send();
        echo "Message has been sent for Ticket ID #{$_ticketVariables['{_ticketNumber}']}";
    } catch (Exception $e) {
        echo "Message could not be sent for Ticket ID #{$_ticketVariables['{_ticketNumber}']} Mailer Error: ", $mail->ErrorInfo;
    }

}