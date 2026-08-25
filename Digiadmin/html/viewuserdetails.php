<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['userdata'])) {
    header("Location: adminlogin.php");
    exit;
}

// Retrieve user information from session
$usernamedetails = $_SESSION['userdata'];

require_once 'config.php';
$conn = getDbConnection();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Render a labelled row only when the value is non-empty (hide blank fields in the view).
function fieldRow($label, $val) {
    $val = trim((string)$val);
    if ($val !== '') {
        echo '<p><strong>' . htmlspecialchars($label) . ':</strong> ' . htmlspecialchars($val) . '</p>';
    }
}

// Fetch unique bank names for dropdown
$bankQuery = "SELECT DISTINCT bnkname FROM digitechcrm";
$bankResult = $conn->query($bankQuery);
$bankNames = [];
if ($bankResult) {
    while ($row = $bankResult->fetch_assoc()) {
        $bankNames[] = $row['bnkname'];
    }
}

foreach (array('JKSCB', 'CSFB') as $fixedBank) {
    if (!in_array($fixedBank, $bankNames, true)) { $bankNames[] = $fixedBank; }
}
sort($bankNames);

// Get selected bank name from request
$selectedBank = isset($_POST['bank']) ? $_POST['bank'] : '';

// SQL query to select data from database based on selected bank
$sql = "SELECT id, tiknumber, error, issues, stationaryreq, atmworking, callregtimings, tktstatusdetails, tickview, branchname, bnkname, atmid, custnumber, calltype, warrantystatus,
        prevmaintain, stationarydelvrd, complainno, callregthr, calltimings, callregoememail, callregdsi, datetimee, resolby, demandoem, partnm, oemeng,
        digieng, timeofclos, svr, downntym, calllogtym, email, warrantydatetym, remarksifany, approvalpending, bankend, bankendtext, partdemand FROM digitechcrm" .
        ($selectedBank ? " WHERE bnkname = '" . $conn->real_escape_string($selectedBank) . "'" : "") .
        // Pair each ticket's registration and closure together: group by the
        // unified ticket number (registration uses tiknumber, closure uses
        // tickview), newest first, registration shown before its closure.
        " ORDER BY (CASE WHEN tiknumber <> '' THEN tiknumber ELSE tickview END) DESC, (CASE WHEN tiknumber <> '' THEN 0 ELSE 1 END) ASC";

$result = $conn->query($sql);

// Check if query executed successfully
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Digitech User Details</title>
    <link rel="stylesheet" href="./css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <meta name="robots" content="noindex,nofollow" />
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/favicon.png" />
    <link href="../assets/libs/flot/css/float-chart.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet" />
    <style>
      /* Custom CSS for table border */
      table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
      }
      table, th, td {
        border: 1px solid black;
      }
      th, td {
        padding: 8px;
        text-align: left;
      }
      th {
        background-color: #f2f2f2;
      }
    </style>
</head>

