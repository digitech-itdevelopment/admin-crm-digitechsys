<?php
session_start();
if (!isset($_SESSION['userdata'])) { header("Location: adminlogin.php"); exit; }
require_once 'config.php';
$conn = getDbConnection();
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Map a posted list name to its table (whitelist — never interpolate raw input).
function inv_table($v) { return ($v === 'refurbished') ? 'inventory_refurbished' : 'inventory'; }

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_qty'])) {
    $id = (int)$_POST['id']; $qty = (int)$_POST['quantity']; $t = inv_table($_POST['list'] ?? 'new');
    try { $stmt = $conn->prepare("UPDATE $t SET quantity=? WHERE id=?"); $stmt->bind_param("ii",$qty,$id); $stmt->execute(); }
    catch (\Throwable $e) { error_log("inventory update failed: ".$e->getMessage()); }
    header("Location: viewinventorystatus.php"); exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_part'])) {
    $name = trim($_POST['part_name'] ?? ''); $key = strtolower(preg_replace('/[^A-Za-z0-9]/','',$name));
    $qty = (int)($_POST['quantity'] ?? 0); $t = inv_table($_POST['list'] ?? 'new');
    if ($name !== '' && $key !== '') {
        try {
            $stmt = $conn->prepare("INSERT IGNORE INTO $t (part_key, part_name, quantity) VALUES (?,?,?)");
            $stmt->bind_param("ssi",$key,$name,$qty); $stmt->execute();
            $_SESSION['inv_notice'] = ($stmt->affected_rows === 0) ? "A part with a similar name already exists." : "Part added.";
        } catch (\Throwable $e) { error_log("inventory add failed: ".$e->getMessage()); }
    }
    header("Location: viewinventorystatus.php"); exit();
}
$notice = $_SESSION['inv_notice'] ?? ''; unset($_SESSION['inv_notice']);
try {
    $resultNew = $conn->query("SELECT id, part_key, part_name, quantity FROM inventory ORDER BY part_name ASC");
    $resultRefurb = $conn->query("SELECT id, part_key, part_name, quantity FROM inventory_refurbished ORDER BY part_name ASC");
} catch (\Throwable $e) { die("Inventory table unavailable. Run the inventory migration first."); }
if (!$resultNew || !$resultRefurb) { die("Query failed: " . $conn->error); }
?>


