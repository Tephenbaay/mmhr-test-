<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "mmhr_census";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$files_query = "SELECT id, file_name FROM uploaded_files ORDER BY upload_date DESC";
$files_result = $conn->query($files_query);
$files = [];
while ($row = $files_result->fetch_assoc()) {
    $files[] = $row;
}
$selected_file_id = isset($_GET['file_id']) ? intval($_GET['file_id']) : 0;

$sheets = [];
if ($selected_file_id) {
    $sheets_query = "SELECT DISTINCT sheet_name FROM patient_records WHERE file_id = $selected_file_id";
    $sheets_result = $conn->query($sheets_query);
    while ($row = $sheets_result->fetch_assoc()) {
        $sheets[] = $row['sheet_name'];
    }

    $sheets_query_2 = "SELECT DISTINCT sheet_name_2 FROM patient_records_2 WHERE file_id = $selected_file_id";
    $sheets_result_2 = $conn->query($sheets_query_2);
    $sheets_2 = [];
    while ($row = $sheets_result_2->fetch_assoc()) {
        $sheets_2[] = $row['sheet_name_2'];
    }

    $sheets_query_3 = "SELECT DISTINCT sheet_name_3 FROM patient_records_3 WHERE file_id = $selected_file_id";
    $sheets_result_3 = $conn->query($sheets_query_3);
    $sheets_3 = [];
    while ($row = $sheets_result_3->fetch_assoc()) {
        $sheets_3[] = $row['sheet_name_3'];
    }
}

$selected_sheet_1 = isset($_GET['sheet_1']) ? $_GET['sheet_1'] : '';
$selected_sheet_2 = isset($_GET['sheet_2']) ? $_GET['sheet_2'] : '';
$selected_sheet_3 = isset($_GET['sheet_3']) ? $_GET['sheet_3'] : '';

$all_patient_data = [];

$all_sheets_query = "SELECT admission_date, discharge_date, member_category, sheet_name 
                     FROM patient_records 
                     WHERE file_id = $selected_file_id";
$all_sheets_result = $conn->query($all_sheets_query);

