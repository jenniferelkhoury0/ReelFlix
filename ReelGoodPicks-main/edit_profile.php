<?php
session_start();
if (!isset($_SESSION['id'])) {
    header('Location: login.html');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'movies_db');
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

$user_id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT * FROM USERS WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $bio      = trim($_POST['bio']      ?? '');
    $current_password  = $_POST['current_password']  ?? '';
    $new_password      = $_POST['new_password']      ?? '';
    $confirm_password  = $_POST['confirm_password']  ?? '';

    if (empty($fullname)) {
        $error = 'Full name is required.';
    } elseif (empty($username)) {
        $error = 'Username is required.';
    } else {
        $chk = $conn->prepare("SELECT id FROM USERS WHERE username = ? AND id != ?");
        $chk->bind_param('si', $username, $user_id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) $error = 'Username is already taken.';
        $chk->close();

        if (empty($error)) {
            $conn->begin_transaction();
            try {
                $upd = $conn->prepare("UPDATE USERS SET fullname = ?, username = ?, bio = ? WHERE id = ?");
                $upd->bind_param('sssi', $fullname, $username, $bio, $user_id);
                $upd->execute();
                $upd->close();

                if (!empty($current_password) && !empty($new_password)) {
                    $vfy = $conn->prepare("SELECT hashedpassword FROM USERS WHERE id = ?");
                    $vfy->bind_param('i', $user_id);
                    $vfy->execute();
                    $hashed = $vfy->get_result()->fetch_assoc()['hashedpassword'];
                    $vfy->close();
                    if (!password_verify($current_password, $hashed)) throw new Exception('Current password is incorrect.');
                    if ($new_password !== $confirm_password)          throw new Exception('New passwords do not match.');
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $pwd = $conn->prepare("UPDATE USERS SET hashedpassword = ? WHERE id = ?");
                    $pwd->bind_param('si', $new_hash, $user_id);
                    $pwd->execute();
                    $pwd->close();
                }

                $conn->commit();
                $_SESSION['fullname'] = $fullname;
                $_SESSION['username'] = $username;
                $success = 'Profile updated successfully!';

                // Refresh user data
                $stmt2 = $conn->prepare("SELECT * FROM USERS WHERE id = ?");
                $stmt2->bind_param('i', $user_id);
                $stmt2->execute();
                $user = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Edit your ReelFlix profile — update your name, username, bio and password." />
    <title>Edit Profile - ReelFlix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
    <style>
      .edit-profile-container {
        max-width: 700px;
        margin: 0 auto;
        background: var(--surface);
        border: 1px solid var(--glass-highlight);
        border-radius: var(--radius);
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
      }
      .form-label { color: var(--accent); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
      .form-control {
        background: rgba(0,0,0,0.3);
        color: var(--text);
        border: 1px solid var(--glass-border);
        border-radius: 10px;
        padding: 12px 14px;
        font-family: 'Inter', sans-serif;
        transition: all 0.3s ease;
      }
      .form-control:focus {
        background: rgba(0,0,0,0.5);
        color: var(--text);
        border-color: var(--accent);
        box-shadow: 0 0 12px var(--accent-glow);
      }
      .section-divider {
        border-top: 1px solid var(--glass-border);
        margin: 30px 0 24px;
        padding-top: 24px;
      }
      .section-divider h4 {
        color: var(--accent);
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 18px;
      }
    </style>
  </head>
  <body>
    <nav class="nav-bar reveal">
      <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="about.html">About Us</a></li>
        <li>
          <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius:20px">Menu</button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="random.php">Surprise Me</a></li>
              <li><a class="dropdown-item" href="watchlist.php">Watchlist</a></li>
              <li><a class="dropdown-item" href="trending.php">Trending</a></li>
              <li><a class="dropdown-item" href="toprated.php">Top Rated</a></li>
              <li><a class="dropdown-item" href="test.html">Movie Quiz</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            </ul>
          </div>
        </li>
      </ul>
    </nav>

    <header class="header reveal">
      <h1><b>EDIT PROFILE</b></h1>
    </header>

    <main class="main-content">
      <div class="edit-profile-container reveal">

        <?php if ($success): ?>
          <div style="background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.4);color:#4ade80;padding:14px 18px;border-radius:10px;margin-bottom:20px;">
            <?php echo htmlspecialchars($success); ?>
          </div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div style="background:rgba(229,9,20,0.12);border:1px solid rgba(229,9,20,0.4);color:#f87171;padding:14px 18px;border-radius:10px;margin-bottom:20px;">
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <form action="edit_profile.php" method="POST">
          <!-- Basic info -->
          <div class="mb-3">
            <label for="fullname" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="fullname" name="fullname"
                   value="<?php echo htmlspecialchars($user['FULLNAME'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username"
                   value="<?php echo htmlspecialchars($user['USERNAME'] ?? ''); ?>" required autocomplete="username" />
          </div>
          <div class="mb-3">
            <label for="bio" class="form-label">Bio</label>
            <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="Tell us a little about yourself…"><?php echo htmlspecialchars($user['BIO'] ?? ''); ?></textarea>
          </div>

          <!-- Password change -->
          <div class="section-divider">
            <h4>Change Password <span style="color:var(--muted);font-size:0.8rem;font-weight:400;">(leave blank to keep current)</span></h4>
          </div>
          <div class="mb-3">
            <label for="current_password" class="form-label">Current Password</label>
            <div class="input-password-wrapper">
              <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" />
              <button type="button" class="toggle-password" title="Show/hide">👁</button>
            </div>
          </div>
          <div class="mb-3">
            <label for="new_password" class="form-label">New Password</label>
            <div class="input-password-wrapper">
              <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password" />
              <button type="button" class="toggle-password" title="Show/hide">👁</button>
            </div>
          </div>
          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm New Password</label>
            <div class="input-password-wrapper">
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password" />
              <button type="button" class="toggle-password" title="Show/hide">👁</button>
            </div>
          </div>

          <div style="display:flex;gap:12px;margin-top:10px;">
            <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
            <a href="profile.php" class="btn" style="border-color:var(--glass-highlight);color:var(--muted);">Cancel</a>
          </div>
        </form>
      </div>
    </main>

    <div class="copyright-wrapper reveal">&copy; 2026 ReelFlix &#124; All rights reserved</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js"></script>
  </body>
</html>
