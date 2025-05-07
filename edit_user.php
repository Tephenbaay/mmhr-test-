<?php
session_start();
include 'config.php';
$db_name = 'mmhr_census'; 

// Check if ID is set in URL
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Fetch user data from the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        echo "<div class='alert alert-danger'>User not found.</div>";
        exit;
    }

    // Handle form submission for updating user details
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $role = $_POST['role'];

        // Update user information in the database
        $update_stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
        $update_stmt->bind_param("sssi", $username, $email, $role, $user_id);
        $update_stmt->execute();

        // Redirect to the admin dashboard after successful update
        header("Location: admin_dashboard.php#manage-users");
        exit;
    }
} else {
    echo "<div class='alert alert-danger'>Invalid user ID.</div>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update General Settings
    if (isset($_POST['update_general_settings'])) {
        $site_title = $_POST['site_title'];
        $logo = $_POST['logo'];
        $timezone = $_POST['timezone'];
  
        // Update the correct columns for site title, logo, and timezone
        $stmt = $conn->prepare("UPDATE settings SET site_title = ?, logo = ?, timezone = ? WHERE id = 1");
        $stmt->bind_param("sss", $site_title, $logo, $timezone);
        $stmt->execute();
    }
  
    // Update User Management Settings
    if (isset($_POST['update_user_settings'])) {
        $default_role = $_POST['default_role'];
  
        // Update the correct column for default role
        $stmt = $conn->prepare("UPDATE settings SET default_role = ? WHERE id = 1");
        $stmt->bind_param("s", $default_role);
        $stmt->execute();
    }
  
    // Update File Management Settings
    if (isset($_POST['update_file_settings'])) {
        $max_upload_size = $_POST['max_upload_size'];
  
        // Update the correct column for max file size
        $stmt = $conn->prepare("UPDATE settings SET max_file_size_mb = ? WHERE id = 1");
        $stmt->bind_param("i", $max_upload_size);
        $stmt->execute();
    }
  
    // Update Email Settings
    if (isset($_POST['update_email_settings'])) {
        $smtp_server = $_POST['smtp_server'];
        $smtp_port = $_POST['smtp_port'];
  
        // Update the correct columns for SMTP server and port
        $stmt = $conn->prepare("UPDATE settings SET smtp_server = ?, smtp_port = ? WHERE id = 1");
        $stmt->bind_param("si", $smtp_server, $smtp_port);
        $stmt->execute();
    }
  
    // Update Audit Logs Settings
    if (isset($_POST['update_audit_settings'])) {
        $audit_logging = isset($_POST['audit_logging']) ? 1 : 0;
  
        // Update the correct column for audit logging
        $stmt = $conn->prepare("UPDATE settings SET audit_logging = ? WHERE id = 1");
        $stmt->bind_param("i", $audit_logging);
        $stmt->execute();
    }
  }
  
  $result = $conn->query("SELECT * FROM settings WHERE id = 1");
  $settings = $result->fetch_assoc();
  
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="sige/admin_dashboard.css">
    <link rel="icon" href="sige/download-removebg-preview.png" type="image/png">