<body>
   <div class="preloader">
      <div class="lds-ripple">
        <div class="lds-pos"></div>
        <div class="lds-pos"></div>
      </div>
    </div>
    <div id="main-wrapper" data-layout="vertical" data-navbarbg="skin5" data-sidebartype="full" data-sidebar-position="absolute" data-header-position="absolute" data-boxed-layout="full">
      <header class="topbar" data-navbarbg="skin5">
        <nav class="navbar top-navbar navbar-expand-md navbar-dark">
          <div class="navbar-header" data-logobg="skin5">
            <a class="navbar-brand" href="index.html">
              <b class="logo-icon ps-2">
                <img src="../assets/images/logo-icon.png" alt="homepage" class="light-logo" width="25" />
              </b>
              <span class="pt-3">
                <h6>Digitech Systems</h6>
              </span>
            </a>
            <a class="nav-toggler waves-effect waves-light d-block d-md-none" href="javascript:void(0)"><i class="ti-menu ti-close"></i></a>
          </div>
          <div class="navbar-collapse collapse" id="navbarSupportedContent" data-navbarbg="skin5">
            <ul class="navbar-nav float-start me-auto">
              <li class="nav-item d-none d-lg-block">
                <a class="nav-link sidebartoggler waves-effect waves-light" href="javascript:void(0)" data-sidebartype="mini-sidebar"><i class="mdi mdi-menu font-24"></i></a>
              </li>
              <li class="nav-item search-box">
                <a class="nav-link waves-effect waves-dark" href="javascript:void(0)"><i class="mdi mdi-magnify fs-4"></i></a>
                <form class="app-search position-absolute">
                  <input type="text" class="form-control" placeholder="Search &amp; enter" />
                  <a class="srh-btn"><i class="mdi mdi-window-close"></i></a>
                </form>
              </li>
            </ul>
            <ul class="navbar-nav float-end">
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="mdi mdi-bell font-24"></i>
                </a>
                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                  <li><a class="dropdown-item" href="#">Action</a></li>
                  <li><a class="dropdown-item" href="#">Another action</a></li>
                  <li><hr class="dropdown-divider" /></li>
                  <li><a class="dropdown-item" href="#">Something else here</a></li>
                </ul>
              </li>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle waves-effect waves-dark" href="#" id="2" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="font-24 mdi mdi-comment-processing"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end mailbox animated bounceInDown" aria-labelledby="2">
                  <ul class="list-style-none">
                    <li>
                      <div class="">
                        <a href="javascript:void(0)" class="link border-top">
                          <div class="d-flex no-block align-items-center p-10">
                            <span class="btn btn-success btn-circle d-flex align-items-center justify-content-center"><i class="mdi mdi-calendar text-white fs-4"></i></span>
                            <div class="ms-2">
                              <h5 class="mb-0">Event today</h5>
                              <span class="mail-desc">Just a reminder that event</span>
                            </div>
                          </div>
                        </a>
                        <a href="javascript:void(0)" class="link border-top">
                          <div class="d-flex no-block align-items-center p-10">
                            <span class="btn btn-info btn-circle d-flex align-items-center justify-content-center"><i class="mdi mdi-settings fs-4"></i></span>
                            <div class="ms-2">
                              <h5 class="mb-0">Settings</h5>
                              <span class="mail-desc">You can customize this template</span>
                            </div>
                          </div>
                        </a>
                        <a href="javascript:void(0)" class="link border-top">
                          <div class="d-flex no-block align-items-center p-10">
                            <span class="btn btn-primary btn-circle d-flex align-items-center justify-content-center"><i class="mdi mdi-account fs-4"></i></span>
                            <div class="ms-2">
                              <h5 class="mb-0">Pavan kumar</h5>
                              <span class="mail-desc">Just see the my admin!</span>
                            </div>
                          </div>
                        </a>
                        <a href="javascript:void(0)" class="link border-top">
                          <div class="d-flex no-block align-items-center p-10">
                            <span class="btn btn-danger btn-circle d-flex align-items-center justify-content-center"><i class="mdi mdi-link fs-4"></i></span>
                            <div class="ms-2">
                              <h5 class="mb-0">Luanch Admin</h5>
                              <span class="mail-desc">Just see the my new admin!</span>
                            </div>
                          </div>
                        </a>
                      </div>
                    </li>
                  </ul>
                </ul>
              </li>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-muted waves-effect waves-dark pro-pic" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <img src="../assets/images/users/1.jpg" alt="user" class="rounded-circle" width="31" />
                </a>
                <ul class="dropdown-menu dropdown-menu-end user-dd animated" aria-labelledby="navbarDropdown">
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item" href="logout.php"><i class="fa fa-power-off me-1 ms-1"></i> Logout</a>
                </ul>
              </li>
            </ul>
          </div>
        </nav>
      </header>

      <aside class="left-sidebar" data-sidebarbg="skin5">
        <div class="scroll-sidebar">
          <nav class="sidebar-nav">
            <ul id="sidebarnav" class="pt-4">
              <li class="sidebar-item">
                <a class="sidebar-link waves-effect waves-dark sidebar-link" href="index.html" aria-expanded="false"><i class="mdi mdi-view-dashboard"></i><span class="hide-menu">Dashboard</span></a>
              </li>
            </ul>
          </nav>
        </div>
      </aside>
       
       
       <div class="page-wrapper bg-white">
       <div class="container">
                <div class="row">
                 
                    <div class="text-md-center text-danger col-md-12">
                        <h3>Digitech CRM Online User Record</h3>
                    </div>

                  
                </div>

                <div class="row">
    <div class="col-md-4 text-md-center mx-auto">
        <form id="filterForm" method="POST" action="" >
            <label for="bankDropdown">Select Bank:</label>
            <select id="bankDropdown" name="bank" class="form-select">
                <option value="">All Banks</option>
                <?php foreach ($bankNames as $bankName) { ?>
                    <option value="<?php echo htmlspecialchars($bankName); ?>" <?php echo ($selectedBank === $bankName) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($bankName); ?>
                    </option>
                <?php } ?>
            </select>
        </form>
    </div>