$summary = array_fill(1, 31, [
    'govt' => 0, 'private' => 0, 'self_employed' => 0, 'ofw' => 0,
    'owwa' => 0, 'sc' => 0, 'pwd' => 0, 'indigent' => 0, 'pensioners' => 0,
    'nhip' => 0, 'non_nhip' => 0, 'total_admissions' => 0, 'total_discharges_nhip' => 0,
    'total_discharges_non_nhip' => 0,'lohs_nhip' => 0, 'lohs_non_nhip' => 0
]);

    #column 1-5
    while ($row = $all_sheets_result->fetch_assoc()) {
        $admit = DateTime::createFromFormat('Y-m-d', trim($row['admission_date']))->setTime(0, 0, 0);
        $discharge = DateTime::createFromFormat('Y-m-d', trim($row['discharge_date']))->setTime(0, 0, 0);
        $category = trim(strtolower($row['member_category']));
    
        $selected_year = 2025;
        $month_numbers = [
            'JANUARY' => 1, 'FEBRUARY' => 2, 'MARCH' => 3, 'APRIL' => 4, 'MAY' => 5, 'JUNE' => 6,
            'JULY' => 7, 'AUGUST' => 8, 'SEPTEMBER' => 9, 'OCTOBER' => 10, 'NOVEMBER' => 11, 'DECEMBER' => 12
        ];
    
        $selected_month_name = strtoupper($selected_sheet_1);
        if (!isset($month_numbers[$selected_month_name])) {
            continue;
        }
        $selected_month = $month_numbers[$selected_month_name];
    
        $first_day_of_month = new DateTime("$selected_year-$selected_month-01");
        $last_day_of_month = new DateTime("$selected_year-$selected_month-" . cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year));
    
        if ($admit == $discharge) {
            continue;
        }
    
        // If the patient has days in this selected month
        if ($discharge >= $first_day_of_month && $admit <= $last_day_of_month) {
            $startDay = max($first_day_of_month, $admit)->format('d');
            $endDay = min($last_day_of_month, (clone $discharge)->modify('-1 day'))->format('d');
    
            for ($day = (int)$startDay; $day <= (int)$endDay; $day++) {
                if (!isset($summary[$day])) {
                    $summary[$day] = [
                        'govt' => 0, 'private' => 0, 'self_employed' => 0, 'ofw' => 0,
                        'owwa' => 0, 'sc' => 0, 'pwd' => 0, 'indigent' => 0, 'pensioners' => 0
                    ];
                }
    
                // Categorizing patients
                if (stripos($category, 'formal-government') !== false || stripos($category, 'sponsored- local govt unit') !== false) {
                    $summary[$day]['govt'] += 1;
                } elseif (stripos($category, 'formal-private') !== false) {
                    $summary[$day]['private'] += 1;
                } elseif (stripos($category, 'self earning individual') !== false || stripos($category, 'indirect contributor') !== false
                    || stripos($category, 'informal economy- informal sector') !== false) {
                    $summary[$day]['self_employed'] += 1;
                } elseif (stripos($category, 'migrant worker') !== false) {
                    $summary[$day]['ofw'] += 1;
                } elseif (stripos($category, 'direct contributor') !== false) {
                    $summary[$day]['owwa'] += 1;
                } elseif (stripos($category, 'senior citizen') !== false) {
                    $summary[$day]['sc'] += 1;
                } elseif (stripos($category, 'pwd') !== false) {
                    $summary[$day]['pwd'] += 1;
                } elseif (stripos($category, 'indigent') !== false || stripos($category, 'sponsored- pos financially incapable') !== false
                    || stripos($category, '4ps/mcct') !== false) {
                    $summary[$day]['indigent'] += 1;
                } elseif (stripos($category, 'lifetime member') !== false) {
                    $summary[$day]['pensioners'] += 1;
                }
            }
        }
    }

    #nhip column
    foreach ($summary as $day => $row) {
        $summary[$day]['nhip'] = 
            $row['govt'] + $row['private'] + $row['self_employed'] + 
            $row['ofw'] + $row['owwa'] + $row['sc'] + 
            $row['pwd'] + $row['indigent'] + $row['pensioners'];
    }    

    #column 9 non-nhip
    foreach ($summary as $day => $row) {
        $summary[$day]['lohs_nhip'] = 
            $row['govt'] + $row['private'] + $row['self_employed'] + 
            $row['ofw'] + $row['owwa'] + $row['sc'] + 
            $row['pwd'] + $row['indigent'] + $row['pensioners'];
    }  

    #non-nhip column
    $non_nhip_query = "SELECT date_admitted, date_discharge, category, sheet_name_3 
                   FROM patient_records_3 
                   WHERE sheet_name_3 = '$selected_sheet_3' AND file_id = $selected_file_id";
    $non_nhip_result = $conn->query($non_nhip_query);

    while ($row = $non_nhip_result->fetch_assoc()) {
        $admit = new DateTime($row['date_admitted']);
        $discharge = new DateTime($row['date_discharge']);
        $category = strtolower($row['category']);

        if (!(stripos($category, 'n/a') !== false)) {
            continue;
        }

        if ($admit->format('Y-m-d') === $discharge->format('Y-m-d')) {
            continue;
        }

        if ((int) $discharge->format('d') === 1) {
            continue;
        }

        $selected_month_name = date('F', mktime(0, 0, 0, $selected_month, 1, $selected_year));

        $monthStart = new DateTime("first day of $selected_month_name $selected_year");
        $monthEnd = new DateTime("last day of $selected_month_name $selected_year");

        $startDay = max(1, (int) $admit->format('d'));
        if ($admit < $monthStart) {
            $startDay = 1;
        }

        $endDay = min((int) $discharge->format('d') - 1, (int) $monthEnd->format('d'));

        if ($startDay <= $endDay) {
            for ($day = $startDay; $day <= $endDay; $day++) {
                $summary[$day]['non_nhip'] += 1;
            }
        }
    }

    #total admission column
    $admission_query = "SELECT admission_date_2 FROM patient_records_2 
                    WHERE sheet_name_2 = '$selected_sheet_2' AND file_id = $selected_file_id";
    $admission_result = $conn->query($admission_query);

    while ($row = $admission_result->fetch_assoc()) {
        $admit_day = (int)date('d', strtotime($row['admission_date_2']));

        if ($admit_day >= 1 && $admit_day <= 31) {
            $summary[$admit_day]['total_admissions'] += 1;
        }
    }

    $discharge_query = "SELECT date_discharge, category FROM patient_records_3 
                    WHERE sheet_name_3 = '$selected_sheet_3' AND file_id = $selected_file_id";
    $discharge_result = $conn->query($discharge_query);

    while ($row = $discharge_result->fetch_assoc()) {
        $discharge_day = (int)date('d', strtotime($row['date_discharge'])); 
        $category = strtolower($row['category']);

        if ($discharge_day >= 1 && $discharge_day <= 31) {
            if (!isset($summary[$discharge_day])) {
                $summary[$discharge_day] = [
                    'total_discharges_non_nhip' => 0,
                    'total_discharges_nhip' => 0
                ];
            }
            if (strpos($category, 'n/a') !== false || strpos($category, 'non phic') !== false || strpos($category, '#n/a') !== false) {
                $summary[$discharge_day]['total_discharges_non_nhip'] += 1;
            } else {
                $summary[$discharge_day]['total_discharges_nhip'] += 1;
            }
        }
    }
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMHR Census</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="sige\mmhr.css">
</head>
<body class="container mt-4">

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <div class="navb">
            <img src="sige/download-removebg-preview.png" alt="icon">
            <div class="nav-text">
            <a class="navbar-brand" href="dashboard.php">BicutanMed</a>
            <p style = "margin-top: 0px; font-size: 13px; margin-left: -18px;">Caring For Life</p>
            </div>
            <form action="display_summary.php">
            <button class="btn2">↪</button>
        </div>
    </div>
