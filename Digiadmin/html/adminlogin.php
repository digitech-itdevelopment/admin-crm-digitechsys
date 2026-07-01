<!DOCTYPE html>
<html dir="ltr" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- Tell the browser to be responsive to screen width -->
    
    <title>Admin Login</title>
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
      
     
       
        <div class="container shadow-lg mt-3">
          <div class="row justify-content-center pt-5">
              <div class="col-md-6   rounded-5">
  
                  <div class="row">
                      <div class="col-md-12 text-center">
                          <img src="../images/digilogo.png">
                      </div><!--column ends-->
                  </div><!--row ends-->
                  <h1 class="text-center">Admin Login</h1>
  
               
                  <?php
                  
                  session_start();
                   require_once 'config.php';
                      $conn = getDbConnection();
                 
                  if (isset($_SESSION['error'])) {
                      echo '<div class="alert alert-danger" role="alert">' . $_SESSION['error'] . '</div>';
                      unset($_SESSION['error']);
                  }
                   ?>
  
                  <form name="loginForm" action="adminlogindb.php" method="POST" onsubmit="return validateForm()">
                      <div class="form-group">
                          <label for="username">Enter Username:</label>
                          <input type="text" name="username" class="form-control" id="username">
                      </div>
                      <div class="form-group mt-2">
                          <label for="password">Enter Password:</label>
                          <input type="password" name="password" class="form-control" id="password">
                      </div>
                      <div class="form-group  mt-1 pb-5">
                          <input id="loginButton" name="submit" type="submit" class="btn btn-lg btn-warning mybtn"
                              value="Login">
                      </div>
  
                      <div class="loader form-group  mt-1 pb-5 text-center">
                          <img src="../images/Dual.gif" alt="Loading...">
                      </div>
                  </form>
              </div>
          </div>
      </div>
  
       



       
  <footer class="footer text-center">
          All Rights Reserved By.
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