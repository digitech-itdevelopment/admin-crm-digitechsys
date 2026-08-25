<?php
session_start();
require_once 'config.php';
$conn = getDbConnection();



// Build the comma-separated list of requested stationary items.
// NOTE: This block previously ran a preliminary INSERT that supplied only
// the `stationaryreq` column. Because `tiknumber` (and other columns) are
// NOT NULL with no default and MariaDB runs in strict mode, that insert
// threw an uncaught mysqli exception -> HTTP 500, aborting the request
// before the real record below could ever be saved. The insert is removed;
// only the value we still need ($stationaryreq_str) is kept.
if (isset($_POST['stationaryreq']) && is_array($_POST['stationaryreq'])) {
    $stationaryreq = $_POST['stationaryreq'];
} else {
    $stationaryreq = array();
}

// Convert the array to a comma-separated string
$stationaryreq_str = implode(', ', $stationaryreq);

// Collect POST data
$prevmaintain = isset($_POST['prevmaintain']) ? 'Yes' : 'No';
$stationarydelvrd = isset($_POST['stationarydelv']) ? 'Yes' : 'No';
$atmworking = isset($_POST['atmstatus']) ? 'Not Working' : 'Working';
$complainnumber = isset($_POST['txtcomplaintno']) ? $_POST['txtcomplaintno'] : '';
$issues = isset($_POST['issuestxt']) ? $_POST['issuestxt'] : '';
$callregtime = isset($_POST['txtDateTimeregcall']) ? $_POST['txtDateTimeregcall'] : '';
$ticketnumberr = isset($_POST['ticketnumber']) ? $_POST['ticketnumber'] : '';
$ticketview = isset($_POST['ticketview']) ? $_POST['ticketview'] : '';
$ticketstat = isset($_POST['ticketstatus']) ? $_POST['ticketstatus'] : '';
$bname = isset($_POST['txtbranchname']) ? $_POST['txtbranchname'] : '';
$banknamee = isset($_POST['bankname']) ? $_POST['bankname'] : '';
$atmidd = isset($_POST['atmid']) ? $_POST['atmid'] : '';
$custnumber = isset($_POST['txtcustodianno']) ? $_POST['txtcustodianno'] : '';
$calltype = isset($_POST['typeofcall']) ? $_POST['typeofcall'] : '';
$warrantystatus = isset($_POST['warrantystatusoem']) ? $_POST['warrantystatusoem'] : '';
$callregthr = isset($_POST['callregthrough']) ? $_POST['callregthrough'] : '';
$callregoememail = isset($_POST['callregwithoemtc']) ? 'Yes' : 'No';
$callregdsi = isset($_POST['callregwithdsi']) ? 'Yes' : 'No';
$calltimings = isset($_POST['txtDateTime1']) ? $_POST['txtDateTime1'] : '';
$datetimee = isset($_POST['txtDateTime2']) ? $_POST['txtDateTime2'] : '';
$resolby = isset($_POST['resolutionby']) ? $_POST['resolutionby'] : '';
$demandoem = isset($_POST['demandwithoem']) ? 'Yes' : 'No';
$partnm = isset($_POST['partname']) ? $_POST['partname'] : '';
$oemeng = isset($_POST['oemengg']) ? $_POST['oemengg'] : '';
$digieng = isset($_POST['digiengg']) ? $_POST['digiengg'] : '';
$timeofclos = isset($_POST['txtDateTime3']) ? $_POST['txtDateTime3'] : '';
$calllogtym = isset($_POST['txtExcessDowntime']) ? $_POST['txtExcessDowntime'] : '';
$downntym = isset($_POST['txtDateTime4']) ? $_POST['txtDateTime4'] : '';
$email = isset($_POST['emailinfo']) ? 'Yes' : 'No';
$warrantydatetym = isset($_POST['txtDateTime5']) ? $_POST['txtDateTime5'] : '';
$remarksifany = isset($_POST['txtremarks']) ? $_POST['txtremarks'] : '';
$error = isset($_POST['errorcode']) ? $_POST['errorcode'] : '';
$partdemand = isset($_POST['partdemand']) ? $_POST['partdemand'] : '';
$approvalpending = isset($_POST['approvalpending']) ? 'Yes' : 'No';
$bankend = isset($_POST['bankend']) ? 'Yes' : 'No';
$bankendtext = isset($_POST['bankendtext']) ? $_POST['bankendtext'] : '';



