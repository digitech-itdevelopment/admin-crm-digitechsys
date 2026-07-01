<?php
session_start();
require_once 'config.php';

$conn = getDbConnection();




// Initialize variables with form data





$prevmaintain = isset($_POST['prevmaintain']) ? 'Yes' : 'No';
$stationarydelvrd = isset($_POST['stationarydelv']) ? 'Yes' : 'No';

$atmworking = isset($_POST['atmstatus']) ? 'Not Working' : 'Working';
$stationaryrequired = isset($_POST['stationaryreq']) ? $_POST['stationaryreq'] : '';
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



// Initialize array to store file paths
$file_paths = [];

// Handle file uploads
if (!empty($_FILES['fileToUpload']['name'][0])) {
    $target_dir = "image/";

    // Loop through each file
    for ($i = 0; $i < count($_FILES['fileToUpload']['name']); $i++) {
        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"][$i]);
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"][$i], $target_file)) {
            $file_paths[] = $target_file;
        } else {
            echo "Sorry, there was an error uploading one or more of your files.";
        }
    }
}

// Convert file paths array to a comma-separated string for database storage
$file_paths_str = implode(',', $file_paths); // Change delimiter as needed


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
$mail->Password = 'temp@#$passs'; // Adjust according to your SMTP settings

// Set common email parameters
$mail->setFrom("helpdesk@digitechsys.net", "Digitech CRM");
$mail->isHTML(true);



if (($ticketstat == "Pending" || $ticketstat == "Closed") && 
    (($calltype == "AMC" || $calltype == "BLA" || $calltype == "Non-AMC" || $calltype == "UPS")) && 
    (($warrantystatus == "AGS" || $warrantystatus == "diebold" || $warrantystatus == "under-warranty" || $warrantystatus == "non-warranty"))) {
    
    $mail->addAddress("info@digitechsys.net");
}

else if ($calltype == "AMC"  && ($warrantystatus == "AGS")) {
  $mail->addAddress("chaba.raman@agsindia.com");
     $mail->addAddress("helpdesk_support@agsindia.com");
    $mail->addAddress("dharminder.kumar@agsindia.com");
}


  else  if ($calltype == "AMC"  && ($warrantystatus == "Diebold")) {
        $mail->addAddress("raina.atul@dieboldnixdorf.com");


} else if ($warrantystatus == "Under-Warranty") {
    $mail->addAddress("helpdesk_support@agsindia.com");
    $mail->addAddress("dharminder.kumar@agsindia.com");
    $mail->addAddress("chaba.raman@agsindia.com");
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

// Attempt to send the email
if ($mail->send()) {
    // Email sent successfully
    $_SESSION['success_message'] = "Form submitted successfully!";
} else {
    // Email sending failed
    echo "Mailer Error: " . $mail->ErrorInfo;
}



$sql = "INSERT INTO digitechcrm (tiknumber, error, issues, stationaryreq, atmworking, callregtimings, tickview, tktstatusdetails, branchname, bnkname, atmid, custnumber, calltype, warrantystatus, prevmaintain, stationarydelvrd, complainno, callregthr, calltimings, callregoememail, callregdsi,
                                datetimee, resolby, demandoem, partnm, oemeng, digieng,
                                timeofclos, calllogtym, email, remarksifany, warrantydatetym, downntym, svr)
        VALUES ('$ticketnumberr', '$error', '$issues', '$stationaryrequired', '$atmworking', '$callregtime', '$ticketview', '$ticketstat', '$bname', '$banknamee', '$atmidd', '$custnumber', '$calltype', '$warrantystatus', '$prevmaintain', '$stationarydelvrd', '$complainnumber', '$callregthr',
                '$calltimings', '$callregoememail', '$callregdsi', '$datetimee', '$resolby', '$demandoem', '$partnm',
                '$oemeng' , '$digieng', '$timeofclos', '$calllogtym', '$email', '$remarksifany', '$warrantydatetym', '$downntym', '$file_paths_str')";



if ($conn->query($sql) === TRUE) {
    echo "Records inserted successfully.";
}


else {
    echo "Error: " . $sql1 . "<br>" . $conn->error;
}

// Determine where to redirect based on form type
if (isset($_POST['form_type'])) {
    $form_type = $_POST['form_type'];
    if ($form_type == 'callregistered') {
        header("Location: callregistered.php");
    }
     else  {
        header("Location: callclosed.php");
    } 
} 

$conn->close();
exit();

