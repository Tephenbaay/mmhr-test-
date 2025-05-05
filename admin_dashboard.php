<?php
session_start();
include 'config.php';
$db_name = 'mmhr_census'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quota'])) {
  $new_quota = (int) $_POST['new_quota'];
  if ($new_quota > 0) {
      $stmt = $conn->prepare("UPDATE storage_settings SET quota_mb = ? LIMIT 1");
      $stmt->bind_param("i", $new_quota);
      $stmt->execute();
  }
}

$quota_query = $conn->query("SELECT quota_mb FROM storage_settings LIMIT 1");
$max_quota_mb = 100;

if ($quota_query && $quota_query->num_rows > 0) {
  $row = $quota_query->fetch_assoc();
  $max_quota_mb = $row['quota_mb'];
}

$sql = "
SELECT 
  table_schema AS db_name, 
  ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb
FROM information_schema.tables 
WHERE table_schema = '$db_name' 
GROUP BY table_schema
";

$result = $conn->query($sql);
$db_size_mb = 0;

if ($row = $result->fetch_assoc()) {
  $db_size_mb = $row['db_size_mb'];
}

$used_percent = min(round(($db_size_mb / $max_quota_mb) * 100, 2), 100);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if (isset($_POST['add_maintenance'])) {
  $log = $conn->real_escape_string($_POST['maintenance_log']);
  $conn->query("INSERT INTO maintenance_logs (log) VALUES ('$log')");
  header("Location: admin_dashboard.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $performed_by = $_SESSION['role'] ?? $_SESSION['username'] ?? 'Unknown';

    if (isset($_POST['reset_system'])) {
     
        $conn->query("TRUNCATE TABLE patient_records");
        $conn->query("TRUNCATE TABLE patient_records_2");
        $conn->query("TRUNCATE TABLE patient_records_3");
        $conn->query("TRUNCATE TABLE leading_causes");
        $conn->query("TRUNCATE TABLE admin_notes");

        $conn->query("INSERT INTO system_logs (action, performed_by) VALUES ('System reset', '$performed_by')");
        echo "<script>alert('System data has been reset.');</script>";
    }

    if (isset($_POST['delete_uploads'])) {
        $upload_dir = 'uploads/';
        $files = glob($upload_dir . '*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $conn->query("INSERT INTO system_logs (action, performed_by) VALUES ('Deleted all uploads', '$performed_by')");
        echo "<script>alert('All uploaded files have been deleted.');</script>";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];

            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit;
        } else {
            $error = "❌ Incorrect password!";
        }
    } else {
        $error = "❌ Email not found!";
    }
}

if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $password, $role);
    $stmt->execute();

    header("Location: admin_dashboard.php#manage-users");
    exit;
}

if (isset($_POST['save_note'])) {
  $note = $_POST['note'];
  $stmt = $conn->prepare("INSERT INTO admin_notes (note) VALUES (?)");
  $stmt->bind_param("s", $note);
  $stmt->execute();
}

$total_space = disk_total_space("C:"); 
$free_space = disk_free_space("C:");
$used_space = $total_space - $free_space;

$used_percent = ($used_space / $total_space) * 100;
$used_percent = round($used_percent, 2);

if (isset($_POST['backup_db'])) {
  $backup_dir = 'backups/';
  if (!is_dir($backup_dir)) {
      mkdir($backup_dir, 0755, true); 
  }

  $backup_file = $backup_dir . 'mmhr_census_backup_' . date('Ymd_His') . '.sql';
  $db_user = 'root';         
  $db_pass = '';             
  $db_name = 'mmhr_census';  

  $command = "\"C:\\xampp\\mysql\\bin\\mysqldump.exe\" -u$db_user ". ($db_pass ? "-p$db_pass " : "") ."$db_name > \"$backup_file\"";
  system($command, $retval);

  if ($retval === 0) {
      $conn->query("INSERT INTO system_logs (action, performed_by) VALUES ('Database backup created', '$performed_by')");
      echo "<script>alert('Backup created successfully!');</script>";
  } else {
      echo "<script>alert('Backup failed. Make sure mysqldump.exe is correctly configured.');</script>";
  }
}

