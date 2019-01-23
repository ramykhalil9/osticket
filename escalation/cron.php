<?php

// Importing database class 
require __DIR__ . '/db.php';
$db = new Db();

// Importing mail class
require __DIR__ . "/sendgrid-php/sendgrid-php.php";

// Fetching all the tickets that are open
// Closed means status_id = 3, so checking for anything different than 3
$openTickets_query = $db->dbh->query("SELECT * FROM `ost_ticket` WHERE `status_id` != 3");
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
    $ostForm_query = $db->dbh->prepare("SELECT `ost_form_entry_values`.* FROM `ost_form_entry` LEFT JOIN `ost_form_entry_values` ON `ost_form_entry_values`.`entry_id` = `ost_form_entry`.`id` WHERE `ost_form_entry`.`object_type` = 'O' AND `ost_form_entry`.`object_id` = :id AND `ost_form_entry_values`.`field_id` = 38");
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
                    sendMail($openTicket['ticket_id'], $differenceInHours, true);
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
                        sendMail($openTicket['ticket_id'], $differenceInHours, true, true);
                    }
                } 
                
                // If 2 reminders have been sent
                if($openTicket['reminders_sent'] == 2) {
                    // If it's been 2 hours since the last reminder was sent
                    if($lastReminderDifference >= 2) {
                        // Send email to TM, SM, OM
                        incrementReminder($db, 3, $openTicket['ticket_id']);
                        sendMail($openTicket['ticket_id'], $differenceInHours, true, true, true);
                    }
                }

                // If 3 reminders have been sent
                if($openTicket['reminders_sent'] == 3) {
                    // If it's been 20 hours since the last reminder was sent
                    if($lastReminderDifference >= 20) {
                        // Send email to TM, SM, OM, and President
                        incrementReminder($db, 4, $openTicket['ticket_id']);
                        sendMail($openTicket['ticket_id'], $differenceInHours, true, true, true, true);
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
}

function incrementReminder($db, $i, $id) {
    $db->updateValue(array("last_reminder_sent" => time(), 'reminders_sent' => $i), array('ticket_id' => $id), 'ost_ticket');
}

function sendMail($ticketID, $openFor, $tm = false, $sm = false, $om = false, $president = false) {
    $email = new \SendGrid\Mail\Mail(); 
    $email->setFrom("andy@yllw.com");
    $email->setSubject("Ticket #{$ticketID} is still open.");

    if($tm) {
        $email->addTo("andy@yllw.com");
    }

    if($sm) {
        $email->addTo("andy.abihaidar@xtnd.io");
    }

    if($om) {
        $email->addTo("andy@xtnd.io");
    }

    if($president) {
        $email->addTo("andyabihaidar@gmail.com");
    }

    $email->addContent("text/plain", "Please note ticket #{$ticketID} has been open for {$openFor} hours.");
    $email->addContent(
        "text/html", "Please note ticket #{$ticketID} has been open for {$openFor} hours."
    );
    $sendgrid = new \SendGrid("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");
    try {
        $response = $sendgrid->send($email);
        print $response->statusCode() . "\n";
        print_r($response->headers());
        print $response->body() . "\n";
    } catch (Exception $e) {
        echo 'Caught exception: '. $e->getMessage() ."\n";
    }
}