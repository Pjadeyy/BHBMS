<?php

session_start();

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Boarding House Dashboard</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="../style.css">
</head>

<body>

  <!-- Sidebar -->
  <div class="d-flex">
    <nav class="sidebar  text-white p-3">
      <div class="text-center mb-4">
        <img src="https://musnvi.42web.io/uploads/68160de3dab6c.png" alt="Logo" class="img-fluid" style="max-width: 100px;">
        <h5 class="mt-2">Rent Billing Management System</h5>
      </div>
      <!-- Admin Profile Section -->
      <!-- Admin Profile Section -->
      <a href="../admin.php"
        class="d-flex align-items-center justify-content-between mb-4 px-2 nav-link admin-border">
        <div class="d-flex align-items-center">
          <span class="material-icons me-2" style="font-size: 32px;">person</span>
          <span class="fw-bold">Admin</span>
        </div>
        <span class="material-icons" style="font-size: 20px;">chevron_right</span>
      </a>

      <ul class="nav flex-column">
        <li class="nav-item mb-2">
          <a class="nav-link text-white" href="../dashboard.php"><span
              class="material-icons me-2">dashboard</span>Dashboard</a>
        </li>
        <li class="nav-item mb-2">
          <a class="nav-link text-white" href="../rent_payment.php"><span
              class="material-icons me-2">payments</span>Rent Payment</a>
        </li>
        <li class="nav-item mb-2">
          <a class="nav-link text-white" href="../receipt.php"><span
              class="material-icons me-2">receipt</span>Receipt/Invoice</a>
        </li>
        <li class="nav-item mb-2">
          <a class="nav-link text-white" href="../financial_rep.php"><span
              class="material-icons me-2">assessment</span>Financial Report</a>
        </li>
        <li class="nav-item mb-2">
          <a class="nav-link text-white" href="../boarder_profile.php"><span
              class="material-icons me-2">group</span>Boarders Profile</a>
        </li>
        <li class="nav-item mb-2">
          <a class="nav-link text-white" href="../communication.php"><span
              class="material-icons me-2">chat</span>Communication</a>
        </li>
      </ul>

      <div class="mt-2">
        <h6 class="text-uppercase small">Sub Menu</h6>
        <ul class="nav flex-column">
          <li class="nav-item mb-2">
            <a class="nav-link text-white" href="../sub-menu/notif.php"><span
                class="material-icons me-2">notifications</span>Notification</a>
          </li>
          <li class="nav-item mb-2">
            <a class="nav-link text-white active" href="../sub-menu/settings.php"><span
                class="material-icons me-2">settings</span>Settings</a>
          </li>
          <li class="nav-item mb-2">
            <a class="nav-link text-white" onclick="logout()">
              <span class="material-icons me-2">logout</span> Log Out
            </a>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Main Content -->
    <div class="flex-grow-1">
      <!-- Header -->
      <header class="d-flex justify-content-between align-items-center p-3 border-bottom">
        <h4 class="mb-0" style="font-weight: bold;">Kring-Kring Ladies Boarding House</h4>
        <div class="d-flex align-items-center gap-3">
          <div class="input-group">
            <input type="text" class="form-control" placeholder="Search...">
            <button class="btn " type="button">
              <span class="material-icons">search</span>
            </button>
          </div>
          <button class="btn btn-outline-primary position-relative">
            <span class="material-icons">notifications</span>
          </button>
          <button class="btn btn-outline-secondary">
            <span class="material-icons">calendar_month</span>
          </button>
        </div>
      </header>

      <!-- Main Dashboard Content-->
      <div class="content p-4">

        <!-- Dashboard Title -->
        <div class="mb-4">
          <h5 style="font-weight: bold; color: #360938; border-bottom: 2px solid #360938; padding-bottom: 10px;">
            SETTINGS
          </h5>

          <div class="container mt-5">
            <div class="border p-4 rounded shadow-sm">
              <h4 class="mb-4">Admin Settings</h4>
          
              <!-- Update Profile -->
              <form id="updateProfileForm">
                <h5>Profile Info</h5>
                <div class="mb-3">
                  <label for="adminName" class="form-label">Full Name</label>
                  <input type="text" class="form-control" id="adminName" placeholder="Admin Name">
                </div>
                <div class="mb-3">
                  <label for="adminEmail" class="form-label">Email</label>
                  <input type="email" class="form-control" id="adminEmail" placeholder="admin@example.com">
                </div>
                <button type="submit" class="btn btn-primary">Update Profile</button>
              </form>
          
              <hr class="my-4">
          
              <!-- Change Password -->
              <form id="changePasswordForm">
                <h5>Change Password</h5>
                <div class="mb-3">
                  <label for="currentPassword" class="form-label">Current Password</label>
                  <input type="password" class="form-control" id="currentPassword">
                </div>
                <div class="mb-3">
                  <label for="newPassword" class="form-label">New Password</label>
                  <input type="password" class="form-control" id="newPassword">
                </div>
                <div class="mb-3">
                  <label for="confirmPassword" class="form-label">Confirm New Password</label>
                  <input type="password" class="form-control" id="confirmPassword">
                </div>
                <button type="submit" class="btn btn-warning">Change Password</button>
              </form>
          
              <hr class="my-4">
          
              <!-- Notification Preferences -->
              <div id="notificationSettings">
                <h5>Notification Preferences</h5>
                <div class="form-check form-switch mb-2">
                  <input class="form-check-input" type="checkbox" id="emailNotif" checked>
                  <label class="form-check-label" for="emailNotif">Email Notifications</label>
                </div>
                <div class="form-check form-switch mb-2">
                  <input class="form-check-input" type="checkbox" id="smsNotif">
                  <label class="form-check-label" for="smsNotif">SMS Notifications</label>
                </div>
              </div>
          
              <hr class="my-4">
          
              <!-- System Settings -->
              <div id="systemSettings">
                <h5>System Settings</h5>
                <div class="mb-3">
                  <label for="defaultLanguage" class="form-label">Default Language</label>
                  <select class="form-select" id="defaultLanguage">
                    <option selected>English</option>
                    <option>Filipino</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label for="timezone" class="form-label">Time Zone</label>
                  <select class="form-select" id="timezone">
                    <option selected>Asia/Manila</option>
                    <option>UTC</option>
                    <option>America/New_York</option>
                  </select>
                </div>
              </div>
          
              <button class="btn btn-success mt-3">Save Settings</button>
            </div>
          </div>
          
        </div>
      </div>

      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
      <script src="../sidebar/scripts.js"></script>

</body>

</html>