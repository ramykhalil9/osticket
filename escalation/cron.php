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
$openTickets_query = $db->dbh->query("
    SELECT `ost_ticket`.*, `ost_ticket__cdata`.*, `ost_ticket_priority`.`priority_desc`
    FROM `ost_ticket`
    LEFT JOIN `ost_ticket__cdata` ON `ost_ticket__cdata`.`ticket_id` = `ost_ticket`.`ticket_id`
    LEFT JOIN `ost_ticket_priority` ON `ost_ticket__cdata`.`priority` = `ost_ticket_priority`.`priority_id` 
    WHERE `status_id` != 3 AND `status_id` != 2");
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

    $staff_id = $openTicket['staff_id'];
    $staff_query = $db->dbh->prepare("SELECT * FROM `ost_staff` WHERE `staff_id` = :id");
    $staff_query->bindParam(":id", $staff_id);
    $staff_query->execute();
    $staff_result = $staff_query->fetchAll(PDO::FETCH_ASSOC);
    $staff = !empty($staff_result) ? $staff_result[0] : array('firstname' => 'Not', 'lastname' => 'Assigned');

    // Stop if user is not part of an organization
    if($user['org_id'] == 0) continue;


    // Fetch organization
    $organization_id = $user['org_id'];
    $organization_query = $db->dbh->prepare("SELECT * FROM `ost_organization` WHERE `id` = :id");
    $organization_query->bindParam(":id", $organization_id);
    $organization_query->execute();
    $organization_result = $organization_query->fetchAll(PDO::FETCH_ASSOC);
    $organization = $organization_result[0];


    // Fetching form entry to check the Organization's SLA
    $ostForm_query = $db->dbh->prepare("
        SELECT `ost_form_entry_values`.* FROM `ost_form_entry` 
        LEFT JOIN `ost_form_entry_values` ON `ost_form_entry_values`.`entry_id` = `ost_form_entry`.`id` 
        WHERE `ost_form_entry`.`object_type` = 'O' AND `ost_form_entry`.`object_id` = :id AND `ost_form_entry_values`.`field_id` = 38");
    $ostForm_query->bindParam(":id", $organization_id);
    $ostForm_query->execute();
    $ostForm_results = $ostForm_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Setting SLA to Organization
    $orgSLA = false;
    switch($ostForm_results[0]['value']) {
        case('{"Bronze":"Bronze"}'):
            $orgSLA = "Bronze";
            break;
        case('{"Silver":"Silver"}'):
            $orgSLA = "Silver";
            break;
        case('{"Gold":"Gold"}'):
            $orgSLA = "Gold";
            break;
    }

    // If no SLA, return
    if(!$orgSLA) continue;

    // Check how long it has been open in hours
    $ticketDate = strtotime($openTicket['created']);
    $nowDate = time();
    $differenceInHours = floor(($nowDate - $ticketDate) / 3600);

    // Template Variables
    $_emailVariables = array(
        '{_userName}' => $user['name'],
        '{_ticketNumber}' => $openTicket['number'],
        '{_ticketID}' => $openTicket['ticket_id'],
        '{_ticketSubject}' => $openTicket['subject'],
        '{_ticketPriority}' => $openTicket['priority_desc'],
        '{_assignedName}' => $staff['firstname'] . ' ' . $staff['lastname'],
    );

    // Getting Ticket SLA ID
    $ticketSLA = $openTicket['sla_id'];

    if($orgSLA == 'Bronze') {
        if($ticketSLA == 1) {
            // If Default SLA            
            
        } else if($ticketSLA == 2) {
            // If Gold SLA

        } else if($ticketSLA == 3) {
            // If Silver SLA

        } else if($ticketSLA == 4) {
            // If Bronze SLA

        } else if($ticketSLA == 5) {
            // If No SLA

        }
    } else if($orgSLA == 'Silver') {
        if($ticketSLA == 1) {
            // If Default SLA

        } else if($ticketSLA == 2) {
            // If Gold SLA

        } else if($ticketSLA == 3) {
            // If Silver SLA

        } else if($ticketSLA == 4) {
            // If Bronze SLA

        } else if($ticketSLA == 5) {
            // If No SLA
            
        }
    } else if($orgSLA == 'Gold') {
        if($ticketSLA == 1) {
            // If Default SLA

        } else if($ticketSLA == 2) {
            // If Gold SLA

            // If no reminder has been sent
            if($openTicket['reminders_sent'] == 0) {
                // If 72 hours have passed
                if($differenceInHours >= 72) {
                    // Send email reminder to TM
                    incrementReminder($db, 1, $openTicket['ticket_id']);
                    sendMail($openTicket['ticket_id'], $differenceInHours, $_emailVariables, true);
                }
            } else {
                // If a reminder has already been sent
                
                // Check how long it's been since the last reminder was sent
                $lastReminderDate = $openTicket['last_reminder_sent'];
                $nowDate = time();
                $lastReminderDifference = floor(($nowDate - $lastReminderDate) / 3600);

                // If only 1 reminder has been sent
                if($openTicket['reminders_sent'] == 1) {
                    // If it's been 1 hour since the last reminder was sent
                    if($lastReminderDifference >= 1) {
                        // Send email to TM, SM
                        incrementReminder($db, 2, $openTicket['ticket_id']);
                        sendMail($openTicket['ticket_id'], $differenceInHours, $_emailVariables, true, true);
                    }
                } 
                
                // If 2 reminders have been sent
                if($openTicket['reminders_sent'] == 2) {
                    // If it's been 2 hours since the last reminder was sent
                    if($lastReminderDifference >= 2) {
                        // Send email to TM, SM, OM
                        incrementReminder($db, 3, $openTicket['ticket_id']);
                        sendMail($openTicket['ticket_id'], $differenceInHours, $_emailVariables, true, true, true);
                    }
                }

                // If 3 reminders have been sent
                if($openTicket['reminders_sent'] == 3) {
                    // If it's been 20 hours since the last reminder was sent
                    if($lastReminderDifference >= 20) {
                        // Send email to TM, SM, OM, and President
                        incrementReminder($db, 4, $openTicket['ticket_id']);
                        sendMail($openTicket['ticket_id'], $differenceInHours, $_emailVariables, true, true, true, true);
                    }
                }

            }
        } else if($ticketSLA == 3) {
            // If Silver SLA

        } else if($ticketSLA == 4) {
            // If Bronze SLA

        } else if($ticketSLA == 5) {
            // If No SLA
            
        }

    }
    
    echo "<pre>";
    print_r($openTicket);
    print_r($_ticketVariables);
    echo "</pre>";
}

function incrementReminder($db, $i, $id) {
    $db->updateValue(array("last_reminder_sent" => time(), 'reminders_sent' => $i), array('ticket_id' => $id), 'ost_ticket');
}

function sendMail($ticketID, $openFor, $_ticketVariables, $tm = false, $sm = false, $om = false, $president = false) {
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
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = "Ticket #{$ticketID} is still open.";

        
        $tpl = file_get_contents(__DIR__ . "/templates/escalation.tpl");
        
        foreach($_ticketVariables as $variable => $value) {
            $tpl = str_replace($variable, $value, $tpl);
        }

        $tpl = str_replace('{_openFor}', $openFor, $tpl);

        $tpl = nl2br($tpl);

        $mail->Body    = $tpl;
        $mail->AltBody = $tpl;
            
        if($tm) {
            $mail->addAddress("andy@yllw.com");
        }

        if($sm) {
            $mail->addAddress("andy.abihaidar@xtnd.io");
        }

        if($om) {
            $mail->addAddress("andy@xtnd.io");
        }

        if($president) {
            $mail->addAddress("andyabihaidar@gmail.com");
        }

        $mail->send();
        echo "Message has been sent for Ticket ID {$ticketID}";
    } catch (Exception $e) {
        echo "Message could not be sent for Ticket ID {$ticketID} Mailer Error: ", $mail->ErrorInfo;
    }

}