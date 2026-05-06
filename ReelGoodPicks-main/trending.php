<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'movies_db');
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

// Trending = most recently added movies
$result = $conn->query("SELECT * FROM MOVIES ORDER BY CREATED_AT DESC");
$movies = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Trending movies on ReelFlix — the latest additions to our collection." />
    <title>Trending Movies - ReelFlix</title>
    <link rel="icon" type="image/x-icon" href="images/trend-icon-sign-symbol-design-free-png.webp" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  </head>
  <body>
    <nav class="nav-bar reveal">
      <ul>
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="search.php">Search</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="about.html">About Us</a></li>
        <li>
          <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Menu</button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="genre.php">Genres</a></li>
              <li><a class="dropdown-item" href="random.php">Surprise Me</a></li>
              <li><a class="dropdown-item" href="watchlist.php">Watchlist</a></li>
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
      <h1><b>TRENDING</b></h1>
      <p style="color:var(--muted);margin-top:6px;">The latest additions to ReelFlix</p>
    </header>

    <main class="main-content">
      <div id="movieList" class="movie-grid">
        <?php if (count($movies) > 0): ?>
          <?php foreach ($movies as $movie): ?>
            <?php
            $in_watchlist = false;
            if (isset($_SESSION['id'])) {
                $chk = $conn->prepare("SELECT 1 FROM WATCHLIST WHERE user_id = ? AND movie_id = ?");
                $chk->bind_param('ii', $_SESSION['id'], $movie['ID']);
                $chk->execute();
                $in_watchlist = $chk->get_result()->num_rows > 0;
                $chk->close();
            }
            ?>
            <div class="movie-card reveal" onclick="window.location.href='moviedetails.php?id=<?php echo $movie['ID']; ?>'">
              <img src="<?php echo htmlspecialchars($movie['POSTERURL']); ?>" alt="<?php echo htmlspecialchars($movie['TITLE']); ?>">
              <h3><?php echo htmlspecialchars($movie['TITLE']); ?></h3>
              <?php if (!empty($movie['RATING'])): ?>
                <span class="rating-badge"><?php echo $movie['RATING']; ?></span>
              <?php endif; ?>
              <p class="movie-description"><?php echo htmlspecialchars($movie['DESCRIPTION']); ?></p>
              <?php if (isset($_SESSION['id'])): ?>
                <?php if (!$in_watchlist): ?>
                  <a href="#" class="btn btn-sm btn-primary add-to-watchlist" id="add-<?php echo $movie['ID']; ?>" onclick="event.stopPropagation();">Add to Watchlist</a>
                <?php else: ?>
                  <a href="#" class="btn btn-sm btn-danger remove-from-watchlist" id="remove-<?php echo $movie['ID']; ?>" onclick="event.stopPropagation();">Remove from Watchlist</a>
                <?php endif; ?>
              <?php endif; ?>
              <?php if (!empty($movie['TRAILERURL'])): ?>
                <a href="<?php echo htmlspecialchars($movie['TRAILERURL']); ?>" target="_blank" class="btn btn-sm btn-danger mt-2" onclick="event.stopPropagation();">Watch Trailer</a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon">🎬</div>
            <h3>Nothing trending yet</h3>
            <p>Check back soon.</p>
          </div>
        <?php endif; ?>
      </div>
    </main>

    <div class="copyright-wrapper reveal">&copy; 2026 ReelFlix &#124; All rights reserved</div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js"></script>
  </body>
</html>
<?php $conn->close(); ?>
