<?php
session_start();

$success_message = '';
$error_message   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']         ?? '');
    $email    = trim($_POST['email']        ?? '');
    $type     = trim($_POST['feedbackType'] ?? 'General Suggestion');
    $rating   = isset($_POST['rating']) && $_POST['rating'] !== '' ? intval($_POST['rating']) : null;
    $message  = trim($_POST['message']      ?? '');

    if ($name && $message) {
        $conn = new mysqli('localhost', 'root', '', 'movies_db');
        if (!$conn->connect_error) {
            $stmt = $conn->prepare(
                "INSERT INTO FEEDBACK (NAME, EMAIL, FEEDBACK_TYPE, RATING, MESSAGE) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('sssis', $name, $email, $type, $rating, $message);
            if ($stmt->execute()) {
                $success_message = "Thank you, {$name}! Your feedback has been saved.";
            } else {
                $error_message = 'Something went wrong saving your feedback. Please try again.';
            }
            $stmt->close();
            $conn->close();
        } else {
            $error_message = 'Database unavailable. Please try again later.';
        }
    } else {
        $error_message = 'Name and message are required.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Send feedback to ReelFlix — tell us what you love or what we can improve." />
    <title>Feedback - ReelFlix</title>
    <link rel="icon" type="image/x-icon" href="images/Popcorn-icon.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
      .feedback-container {
        max-width: 600px;
        margin: 40px auto;
        padding: 40px;
        background: var(--surface);
        border: 1px solid var(--glass-highlight);
        border-radius: var(--radius);
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
      }
      .feedback-container h2 {
        color: var(--accent);
        margin-bottom: 6px;
        font-weight: 700;
      }
      .form-group { margin-bottom: 20px; text-align: left; }
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
        outline: none;
      }
      .form-control option { background: #0f1724; }
      .alert-success-custom {
        background: rgba(34,197,94,0.12);
        border: 1px solid rgba(34,197,94,0.4);
        color: #4ade80;
        padding: 16px;
        border-radius: var(--radius);
        margin-bottom: 20px;
        animation: fadeInDown 0.4s ease;
      }
      .alert-error-custom {
        background: rgba(229,9,20,0.12);
        border: 1px solid rgba(229,9,20,0.4);
        color: #f87171;
        padding: 16px;
        border-radius: var(--radius);
        margin-bottom: 20px;
        animation: fadeInDown 0.4s ease;
      }
      @keyframes fadeInDown {
        from { opacity:0; transform: translateY(-12px); }
        to   { opacity:1; transform: translateY(0); }
      }
      .star-label-text { color: var(--muted); font-size: 0.82rem; margin-bottom: 8px; display: block; }
    </style>
  </head>
  <body>
    <nav class="nav-bar reveal">
      <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="search.php">Search</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="about.html">About Us</a></li>
        <li><a href="feedback.php">Feedback</a></li>
        <li>
          <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Menu</button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="genre.php">Genres</a></li>
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
      <h1><b><span style="color:var(--accent-strong);">REEL</span>FLIX FEEDBACK</b></h1>
      <p style="color:var(--muted);margin-top:6px;">Your voice shapes what ReelFlix becomes.</p>
    </header>

    <main class="container" style="min-height:70vh;text-align:center;">
      <div class="feedback-container reveal">
        <h2>We Value Your Thoughts</h2>
        <p style="color:var(--muted);margin-bottom:28px;font-size:0.95rem;">Tell us what you love, what's broken, or which movies we're missing.</p>

        <?php if ($success_message): ?>
          <div class="alert-success-custom"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
          <div class="alert-error-custom"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!$success_message): ?>
        <form method="POST" action="feedback.php">
          <div class="form-group">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" required placeholder="Your name" value="<?php echo isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : ''; ?>" />
          </div>
          <div class="form-group">
            <label for="email" class="form-label">Email (optional)</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>" />
          </div>
          <div class="form-group">
            <label for="feedbackType" class="form-label">Type of Feedback</label>
            <select class="form-control" id="feedbackType" name="feedbackType">
              <option>General Suggestion</option>
              <option>Bug Report</option>
              <option>Movie Request</option>
              <option>Praise</option>
              <option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Rate Your Experience</label>
            <span class="star-label-text">How would you rate ReelFlix overall?</span>
            <div class="star-rating">
              <input type="radio" id="star5" name="rating" value="5" /><label for="star5" title="5 stars">★</label>
              <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 stars">★</label>
              <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 stars">★</label>
              <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 stars">★</label>
              <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 star">★</label>
            </div>
          </div>
          <div class="form-group">
            <label for="message" class="form-label">Message</label>
            <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Share your thoughts…"></textarea>
          </div>
          <button type="submit" class="btn btn-primary w-100" style="font-size:1rem;padding:14px;">Submit Feedback</button>
        </form>
        <?php else: ?>
          <a href="dashboard.php" class="btn btn-primary" style="margin-top:10px;">Back to Movies</a>
        <?php endif; ?>
      </div>
    </main>

    <div class="copyright-wrapper reveal">&copy; 2026 ReelFlix &#124; All rights reserved</div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js"></script>
  </body>
</html>