</head>
<body>
    <div class="navbar">
        <h1>Edit User</h1>
    </div>

    <div class="sidebar">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_dashboard.php">Manage Users</a>
        <a href="settings.php" id="settingsBtn">Settings</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="main-content">
        <div class="card">
            <h2>👥 Edit User</h2>

            <!-- Edit User Form -->
            <h3>Edit User</h3>
            <div class="form-container">
            <form action="edit_user.php?id=<?php echo $user['id']; ?>" method="POST" class="edit-user-form">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo $user['username']; ?>" placeholder="Username" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" placeholder="Email" required>

                <label for="role">Role</label>
                <select name="role" id="role">
                <option value="user" <?php echo ($user['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>

                <button type="submit">✅ Update User</button>
            </form>
            </div>
        </div>
    </div>
    <div class="floating-settings-card" id="settingsCard" style="display: none;">
    <div class="settings-content">
    <h2>Settings</h2>

    <!-- General Settings -->
<div class="section">
  <h3>General Settings</h3>
  <form action="admin_dashboard.php" method="POST">
    <label for="site-title">Site Title:</label>
    <input type="text" id="site-title" name="site_title" value="<?= htmlspecialchars($settings['site_title']) ?>" required>

    <label for="logo">Logo (URL):</label>
    <input type="text" id="logo" name="logo" value="<?= htmlspecialchars($settings['logo']) ?>">

    <label for="timezone">Timezone:</label>
    <select id="timezone" name="timezone">
      <option value="UTC" <?= ($settings['timezone'] === 'UTC') ? 'selected' : '' ?>>UTC</option>
      <option value="GMT" <?= ($settings['timezone'] === 'GMT') ? 'selected' : '' ?>>GMT</option>
      <option value="PST" <?= ($settings['timezone'] === 'PST') ? 'selected' : '' ?>>PST</option>
    </select>

    <button type="submit" name="update_general_settings">Save Settings</button>
  </form>
</div>

<!-- User Management Settings -->
<div class="section">
  <h3>User Management Settings</h3>
  <form action="admin_dashboard.php" method="POST">
    <label for="default-role">Default User Role:</label>
    <select id="default-role" name="default_role">
      <option value="user" <?= ($settings['default_role'] === 'user') ? 'selected' : '' ?>>User</option>
      <option value="admin" <?= ($settings['default_role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
    </select>

    <button type="submit" name="update_user_settings">Save Settings</button>
  </form>
</div>

<!-- File Management Settings -->
<div class="section">
  <h3>File Management</h3>
  <form action="admin_dashboard.php" method="POST">
    <label for="max-upload-size">Max File Upload Size (MB):</label>
    <input type="number" id="max-upload-size" name="max_upload_size" value="<?= htmlspecialchars($settings['max_file_size_mb']) ?>" min="1">

    <button type="submit" name="update_file_settings">Save Settings</button>
  </form>
</div>

<!-- Email Settings -->
<div class="section">
  <h3>Email Settings</h3>
  <form action="admin_dashboard.php" method="POST">
    <label for="smtp-server">SMTP Server:</label>
    <input type="text" id="smtp-server" name="smtp_server" value="<?= htmlspecialchars($settings['smtp_server']) ?>">

    <label for="smtp-port">SMTP Port:</label>
    <input type="number" id="smtp-port" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port']) ?>">

    <button type="submit" name="update_email_settings">Save Settings</button>
  </form>
</div>

<!-- Audit Logs Settings -->
<div class="section">
  <h3>Audit Logs</h3>
  <form action="admin_dashboard.php" method="POST">
    <label for="audit-logging">Enable Audit Logging:</label>
    <input type="checkbox" id="audit-logging" name="audit_logging" <?= ($settings['audit_logging'] == 1) ? 'checked' : '' ?>>

    <button type="submit" name="update_audit_settings">Save Settings</button>
  </form>
</div>
  </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
  const dashboardLink = document.getElementById("dashboard-link");
  const manageUsersLink = document.getElementById("manage-users-link");
  const settingsBtn = document.getElementById("settingsBtn");
  const settingsCard = document.getElementById("settingsCard");
  const manageUsersSection = document.getElementById("manage-users");

  dashboardLink.addEventListener("click", function (e) {
    e.preventDefault();
    settingsCard.style.display = "none";
    window.scrollTo({ top: 0, behavior: "smooth" });
  });

  manageUsersLink.addEventListener("click", function (e) {
    e.preventDefault();
    settingsCard.style.display = "none";
    manageUsersSection.scrollIntoView({ behavior: "smooth" });
  });

  settingsBtn.addEventListener("click", function (e) {
    e.preventDefault();
    const isOpen = settingsCard.style.display === "block";
    settingsCard.style.display = isOpen ? "none" : "block";

    if (!isOpen) {
      settingsBtn.classList.add("active");
      dashboardLink.classList.remove("active");
      manageUsersLink.classList.remove("active");
    } else {
      settingsBtn.classList.remove("active");
      dashboardLink.classList.add("active");
    }
  });

  function updateSidebarHighlight() {
    const rect = manageUsersSection.getBoundingClientRect();
    const inView = rect.top < window.innerHeight && rect.bottom >= 100;
    const settingsOpen = settingsCard.style.display === "block";

    if (settingsOpen) {
      settingsBtn.classList.add("active");
      dashboardLink.classList.remove("active");
      manageUsersLink.classList.remove("active");
    } else if (inView) {
      manageUsersLink.classList.add("active");
      dashboardLink.classList.remove("active");
      settingsBtn.classList.remove("active");
    } else {
      dashboardLink.classList.add("active");
      manageUsersLink.classList.remove("active");
      settingsBtn.classList.remove("active");
    }
  }

  window.addEventListener("scroll", updateSidebarHighlight);
  window.addEventListener("resize", updateSidebarHighlight);
  updateSidebarHighlight();
});

</script>
</body>
</html>