$logs_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $logs_per_page;

$total_logs_result = $conn->query("SELECT COUNT(*) AS total FROM system_logs");
$total_logs = $total_logs_result->fetch_assoc()['total'];
$total_pages = ceil($total_logs / $logs_per_page);

$logs = $conn->query("SELECT * FROM system_logs ORDER BY timestamp DESC LIMIT $logs_per_page OFFSET $offset");

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="sige/admin_dashboard.css">
  <link rel="icon" href="sige/download-removebg-preview.png" type="image/png">
</head>
<body>
  <div class="navbar">
    <h1>Admin Dashboard</h1>
    <div>Welcome, Admin</div>
  </div>

  <div class="sidebar">
    <a href="display_summary.php">MMHR Dashboard</a>
    <a href="#">Manage Users</a>
    <a href="#">Settings</a>
    <a href="logout.php">Logout</a>
  </div>

  <div class="main-content">
    <div class="card">
  <h2>📋 Updates</h2>

  <form action="admin_dashboard.php" method="POST" class="add-update-form">
    <div class="form-field">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" placeholder="Update title" required>
    </div>

    <div class="form-field">
      <label for="description">Description</label>
      <textarea id="description" name="description" rows="4" placeholder="Enter update details..." required></textarea>
    </div>

    <button type="submit" name="add_update" class="submit-btn">Add Update</button>
  </form>

<?php
  if (isset($_POST['add_update'])) {
      $title = $_POST['title'];
      $description = $_POST['description'];

      $stmt = $conn->prepare("INSERT INTO updates (title, description) VALUES (?, ?)");
      $stmt->bind_param("ss", $title, $description);
      $stmt->execute();

      echo "<p class='success-message'>✅ Update added successfully!</p>";
  }
  ?>

    <div class="existing-updates">
      <h3>Existing Updates</h3>
      <ul>
        <?php
        $result = $conn->query("SELECT * FROM updates ORDER BY created_at DESC LIMIT 5");
        while ($update = $result->fetch_assoc()) {
          echo "<li>
                  <strong>{$update['title']}</strong><br>
                  {$update['description']}<br>
                  <small>Added on: {$update['created_at']}</small><br>
                  <a href='edit_update.php?id={$update['id']}' class='edit-btn'>Edit</a> | 
                  <a href='delete_update.php?id={$update['id']}' class='delete-btn' onclick='return confirm(\"Are you sure you want to delete this update?\")'>Delete</a>
                </li><hr>";
        }
        ?>
      </ul>

      <div class="pagination">
        <a href="admin_dashboard.php?page=1">1</a>
        <a href="admin_dashboard.php?page=2">2</a>
        <a href="admin_dashboard.php?page=3">3</a>
      </div>
    </div>
  </div>

  <div class="card">
    <h2>🛠️ Maintenance</h2>
    <form action="admin_dashboard.php" method="POST" style="margin-bottom: 15px;">
      <textarea name="maintenance_log" rows="3" placeholder="Add maintenance note..." required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc;"></textarea>
      <button type="submit" name="add_maintenance" style="margin-top: 10px; padding: 8px 16px; background: #007BFF; color: white; border: none; border-radius: 5px;">Add Log</button>
    </form>

  <div class="table-container">
    <table class="user-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Log</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
          $result = $conn->query("SELECT * FROM maintenance_logs ORDER BY created_at DESC");
          while ($log = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$log['id']}</td>
                    <td>{$log['log']}</td>
                    <td>{$log['created_at']}</td>
                    <td>
                      <a href='delete_maintenance.php?id={$log['id']}' class='delete-btn' onclick='return confirm(\"Delete this maintenance log?\")'>Delete</a>
                    </td>
                  </tr>";
          }
        ?>
      </tbody>
    </table>
  </div>
