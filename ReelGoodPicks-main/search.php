<?php
session_start();

if (!isset($_SESSION['id'])) {
    header('Location: login.html');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'movies_db');
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

$user_id = $_SESSION['id'];
$query   = isset($_GET['q']) ? trim($_GET['q']) : '';
$movies  = [];
$watchlist_ids = [];

if ($query !== '') {
    $like = '%' . $query . '%';
    $sql  = "SELECT DISTINCT m.* FROM MOVIES m
             LEFT JOIN MOVIE_GENRES mg ON m.id = mg.movie_id
             WHERE m.TITLE LIKE ? OR m.DIRECTOR LIKE ? OR m.DESCRIPTION LIKE ? OR mg.GENRE LIKE ?
             ORDER BY m.TITLE";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $movies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Pre-fetch watchlist to avoid per-card queries
    $wl = $conn->prepare("SELECT movie_id FROM WATCHLIST WHERE user_id = ?");
    $wl->bind_param('i', $user_id);
    $wl->execute();
    foreach ($wl->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $watchlist_ids[] = $r['movie_id'];
    }
    $wl->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Search ReelFlix — find any movie by title, director, or genre." />
    <title>Search<?php echo $query ? ' — ' . htmlspecialchars($query) : ''; ?> - ReelFlix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>
    <nav class="nav-bar reveal">
      <ul>
        <li><a href="dashboard.php">Home</a></li>
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

    <header class="search-page-header reveal">
      <h1>Search</h1>
      <p>Find any film by title, director, or genre.</p>
    </header>

    <main class="main-content">
      <form action="search.php" method="GET" class="search-bar-wrapper reveal">
        <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Title, director, or genre…" autofocus />
        <button type="submit">Search</button>
      </form>

      <?php if ($query !== ''): ?>
        <p class="reveal" style="color:var(--muted);margin-bottom:20px;">
          <?php echo count($movies); ?> result<?php echo count($movies) !== 1 ? 's' : ''; ?> for
          "<strong style="color:var(--text)"><?php echo htmlspecialchars($query); ?></strong>"
        </p>
      <?php endif; ?>

      <div class="movie-grid">
        <?php if ($query !== '' && count($movies) === 0): ?>
          <div class="empty-state">
            <h3>No results for "<?php echo htmlspecialchars($query); ?>"</h3>
            <p>Try a different title, director, or genre.</p>
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
          </div>
        <?php elseif ($query === ''): ?>
          <div class="empty-state">
            <h3>Start typing to search the catalogue.</h3>
            <p>Match on title, director, or genre.</p>
          </div>
        <?php else: ?>
          <?php foreach ($movies as $movie): ?>
            <?php $in_watchlist = in_array($movie['ID'], $watchlist_ids); ?>
            <div class="movie-card reveal" onclick="window.location.href='moviedetails.php?id=<?php echo $movie['ID']; ?>'">
              <img src="<?php echo htmlspecialchars($movie['POSTERURL']); ?>" alt="<?php echo htmlspecialchars($movie['TITLE']); ?>" loading="lazy">
              <h3><?php echo htmlspecialchars($movie['TITLE']); ?></h3>
              <?php if (!empty($movie['RATING'])): ?>
                <span class="rating-badge"><?php echo $movie['RATING']; ?></span>
              <?php endif; ?>
              <p class="movie-description"><?php echo htmlspecialchars(substr($movie['DESCRIPTION'], 0, 120)); ?>…</p>
              <?php if (!$in_watchlist): ?>
                <a href="#" class="btn btn-sm btn-primary add-to-watchlist" id="add-<?php echo $movie['ID']; ?>" onclick="event.stopPropagation();">+ Watchlist</a>
              <?php else: ?>
                <a href="#" class="btn btn-sm btn-danger remove-from-watchlist" id="remove-<?php echo $movie['ID']; ?>" onclick="event.stopPropagation();">In Watchlist</a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>

    <div class="copyright-wrapper reveal">&copy; 2026 ReelFlix &#124; All rights reserved</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js"></script>
  </body>
</html>
