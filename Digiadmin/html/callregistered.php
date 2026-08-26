<?php session_start();
require_once 'config.php';
$conn = getDbConnection();

function getAndUpdateTicketCounter() {
    $counterFile = 'ticket_counter.txt';

    // Read current counter from file or initialize to 0
    $currentCounter = (file_exists($counterFile)) ? intval(file_get_contents($counterFile)) : 0;

    // Increment counter
    $currentCounter++;

    // Store updated counter back into file
    file_put_contents($counterFile, $currentCounter);

    return str_pad($currentCounter, 6, '0', STR_PAD_LEFT); // Format as 6-digit with leading zeros
}

function getAndUpdateComplaintCounter() {
    $counterFile = 'complaint_counter.txt';
    $current = (file_exists($counterFile)) ? intval(file_get_contents($counterFile)) : 9999;
    $current++;
    file_put_contents($counterFile, $current);
    return $current;
}

// Get or update ticket counter
$ticketNumber = getAndUpdateTicketCounter();
$complaintNumber = getAndUpdateComplaintCounter();

// Store the generated ticket number in session for further processing
$_SESSION['generated_ticket_number'] = $ticketNumber;

?>




<!DOCTYPE html>
<html dir="ltr" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Digitech User Details</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <meta name="robots" content="noindex,nofollow" />
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/images/favicon.png" />
    <link href="../assets/libs/flot/css/float-chart.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="../css/bootstrap.min.css" />

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
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

 <script>
        // function validateForm() {
        //     var custodianNo = document.getElementById("txtcustodianno").value;

        //     // Regular expression pattern for Indian mobile numbers and 11-digit landline numbers
        //     var pattern = /^(?:\d{10}|\d{3}-\d{8}|\d{4}-\d{7}|0\d{10})$/;

        //     if (!pattern.test(custodianNo)) {
        //         alert("Please enter a valid Indian mobile number or an 11-digit landline number starting with 0.");
        //         return false;
        //     }
        //     return true;
        // }

        function calculateDowntime() {
            var callTime = new Date(document.getElementById("txtDateTime1").value);
            var resolutionTime = new Date(document.getElementById("txtDateTime3").value);
            var typeofcall = document.getElementById("typeofcall").value; // Get the value of Type of Call select element

            if (!isNaN(callTime.getTime()) && !isNaN(resolutionTime.getTime())) {
                var downtimeHours = (resolutionTime - callTime) / (1000 * 60 * 60); // Calculate downtime in hours

                // Round downtime hours to two decimal places
                downtimeHours = downtimeHours.toFixed(2);

                // Set value of txtDateTime4 to show only hours
                document.getElementById("txtDateTime4").value = downtimeHours;

                // Check if downtime exceeds 48 hours or 72 hours for UPS calls
                if (downtimeHours > 48) {
                    if (typeofcall === "UPS" && downtimeHours > 72) {
                        var excessHours = downtimeHours - 72;
                        document.getElementById("txtExcessDowntime").value = excessHours.toFixed(2);
                        document.getElementById("txtExcessDowntime").style.display = "block"; // Show the excess downtime textbox
                    } else {
                        var excessHours = downtimeHours - 48;
                        document.getElementById("txtExcessDowntime").value = excessHours.toFixed(2);
                        document.getElementById("txtExcessDowntime").style.display = "block"; // Show the excess downtime textbox
                    }
                } else {
                    document.getElementById("txtExcessDowntime").value = ""; // Clear the excess downtime textbox
                    document.getElementById("txtExcessDowntime").style.display = "none"; // Hide the excess downtime textbox
                }
            }
        }

    </script>


    <script>
        function handleWarrantyStatusChange() {
            var warrantyStatus = document.getElementById("warrantystatusoem").value;
            var callRegWithDSISection = document.getElementById("callRegWithDSISection");
            var complaintNumberRow = document.getElementById("complaintNumberRow");


            if (warrantyStatus === "Under-Warranty") {
                complaintNumberRow.style.display = "block"; // Show the Complaint Number field
            } else {
                complaintNumberRow.style.display = "none"; // Hide the Complaint Number field
            }


            // Check if warranty status is Non-Warranty
            if (warrantyStatus === "Non-Warranty") {
                // Show the Call registered with DSI section
                callRegWithDSISection.style.display = "block";
                document.getElementById("callregwithoemtc").parentNode.style.display = "none";

            }
            else {
                // Hide the Call registered with DSI section
                callRegWithDSISection.style.display = "none";
                document.getElementById("callregwithoemtc").parentNode.style.display = "block";

            }




            // Disable/enable the Call registered with OEM checkbox based on the warranty status
            var callRegCheckbox = document.getElementById("callregwithoemtc");
            if (warrantyStatus === "Non-Warranty") {
                callRegCheckbox.disabled = true; // Disable the checkbox
                callRegCheckbox.checked = false; // Ensure it's unchecked if disabled
            } else {
                callRegCheckbox.disabled = false; // Enable the checkbox
            }
        }




        jQuery(document).ready(function ($) {
            $('#typeofcall').on('change', function (e) {
                var className = e.target.value;

                // Reset the selection and visibility of options in #warrantystatusoem
                $('#warrantystatusoem').val(''); // Reset selected value

                // Hide all options in #warrantystatusoem
                $('#warrantystatusoem option').hide();

                // Show options based on the selected className
                $('#warrantystatusoem').find('option.' + className).show();

                // Show #warrantystatusoem if it was hidden
                $('#warrantystatusoem').show();
            });
        });

    </script>



    <style type="text/css">
        .successmsg {
            height: 41px;
            background-color: darkgreen;
            padding-top: 6px;
            color: white;

        }

        .error-message {
            animation: fadeOut 2s forwards;
            animation-delay: 5s;
            background: darkgreen;
            color: white;
            text-align: center;
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }
    </style>
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
      

     
        



    <form id="userfrm" method="post" action="userdetails.php" enctype="multipart/form-data"
        onsubmit="return validateForm()">

        <div class="container">
            <div class="row vertical-center d-flex">

                <div class="col-md-8 shadow-sm">

                    <div style="text-align: center;">
                        <img src="../images/icon.png" width="100" height="80" />


                    </div>
                    <h4 style="text-align: center">Call Registeration</h4>


                    <div class="row py-3">
                        <div class="col-md-12">
                            <label>Ticket No:</label>
                        </div>
                        <div class="col-md-12">
                            <!-- Display the generated ticket number here -->



                            <input type="text" name="ticketnumber" class="form-control rounded rounded-5" value="<?php echo $ticketNumber; ?>" readonly>

                        </div>
                    </div>
                    <!--row ends-->

                    <div class="row py-3" id="complaintNumberRow" style="display: none;">
                        <div class="col-md-12">
                            <label>Complaint Number</label>
                        </div>
                        <div class="col-md-12">
                            <input type="text" id="txtcomplaintno" name="txtcomplaintno"
                                class="form-control rounded rounded-5" value="<?php echo $complaintNumber; ?>" readonly>
                        </div>
                    </div> <!--row ends-->




                    <div class="row py-3">
                        <div class="col-md-12">
                            <label>Call Register Date / Time:</label>
                            <input type="datetime-local" name="txtDateTimeregcall" id="txtDateTimeregcall"
                                class="form-control rounded rounded-5">
                        </div>
                    </div>
                    <!--row ends-->

                    <div class="row py-3">
                        <div class="col-md-12"><label>Call Registered through:</label></div>
                        <div class="col-md-12">
                            <select ID="callregthrough" name="callregthrough" class="form-control rounded rounded-5">
                                <option value="Not Applicable">Please Select</option>
                                <option value="Email">Email</option>
                                <option value="Telecalling">Telecalling</option>
                            </select>
                        </div>
                    </div>
                    <!--row ends-->



                    <div class="row py-3">

                        <div class="col-md-12">

                            <label>Bank Name</label>

                        </div>

                        <div class="col-md-12">
                            <select id="bankname" name="bankname" class="form-control rounded rounded-5">
                                <option value="KCCB">KCCB</options>
                                <option value="HPSCB">HPSCB</options>
                                <option value="JCCB">JCCB</options>
                                <option value="JKSCB">JKSCB</options>
                                <option value="CSFB">CSFB</options>
                            </select>
                        </div><!--ends col-->




                    </div>
                    <!--row ends-->







                    <div class="row py-3">

                        <div class="col-md-12">
                            <label>ATM ID:</label>

                        </div>


                        <div class="col-md-12">

                            <input type="text" name="atmid" class="form-control rounded rounded-5">
                        </div>


                        <div class="col-md-12">
                        </div>



                    </div>
                    <!--row ends-->


                    <div class="row">

                        <div class="col-md-12">
                            <label>Branch Name</label>

                        </div>


                        <div class="col-md-12">

                            <input type="text" name="txtbranchname" class="form-control rounded rounded-5">
                        </div>


                        <div class="col-md-12">
                        </div>



                    </div>
                    <!--row ends-->

                    <div class="row py-3">
                        <div class="col-md-12">

                            <label>Custodian Number</label>

                        </div>

                        <div class="col-md-12">


                            <input type="text" id="txtcustodianno" name="txtcustodianno"
                                class="form-control rounded rounded-5">

                            <span id="custodianError" style="color: red; display: none;">Please enter a valid 10-digit
                                Custodian number starting with 6-9.</span>

                        </div>


                    </div>
                    <!--row ends-->







                    <div class="row py-3">

                        <div class="col-md-12">

                            <label>Type Of Call</label>

                        </div>

                        <div class="col-md-12">


                            <select id="typeofcall" name="typeofcall" class="form-control rounded rounded-5">
                                <option value="Not Applicable">Please Select</option>
                                <option value="AMC">AMC</option>
                                <option value="BLA">BLA</option>
                                <option value="Non-AMC">Non-AMC</option>
                                <option value="UPS">UPS</option>
                                <option value="Stationary">Stationary</option>
                            </select>
                        </div>
                     </div>
                    <!--row ends-->




                    <div class="row py-3">

                        <div class="col-md-12">


                            <label>Warranty status with OEM:</label>



                        </div>

                        <div class="col-md-12">





                            <select ID="warrantystatusoem" name="warrantystatusoem"
                                class="form-control rounded rounded-5" onchange="handleWarrantyStatusChange()">
                                <option value="Not Applicable">Please Select</option>
                                <option class="BLA Non-AMC UPS" value="Under-Warranty">Under-Warranty</option>
                                <option class="BLA Non-AMC UPS" value="Non-Warranty">Non-Warranty</option>
                                <option class="AMC" value="Dibold">Diebold</option>
                             <option class="AMC" value="dsi">DSI</option>
                            </select>




                        </div>




                    </div>
                    <!--row ends-->

                    <div class="row py-3">
                        <div class="col-md-12"><label>Part Demand:</label></div>
                        <div class="col-md-12">
                            <select id="partdemand" name="partdemand" class="form-control rounded rounded-5">
                                <option value="">Please Select</option>
                                <option value="OEM">OEM</option>
                                <option value="DSI">DSI</option>
                            </select>
                        </div>
                    </div>
                    <!--row ends-->




                     <div class="row py-3">