</div>

  <div class="card">
    <h2>📊 Storage Graph</h2>
    <p>Total Quota: <?php echo $max_quota_mb; ?> MB</p>
    <p>Used: <?php echo $db_size_mb; ?> MB (<?php echo $used_percent; ?>%)</p>

    <div class="graph-bar-container">
      <div class="graph-bar-used" style="width: <?php echo $used_percent; ?>%;"></div>
    </div>
    <button class="custom-btn" onclick="toggleQuotaEdit()">✏️ Edit Quota</button>

    <form id="quotaForm" method="POST" class="quota-form">
      <input type="number" name="new_quota" min="1" value="<?php echo $max_quota_mb; ?>" required> MB
      <button type="submit" name="update_quota" class="custom-save-btn">Save</button>
    </form>

  </div>

  <div class="card restricted">
    <h2>🔐 Admin-Only Actions</h2>
    <form method="POST" onsubmit="return confirm('Are you sure? This action cannot be undone.');">
      <button type="submit" name="reset_system" class="admin-btn">🔄 Reset System</button>
      <button type="submit" name="delete_uploads" class="admin-btn">🗑️ Delete Uploads</button>
      <button type="submit" name="backup_db" class="admin-btn">💾 Backup Database</button>
    </form>
  </div>

<div class="card" id="system-logs">
  <h2>📁 System Logs</h2>
  <table class="user-table">
    <thead>
      <tr><th>Timestamp</th><th>Action</th><th>Performed By</th></tr>
    </thead>
      <tbody>
        <?php while ($log = $logs->fetch_assoc()): ?>
          <tr>
            <td><?= $log['timestamp'] ?></td>
            <td><?= $log['action'] ?></td>
            <td><?= $log['performed_by'] ?></td>
          </tr>
        <?php endwhile; ?>
    </tbody>
  </table>
  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="?page=<?= $page - 1 ?>">⬅️ Previous</a>
    <?php endif; ?>

    <span>Page <?= $page ?> of <?= $total_pages ?></span>

    <?php if ($page < $total_pages): ?>
      <a href="?page=<?= $page + 1 ?>">Next ➡️</a>
    <?php endif; ?>
  </div>
</div>

    <div class="card">
      <h2>📝 Admin Notes / Logs</h2>
      <p>These notes are visible to users for awareness and transparency.</p>
      <form action="admin_dashboard.php" method="POST">
        <textarea name="note" rows="4" style="width: 100%;" required></textarea>
        <br><br>
        <button type="submit" name="save_note" style="padding: 8px 16px; background-color: #1e3a8a; color: white; border: none; border-radius: 4px;">Save Note</button>
      </form>
    </div>

    <div class="card" id="manage-users">
      <h2>👥 Manage Users</h2>

      <h3>Add New User</h3>
      <div class="form-container">
        <form action="admin_dashboard.php" method="POST" class="add-user-form">
          <input type="text" name="username" placeholder="Username" required>
          <input type="email" name="email" placeholder="Email" required>
          <input type="password" name="password" placeholder="Password" required>
          <select name="role">
            <option value="user">User</option>
            <option value="admin">Admin</option>
          </select>
          <button type="submit" name="add_user">➕ Add User</button>
        </form>
      </div>

<h3>Existing Users</h3>
<div class="table-container">
  <table class="user-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>Role</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
        $result = $conn->query("SELECT * FROM users");
        while ($user = $result->fetch_assoc()) {
          echo "<tr>
                  <td>{$user['id']}</td>
                  <td>{$user['username']}</td>
                  <td>{$user['email']}</td>
                  <td>{$user['role']}</td>
                  <td>
                    <a href='edit_user.php?id={$user['id']}' class='edit-btn'>Edit</a> | 
                    <a href='delete_user.php?id={$user['id']}' class='delete-btn'>Delete</a>
                  </td>
                </tr>";
        }
      ?>
    </tbody>
  </table>
    </div>
  </div>

<script>
  function toggleQuotaEdit() {
    const form = document.getElementById('quotaForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
  }

  document.addEventListener("DOMContentLoaded", function() {
    if (window.location.hash === "#system-logs") {
      const target = document.getElementById("system-logs");
      if (target) {
        target.scrollIntoView({ behavior: "smooth" });
      }
    }
  });
</script>
</body>
</html>