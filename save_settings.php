<?php
$mysqli = new mysqli("localhost", "root", "", "mmhr_census");
foreach ($_POST as $key => $value) {
    $stmt = $mysqli->prepare("REPLACE INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
    $stmt->bind_param("ss", $key, $value);
    $stmt->execute();
}
echo "Settings saved.";
?>