<!DOCTYPE html>
<html dir="ltr" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Digitech User Details</title>
    <link rel="stylesheet" href="./css/bootstrap.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <meta name="robots" content="noindex,nofollow" />
    <!-- Favicon icon -->
    <link
      rel="icon"
      type="image/png"
      sizes="16x16"
      href="../assets/images/favicon.png"
    />
    <!-- Custom CSS -->
    <link href="../assets/libs/flot/css/float-chart.css" rel="stylesheet" />
    <!-- Custom CSS -->
    <link href="../dist/css/style.min.css" rel="stylesheet" />
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>
    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
    <div class="preloader">
      <div class="lds-ripple">
        <div class="lds-pos"></div>
        <div class="lds-pos"></div>
      </div>
    </div>
    <!-- ============================================================== -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <div
      id="main-wrapper"
      data-layout="vertical"
      data-navbarbg="skin5"
      data-sidebartype="full"
      data-sidebar-position="absolute"
      data-header-position="absolute"
      data-boxed-layout="full"
    >
      <!-- ============================================================== -->
      <!-- Topbar header - style you can find in pages.scss -->
      <!-- ============================================================== -->
      <header class="topbar" data-navbarbg="skin5">
        <nav class="navbar top-navbar navbar-expand-md navbar-dark">
          <div class="navbar-header" data-logobg="skin5">
            <!-- ============================================================== -->
            <!-- Logo -->
            <!-- ============================================================== -->
            <a class="navbar-brand" href="index.html">
              <!-- Logo icon -->
              <b class="logo-icon ps-2">
                <!--You can put here icon as well // <i class="wi wi-sunset"></i> //-->
                <!-- Dark Logo icon -->
                <img
                  src="../assets/images/logo-icon.png"
                  alt="homepage"
                  class="light-logo"
                  width="25"
                />
              </b>
              <!--End Logo icon -->
              <!-- Logo text -->
              <span class="pt-3">
                <!-- dark Logo text -->
              <h6>Digitech Systems</h6>
              </span>
              <!-- Logo icon -->
              <!-- <b class="logo-icon"> -->
              <!--You can put here icon as well // <i class="wi wi-sunset"></i> //-->
              <!-- Dark Logo icon -->
              <!-- <img src="../assets/images/logo-text.png" alt="homepage" class="light-logo" /> -->

              <!-- </b> -->
              <!--End Logo icon -->
            </a>
            <!-- ============================================================== -->
            <!-- End Logo -->
            <!-- ============================================================== -->
            <!-- ============================================================== -->
            <!-- Toggle which is visible on mobile only -->
            <!-- ============================================================== -->
            <a
              class="nav-toggler waves-effect waves-light d-block d-md-none"
              href="javascript:void(0)"
              ><i class="ti-menu ti-close"></i
            ></a>
          </div>
          <!-- ============================================================== -->
          <!-- End Logo -->
          <!-- ============================================================== -->
          <div
            class="navbar-collapse collapse"
            id="navbarSupportedContent"
            data-navbarbg="skin5"
          >
            <!-- ============================================================== -->
            <!-- toggle and nav items -->
            <!-- ============================================================== -->
            <ul class="navbar-nav float-start me-auto">
              <li class="nav-item d-none d-lg-block">
                <a
                  class="nav-link sidebartoggler waves-effect waves-light"
                  href="javascript:void(0)"
                  data-sidebartype="mini-sidebar"
                  ><i class="mdi mdi-menu font-24"></i
                ></a>
              </li>
              <!-- ============================================================== -->
              <!-- create new -->
              <!-- ============================================================== -->
          
              <!-- ============================================================== -->
              <!-- Search -->
              <!-- ============================================================== -->
              <li class="nav-item search-box">
                <a
                  class="nav-link waves-effect waves-dark"
                  href="javascript:void(0)"
                  ><i class="mdi mdi-magnify fs-4"></i
                ></a>
                <form class="app-search position-absolute">
                  <input
                    type="text"
                    class="form-control"
                    placeholder="Search &amp; enter"
                  />
                  <a class="srh-btn"><i class="mdi mdi-window-close"></i></a>
                </form>
              </li>
            </ul>
            <!-- ============================================================== -->
            <!-- Right side toggle and nav items -->
            <!-- ============================================================== -->
            <ul class="navbar-nav float-end">
              <!-- ============================================================== -->
              <!-- Comment -->
              <!-- ============================================================== -->
              <li class="nav-item dropdown">
                <a
                  class="nav-link dropdown-toggle"
                  href="#"
                  id="navbarDropdown"
                  role="button"
                  data-bs-toggle="dropdown"
                  aria-expanded="false"
                >
                  <i class="mdi mdi-bell font-24"></i>
                </a>
                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                  <li><a class="dropdown-item" href="#">Action</a></li>
                  <li><a class="dropdown-item" href="#">Another action</a></li>
                  <li><hr class="dropdown-divider" /></li>
                  <li>
                    <a class="dropdown-item" href="#">Something else here</a>
                  </li>
                </ul>
              </li>
              <!-- ============================================================== -->
              <!-- End Comment -->
              <!-- ============================================================== -->
              <!-- ============================================================== -->
              <!-- Messages -->
              <!-- ============================================================== -->
              <li class="nav-item dropdown">
                <a
                  class="nav-link dropdown-toggle waves-effect waves-dark"
                  href="#"
                  id="2"
                  role="button"
                  data-bs-toggle="dropdown"
                  aria-expanded="false"
                >
                  <i class="font-24 mdi mdi-comment-processing"></i>
                </a>
                <ul
                  class="
                    dropdown-menu dropdown-menu-end
                    mailbox
                    animated
                    bounceInDown
                  "
                  aria-labelledby="2"
                >
                  <ul class="list-style-none">
                    <li>
                      <div class="">
                        <!-- Message -->
                        <a href="javascript:void(0)" class="link border-top">
                          <div class="d-flex no-block align-items-center p-10">
                            <span
                              class="
                                btn btn-success btn-circle
                                d-flex
                                align-items-center
                                justify-content-center
                              "
                              ><i class="mdi mdi-calendar text-white fs-4"></i
                            ></span>
                            <div class="ms-2">
                              <h5 class="mb-0">Event today</h5>
                              <span class="mail-desc"
                                >Just a reminder that event</span
                              >
                            </div>
                          </div>
                        </a>
                        <!-- Message -->
                        <a href="javascript:void(0)" class="link border-top">
                          <div class="d-flex no-block align-items-center p-10">
                            <span
                              class="
                                btn btn-info btn-circle
                                d-flex
                                align-items-center
                                justify-content-center
                              "
                              ><i class="mdi mdi-settings fs-4"></i
                            ></span>
                            <div class="ms-2">
                              <h5 class="mb-0">Settings</h5>
                              <span class="mail-desc"
                                >You can customize this template</span
                              >
                            </div>
                          </div>
                        </a>
                        <!-- Message -->
                        <a href="javascript:void(0)" class="link border-top">
                          <div class="d-flex no-block align-items-center p-10">
                            <span
                              class="
                                btn btn-primary btn-circle
                                d-flex
                                align-items-center
                                justify-content-center
                              "
                              ><i class="mdi mdi-account fs-4"></i
                            ></span>
                            <div class="ms-2">
                              <h5 class="mb-0">Pavan kumar</h5>
                              <span class="mail-desc"
                                >Just see the my admin!</span
                              >
                            </div>
                          </div>
                        </a>
                        <!-- Message -->
                        <a href="javascript:void(0)" class="link border-top">
                          <div class="d-flex no-block align-items-center p-10">
                            <span
                              class="
                                btn btn-danger btn-circle
                                d-flex
                                align-items-center
                                justify-content-center
                              "
                              ><i class="mdi mdi-link fs-4"></i
                            ></span>
                            <div class="ms-2">
                              <h5 class="mb-0">Luanch Admin</h5>
                              <span class="mail-desc"
                                >Just see the my new admin!</span
                              >
                            </div>
                          </div>
                        </a>
                      </div>
                    </li>
                  </ul>
                </ul>
              </li>
              <!-- ============================================================== -->
              <!-- End Messages -->
              <!-- ============================================================== -->

              <!-- ============================================================== -->
              <!-- User profile and search -->
              <!-- ============================================================== -->
              <li class="nav-item dropdown">
                <a
                  class="
                    nav-link
                    dropdown-toggle
                    text-muted
                    waves-effect waves-dark
                    pro-pic
                  "
                  href="#"
                  id="navbarDropdown"
                  role="button"
                  data-bs-toggle="dropdown"
                  aria-expanded="false"
                >
                  <img
                    src="../assets/images/users/1.jpg"
                    alt="user"
                    class="rounded-circle"
                    width="31"
                  />
                </a>
                <ul
                  class="dropdown-menu dropdown-menu-end user-dd animated"
                  aria-labelledby="navbarDropdown"
                >
                  
                
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item" href="logout.php"
                    ><i class="fa fa-power-off me-1 ms-1"></i> Logout</a
                  >
                  
                </ul>
              </li>
              <!-- ============================================================== -->
              <!-- User profile and search -->
              <!-- ============================================================== -->
            </ul>
          </div>
        </nav>
      </header>
      <!-- ============================================================== -->
      <!-- End Topbar header -->
      <!-- ============================================================== -->
      <!-- ============================================================== -->
      <!-- Left Sidebar - style you can find in sidebar.scss  -->
      <!-- ============================================================== -->
      <aside class="left-sidebar" data-sidebarbg="skin5">
        <!-- Sidebar scroll-->
        <div class="scroll-sidebar">
          <!-- Sidebar navigation-->
          <nav class="sidebar-nav">
            <ul id="sidebarnav" class="pt-4">
              <li class="sidebar-item">
                <a
                  class="sidebar-link waves-effect waves-dark sidebar-link"
                  href="index.html"
                  aria-expanded="false"
                  ><i class="mdi mdi-view-dashboard"></i
                  ><span class="hide-menu">Dashboard</span></a
                >
              </li>
             
            
            
             
            </ul>
          </nav>
          <!-- End Sidebar navigation -->
        </div>
        <!-- End Sidebar scroll-->
      </aside>
      <!-- ============================================================== -->
      <!-- End Left Sidebar - style you can find in sidebar.scss  -->
      <!-- ============================================================== -->
      <!-- ============================================================== -->
      <!-- Page wrapper  -->
      <!-- ============================================================== -->
      <div class="page-wrapper bg-white">
        <!-- ============================================================== -->
        <!-- Bread crumb and right sidebar toggle -->
        <!-- ============================================================== -->

        <div class="container">
        <div class="row">
            <div class="text-md-center text-danger col-md-12">
            <img src="../images/stock.png" width="90" height="60" />
            <h3>Digitech Online Stock Inventory Record</h3>
            </div>
        </div>

        <div class="row justify-content-center">

        <div class="col-md-10">

