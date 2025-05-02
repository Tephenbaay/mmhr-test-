<?php
session_start();
include 'config.php';

// Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php"); // or your login page
    exit;
}

if (isset($_POST['add_maintenance'])) {
  $log = $conn->real_escape_string($_POST['maintenance_log']);
  $conn->query("INSERT INTO maintenance_logs (log) VALUES ('$log')");
  header("Location: admin_dashboard.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['reset_system'])) {
      // Example: Truncate certain tables
      $conn->query("TRUNCATE TABLE patient_records");
      $conn->query("TRUNCATE TABLE patient_records_2");
      $conn->query("TRUNCATE TABLE patient_records_3");
      $conn->query("TRUNCATE TABLE leading_causes");
      $conn->query("TRUNCATE TABLE admin_notes");
      $conn->query("TRUNCATE TABLE updates_log");
      $conn->query("INSERT INTO system_logs (action, performed_by) VALUES ('System reset', '$admin')");
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
      $conn->query("INSERT INTO system_logs (action, performed_by) VALUES ('Deleted all uploads', '$admin')");
      echo "<script>alert('All uploaded files have been deleted.');</script>";
  }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Login success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];

            // Redirect to dashboard
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

$total_space = disk_total_space("C:");  // or "/" on Linux
$free_space = disk_free_space("C:");
$used_space = $total_space - $free_space;

$used_percent = ($used_space / $total_space) * 100;
$used_percent = round($used_percent, 2);

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
    <a href="#">Dashboard</a>
    <a href="#">Manage Users</a>
    <a href="#">Settings</a>
    <a href="logout.php">Logout</a>
  </div>

  <div class="main-content">
    <!-- Updates Card -->
    <div class="card">
  <h2>📋 Updates</h2>

  <!-- Add Update Form -->
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
  // Handle Add Update Form Submission
  if (isset($_POST['add_update'])) {
      $title = $_POST['title'];
      $description = $_POST['description'];

      $stmt = $conn->prepare("INSERT INTO updates (title, description) VALUES (?, ?)");
      $stmt->bind_param("ss", $title, $description);
      $stmt->execute();

      echo "<p class='success-message'>✅ Update added successfully!</p>";
  }
  ?>

  <!-- Display Existing Updates -->
  <div class="existing-updates">
    <h3>Existing Updates</h3>
    <ul>
      <?php
      $result = $conn->query("SELECT * FROM updates ORDER BY created_at DESC LIMIT 5");  // Pagination: Limit 5 updates per page
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

    <!-- Pagination: Show Next 5 Updates -->
    <div class="pagination">
      <a href="admin_dashboard.php?page=1">1</a>
      <a href="admin_dashboard.php?page=2">2</a>
      <a href="admin_dashboard.php?page=3">3</a>
    </div>
  </div>
</div>

    <!-- Maintenance Card -->
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

    <!-- Storage Graph Card -->
    <div class="card">
  <h2>📊 Storage Graph</h2>
  <p>Total Space: <?php echo round($total_space / 1_073_741_824, 2); ?> GB</p>
  <p>Used: <?php echo round($used_space / 1_073_741_824, 2); ?> GB (<?php echo $used_percent; ?>%)</p>

  <div class="graph-bar-container">
    <div class="graph-bar-used" style="width: <?php echo $used_percent; ?>%;"></div>
  </div>
</div>

    <!-- Admin-Only Actions Card (Restricted) -->
    <div class="card restricted">
  <h2>🔐 Admin-Only Actions</h2>
  <form method="POST" onsubmit="return confirm('Are you sure? This action cannot be undone.');">
    <button type="submit" name="reset_system" class="admin-btn">🔄 Reset System</button>
    <button type="submit" name="delete_uploads" class="admin-btn">🗑️ Delete Uploads</button>
    <a href="#user-management" class="admin-btn-link">🔧 Manage Permissions</a>
  </form>
</div>

<div class="card">
  <h2>📁 System Logs</h2>
  <table class="user-table">
    <thead>
      <tr><th>Timestamp</th><th>Action</th><th>Performed By</th></tr>
    </thead>
    <tbody>
      <?php
        $logs = $conn->query("SELECT * FROM system_logs ORDER BY timestamp DESC");
        while ($log = $logs->fetch_assoc()) {
          echo "<tr>
                  <td>{$log['timestamp']}</td>
                  <td>{$log['action']}</td>
                  <td>{$log['performed_by']}</td>
                </tr>";
        }
      ?>
    </tbody>
  </table>
</div>

    <!-- Admin Notes Card -->
    <div class="card">
      <h2>📝 Admin Notes / Logs</h2>
      <p>These notes are visible to users for awareness and transparency.</p>
      <form action="admin_dashboard.php" method="POST">
        <textarea name="note" rows="4" style="width: 100%;" required></textarea>
        <br><br>
        <button type="submit" name="save_note" style="padding: 8px 16px; background-color: #1e3a8a; color: white; border: none; border-radius: 4px;">Save Note</button>
      </form>
    </div>

    <!-- Manage Users Card -->
    <div class="card" id="manage-users">
      <h2>👥 Manage Users</h2>

      <!-- Add User Form -->
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
</body>
</html>