// Handle file uploads
$file_paths = [];
if (!empty($_FILES['fileToUpload']['name'][0])) {
    $target_dir = "image/";
    for ($i = 0; $i < count($_FILES['fileToUpload']['name']); $i++) {
        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"][$i]);
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"][$i], $target_file)) {
            $file_paths[] = $target_file;
        } else {
            echo "Sorry, there was an error uploading one or more of your files.";
        }
    }
}
$file_paths_str = implode(',', $file_paths);


require "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
$mail = new PHPMailer(TRUE);
$mail->isSMTP();
$mail->SMTPDebug = SMTP::DEBUG_OFF; // Set to SMTP::DEBUG_SERVER for detailed debug output
$mail->SMTPAuth = FALSE; // Assuming SMTP authentication is not required
$mail->SMTPAutoTLS = true;
$mail->SMTPKeepAlive = true;
$mail->Mailer = "smtpout.secureserver.net"; // Adjust according to your SMTP settings
$mail->Host = "smtp"; // Adjust according to your SMTP settings
$mail->Port = 465; // Adjust according to your SMTP settings
$mail->Username = "helpdesk@digitechsys.net"; // Adjust according to your SMTP settings
$mail->Password = "temp@#$passs"; // Adjust according to your SMTP settings

// Set common email parameters
$mail->setFrom("helpdesk@digitechsys.net", "Digitech CRM");
$mail->isHTML(true);



if (($ticketstat == "Pending" || $ticketstat == "Closed") && 
    (($calltype == "AMC" || $calltype == "BLA" || $calltype == "Non-AMC" || $calltype == "UPS")) && 
    (($warrantystatus == "AGS" || $warrantystatus == "Dibold" || $warrantystatus == "under-warranty" || $warrantystatus == "non-warranty"))) {
    
    $mail->addAddress("info@digitechsys.net");
}

// AGS branch removed (AGS no longer a warranty option)


  else  if ($calltype == "AMC"  && ($warrantystatus == "Dibold")) {
        $mail->addAddress("raina.atul@dieboldnixdorf.com");


} 



  else  if ($calltype == "AMC"  && ($warrantystatus == "dsi")) {
          $mail->addAddress("suhas@digitechsys.net");
      $mail->addAddress("jyotin@digitechsys.net");


} 




else if ($warrantystatus == "Under-Warranty") {
    $mail->addAddress("suhas@digitechsys.net");
    $mail->addAddress("jyotin@digitechsys.net");
} 


else if ($warrantystatus == "Non-Warranty") {
      $mail->addAddress("suhas@digitechsys.net");
      $mail->addAddress("jyotin@digitechsys.net");

}


else {
    $mail->addAddress("suhas@digitechsys.net");
    $mail->addAddress("jyotin@digitechsys.net");
}


// Compose email body
$mail->Subject = "From Digitech HelpDesk";
$mail->Body = '<html><head><style>table {border-collapse: collapse;width: 100%;}th, td {border: 1px solid black;padding: 8px;text-align: left;}</style></head><body><h2>Details Summary</h2><table><tr><th>Particulars</th><th>Detailed Information</th></tr><tr><td>Branch Name</td><td>' . $bname . '</td></tr><tr><td>Custodian Number</td><td>' . $custnumber . '</td></tr><tr><td>Call Type</td><td>' . $calltype . '</td></tr><tr><td>Warranty Status</td><td>' . $warrantystatus . '</td></tr>';

