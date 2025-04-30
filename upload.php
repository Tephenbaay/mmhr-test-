<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

ini_set('max_execution_time', 300);

// --- DB connection ---
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "mmhr_census";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// (2) Fetch and enforce settings
function getSetting($conn, $key) {
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $key);
    $stmt->execute();
    $stmt->store_result();

    $value = null;  // ✅ Initialize before binding
    $stmt->bind_result($value);

    if ($stmt->num_rows > 0 && $stmt->fetch()) {
        return $value;
    } else {
        return null;
    }

    $stmt->close(); // unreachable, but for good practice move before return if needed
}

// --- Set limits ---
$maxFilesAllowed = (int)(getSetting($conn, 'max_upload_files') ?? 10);
$maxFileSizeMB = (int)(getSetting($conn, 'max_file_size_mb') ?? 5);
$allowedExtensions = explode(',', getSetting($conn, 'allowed_file_extensions') ?? 'xlsx,xls');
$maxFileSize = $maxFileSizeMB * 1024 * 1024;

// (3) Enforce max file size
if ($_FILES['excelFile']['size'] > ($maxSizeMB * 1024 * 1024)) {
    die("File too large. Max: {$maxSizeMB}MB");
}

// (4) Enforce allowed file extensions
$ext = strtolower(pathinfo($_FILES['excelFile']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExtensions)) {
    die("Invalid file extension. Allowed: " . implode(', ', $allowedExtensions));
}

// (5) Optional: Enforce max number of files in DB
$result = $conn->query("SELECT COUNT(*) AS total FROM uploaded_files");
$row = $result->fetch_assoc();
if ((int)$row['total'] >= $maxFiles) {
    die("Maximum number of uploaded files reached. Limit: {$maxFiles}");
}

// --- Utility: Convert Excel date ---
function convertExcelDate($value) {
    if (is_numeric($value)) {
        return date('Y-m-d', Date::excelToTimestamp($value));
    } else {
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $value, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = $matches[3];
            $date = DateTime::createFromFormat('d/m/Y', "$day/$month/$year");
            return $date ? $date->format('Y-m-d') : null;
        }
    }
    return null;
}

