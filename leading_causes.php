<?php
include 'config.php';

// Get list of files
$files_query = "SELECT id, file_name FROM uploaded_files ORDER BY upload_date DESC";
$files_result = $conn->query($files_query);
$files = [];
while ($row = $files_result->fetch_assoc()) {
    $files[] = $row;
}

// Get selected file ID and sheet
$selected_file_id = isset($_GET['file_id']) ? intval($_GET['file_id']) : 0;
$selected_sheet = $_GET['sheet'] ?? '';

// Sheets from selected file
$sheets = [];
if ($selected_file_id) {
    $sheets_query = "SELECT DISTINCT sheet_name FROM patient_records WHERE file_id = $selected_file_id";
    $sheets_result = $conn->query($sheets_query);
    while ($row = $sheets_result->fetch_assoc()) {
        $sheets[] = $row['sheet_name'];
    }
}

// ICD summary query
$icd_summary = [];

if ($selected_file_id && $selected_sheet) {
    $query = "
        SELECT 
            lc.icd_10,
            SUM(CASE WHEN pr.member_category = 'N/A' THEN 1 ELSE 0 END) AS non_nhip_total,
            SUM(CASE WHEN pr.member_category != 'N/A' THEN 1 ELSE 0 END) AS nhip_total
        FROM leading_causes lc
        JOIN patient_records pr 
            ON lc.patient_name = pr.patient_name AND lc.file_id = pr.file_id
        WHERE lc.sheet_name = ? AND lc.file_id = ?
        GROUP BY lc.icd_10
        ORDER BY nhip_total DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $selected_sheet, $selected_file_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $icd_summary[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Leading Causes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <link rel="stylesheet" href="sige/summary.css">
   
</head>
<body class="container mt-4">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <div class="navb">
            <img src="sige/download-removebg-preview.png" alt="icon">
            <div class="nav-text">
            <a class="navbar-brand" href="dashboard.php">BicutanMed</a>
            <p style = "margin-top: 0px;">Caring For Life</p>
            </div>
            <form action="dashboard.php">
            <button class="btn2">↪</button>
        </div>
    </div>
</nav>

<aside>
    <div class="sidebar" id="sidebar">
        <h3>Upload Excel File</h3>
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="excelFile" accept=".xlsx, .xls">
            <button type="submit" class="btn1">Upload</button>
            <button onclick="printTable()" class="btn btn-success mt-3">Print Table</button>
        </form>
        <form action="mmhr_census.php" method="GET">
            <button type="submit" class="btn btn-primary btn-2">View MMHR Census</button>
        </form>
        <form action="display_summary.php" method="GET">
            <button type="submit" class="btn btn-primary btn-4">View MMHR Table</button>
        </form>
    </div>
</aside>
<button class="toggle-btn" id="toggleBtn">Hide</button>

<div class="table-responsive" id="content">
<h2 class="text-center mt-4" syle="margin-top:20px;">Leading Causes Summary</h2>

<form method="GET" class="mb-4" id="filterForm">
    <div class="sige">
        <label for="file_id">Select File:</label>
        <select name="file_id" id="file_id" onchange="document.getElementById('filterForm').submit()" class="form-select w-25 d-inline-block mb-2">
            <option value="">-- Choose File --</option>
            <?php foreach ($files as $file): ?>
                <option value="<?= $file['id'] ?>" <?= $selected_file_id == $file['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($file['file_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($selected_file_id): ?>
            <label for="sheet">Select Sheet:</label>
            <select name="sheet" id="sheet" onchange="document.getElementById('filterForm').submit()" class="form-select w-25 d-inline-block mb-2">
                <option value="" disabled selected>Select Month</option>
                <?php foreach ($sheets as $sheet): ?>
                    <option value="<?= $sheet ?>" <?= $sheet === $selected_sheet ? 'selected' : '' ?>>
                        <?= $sheet ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    </div>
</form>

<div class="table-responsive1" id="printable">
<?php if ($selected_sheet): ?>
    <div class="table-wrapper">
    <table class="table table-bordered">
        <thead class="table-dark text-center">
            <tr class="th1">
                <th rowspan="2" style="background-color: black; color: white;">ICD-10</th>
                <th colspan="2" style="background-color: black; color: white;">TOTAL</th>
            </tr>
            <tr>
                <th style="background-color: black; color: white;">NHIP</th>
                <th style="background-color: black; color: white;">NON-NHIP</th>
            </tr>
        </thead>
        <tbody class="text-center">
            <?php foreach ($icd_summary as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['icd_10']) ?></td>
                    <td><?= $row['nhip_total'] ?></td>
                    <td><?= $row['non_nhip_total'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
<?php else: ?>
    <p class="text-muted">Please select a month to view ICD-10 summary.</p>
<?php endif; ?>
</div>
</div>
<script>

function printTable() {
    var printContents = document.getElementById("printable").innerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;

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

</script>

</body>
</html>