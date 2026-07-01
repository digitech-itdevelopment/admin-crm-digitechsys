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

// Get or update ticket counter
$ticketNumber = getAndUpdateTicketCounter();

// Store the generated ticket number in session for further processing
$_SESSION['generated_ticket_number'] = $ticketNumber;

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">

<head id="Head1" runat="server">
    <title>DigiTech CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/bootstrap.min.css" />
    <script src="./Scripts/jquery.min.js"></script>

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




    <form id="userfrm" method="post" action="userdetails.php" enctype="multipart/form-data"
        onsubmit="return validateForm()">

        <div class="container">
            <div class="row vertical-center d-flex">

                <div class="col-md-6 shadow-lg">

                    <div style="text-align: center;">
                        <img src="images/icon.png" width="100" height="80" />


                    </div>
                    <h4 style="text-align: center">Digitech Helpdesk</h4>


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
                                class="form-control rounded rounded-5">
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

                        <div class="col-md-12">

                            <label>Bank Name</label>

                        </div>

                        <div class="col-md-12">
                            <select id="bankname" name="bankname" class="form-control rounded rounded-5">
                                <option value="Not Applicable">Please Select</options>
                                <option value="KCCB">KCCB</options>
                                <option value="HPSCB">HPSCB</options>
                                <option value="JCCB">JCCB</options>
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
                                <option class="AMC" value="AGS">AGS</option>
                                <option class="AMC" value="Dibold">Diebold</option>
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
                            <select id="stationaryreq" name="stationaryreq" class="form-control rounded rounded-5">
                                <option value="Not Applicable">Please Select</options>
                                <option value="Journal Printer">Journal Printer</options>
                                <option value="Receipt Printer">Receipt Printer</options>
                                <option value="JP Printer">JP Ribbon</options>
                            </select>
                        </div><!--ends col-->
                    </div>
                    <!--row ends-->



                    <div class="row py-3">
                        <div class="col-md-12">

                            <div class="form-check form-switch">
                                <input class="form-check-input" name="atmstatus" type="checkbox"
                                    id="flexSwitchCheckDefault">
                                <label class="form-check-label" for="flexSwitchCheckDefault">ATM was not Working</label>
                            </div>
                        </div>
                    </div> <!--row ends-->






                    <div class="row py-3">

                        <div class="col-md-12">


                            <label>Call Registered through:</label>


                        </div>

                        <div class="col-md-12">



                            <select ID="callregthrough" name="callregthrough" class="form-control rounded rounded-5">
                                <option value="Not Applicable">Please Select</options>
                                <option value="Email">Email</options>
                                <option value="Telecalling">Telecalling</options>
                            </select>







                        </div>




                    </div>
                    <!--row ends-->






                    <div class="row py-3">
                        <div class="col-md-12">
                            <label>Call timings:</label>
                            <input type="datetime-local" name="txtDateTime1" id="txtDateTime1"
                                class="form-control rounded rounded-5" onchange="calculateDowntime()">
                        </div>
                    </div>
                    <!--row ends-->





                    <div class="row py-3">

                        <div class="col-md-12">



                            <label>Warranty Call</label>
                        </div>

                        <div class="row">

                            <div class="col-md-5">
                                <input type="checkbox" name="callregwithoemtc" id="callregwithoemtc">
                                <label>Call registered with OEM</label>


                            </div>


                            <div class="col-md-5" name="callregwithdsi" id="callRegWithDSISection"
                                style="display: none;">

                                <input type="checkbox" name="callregwithdsi" id="callregwithdsi">
                                <label>Call registered with DSI</label>
                            </div>



                            <div class="col-md-2">


                                <label>Date and Time</label>


                            </div>

                            <div class="col-md-4">

                                <input type="datetime-local" name="txtDateTime2" id="txtDateTime2"
                                    class="form-control rounded rounded-5">



                            </div>
                        </div>




                    </div>
                    <!--row ends-->











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



</body>

</html>