<?php session_start();
include_once 'config.php';
$conn = getDbConnection();
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

            } else {
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
                            <input type="text" name="ticketview" class="form-control rounded rounded-5">

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

                            <label>Ticket Status:</label>

                        </div>

                        <div class="col-md-12">


                            <select id="ticketstatus" name="ticketstatus" class="form-control rounded rounded-5">
                                <option value="Not Applicable">Please Select</options>
                                <option value="Pending">Pending</options>
                                <option value="Closed">Closed</options>
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
                                <option value="Not Applicable">Please Select</options>
                                <option value="KCCB">KCCB</options>
                                <option value="HPSCB">HPSCB</options>
                                <option value="JCCB">JCCB</options>
                            </select>
                        </div>
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
                            <label>Branch Name:</label>

                        </div>


                        <div class="col-md-12">

                            <input type="text" name="txtbranchname" class="form-control rounded rounded-5">
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
                                <option class="AMC" value="Dibold">Dibold</option>
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



                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" name="prevmaintain" type="checkbox"
                                    id="flexSwitchCheckDefault">
                                <label class="form-check-label" for="flexSwitchCheckDefault">Preventive Maintenance
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" name="stationarydelv" type="checkbox"
                                    id="flexSwitchCheckDefault">
                                <label class="form-check-label" for="flexSwitchCheckDefault">Stationary Delivered
                                </label>
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







                    <div class="row py-3">

                        <div class="col-md-12">



                            <label>Resolution Through:</label>
                        </div>

                        <div class="col-md-12">



                            <select ID="resolutionby" name="resolutionby" class="form-control rounded rounded-5">
                                <option value="Not Applicable">Please Select</options>
                                <option value="Site-Visit">Site-Visit</options>
                                <option value="Tele-Support">Tele-Support</options>
                            </select>





                        </div>

                        <div class="col-md-12">

                        </div>


                    </div>
                    <!--row ends-->


                    <br />


                    <div class="row py-3">

                        <div class="col-md-2">



                            <label>Part Utilized:</label>

                        </div>

                        <div class="col-md-3">

                            <input ID="demandwithoem" type="checkbox" name="demandwithoem">
                            <label>Demand with OEM</label>



                        </div>

                        <div class="col-md-3">


                            <label>Name of Part</label>
                        </div>


                        <div class="col-md-4">

                            <select ID="partname" name="partname" class="form-control rounded rounded-5">
                                <option value="Not Applicable">Please Select</option>
                                <option value="dispensershutter">Dispenser Shutter</option>
                                <option value="journalprinter">Journal Printer</option>
                                <option value="controllercard">Controller Card</option>
                                <option value="motherboard">Mother Board</option>
                                <option value="ccaboard">CCA Board</option>
                                <option value="hookbelt">Hook Belt</option>
                                <option value="fan">Fan</option>
                                <option value="clamp">Clamp</option>
                                <option value="belts">High Belts</option>
                                <option value="cassette">Currency Casseltes</option>
                                <option value="Stacker">Stacker</option>
                                <option value="cmdcontrollercard">CMD Controller Card</option>
                                <option value="feedsocket">Feed Socket</option>
                                <option value="hdd">HDD</option>
                                <option value="cardreader">Card Reader </option>
                                <option value="safelock">Safe Lock </option>
                                <option value="upsbatteries">UPS Batteries </option>
                                <option value="upscontrollercard">UPS Controller Card </option>
                                <option value="receiptprinter">Receipt Printer </option>
                                <option value="epp">EPP </option>
                                <option value="dispenser">Dispenser </option>
                                <option value="Softkeys">Softkeys </option>
                                <option value="roller">Roller </option>
                                <option value="cpufan">CPU Fan </option>
                                <option value="feedshafts">Feed Shafts </option>
                                <option value="hooddoorlock">Hood Door Lock </option>
                                <option value="doubleextractor">Double Extractor </option>
                                <option value="ddu">DDU </option>
                                <option value="divertcassette">Divert Cassette </option>
                                <option value="shuttercable">Shutter Cable </option>
                                <option value="Stacker">Stacker </option>
                            </select>
                        </div>


                    </div>
                    <!--row ends-->






                    <div class="row py-3">

                        <div class="col-md-12">


                            <label>Engineers Details</label>
                        </div>

                        <div class="col-md-6">

                            <input type="text" ID="oemengg" name="oemengg" class="form-control rounded rounded-5"
                                placeholder="Name of OEM Engineer">



                        </div>

                        <div class="col-md-6">


                            <input type="text" ID="digitechengg" name="digiengg" class="form-control rounded rounded-5"
                                placeholder="Name of DSI">

                        </div>


                    </div>
                    <!--row ends-->






                    <div class="row py-3">

                        <div class="col-md-6">


                            <label>Resolution Time:</label>
                        </div>

                        <div class="col-md-6">


                            <label>Time of Call Closed:</label>

                            <input type="datetime-local" name="txtDateTime3" id="txtDateTime3"
                                class="form-control rounded rounded-5" onchange="calculateDowntime()">



                        </div>



                    </div>
                    <!--row ends-->



                    <div class="row py-3">

                        <div class="col-md-12">


                            <label>Upload SCR File:</label>
                        </div>

                        <div class="col-md-12">

                            <input type="file" name="fileToUpload[]" id="fileToUpload" multiple>


                        </div>

                        <div class="col-md-12 mx-2">


                            <label>Only PDF's, JPG files are allowed.</label>
                        </div>



                    </div>
                    <!--row ends-->




                    <div class="row py-3">

                        <div class="col-md-6">

                            <label>Downtime:</label>
                        </div>

                        <div class="col-md-6">

                            <input type="text" name="txtDateTime4" id="txtDateTime4"
                                class="form-control rounded rounded-5" readonly>




                        </div>



                    </div>

                    <div class="row py-3">
                        <div class="col-md-6">
                            <label>Excess Downtime (In hours):</label>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="txtExcessDowntime" id="txtExcessDowntime"
                                class="form-control rounded rounded-5" readonly style="display: none;">
                        </div>
                    </div>
                    <!--row ends-->


                    <div class="row py-3">

                        <div class="col-md-12">

                            <label>Call Closed information to Customer by:</label>
                        </div>

                        <div class="row py-3">

                            <div class="col-md-7">

                                <input type="checkbox" ID="emailinfo" name="emailinfo">
                                <label>Email Information</label>

                            </div>

                            <div class="col-md-1">


                                <label>Date:</label>
                            </div>

                            <div class="col-md-4">
                                <input type="datetime-local" name="txtDateTime5" id="txtDateTime5"
                                    class="form-control rounded rounded-5">



                            </div>
                        </div>
                    </div>
                    <!--row ends-->


                    <div class="row py-3">
                        <div class="col-md-6">
                            <label>Remarks (if Any):</label>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="txtremarks" class="form-control rounded rounded-5">
                        </div>
                    </div>





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

        <input type="hidden" name="form_type" value="callclosed">


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