// --- Handle upload ---
if (isset($_FILES['excelFile'])) {
    $fileName = $_FILES['excelFile']['name'];
    $fileTmp = $_FILES['excelFile']['tmp_name'];
    $fileSize = $_FILES['excelFile']['size'];

    // --- Check number of uploaded files ---
    $result = $conn->query("SELECT COUNT(*) as total FROM uploaded_files");
    $data = $result->fetch_assoc();
    if ($data['total'] >= $maxFilesAllowed) {
        die("<h3 style='color:red;'>❌ Upload limit reached. Only $maxFilesAllowed files allowed.</h3><p><a href='dashboard.php'>Go back</a></p>");
    }

    // --- Check file size ---
    if ($fileSize > $maxFileSize) {
        die("<h3 style='color:red;'>❌ File too large. Maximum allowed is 5MB.</h3><p><a href='dashboard.php'>Go back</a></p>");
    }

    // --- Record file name ---
    $stmt = $conn->prepare("INSERT INTO uploaded_files (file_name) VALUES (?)");
    $stmt->bind_param("s", $fileName);
    $stmt->execute();
    $fileId = $stmt->insert_id;
    $stmt->close();

    $spreadsheet = IOFactory::load($fileTmp);

    foreach ($spreadsheet->getSheetNames() as $sheetName) {
        $sheet = $spreadsheet->getSheetByName($sheetName);
        $highestRow = $sheet->getHighestRow(); 
        
        $batchData = [];
        $leadingCausesData = [];
        $normalizedSheetName = strtoupper(trim($sheetName));

        if (preg_match('/^(JANUARY|FEBRUARY|MARCH|APRIL|MAY|JUNE|JULY|AUGUST|SEPTEMBER|OCTOBER|NOVEMBER|DECEMBER)$/', $normalizedSheetName)) {
            $startRow = 3;
            $colPatientName = "F"; 
            $colAdmissionDate = "C"; 
            $colDischargeDate = "D";
            $colMemberCategory = "L";
            $colICD10 = "P"; 
            $tableName = "patient_records";
        } elseif (stripos($sheetName, 'admission') !== false) {
            $startRow = 9;
            $colPatientName = "D"; 
            $colAdmissionDate = "H"; 
            $colMemberCategory = "K";
            $tableName = "patient_records_2";
        } elseif (stripos($sheetName, 'discharge') !== false) {
            $startRow = 3;
            $colPatientName = "A";
            $colAdmissionDate = "K";
            $colDischargeDate = "M";
            $colCategory = "T";
            $tableName = "patient_records_3";
        } else {
            continue;
        }

        for ($rowIndex = $startRow; $rowIndex <= $highestRow; $rowIndex++) {
            $patientName = trim($sheet->getCell("{$colPatientName}$rowIndex")->getValue());
            $admissionDate = convertExcelDate(trim($sheet->getCell("{$colAdmissionDate}$rowIndex")->getValue()));
            $dischargeDate = convertExcelDate(trim($sheet->getCell("{$colDischargeDate}$rowIndex")->getValue()));

            if (empty($patientName) || empty($admissionDate)) {
                continue;
            }

            if ($tableName === "patient_records_3") {
                $category = trim($sheet->getCell("{$colCategory}$rowIndex")->getValue());
                
                $batchData[] = "($fileId, '$sheetName', '$admissionDate', " . 
                    (!empty($dischargeDate) ? "'$dischargeDate'" : "NULL") . ", " . 
                    (!empty($category) ? "'$category'" : "NULL") . ", '$patientName')";

            } elseif ($tableName === "patient_records_2") {
                $cell = $sheet->getCell("{$colMemberCategory}$rowIndex");
                $memberCategory = $cell->getCalculatedValue(); 
                $batchData[] = "($fileId, '$sheetName', '$admissionDate', '$patientName', '$memberCategory')";
            } else {
                $memberCategory = trim($sheet->getCell("{$colMemberCategory}$rowIndex")->getValue());
                $icd10 = trim($sheet->getCell("{$colICD10}$rowIndex")->getValue());

                $batchData[] = "($fileId, '$sheetName', '$admissionDate', '$dischargeDate', '$memberCategory', '$patientName')";

                if (!empty($icd10)) {
                    $leadingCausesData[] = "($fileId, '$patientName', '$icd10', '$sheetName', '$memberCategory')";
                }
            }

            if (count($batchData) >= 500) {
                if ($tableName === "patient_records_3") {
                    $query = "INSERT INTO patient_records_3 (file_id, sheet_name_3, date_admitted, date_discharge, category, patient_name_3) VALUES " . implode(',', $batchData);
                } else if ($tableName === "patient_records_2") {
                    $query = "INSERT INTO patient_records_2 (file_id, sheet_name_2, admission_date_2, patient_name_2, category_2) VALUES " . implode(',', $batchData);
                } else {
                    $query = "INSERT INTO patient_records (file_id, sheet_name, admission_date, discharge_date, member_category, patient_name) VALUES " . implode(',', $batchData);
                }
                $conn->query($query);
                $batchData = [];
            }

            if (count($leadingCausesData) >= 500) {
                $query = "INSERT INTO leading_causes (file_id, patient_name, icd_10, sheet_name, category) VALUES " . implode(',', $leadingCausesData);
            }
        }

        if (!empty($batchData)) {
            if ($tableName === "patient_records_3") {
                $query = "INSERT INTO patient_records_3 (file_id, sheet_name_3, date_admitted, date_discharge, category, patient_name_3) VALUES " . implode(',', $batchData);
            } else if ($tableName === "patient_records_2") {
                $query = "INSERT INTO patient_records_2 (file_id, sheet_name_2, admission_date_2, patient_name_2, category_2) VALUES " . implode(',', $batchData);
            } else {
                $query = "INSERT INTO patient_records (file_id, sheet_name, admission_date, discharge_date, member_category, patient_name) VALUES " . implode(',', $batchData);
            }
            $conn->query($query);
        }

        if (!empty($leadingCausesData)) {
            $query = "INSERT INTO leading_causes (file_id, patient_name, icd_10, sheet_name, category) VALUES " . implode(',', $leadingCausesData);
            $conn->query($query);
        }
    }

    echo "File uploaded and processed successfully!";
} else {
    echo "No file uploaded.";
}

$conn->close();
?>