</nav>

<aside>
    <div class="sidebar" id="sidebar" style="background-color: #6ba172;">
        <h2>Upload Excel File</h2>
        <form action="display_summary.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="excelFile" accept=".xlsx, .xls">
            <button type="submit" class="btn1 btn btn-success">Upload</button>
            <button type="button" onclick="printTable()" class="btn btn-success ">Print Table</button>
        </form>
        <form action="display_summary.php" method="GET">
            <button type="submit" class="btn btn-primary btn-2">View MMHR</button>
        </form>
        <form action="leading_causes.php" method="GET">
            <button type="submit" class="btn btn-primary btn-3">View Leading Causes</button>
        </form>
    </div>
</aside>
<button class="toggle-btn" id="toggleBtn" style="background-color: #6ba172;">Hide</button>

<div class="main-content" id="main-content">
            <div class="header-text">
                <div class="container">
                    <p>REPUBLIC OF THE PHILIPPINES</p>
                    <p>PHILIPPINE HEALTH INSURANCE CORPORATION</p>
                    <p>MANDATORY MONTHLY HOSPITAL REPORT</p>
                    <p>12/F City State Centre, 709 Shaw Blvd., Brgy. Oranbo, Pasig City</p>
                    <div class="text-center">
                        <p class="d-inline-block">For the Month of 
                            <input type="text" class="form-control form-control-sm d-inline-block fs-5" name="month" style="width: 140px; text-align:center;"> 
                            2025
                        </p>
                    </div>
                </div>
                <form class="form1"><br>
                <div class="row">
                    <!-- LEFT SIDE -->
                    <div class="col-md-6 text-start">
                        <div class="form-group row mb-3">
                            <label class="col-sm-5 col-form-label text-end fs-6">Accreditation No. :</label>
                            <div class="col-sm-7">
                                <input type="text" class="form-control fs-6" name="accreditation_no">
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label class="col-sm-5 col-form-label text-end fs-6">Name of Hospital :</label>
                            <div class="col-sm-7">
                                <input type="text" class="form-control fs-6" name="hospital_name">
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label class="col-sm-5 col-form-label text-end fs-6">Address No./Street :</label>
                            <div class="col-sm-7">
                                <input type="text" class="form-control fs-6" name="address">
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label class="col-sm-5 col-form-label text-end fs-6">Municipality :</label>
                            <div class="col-sm-7">
                                <input type="text" class="form-control fs-6" name="municipality">
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label class="col-sm-5 col-form-label text-end fs-6">Province :</label>
                            <div class="col-sm-7">
                                <input type="text" class="form-control fs-6" name="province">
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label class="col-sm-5 col-form-label text-end fs-6">Zip Code :</label>
                            <div class="col-sm-7">
                                <input type="text" class="form-control fs-6" name="zip_code">
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT SIDE -->
                    <div class="col-md-6">
                        <div class="form-group row mb-3">
                            <label class="col-sm-5 col-form-label text-end fs-6">Region :</label>
                            <div class="col-sm-7">
                                <input type="text" class="form-control fs-6" name="region">
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label class="col-sm-5 col-form-label text-end fs-6">Category :</label>
                            <div class="col-sm-7">
                                <input type="text" class="form-control fs-6" name="category">
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label class="col-sm-5 col-form-label text-end fs-6">PHIC Accredited Beds :</label>
                            <div class="col-sm-7">
                                <input type="text" class="form-control fs-6" name="phic_beds">
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label class="col-sm-5 col-form-label text-end fs-6">DOH Authorized Beds :</label>
                            <div class="col-sm-7">
                                <input type="text" class="form-control fs-6" name="doh_beds">
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <form method="GET" class="mb-3" id="filterForm">
            <div class="sige">
            <label for="file_id" class="me-2" style="font-size:15px;">Select File:</label>
            <select name="file_id" id="file_id" onchange="document.getElementById('filterForm').submit()" class="form-select me-2">
                <option value="">-- Choose File --</option>
                <?php foreach ($files as $file): ?>
                    <option value="<?= $file['id'] ?>" <?= $selected_file_id == $file['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($file['file_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($selected_file_id): ?>
            <label class="col2-5 me-2" style="font-size:15px;">Select Sheet: </label>
            <select name="sheet_1" onchange="document.getElementById('filterForm').submit()" class="form-select mb-2 me-2">
            <option value="" disabled selected>Select Month</option>
                <?php foreach ($sheets as $sheet) { ?>
                    <option value="<?php echo $sheet; ?>" <?php echo $sheet === $selected_sheet_1 ? 'selected' : ''; ?>>
                        <?php echo $sheet; ?>
                    </option>
                <?php } ?>
            </select>

            <label class="col7"></label>
            <select name="sheet_2" onchange="document.getElementById('filterForm').submit()" class="form-select mb-2 me-2">
            <option value="" disabled selected>Select Admission Sheet</option>
                <?php foreach ($sheets_2 as $sheet) { ?>
                    <option value="<?php echo $sheet; ?>" 
                        <?php echo $sheet === $selected_sheet_2 ? 'selected' : ''; ?>>
                        <?php echo $sheet; ?>
                    </option>
                <?php } ?>
            </select>

            <label class="col8"></label>
            <select name="sheet_3" onchange="document.getElementById('filterForm').submit()" class="form-select mb-2">
            <option value="" disabled selected>Select Discharge Sheet</option>
            <?php foreach ($sheets_3 as $sheet): ?>
                <option value="<?= $sheet ?>" <?= $sheet == $selected_sheet_3 ? 'selected' : '' ?>><?= $sheet ?></option>
            <?php endforeach; ?>
            </select>
            <?php endif; ?>
            </div>
        </form>
                
            <br>
                <div class="table-container">
                    <div class="col-md-6">
                    <p style="text-align:left; font-size:15px;">A. DAILY CENSUS OF NHIP PATIENTS</p>
                    <p class="text-center"><b>(EVERY 12:00MN.)</b></p>
                        <table class="table table-bordered text-center" style="font-size: 10px;">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th rowspan="2">DATE</th>
                                    <th colspan="3">CENSUS</th> 
                                </tr>
                                <tr>
                                    <th>NHIP</th>
                                    <th>NON-NHIP</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totals = ['nhip' => 0, 'non_nhip' => 0, 'total' => 0];
                                for ($i = 1; $i <= 31; $i++) { 
                                    $nhip = $summary[$i]['nhip'] ?? 0;
                                    $non_nhip = $summary[$i]['non_nhip'] ?? 0;
                                    $total = $nhip + $non_nhip;
            
                                    $totals['nhip']  += $nhip;
                                    $totals['non_nhip'] += $non_nhip;
                                    $totals['total'] += $total;
                                ?>
                                    <tr>
                                        <td><?php echo $i; ?></td>
                                        <td><?php echo $nhip; ?></td>
                                        <td><?php echo $non_nhip; ?></td>
                                        <td><?php echo $total; ?></td>
                                    </tr>
                                <?php } ?>
                                <tr class="fw-bold text-center">
                                    <td colspan="4">*** NOTHING FOLLOWS ***</td>
                                </tr>
                                <tr class="table-dark fw-bold">
                                    <td>Total</td>
                                    <td><?php echo $totals['nhip']; ?></td>
                                    <td><?php echo $totals['non_nhip']; ?></td>
                                    <td><?php echo $totals['total']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- Quality Assurance Indicator Section -->
                        <div class="mt-4">
                            <h4 style="text-align: left;"><strong>B. QUALITY ASSURANCE INDICATOR</strong></h4>
                            <?php 
                                $days_in_month_map = [
                                    'JANUARY' => 31, 'FEBRUARY' => 28, 'MARCH' => 31,
                                    'APRIL' => 30, 'MAY' => 31, 'JUNE' => 30,
                                    'JULY' => 31, 'AUGUST' => 31, 'SEPTEMBER' => 30,
                                    'OCTOBER' => 31, 'NOVEMBER' => 30, 'DECEMBER' => 31
                                ];
                                $month_upper = strtoupper($selected_sheet_1 ?? '');
                                $days_in_month = $days_in_month_map[$month_upper] ?? 30; 
                                $days_in_thousand = $days_in_month * 100;

                                $total_all = $totals['total'];
                                $total_nhip = $totals['nhip'];

                                $mbor = $days_in_thousand > 0 ? round(($total_all / $days_in_thousand) * 100, 2) : 0;
                                $mnhibor = $days_in_thousand > 0 ? round(($total_nhip / $days_in_thousand) * 100, 2) : 0;
                            ?>
                             <!-- MBOR -->
                            <p style="text-align: left; font-size:18px;"><b>1. Monthly Bed Occupancy Rate (MBOR) = <u><?= number_format($mbor, 2); ?>%</u></b><p><br>
                            <div style="text-align: center;"> 
                                <div>Total of NHIP Census + Total of NON-NHIP CENSUS</div>
                                <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $totals['total']; ?></div>
                                <div>MBOR = (------------------------------------------) x 100</div>
                
                                <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $days_in_month * 100; ?></div>
                                <div>Number of days per Month indicated X Number of DOH Authorized Beds</div><br><br>
                            </div>
                            <!-- MNHIBOR -->
                            <p style="text-align: left; font-size:18px;"><b>2. Monthly NHIP Beneficiary Occupancy Rate (MNHIBOR) = <u><?= number_format($mnhibor, 2); ?>%</u></b></p><br>
                            <div style="text-align: center;">
                                <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Total of NHIP CENSUS</div>
                                <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $totals['nhip']; ?></div>
                                <div>MNHIBOR = (------------------------------------------) x 100</div>
                                <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $days_in_month * 100; ?></div>
                                <div>Number of days per Month indicated X Number of PHIC Accredited Beds</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p style="text-align:left; font-size:15px;">CENSUS FOR THE DAY=CENSUS OF THE PREVIOUS DAY PLUS THE ADMISSION OF THE DAY</p>
                        <p class="text-center" style="font-size:20px;"><b>minus DISCHARGES of the day</b></p>
                        <table class="table table-bordered text-center" style="font-size: 10px;">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th rowspan="2">DATE</th>
                                    <th colspan="3">DISCHARGES</th>
                                </tr>
                                <tr>
                                    <th>NHIP</th>
                                    <th>NON-NHIP</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totals_discharge = ['nhip' => 0, 'non_nhip' => 0, 'total' => 0];
                                for ($i = 1; $i <= 31; $i++) { 
                                    $nhip = $summary[$i]['total_discharges_nhip'] ?? 0;
                                    $non_nhip = $summary[$i]['total_discharges_non_nhip'] ?? 0;
                                    $total = $nhip + $non_nhip;
            
                                    $totals_discharge['nhip'] += $nhip;
                                    $totals_discharge['non_nhip'] += $non_nhip;
                                    $totals_discharge['total'] += $total;
                                ?>
                                    <tr>
                                        <td><?php echo $i; ?></td>
                                        <td><?php echo $nhip; ?></td>
                                        <td><?php echo $non_nhip; ?></td>
                                        <td><?php echo $total; ?></td>
                                    </tr>
                                <?php } ?>
                                <tr class="fw-bold text-center">
                                    <td colspan="4">*** NOTHING FOLLOWS ***</td>
                                </tr>
                                <tr class="table-dark fw-bold">
                                    <td>Total</td>
                                    <td><?php echo $totals_discharge['nhip']; ?></td>
                                    <td><?php echo $totals_discharge['non_nhip']; ?></td>
                                    <td><?php echo $totals_discharge['total']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- ASLP Section -->
                        <?php 
                            $total_nhip_discharged = $totals_discharge['nhip'];
                            $aslp = $total_nhip_discharged > 0 ? round($total_nhip / $total_nhip_discharged, 2) : 0;
                        ?>
                        <div class="mt-4">
                            <br><p style="text-align:left; font-size:18px;"><b>3. Average Length of Stay per NHIP Patient (ASLP) = <u><?= $totals_discharge['nhip'] > 0 ? number_format($aslp, 2) : 'N/A'; ?></u></b></p><br>
                            <div style="text-align: center;">
                                <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Total No. of NHIP CENSUS</div>
                                <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $totals['nhip']; ?></div>
                                <div>ASLP = (------------------------------------------)   </div>
                                <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $totals_discharge['nhip']; ?></div>
                                <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Total No. of NHIP Discharges</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>

function printTable() {
    const mainContent = document.getElementById("main-content");

    // Clone content so original stays intact
    const printWindow = window.open('', '_blank');
    
    const printStyles = `
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                padding: 0;
                color: #000;
            }
            h2 {
                text-align: center;
                margin-bottom: 20px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th, td {
                border: 1px solid black;
                padding: 8px;
                text-align: center;
                font-size: 14px;
            }
            .header-text {
                text-align: center;
                font-weight: bold;
            }

            @media print {
                button, select, input, .no-print {
                    display: none !important;
                }
            }
        </style>
    `;

    printWindow.document.write(`
        <html>
        <head>
            <title>Print Table</title>
            ${printStyles}
        </head>
        <body>
            <div class="header-text">
                <h2>Mandatory Monthly Hospital Report</h2>
            </div>
            ${mainContent.innerHTML}
        </body>
        </html>
    `);

    printWindow.document.close();
    printWindow.focus();

    // Wait for styles/content to load before printing
    printWindow.onload = () => {
        printWindow.print();
        printWindow.close();
    };


    reinitializeEventListeners();
}

    function reinitializeEventListeners() {
        const toggleBtn = document.getElementById("toggleBtn");
        const sidebar = document.getElementById("sidebar");
        const content = document.getElementById("content");
        let isSidebarVisible = true;

        toggleBtn.addEventListener("click", () => {
            isSidebarVisible = !isSidebarVisible;
            if (isSidebarVisible) {
                sidebar.classList.remove("hidden");
                toggleBtn.style.left = "260px";
                content.style.marginLeft = "270px"; 
                content.style.marginRight = "0"; 
                toggleBtn.textContent = "Hide";
            } else {
                sidebar.classList.add("hidden");
                toggleBtn.style.left = "10px";
                content.style.marginLeft = "auto"; 
                content.style.marginRight = "auto"; 
                toggleBtn.textContent = "Show";
            }
        });
    }

const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggleBtn");
const content = document.getElementById("content"); 
let isSidebarVisible = true;

toggleBtn.addEventListener("click", () => {
    isSidebarVisible = !isSidebarVisible;
    if (isSidebarVisible) {
        sidebar.classList.remove("hidden");
        toggleBtn.style.left = "260px";
        content.style.marginLeft = "270px"; 
        content.style.marginRight = "0"; 
        toggleBtn.textContent = "Hide";
    } else {
        sidebar.classList.add("hidden");
        toggleBtn.style.left = "10px";
        content.style.marginLeft = "auto"; 
        content.style.marginRight = "auto"; 
        toggleBtn.textContent = "Show";
    }
});

    function printTable() {
        let printWindow = window.open('', '_blank');
        let content = document.getElementById("main-content").outerHTML;

        let styles = Array.from(document.styleSheets)
            .map(sheet => {
                try {
                    return Array.from(sheet.cssRules).map(rule => rule.cssText).join("\n");
                } catch (e) {
                    return ""; 
                }
            })
            .join("\n");

        printWindow.document.open();
        printWindow.document.write(`
            <html>
            <head>
                <title>Print</title>
                <style>
                @media print {
                    body * {
                        visibility: hidden;
                        background-color: white;
                    }
                    .main-content, .main-content * {
                        visibility: visible;
                        margin-top: auto;
                        background-color: white;
                    }
                    .main-content {
                        margin-left: 0;
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 100%;
                    }
                    P{
                        font-size: 10px;
                        font-weight: bold;
                        margin-bottom: 0;
                    }
                    label{
                        font-size: 10px;
                        font-weight: bold;
                    }
                    .form-control{
                        font-size: 10px;
                        font-weight: bold;
                        border: none;
                        border-bottom: 1px solid black;
                    }
                    .table-container{
                        display: flex;
                        margin: 0 auto; /* Centers horizontally */
                        justify-content: center;
                        align-items: flex-start;
                        gap: 40px;
                        width: 40%;
                    }
                    tbody, thead{
                        text-align: center;
                        font-weight: bold;
                        font-size: 8px;
                    }
                    input{
                        border: none;
                        border-bottom: 1px solid black;
                    }
                    .sige{
                        display: none;
                    }
                }
                </style> <!-- Inject all styles -->
            </head>
            <body>
                ${content}
                <script>
                    window.onload = function() { 
                        window.print(); 
                        window.close(); 
                    };
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
</script>

</body>
</html>
