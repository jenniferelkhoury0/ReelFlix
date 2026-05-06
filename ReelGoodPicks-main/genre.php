<?php
session_start();
if (!isset($_SESSION['id'])) {
    header('Location: login.html');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'movies_db');
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

$user_id = $_SESSION['id'];
$genre   = isset($_GET['genre']) && $_GET['genre'] !== '' ? trim($_GET['genre']) : null;

$all_genres = ['Action','Adventure','Biography','Comedy','Crime','Documentary','Drama','Family','Fantasy','Historical','Horror','Musical','Romance','Sci-Fi','Thriller','War'];

$movies       = [];
$watchlist_ids = [];

if ($genre === null) {
    $conn->close();
} else {
    // Fetch movies
    if ($genre === 'All') {
        $stmt = $conn->prepare(
            "SELECT m.ID, m.TITLE, m.RATING, m.RELEASE_YEAR, m.POSTERURL, m.DESCRIPTION, m.TRAILERURL,
                    GROUP_CONCAT(mg2.genre ORDER BY mg2.genre SEPARATOR ', ') AS GENRES
             FROM MOVIES m
             LEFT JOIN MOVIE_GENRES mg2 ON m.id = mg2.movie_id
             GROUP BY m.ID
             ORDER BY m.RATING DESC, m.TITLE"
        );
        $stmt->execute();
    } else {
        $stmt = $conn->prepare(
            "SELECT DISTINCT m.ID, m.TITLE, m.RATING, m.RELEASE_YEAR, m.POSTERURL, m.DESCRIPTION, m.TRAILERURL,
                    (SELECT GROUP_CONCAT(mg2.genre ORDER BY mg2.genre SEPARATOR ', ')
                     FROM MOVIE_GENRES mg2 WHERE mg2.movie_id = m.ID) AS GENRES
             FROM MOVIES m
             INNER JOIN MOVIE_GENRES mg ON m.id = mg.movie_id
             WHERE mg.genre = ?
             ORDER BY m.RATING DESC, m.TITLE"
        );
        $stmt->bind_param('s', $genre);
        $stmt->execute();
    }
    $movies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch watchlist IDs once — avoids per-card query on closed connection
    $wl = $conn->prepare("SELECT movie_id FROM WATCHLIST WHERE user_id = ?");
    $wl->bind_param('i', $user_id);
    $wl->execute();
    foreach ($wl->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $watchlist_ids[] = $r['movie_id'];
    }
    $wl->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="<?php echo $genre ? 'Browse ' . htmlspecialchars($genre) . ' movies on ReelFlix.' : 'Browse movies by genre on ReelFlix.'; ?>" />
    <title><?php echo $genre ? htmlspecialchars($genre) . ' Movies' : 'Genres'; ?> — ReelFlix</title>
    <link rel="icon" type="image/x-icon" href="images/Popcorn-icon.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
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
              <li><a class="dropdown-item" href="trending.php">Trending</a></li>
              <li><a class="dropdown-item" href="toprated.php">Top Rated</a></li>
              <li><a class="dropdown-item" href="test.html">Movie Quiz</a></li>
              <li><hr class="dropdown-divider" /></li>
              <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            </ul>
          </div>
        </li>
      </ul>
    </nav>

    <?php if ($genre === null): ?>
    <!-- ==================== GENRE PICKER ==================== -->
    <main class="genre-picker-page">
      <p class="genre-picker-eyebrow reveal">Catalogue</p>
      <h1 class="genre-picker-title reveal">Browse by Genre</h1>
      <p class="genre-picker-sub reveal">Select a category to explore the full collection.</p>

      <div class="genre-picker-grid">
        <a href="genre.php?genre=All" class="genreCard genre-all reveal">All Films</a>
        <?php foreach ($all_genres as $g): ?>
          <a href="genre.php?genre=<?php echo urlencode($g); ?>" class="genreCard reveal"><?php echo htmlspecialchars($g); ?></a>
        <?php endforeach; ?>
      </div>
    </main>

    <?php else: ?>
    <!-- ==================== MOVIE LISTING ==================== -->
    <div class="genre-listing-header reveal">
      <a href="javascript:history.back()" class="genre-back-link">&#8592; Back</a>
      <h1 class="genre-listing-title"><?php echo $genre === 'All' ? 'All Films' : htmlspecialchars($genre); ?></h1>
      <p class="genre-listing-count"><?php echo count($movies); ?> title<?php echo count($movies) !== 1 ? 's' : ''; ?> found</p>
    </div>

    <main class="main-content">
      <div id="movieList" class="movie-grid">
        <?php if (!empty($movies)): ?>
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
        <?php else: ?>
          <div class="empty-state" style="grid-column:1/-1">
            <h3>No <?php echo htmlspecialchars($genre); ?> films found</h3>
            <p>Try another genre or check back soon.</p>
            <a href="genre.php" class="btn btn-primary" style="margin-top:16px;">Browse Genres</a>
          </div>
        <?php endif; ?>
      </div>
    </main>
    <?php endif; ?>

    <div class="copyright-wrapper reveal">&copy; 2026 ReelFlix. All rights reserved.</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js"></script>
  </body>
</html>
