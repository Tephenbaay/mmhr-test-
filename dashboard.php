<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php"); 
    exit;
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "mmhr_census";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$files = $conn->query("SELECT * FROM uploaded_files");

$totalFiles = $files->num_rows;
$maxFilesAllowed = 10; // Set limit here

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MMHR Census</title>
    <link rel="stylesheet" href="sige/style.css">
    <link rel="icon" href="sige/download-removebg-preview.png" type="image/png">
    <script>
    const totalFiles = <?= $totalFiles ?>;
    const maxFiles = <?= $maxFilesAllowed ?>;

    window.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('form[action="upload.php"]');
        const fileInput = form.querySelector('input[type="file"]');

        form.addEventListener('submit', (e) => {
            const file = fileInput.files[0];
            if (!file) return; // allow empty

            if (totalFiles >= maxFiles) {
                alert(`❌ Upload limit reached. Only ${maxFiles} files allowed.`);
                e.preventDefault();
                return;
            }

            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                alert("❌ File is too large. Max size is 5MB.");
                e.preventDefault();
                return;
            }
        });
    });
</script>

    <style>
        .nav-tools {
            display: flex;
            align-items: center;
            gap: 20px;
            padding-left: 70%;
            position: relative;
        }

        .nav-tools .dropdown {
            position: relative;
            display: inline-block;
        }

        .nav-tools .dropbtn {
            background-color: rgb(21, 126, 21);
            color: white;
            padding: 8px 12px;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .nav-tools .dropbtn:hover {
            background-color: rgb(11, 104, 11);
        }

        .nav-tools .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 160px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            border-radius: 8px;
            z-index: 1;
            text-align: left;
        }

        .nav-tools .dropdown-content a {
            color: black;
            padding: 10px 16px;
            text-decoration: none;
            display: block;
            font-weight: normal;
            border-bottom: 1px solid #ddd;
        }

        .nav-tools .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .nav-tools .dropdown:hover .dropdown-content {
            display: block;
        }
    </style>
</head>

<body style="background-image: url('sige/bgg.png'); background-size: cover; background-repeat: no-repeat;">
    
<!-- ✅ Navbar -->
<nav class="navbar">
    <div class="navb">
        <img src="sige/download-removebg-preview.png" alt="icon">
        <div class="nav-text">
            <h1 style="margin-bottom: -15px;">BicutanMed</h1>
            <p>Caring For Life</p>
        </div>
        <div class="nav-links">
            <a href="https://bicutanmed.com/about-us"></a>
            <a href="#"></a>
            <a href="#"></a>
            <a href="#"></a>
        </div>

        <!-- ✅ Tools Dropdown (CSS only) -->
        <div class="nav-tools">
            <div class="dropdown">
                <button class="dropbtn">Tools</button>
                <div class="dropdown-content">
                    <a href="export.php">Export Data</a>
                    <a href="backup.php">Download Backup</a>
                    <a href="maintenance.php">Maintenance</a>
                    <a href="settings.php">Settings</a>
                    <a href="clear_data.php" style="color: red;">Clear All Data</a>
                </div>
            </div>
        </div>

        <!-- Logout Button -->
        <a href="logout.php" style="margin-left: 20px;" class="logout">
            <img src="sige/power-off.png" alt="logout" style="width: 35px; height: 35px;" class="logout">
        </a>
    </div>
</nav>

<!-- ✅ Main -->
<div class="main">
    <div class="container">
        <h2>Upload Excel File</h2>
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <input type="file" name="excelFile" accept=".xlsx, .xls">
            <button type="submit" class="btn1">Upload</button>
        </form>
    </div>

    <div class="content">
        <h2>Select File & Sheet</h2>
        <form action="display_summary.php" method="GET">
            <label for="file">Select File:</label>
            <select name="file_id" id="file">
                <?php while ($file = $files->fetch_assoc()): ?>
                    <option value="<?= $file['id'] ?>"><?= $file['file_name'] ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="submit">Load Sheets</button>
        </form>
    </div>
</div>
</body>
</html>