<div class="col-md-12">
    <label>Error Code:</label>

</div>


<div class="col-md-12">

    <input type="text" name="errorcode" class="form-control rounded rounded-5">
</div>


<div class="col-md-12">
</div>



</div>
<!--row ends-->

                    
                    
                    
                    
                    


                    <div class="row py-3">

                        <div class="col-md-12">
                            <label>Issues:</label>

                        </div>


                        <div class="col-md-12">

                            <input type="text" name="issuestxt" class="form-control rounded rounded-5">
                        </div>


                        <div class="col-md-12">
                        </div>



                    </div>
                    <!--row ends-->




                    <div class="row py-3">
                        <div class="col-md-12">
                            <label>Stationary Required:</label>
                        </div>

                        <div class="col-md-12">
                           <select id="stationaryreq" name="stationaryreq[]" class="form-control rounded rounded-5 overflow-y-scroll h-75" multiple="multiple">
                                <option value="Not Applicable">Please Select</options>
                                <option value="Journal Printer">Journal Printer</options>
                                <option value="Receipt Printer">Receipt Printer</options>
                                <option value="JP Ribbon">JP Ribbon</options>
                            </select>
                        </div><!--ends col-->
                    </div>
                    <!--row ends-->




                    <!-- Approval Pending (BANK END) (added) -->
                    <div class="row py-3">
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" name="approvalpending" type="checkbox" id="approvalPendingSwitch" onchange="toggleApprovalText()">
                                <label class="form-check-label" for="approvalPendingSwitch">Approval Pending (BANK END)</label>
                            </div>
                        </div>
                    </div>
                    <!--row ends-->

                    <div class="row py-3" id="bankEndTextRow" style="display:none;">
                        <div class="col-md-12">
                            <input type="text" name="bankendtext" id="bankendtext" class="form-control rounded rounded-5">
                        </div>
                    </div>
                    <!--row ends-->

                    <script>
                    function toggleApprovalText() {
                        var on = document.getElementById('approvalPendingSwitch').checked;
                        document.getElementById('bankEndTextRow').style.display = on ? '' : 'none';
                        if (!on) { document.getElementById('bankendtext').value = ''; }
                    }
                    </script>

                    <div class="row py-3">
                        <div class="col-md-12">

                            <div class="form-check form-switch">
                                <input class="form-check-input" name="atmstatus" type="checkbox"
                                    id="flexSwitchCheckDefault">
                                <label class="form-check-label" for="flexSwitchCheckDefault">ATM was not Working</label>
                            </div>
                        </div>
                    </div> <!--row ends-->

                    <div class="row text-center">



                        <div class="col-md-12">
                            <input id="btn_submit" class="mybtn" type="submit" value="Submit">


                        </div>

                        <?php
                        if (isset($_SESSION['success_message'])) {
                            echo '<div  class="successmsg   error-message" role="alert">' . $_SESSION['success_message'] . '</div>';
                            // Unset the session variable to prevent displaying the message on page reload
                            unset($_SESSION['success_message']);
                        }
                        ?>


                    </div>


                    <!--row ends-->
                </div>
            </div>
        </div>

        <input type="hidden" name="form_type" value="callregistered">


    </form>




    <style type="text/css">
        .vertical-center {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            /* vh stands for viewport height */
            padding-top: 30px;
            background-color: white;
            padding-bottom: 30px;
        }





        .mybtn {
            background-color: #FF9900;
            font-weight: bold;
            font-size: medium;
            color: white;
            width: 131px;
            height: 40px;
            margin-bottom: 10px;
            border: 1px solid none;
            border-radius: 20px;
        }
    </style>

     

       
        <footer class="footer text-center">
          All Rights Reserved By.
          <a href="https://www.digitechsys.net">Digitech Systems & Infocom Pvt. Ltd.</a>.
        </footer>
        <!-- ============================================================== -->
        <!-- End footer -->
        <!-- ============================================================== -->
      </div>
     
    </div>
   
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



<style>
       

        label {
    display: inline-block;
    font-weight: 700;
}

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #aaa;
    border-radius: 24px;
    padding: 5px;
    background-color: #fffdf4;
    margin-left: 3px;
}


.dataTables_wrapper .dataTables_filter {
   
    text-align: center;
   
}

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