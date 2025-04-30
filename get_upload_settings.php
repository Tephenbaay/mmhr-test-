<?php
$conn = new mysqli("localhost", "root", "", "mmhr_census");

$settings = ['max_upload_files', 'max_file_size_mb', 'allowed_file_extensions'];
$output = [];

foreach ($settings as $key) {
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $stmt->bind_result($value);
    $stmt->fetch();
    $output[$key] = $value;
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($output);
