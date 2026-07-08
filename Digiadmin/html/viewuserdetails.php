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

// Fetch unique bank names for dropdown
$bankQuery = "SELECT DISTINCT bnkname FROM digitechcrm";
$bankResult = $conn->query($bankQuery);
$bankNames = [];
if ($bankResult) {
    while ($row = $bankResult->fetch_assoc()) {
        $bankNames[] = $row['bnkname'];
    }
}

// Get selected bank name from request
$selectedBank = isset($_POST['bank']) ? $_POST['bank'] : '';

// SQL query to select data from database based on selected bank
$sql = "SELECT tiknumber, error, issues, stationaryreq, atmworking, callregtimings, tktstatusdetails, tickview, branchname, bnkname, atmid, custnumber, calltype, warrantystatus,
        prevmaintain, stationarydelvrd, complainno, callregthr, calltimings, callregoememail, callregdsi, datetimee, resolby, demandoem, partnm, oemeng,
        digieng, timeofclos, svr, downntym, calllogtym, email, warrantydatetym, remarksifany, approvalpending, bankend, bankendtext FROM digitechcrm" .
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

             

                <!-- Hidden table for DataTables functionality -->
                <table id="hiddenTable" class="display" style="display:none;">
                    <thead>
                        <tr>
                        <th>Ticket Number</th>
                    <th>Ticket Status</th>
                    <th>Complain Number</th>
                    <th>Call Register Date & Time</th>
                    <th>Branch Name</th>
                    <th>Bank Name</th>
                    <th>ATM ID</th>
                    <th>Custodian No</th>
                    <th>Type of Call</th>
                    <th>Warranty status</th>
                     <th>Preventive Maintenance</th> 
                    <th>Stationary Delivered</th>
                    <th>Error Code</th>
                    <th>Issues</th>
                    <th>Stationary Required</th>
                    <th>ATM Status</th>
                    <th>Call Registered Through</th>
                    <th>Call Timings</th>
                    <th>Call Registered with OEM</th>
                    <th>Call Registered with Digitech</th>
                    <th>Date and Time</th>
                    <th>Resolved by</th>
                    <th>Part Demand With OEM</th>
                    <th>Name of Part</th>
                    <th>Name of OEM Engineer</th>
                    <th>Name of Digitech Engineer</th>
                    <th>Time of Close</th>
                    <th>SCR Documents</th>
                    <th>Downtime</th>
                    <th>Excess Downtime</th>
                    <th>Customer Information through</th>
                    <th>Closure Date and Time</th>
                    <th>Remarks if any</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                        $result->data_seek(0); // Reset result set pointer to the beginning
                        
                        while ($rows = $result->fetch_assoc()) { ?>
                          
                          <div class="card">
              <div class="card-header">
    <div class="row">
        <div class=" col-md-11">
            <?php if ($rows['tktstatusdetails'] == 'Pending'): ?>
                <img src="../images/ticket-16.png" alt="Ticket Icon"> Ticket Number: <?php echo htmlspecialchars($rows['tickview']); ?>
            <?php elseif ($rows['tktstatusdetails'] == 'Closed'): ?>
                <img src="../images/ticket-16.png" alt="Ticket Icon"> Ticket Number: <?php echo htmlspecialchars($rows['tickview']); ?>
            <?php else: ?>
                <img src="../images/ticket-16.png" alt="Ticket Icon"> Ticket Number: <?php echo htmlspecialchars($rows['tiknumber']); ?>
            <?php endif; ?>
            <?php $isClose = in_array($rows['tktstatusdetails'], array('Pending', 'Closed')); ?>
            <span style="margin-left:10px; padding:2px 8px; border-radius:4px; font-size:12px; background:<?php echo $isClose ? '#8e1b1b' : '#1e7d3c'; ?>; color:#fff;">
                <?php echo $isClose ? 'CLOSED (' . htmlspecialchars($rows['tktstatusdetails']) . ')' : 'REGISTERED'; ?>
            </span>
        </div>
        <div class="col-md-1 justify-content-end ">
            <form action="delete_ticket.php" method="post" style="margin: 0;">
                <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($rows['tiknumber']); ?>">
                <input style="color:black;" class=" form-control font-16" type="submit" value="Delete" onclick="return confirm('Are you sure you want to delete this ticket number: <?php echo htmlspecialchars($rows['tiknumber']); ?> ?');">
            </form>
        </div>
    </div>
</div>


                <div class="card-body">
                    <p><strong>Ticket Status:</strong> <?php echo htmlspecialchars($rows['tktstatusdetails']); ?></p>
                    <p><strong>Complain Number:</strong> <?php echo htmlspecialchars($rows['complainno']); ?></p>
                    <p><strong>Call Register Date & Time:</strong> <?php echo htmlspecialchars($rows['callregtimings']); ?></p>
                    <p><strong>Branch Name:</strong> <?php echo htmlspecialchars($rows['branchname']); ?></p>
                    <p><strong>Bank Name:</strong> <?php echo htmlspecialchars($rows['bnkname']); ?></p>
                    <p><strong>ATM ID:</strong> <?php echo htmlspecialchars($rows['atmid']); ?></p>
                    <p><strong>Custodian No:</strong> <?php echo htmlspecialchars($rows['custnumber']); ?></p>
                    <p><strong>Type of Call:</strong> <?php echo htmlspecialchars($rows['calltype']); ?></p>
                    <p><strong>Warranty status:</strong> <?php echo htmlspecialchars($rows['warrantystatus']); ?></p>
                    <p><strong>Approval Pending (Bank End):</strong> <?php echo htmlspecialchars($rows['approvalpending']); ?></p>
                    <?php if (!empty($rows['bankendtext'])): ?>
                    <p><strong>Bank End details:</strong> <?php echo htmlspecialchars($rows['bankendtext']); ?></p>
                    <?php endif; ?>
                    <p><strong>Preventive Maintenance:</strong> <?php echo htmlspecialchars($rows['prevmaintain']); ?></p>
                    <p><strong>Stationary Delivered:</strong> <?php echo htmlspecialchars($rows['stationarydelvrd']); ?></p> 
                    <p><strong>Error Code:</strong> <?php echo htmlspecialchars($rows['error']); ?></p>              
                    <p><strong>Issues:</strong> <?php echo htmlspecialchars($rows['issues']); ?></p>
                    <p><strong>Stationary Required:</strong> <?php echo htmlspecialchars($rows['stationaryreq']); ?></p>
                    <p><strong>Atm Status:</strong> <?php echo htmlspecialchars($rows['atmworking']); ?></p>
                    <p><strong>Call Registered Through:</strong> <?php echo htmlspecialchars($rows['callregthr']); ?></p>
                    <p><strong>Call Timings:</strong> <?php echo htmlspecialchars($rows['calltimings']); ?></p>
                    <p><strong>Call Registered with OEM:</strong> <?php echo htmlspecialchars($rows['callregoememail']); ?></p>
                    <p><strong>Call Registered with Digitech:</strong> <?php echo htmlspecialchars($rows['callregdsi']); ?></p>
                    <p><strong>Date and Time:</strong> <?php echo htmlspecialchars($rows['datetimee']); ?></p>
                    <p><strong>Resolved by:</strong> <?php echo htmlspecialchars($rows['resolby']); ?></p>
                    <p><strong>Part Demand With OEM:</strong> <?php echo htmlspecialchars($rows['demandoem']); ?></p>
                    <p><strong>Name of Part:</strong> <?php echo htmlspecialchars($rows['partnm']); ?></p>
                    <p><strong>Name of OEM Engineer:</strong> <?php echo htmlspecialchars($rows['oemeng']); ?></p>
                    <p><strong>Name of Digitech Engineer:</strong> <?php echo htmlspecialchars($rows['digieng']); ?></p>
                    <p><strong>Time of Close:</strong> <?php echo htmlspecialchars($rows['timeofclos']); ?></p>

                    <?php
                    $svr_documents = explode(',', $rows['svr']);
                    if (!empty($svr_documents)) {
                        echo '<p><strong>SCR Documents:</strong></p>';
                        foreach ($svr_documents as $document) {
                            echo '<div><a href="' . htmlspecialchars($document) . '" target="_blank">' . htmlspecialchars(basename($document)) . '</a></div>';
                        }
                    }
                    ?>

                    <p><strong>Downtime:</strong> <?php echo htmlspecialchars($rows['downntym']); ?></p>
                    <p><strong>Excess Downtime:</strong> <?php echo htmlspecialchars($rows['calllogtym']); ?></p>
                    <p><strong>Customer Information through:</strong> <?php echo htmlspecialchars($rows['email']); ?></p>
                    <p><strong>Closure Date and Time:</strong> <?php echo htmlspecialchars($rows['warrantydatetym']); ?></p>
                    <p><strong>Remarks if any:</strong> <?php echo htmlspecialchars($rows['remarksifany']); ?></p>
                </div>
            </div>
        <?php } ?>



                      
                    </tbody>
                </table>

                <!-- Cards to display records -->
                
            </div>
        </div>
    </div>

       <script>
        $(document).ready(function() {
            $('#hiddenTable').DataTable();

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

       #hiddenTable {
           display: none;
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