// Include additional fields if under warranty or non-warranty
if ($warrantystatus == "Under-Warranty" || $warrantystatus == "Non-Warranty" || $warrantystatus == "AGS" || $warrantystatus == "Dibold") {
    
    
    $mail->Body .= '<tr><td>Call Registered Through</td><td>' . $callregthr . '</td></tr>';
    $mail->Body .= '<tr><td>Call Timings</td><td>' . $calltimings . '</td></tr>';
    $mail->Body .= '<tr><td>Call Registered with OEM Email</td><td>' . $callregoememail . '</td></tr>';
    $mail->Body .= '<tr><td>Call Registered with DSI</td><td>' . $callregdsi . '</td></tr>';
    $mail->Body .= '<tr><td>Date and Time of Call Registration</td><td>' . $datetimee . '</td></tr>';
    $mail->Body .= '<tr><td>Complaint Number</td><td>' . $complainnumber . '</td></tr>';
    $mail->Body .= '<tr><td>Date and Time of Call Closure</td><td>' . $timeofclos . '</td></tr>';
    $mail->Body .= '<tr><td>Excessive Downtime(in Hrs)</td><td>' . $calllogtym  . '</td></tr>';
    $mail->Body .= '<tr><td>Error Code</td><td>' . $error  . '</td></tr>';


  
  
}

else {
    
    
    $mail->Body .= '<tr><td>Call Registered Through</td><td>' . $callregthr . '</td></tr>';
    $mail->Body .= '<tr><td>Call Timings</td><td>' . $calltimings . '</td></tr>';
    $mail->Body .= '<tr><td>Call Registered OEM Email</td><td>' . $callregoememail . '</td></tr>';
    $mail->Body .= '<tr><td>Call Registered DSI</td><td>' . $callregdsi . '</td></tr>';
    $mail->Body .= '<tr><td>Date and Time of Call Registration</td><td>' . $datetimee . '</td></tr>';
    $mail->Body .= '<tr><td>Resolved By</td><td>' . $resolby . '</td></tr>';
    $mail->Body .= '<tr><td>Demand OEM</td><td>' . $demandoem . '</td></tr>';
    $mail->Body .= '<tr><td>Part Number</td><td>' . $partnm . '</td></tr>';
    $mail->Body .= '<tr><td>Name of OEM Engineer</td><td>' . $oemeng . '</td></tr>';
    $mail->Body .= '<tr><td>Name of Digitech Engineer</td><td>' . $digieng . '</td></tr>';
    $mail->Body .= '<tr><td>Time of Closure</td><td>' . $timeofclos . '</td></tr>';
    $mail->Body .= '<tr><td>Call Log Time</td><td>' . $calllogtym . '</td></tr>';
    $mail->Body .= '<tr><td>Email</td><td>' . $email . '</td></tr>';
    $mail->Body .= '<tr><td>Remarks (if any)</td><td>' . $remarksifany . '</td></tr>';
    $mail->Body .= '<tr><td>Warranty Date and Time</td><td>' . $warrantydatetym . '</td></tr>';
    $mail->Body .= '<tr><td>Downtime</td><td>' . $downntym . '</td></tr>';
    $mail->Body .= '<tr><td>Issues</td><td>' . $issues . '</td></tr>';
    $mail->Body .= '<tr><td>Date and Time of Call Closure</td><td>' . $timeofclos . '</td></tr>';
    $mail->Body .= '<tr><td>Excessive Downtime(In Hrs)</td><td>' . $calllogtym  . '</td></tr>';
    $mail->Body .= '<tr><td>Error Code</td><td>' . $error  . '</td></tr>';
    
     $mail->Body .= '<tr><td>Stationary Required</td><td>' . $stationaryrequired  . '</td></tr>';
     
      $mail->Body .= '<tr><td>ATM Status</td><td>' . $atmworking  . '</td></tr>';

    
}

$mail->Body .= '</table></body></html>';

// Attempt to send the email. PHPMailer is constructed with exceptions
// enabled (new PHPMailer(TRUE)), so an unreachable/misconfigured SMTP host
// throws — which would (previously) abort the request before the record was
// saved. Catch it so notification problems never block saving the call.
try {
    $mail->send();
    $_SESSION['success_message'] = "Form submitted successfully!";
} catch (\Throwable $mailError) {
    error_log("Digitech CRM: email notification failed: " . $mailError->getMessage());
}