</div>

             

                <!-- Cards to display records -->
                <?php
                $result->data_seek(0);
                $groups = array();
                $order  = array();
                while ($rows = $result->fetch_assoc()) {
                    $uid = ($rows['tiknumber'] !== '') ? $rows['tiknumber'] : $rows['tickview'];
                    if (!isset($groups[$uid])) { $groups[$uid] = array(); $order[] = $uid; }
                    $groups[$uid][] = $rows;
                }
                foreach ($order as $uid):
                    usort($groups[$uid], function($a, $b){ return ($a['tiknumber'] !== '' ? 0 : 1) - ($b['tiknumber'] !== '' ? 0 : 1); });
                ?>
                  <div class="row">
                    <?php foreach ($groups[$uid] as $rows):
                        $isClose = ($rows['tiknumber'] === ''); ?>
                      <div class="col-md-6">
                        <div class="card">
                          <div class="card-header">
                            <div class="row">
                              <div class="col-md-9">
                                <img src="../images/ticket-16.png" alt="Ticket Icon">
                                Ticket Number: <?php echo htmlspecialchars($isClose ? $rows['tickview'] : $rows['tiknumber']); ?>
                                <span style="margin-left:8px; padding:2px 8px; border-radius:4px; font-size:12px; background:<?php echo $isClose ? '#8e1b1b' : '#1e7d3c'; ?>; color:#fff;">
                                  <?php echo $isClose ? 'CLOSED (' . htmlspecialchars($rows['tktstatusdetails']) . ')' : 'REGISTERED'; ?>
                                </span>
                              </div>
                              <?php if ($isClose): ?>
                              <div class="col-md-3">
                                <form action="delete_ticket.php" method="post" style="margin:0;">
                                  <input type="hidden" name="ticket_id" value="<?php echo (int)$rows['id']; ?>">
                                  <input style="color:black;" class="form-control font-16" type="submit" value="Delete"
                                         onclick="return confirm('Delete this <?php echo $isClose ? 'closure' : 'registration'; ?> record for ticket <?php echo htmlspecialchars($isClose ? $rows['tickview'] : $rows['tiknumber']); ?>?');">
                                </form>
                              </div>
                              <?php else: ?>
                              <div class="col-md-3">
                                <a href="edit_ticket.php?id=<?php echo (int)$rows['id']; ?>"
                                   class="form-control font-16 text-center"
                                   style="color:black; text-decoration:none; display:inline-block;">Edit</a>
                              </div>
                              <?php endif; ?>
                            </div>
                          </div>
                          <div class="card-body">
                    <?php
                    fieldRow('Ticket Status', $rows['tktstatusdetails'] !== '' ? $rows['tktstatusdetails'] : ($rows['tiknumber'] !== '' ? 'Pending' : ''));
                    fieldRow('Complain Number', $rows['complainno']);
                    fieldRow('Call Register Date & Time', $rows['callregtimings']);
                    fieldRow('Branch Name', $rows['branchname']);
                    fieldRow('Bank Name', $rows['bnkname']);
                    fieldRow('ATM ID', $rows['atmid']);
                    fieldRow('Custodian No', $rows['custnumber']);
                    fieldRow('Type of Call', $rows['calltype']);
                    fieldRow('Warranty status', $rows['warrantystatus']);
                    fieldRow('Approval Pending (Bank End)', $rows['approvalpending']);
                    fieldRow('Bank End details', $rows['bankendtext']);
                    fieldRow('Preventive Maintenance', $rows['prevmaintain']);
                    fieldRow('Stationary Delivered', $rows['stationarydelvrd']);
                    fieldRow('Error Code', $rows['error']);
                    fieldRow('Issues', $rows['issues']);
                    fieldRow('Stationary Required', $rows['stationaryreq']);
                    fieldRow('Atm Status', $rows['atmworking']);
                    fieldRow('Call Registered Through', $rows['callregthr']);
                    fieldRow('Call Timings', $rows['calltimings']);
                    fieldRow('Call Registered with OEM', $rows['callregoememail']);
                    fieldRow('Call Registered with Digitech', $rows['callregdsi']);
                    fieldRow('Date and Time', $rows['datetimee']);
                    fieldRow('Resolved by', $rows['resolby']);
                    fieldRow('Part Demand', $rows['partdemand']);
                    fieldRow('Name of Part', $rows['partnm']);
                    fieldRow('Name of OEM Engineer', $rows['oemeng']);
                    fieldRow('Name of Digitech Engineer', $rows['digieng']);
                    fieldRow('Time of Close', $rows['timeofclos']);

                    if (trim((string)$rows['svr']) !== '') {
                        echo '<p><strong>SCR Documents:</strong></p>';
                        foreach (explode(',', $rows['svr']) as $document) {
                            $document = trim($document);
                            if ($document === '') { continue; }
                            echo '<div><a href="' . htmlspecialchars($document) . '" target="_blank">' . htmlspecialchars(basename($document)) . '</a></div>';
                        }
                    }

                    fieldRow('Downtime', $rows['downntym']);
                    fieldRow('Excess Downtime', $rows['calllogtym']);
                    fieldRow('Customer Information through', $rows['email']);
                    fieldRow('Closure Date and Time', $rows['warrantydatetym']);
                    fieldRow('Remarks if any', $rows['remarksifany']);
                    ?>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>

       <script>
        $(document).ready(function() {
            $('#bankDropdown').change(function() {
                $('#filterForm').submit();
            });
        });
    </script>

    
<style>
       



       .container {
           margin-top: 20px;
       }

       .card {
           border: 1px solid #ccc;
           margin-bottom: 20px;
           border-radius: 5px;
           margin-top: 20px;
       }

       .card-header {
           background-color: red;
           color: #fff;
           font-weight: bold;
           padding: 8px;
           border-bottom: 1px solid #ddd;
           border-top-left-radius: 5px;
           border-top-right-radius: 5px;
       }

       .card-body {
           padding: 15px;
       }

       .card-body p {
           margin: 12px 0;
           border: 1px solid #000;
           padding: 10px;
       }
   </style>
</body>
</html>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../assets/extra-libs/sparkline/sparkline.js"></script>
    <script src="../dist/js/waves.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>

<?php
$conn->close();
?>