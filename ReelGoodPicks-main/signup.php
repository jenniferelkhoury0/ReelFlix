<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Create your ReelFlix account and start discovering great movies." />
    <title>Sign Up - ReelFlix</title>
    <link rel="icon" type="image/x-icon" href="images/Popcorn-icon.png" />
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body style="display:flex;justify-content:center;align-items:center;min-height:100vh;padding:20px 0;">
    <div class="signup-container reveal">

      <div class="auth-logo">
        <h2><span style="color:#e50914;">REEL</span><span style="color:#fff;">FLIX</span></h2>
        <p style="color:var(--muted);font-size:0.9rem;margin-top:4px;">Join the multiverse — it's free.</p>
      </div>

      <?php if (!empty($_SESSION['error'])): ?>
        <p style="color:#e50914;text-align:center;margin-bottom:14px;font-size:0.9rem;">
          <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </p>
      <?php endif; ?>

      <form id="signupForm" action="signuplogic.php" method="POST">
        <div class="input-group">
          <label for="fullname">Full Name</label>
          <input type="text" id="fullname" name="fullname" placeholder="Peter Parker" required />
        </div>
        <div class="input-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" required />
        </div>
        <div class="input-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="choose_a_username" required autocomplete="username" />
        </div>
        <div class="input-group">
          <label for="password">Password</label>
          <div class="input-password-wrapper">
            <input type="password" id="password" name="password" placeholder="••••••••" required autocomplete="new-password" />
            <button type="button" class="toggle-password" title="Show/hide password">👁</button>
          </div>
        </div>
        <div class="input-group">
          <label for="confirm_password">Confirm Password</label>
          <div class="input-password-wrapper">
            <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required autocomplete="new-password" />
            <button type="button" class="toggle-password" title="Show/hide password">👁</button>
          </div>
        </div>
        <div class="input-group">
          <label for="dob">Date of Birth</label>
          <input type="date" id="dob" name="dob" required />
        </div>
        <button type="submit">Create Account</button>
      </form>

      <p id="loginLink" style="text-align:center;margin-top:16px;">
        Already have an account? <a href="login.html">Login</a>
      </p>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js"></script>
  </body>
</html>
