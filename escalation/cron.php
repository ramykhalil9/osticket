<?php
date_default_timezone_set("Asia/Beirut");

// Importing database class 
require __DIR__ . '/db.php';
$db = new Db();

// Importing Config
$configFile = file_get_contents(__DIR__ . '/config.json');
$config = json_decode($configFile, true);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Importing mail class
require __DIR__ . "/vendor/autoload.php";

// Config Translation
$_organizationSLA = array(
    "Bronze"    => "bronze_organization_sla",
    "Silver"    => "silver_organization_sla",
    "Gold"      => "gold_organization_sla"
);

$_ticketSLA = array(
    "1"           => "default_ticket_sla",
    "2"           => "gold_ticket_sla",
    "3"           => "silver_ticket_sla",
    "4"           => "bronze_ticket_sla",
    "5"           => "no_ticket_sla"
);

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
		'{_orgName}' => $organization['name'],
        '{_ticketNumber}' => $openTicket['number'],
        '{_ticketID}' => $openTicket['ticket_id'],
        '{_ticketSubject}' => $openTicket['subject'],
        '{_ticketPriority}' => $openTicket['priority_desc'],
        '{_assignedName}' => $staff['firstname'] . ' ' . $staff['lastname'],
    );

    // Getting Ticket SLA ID
    $ticketSLA = $openTicket['sla_id'];

    $orgConfig = $config[$_organizationSLA[$orgSLA]];
    $ticketConfig = $orgConfig[$_ticketSLA[$ticketSLA]];

    // If no config exists for the amount of reminders already sent
    if($openTicket['reminders_sent'] + 1 > count($ticketConfig['reminders'])) {
        echo "Could not send reminder for Ticket ID {$openTicket['ticket_id']}, no config for that many reminders sent ({$openTicket['reminders_sent']})";
        continue;
    }

    $reminderConfig = $ticketConfig['reminders'][$openTicket['reminders_sent']];
    
    if($differenceInHours >= $reminderConfig['time']) {
        incrementReminder($db, $openTicket['reminders_sent'] + 1, $openTicket['ticket_id']);
        sendMail($openTicket['ticket_id'], $differenceInHours, $_emailVariables, $reminderConfig['send']['DM'], $reminderConfig['send']['TM'], $reminderConfig['send']['CEO'], $reminderConfig['send']['President']);
    }

    echo "<pre>";
    print_r($openTicket);
	print_r($_emailVariables);
    echo "</pre>";
}

function incrementReminder($db, $i, $id) {
    $db->updateValue(array("last_reminder_sent" => time(), 'reminders_sent' => $i), array('ticket_id' => $id), 'ost_ticket');
}

function sendMail($ticketID, $openFor, $_ticketVariables, $dm = false, $tm = false, $ceo = false, $president = false) {
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
        $mail->setFrom('no_reply@bmbgroup.com');
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = "Ticket Escalation Alert";
        
		$tpl = file_get_contents(__DIR__ . "/templates/escalation.tpl");
        
        foreach($_ticketVariables as $variable => $value) {
            $tpl = str_replace($variable, $value, $tpl);
        }
        $tpl = str_replace('{_openFor}', $openFor, $tpl);
        $tpl = nl2br($tpl);
		
        $mail->Body    = $tpl;
        $mail->AltBody = $tpl;
            
        if($dm) {
            $mail->addAddress("ramy.khalil@bmbgroup.com");
        }

        if($tm) {
            $mail->addAddress("ramykhalil9@gmail.com");
        }

        if($ceo) {
            $mail->addAddress("duheli@heximail.com");
        }

        if($president) {
            $mail->addAddress("xudic@22office.com");
        }

        $mail->send();
        echo "Message has been sent for Ticket ID {$ticketID}";
    } catch (Exception $e) {
        echo "Message could not be sent for Ticket ID {$ticketID} Mailer Error: ", $mail->ErrorInfo;
    }

}