<?php
session_start();
require_once 'config.php';
$conn = getDbConnection();

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to select data from database
$sql = "SELECT dateandtime, cardreaders, cassettes, shuttercables, hooddoorlocks, 
softkeys, divertcassettes, belts, rollers, cpufans, clamps, ddus FROM faultyparts";

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
              <h3>Faulty Stock Record View</h3>
            </div>
          </div>

          <table>
            <thead style="font-size:20px;">
              <tr>
               <th>As on Dated</th>
                <th>Card Readers</th>
                <th>Cassettes</th>
                <th>Shutter Cables</th>
                <th>Hood Door Locks</th>
                <th>Soft Keys</th>
                <th>Divert Casettes</th>
                <th>Belts</th>
                <th>Rollers</th>
                <th>CPU Fan's</th>
                <th>Clamps</th>
                <th>DDU's</th>
              </tr>
            </thead>
            <tbody style="font-size:20px;">
              <?php
              $result->data_seek(0); // Reset result set pointer to the beginning
              while ($rows = $result->fetch_assoc()) {
              ?>
              <tr>
              <td><?php echo htmlspecialchars($rows['dateandtime']); ?></td>
                <td><?php echo htmlspecialchars($rows['cardreaders']); ?></td>
                <td><?php echo htmlspecialchars($rows['cassettes']); ?></td>
                <td><?php echo htmlspecialchars($rows['shuttercables']); ?></td>
                <td><?php echo htmlspecialchars($rows['hooddoorlocks']); ?></td>
                <td><?php echo htmlspecialchars($rows['softkeys']); ?></td>
                <td><?php echo htmlspecialchars($rows['divertcassettes']); ?></td>
                <td><?php echo htmlspecialchars($rows['belts']); ?></td>
                <td><?php echo htmlspecialchars($rows['rollers']); ?></td>
                <td><?php echo htmlspecialchars($rows['cpufans']); ?></td>
                <td><?php echo htmlspecialchars($rows['clamps']); ?></td>
                <td><?php echo htmlspecialchars($rows['ddus']); ?></td>
              </tr>
              <?php } ?>
            </tbody>
          </table>

        
        </div>

        <footer class="footer text-center">
          All Rights Reserved By: <a href="https://www.digitechsys.net">Digitech Systems & Infocom Pvt. Ltd.</a>.
        </footer>
      </div>
    </div>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../assets/extra-libs/sparkline/sparkline.js"></script>
    <script src="../dist/js/waves.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
  </body>
</html>