// Registration calls are "Pending" until closed. Set here — AFTER the email
// routing block above — so it does not change notification recipients.
if ((isset($_POST['form_type']) ? $_POST['form_type'] : '') === 'callregistered') { $ticketstat = 'Pending'; }

// Prepare and execute insertion into digitechcrm
$sql = "INSERT INTO digitechcrm (
    tiknumber, error, issues, stationaryreq, atmworking, callregtimings, tickview, tktstatusdetails, branchname, 
    bnkname, atmid, custnumber, calltype, warrantystatus, prevmaintain, stationarydelvrd, complainno, callregthr, 
    calltimings, callregoememail, callregdsi, datetimee, resolby, demandoem, partnm, oemeng, digieng, timeofclos, 
    calllogtym, email, remarksifany, warrantydatetym, downntym, svr, approvalpending, bankend, bankendtext, partdemand)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssssssssssssssssssssssssssssssssss",

    $ticketnumberr, $error, $issues, $stationaryreq_str, $atmworking, $callregtime, $ticketview, $ticketstat, $bname,
    $banknamee, $atmidd, $custnumber, $calltype, $warrantystatus, $prevmaintain, $stationarydelvrd,
    $complainnumber, $callregthr, $calltimings, $callregoememail, $callregdsi, $datetimee,
    $resolby, $demandoem, $partnm, $oemeng, $digieng, $timeofclos, $calllogtym, $email, $remarksifany, $warrantydatetym,
    $downntym, $file_paths_str, $approvalpending, $bankend, $bankendtext, $partdemand);

if ($stmt->execute()) {

    // Decrement the chosen stock list (New/Refurbished/None) for the selected part,
    // by the quantity the engineer actually used (defaults to 1 if not provided).
    $partstock = isset($_POST['partstock']) ? $_POST['partstock'] : 'New';
    $partqty = isset($_POST['partqty']) ? (int)$_POST['partqty'] : 1;
    if ($partqty < 1) { $partqty = 1; }
    if ($partnm !== '' && $partnm !== 'Not Applicable' && $partstock !== 'None') {
        $stockTable = ($partstock === 'Refurbished') ? 'inventory_refurbished' : 'inventory';
        try {
            $up = $conn->prepare("UPDATE $stockTable SET quantity = GREATEST(quantity - ?, 0) WHERE part_key = ?");
            $up->bind_param("is", $partqty, $partnm);
            $up->execute();
        } catch (\Throwable $stockError) {
            error_log("Digitech CRM: stock update skipped/failed: " . $stockError->getMessage());
        }
    }

    // On a closure, propagate the status to the original registration row so both show it.
    if ((isset($_POST['form_type']) ? $_POST['form_type'] : '') !== 'callregistered' && $ticketview !== '') {
        try {
            $su = $conn->prepare("UPDATE digitechcrm SET tktstatusdetails = ? WHERE tiknumber = ? AND tiknumber <> ''");
            $su->bind_param("ss", $ticketstat, $ticketview);
            $su->execute();
        } catch (\Throwable $e) { error_log("Digitech CRM: reg status update failed: " . $e->getMessage()); }
    }

    // Set success message and redirect. No output is echoed before this point,
    // so the header() redirect works (previously the debug echoes above caused
    // "headers already sent" and the redirect silently failed).
    $_SESSION['success_message'] = "Form submitted successfully!";
    $form_type = isset($_POST['form_type']) ? $_POST['form_type'] : '';
    if ($form_type == 'callregistered') {
        header("Location: callregistered.php");
    } else {
        header("Location: callclosed.php");
    }
} else {
    error_log("Digitech CRM: record insert failed: " . $stmt->error);
    echo "Error saving the record. Please contact support.";
}

$stmt->close();
$conn->close();
exit();
?>