<div class="container" style="margin-top:20px;">
  <?php if (!empty($notice)): ?><div class="alert alert-info"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?>

  <h4 class="text-danger">New Parts</h4>
  <div style="overflow-x:auto;">
  <table class="table table-bordered">
    <thead><tr><th>Part Name</th><th style="width:230px;">Quantity</th></tr></thead>
    <tbody>
    <?php while ($row = $resultNew->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($row['part_name']); ?></td>
        <td>
          <form method="post" action="viewinventorystatus.php" style="margin:0; display:flex; gap:6px; align-items:center;">
            <input type="hidden" name="list" value="new">
            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
            <input type="number" name="quantity" value="<?php echo (int)$row['quantity']; ?>" class="form-control" min="0" style="max-width:120px;">
            <button type="submit" name="update_qty" class="btn btn-primary">Save</button>
          </form>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  </div>
  <form method="post" action="viewinventorystatus.php" class="row g-2 mb-5">
    <input type="hidden" name="list" value="new">
    <div class="col-md-6"><input type="text" name="part_name" class="form-control" placeholder="New part name" required></div>
    <div class="col-md-3"><input type="number" name="quantity" class="form-control" value="0" min="0"></div>
    <div class="col-md-3"><button type="submit" name="add_part" class="btn btn-success">Add New Part</button></div>
  </form>

  <h4 class="text-danger mt-4">Refurbished Parts</h4>
  <div style="overflow-x:auto;">
  <table class="table table-bordered">
    <thead><tr><th>Part Name</th><th style="width:230px;">Quantity</th></tr></thead>
    <tbody>
    <?php while ($row = $resultRefurb->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($row['part_name']); ?></td>
        <td>
          <form method="post" action="viewinventorystatus.php" style="margin:0; display:flex; gap:6px; align-items:center;">
            <input type="hidden" name="list" value="refurbished">
            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
            <input type="number" name="quantity" value="<?php echo (int)$row['quantity']; ?>" class="form-control" min="0" style="max-width:120px;">
            <button type="submit" name="update_qty" class="btn btn-primary">Save</button>
          </form>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  </div>
  <form method="post" action="viewinventorystatus.php" class="row g-2">
    <input type="hidden" name="list" value="refurbished">
    <div class="col-md-6"><input type="text" name="part_name" class="form-control" placeholder="Refurbished part name" required></div>
    <div class="col-md-3"><input type="number" name="quantity" class="form-control" value="0" min="0"></div>
    <div class="col-md-3"><button type="submit" name="add_part" class="btn btn-success">Add Refurbished Part</button></div>
  </form>
</div>

        </div>

        </div>

       
    </div>

       

  </div>

       
  <footer class="footer text-center">
          All Rights Reserved by Digitech Systems & Infocom Pvt. Ltd.
              <a href="https://www.digitechsys.net">Digitech Systems & Infocom Pvt. Ltd.</a>.
        </footer>
        <!-- ============================================================== -->
        <!-- End footer -->
        <!-- ============================================================== -->
      </div>
     
    </div>
   
    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap tether Core JavaScript -->
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../assets/extra-libs/sparkline/sparkline.js"></script>
    <!--Wave Effects -->
    <script src="../dist/js/waves.js"></script>
    <!--Menu sidebar -->
    <script src="../dist/js/sidebarmenu.js"></script>
    <!--Custom JavaScript -->
    <script src="../dist/js/custom.min.js"></script>
    <!--This page JavaScript -->
    <!-- <script src="../dist/js/pages/dashboards/dashboard1.js"></script> -->
    <!-- Charts js Files -->
    
  </body>
</html>

<style type="text/css">
  .bglogin {

      background-image: url('./images/login-bg.png');
      background-size: 100% 100%;
      height: 100vh;
  }

  .loader {

      left: 0;
      top: 0;
      width: 100%;
      height: 100%;

      opacity: 2;
      display: none;
      /* Hide loader by default */
      z-index: 9999;
  }

  .loader img {

      width: 10%;
      height: 10%;
      transform: translate(-50%, -50%);
  }
</style>

<script>
  function validateForm() {
      var username = document.forms["loginForm"]["username"].value;
      var password = document.forms["loginForm"]["password"].value;
      if (username == "") {
          alert("Username must be filled out");
          return false;
      }
      if (password == "") {
          alert("Password must be filled out");
          return false;
      }
      return true;
  }
</script>


<script>
  $(document).ready(function () {
      $('#loginButton').click(function () {
          // Show loader when button is clicked
          $('.loader').fadeIn('slow');

          // Simulate delay (replace this with your actual login process)
          setTimeout(function () {
              // After some delay, hide loader (replace with actual success/failure handling)
              $('.loader').fadeOut('slow');
          }, 2000); // Example: Simulating a 2-second delay for demo purposes

       
      });
  });
</script>