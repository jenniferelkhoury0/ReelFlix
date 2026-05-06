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

if (!$user) {
    $user = ['FULLNAME' => 'Unknown', 'EMAIL' => 'Unknown', 'USERNAME' => 'Unknown', 'BIO' => '', 'CREATED_AT' => null, 'PROFILE_PIC_URL' => ''];
}

$wl_stmt = $conn->prepare("SELECT m.* FROM MOVIES m
                           INNER JOIN WATCHLIST w ON m.id = w.movie_id
                           WHERE w.user_id = ? ORDER BY w.added_at DESC");
$wl_stmt->bind_param('i', $user_id);
$wl_stmt->execute();
$watchlist_movies = $wl_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$wl_stmt->close();

$fav_stmt = $conn->prepare("SELECT m.* FROM MOVIES m
                            INNER JOIN USER_FAVORITES uf ON m.id = uf.movie_id
                            WHERE uf.user_id = ? ORDER BY uf.added_at DESC");
$fav_stmt->bind_param('i', $user_id);
$fav_stmt->execute();
$favorite_movies = $fav_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$fav_stmt->close();

// Genre breakdown of watchlist movies
$genre_stmt = $conn->prepare(
    "SELECT mg.genre, COUNT(*) as cnt
     FROM WATCHLIST w
     JOIN MOVIE_GENRES mg ON w.movie_id = mg.movie_id
     WHERE w.user_id = ?
     GROUP BY mg.genre
     ORDER BY cnt DESC
     LIMIT 8"
);
$genre_stmt->bind_param('i', $user_id);
$genre_stmt->execute();
$genre_stats = $genre_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$genre_stmt->close();

// Average rating given by user
$avg_stmt = $conn->prepare("SELECT ROUND(AVG(rating),1) as avg_given, COUNT(*) as total_rated FROM MOVIE_REVIEWS WHERE user_id = ?");
$avg_stmt->bind_param('i', $user_id);
$avg_stmt->execute();
$rating_stats = $avg_stmt->get_result()->fetch_assoc();
$avg_stmt->close();

$conn->close();

$default_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user['FULLNAME']) . '&background=e50914&color=fff&size=128&bold=true';
$avatar = !empty($user['PROFILE_PIC_URL']) ? htmlspecialchars($user['PROFILE_PIC_URL']) : $default_avatar;

$member_since = '';
if (!empty($user['CREATED_AT'])) {
    $member_since = date('F Y', strtotime($user['CREATED_AT']));
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Your ReelFlix profile — manage your watchlist, favourites, and account settings." />
    <title>Profile - ReelFlix</title>
    <link rel="icon" type="image/x-icon" href="images/Profile.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
      .profile-card {
        background: var(--surface);
        padding: 36px 30px;
        border-radius: var(--radius);
        margin-bottom: 40px;
        border: 1px solid var(--glass-highlight);
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        text-align: center;
      }
      .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        border: 3px solid var(--accent);
        object-fit: cover;
        margin: 0 auto 16px;
        display: block;
      }
      .profile-stat {
        display: inline-block;
        background: var(--card);
        border: 1px solid var(--glass-border);
        border-radius: 10px;
        padding: 10px 20px;
        margin: 6px;
        text-align: center;
      }
      .profile-stat strong {
        display: block;
        font-size: 1.5rem;
        color: var(--accent);
        font-family: 'Outfit', sans-serif;
      }
      .profile-stat span {
        font-size: 0.8rem;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: 1px;
      }
      .section-title {
        color: var(--accent);
        font-size: 1.1rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--glass-border);
        text-align: left;
      }
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
      <h1><b>PROFILE</b></h1>
    </header>

    <main class="main-content reveal" style="max-width:860px;">

      <!-- Profile card -->
      <div class="profile-card">
        <img src="<?php echo $avatar; ?>" alt="<?php echo htmlspecialchars($user['FULLNAME']); ?>" class="profile-avatar" />
        <h2 style="color:var(--accent);margin-bottom:4px;"><?php echo htmlspecialchars($user['FULLNAME']); ?></h2>
        <p style="color:var(--muted);font-size:0.9rem;margin-bottom:4px;">@<?php echo htmlspecialchars($user['USERNAME']); ?></p>
        <?php if ($member_since): ?>
          <p style="color:var(--muted);font-size:0.8rem;margin-bottom:14px;">Member since <?php echo $member_since; ?></p>
        <?php endif; ?>
        <?php if (!empty($user['BIO'])): ?>
          <p style="color:var(--text);max-width:480px;margin:0 auto 16px;font-size:0.95rem;"><?php echo htmlspecialchars($user['BIO']); ?></p>
        <?php endif; ?>

        <!-- Stats -->
        <div style="margin:10px 0 20px;">
          <div class="profile-stat">
            <strong><?php echo count($watchlist_movies); ?></strong>
            <span>Watchlist</span>
          </div>
          <div class="profile-stat">
            <strong><?php echo count($favorite_movies); ?></strong>
            <span>Favourites</span>
          </div>
          <?php if ($rating_stats['total_rated'] > 0): ?>
          <div class="profile-stat">
            <strong>★ <?php echo $rating_stats['avg_given']; ?></strong>
            <span>Avg Rating</span>
          </div>
          <div class="profile-stat">
            <strong><?php echo $rating_stats['total_rated']; ?></strong>
            <span>Rated</span>
          </div>
          <?php endif; ?>
        </div>

        <p style="color:var(--muted);font-size:0.85rem;margin-bottom:20px;"><?php echo htmlspecialchars($user['EMAIL']); ?></p>

        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
          <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
          <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
      </div>

      <!-- Genre Stats Chart -->
      <?php if (!empty($genre_stats)): ?>
      <div class="reveal" style="margin-bottom:40px;">
        <h3 class="section-title">Your Taste in Film</h3>
        <div class="genre-chart-wrapper">
          <canvas id="genreChart" width="300" height="300"></canvas>
          <div class="genre-chart-legend" id="genreLegend"></div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Watchlist -->
      <div class="reveal">
        <h3 class="section-title">My Watchlist (<?php echo count($watchlist_movies); ?>)</h3>
        <?php if (count($watchlist_movies) > 0): ?>
          <div class="movie-grid">
            <?php foreach ($watchlist_movies as $movie): ?>
              <div class="movie-card" onclick="window.location.href='moviedetails.php?id=<?php echo $movie['ID']; ?>'">
                <img src="<?php echo htmlspecialchars($movie['POSTERURL']); ?>" alt="<?php echo htmlspecialchars($movie['TITLE']); ?>">
                <h3><?php echo htmlspecialchars($movie['TITLE']); ?></h3>
                <?php if (!empty($movie['RATING'])): ?>
                  <span class="rating-badge"><?php echo $movie['RATING']; ?></span>
                <?php endif; ?>
                <button class="btn btn-sm btn-danger remove-from-watchlist" id="remove-<?php echo $movie['ID']; ?>" onclick="event.stopPropagation();">Remove</button>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-state" style="padding:30px 20px;">
            <div class="empty-icon">📋</div>
            <h3>No movies in your watchlist</h3>
            <p>Browse movies and add them to your watchlist.</p>
            <a href="dashboard.php" class="btn btn-primary">Browse Movies</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- Favourites -->
      <div class="reveal" style="margin-top:40px;">
        <h3 class="section-title">My Favourites (<?php echo count($favorite_movies); ?>)</h3>
        <?php if (count($favorite_movies) > 0): ?>
          <div class="movie-grid">
            <?php foreach ($favorite_movies as $movie): ?>
              <div class="movie-card" onclick="window.location.href='moviedetails.php?id=<?php echo $movie['ID']; ?>'">
                <img src="<?php echo htmlspecialchars($movie['POSTERURL']); ?>" alt="<?php echo htmlspecialchars($movie['TITLE']); ?>">
                <h3><?php echo htmlspecialchars($movie['TITLE']); ?></h3>
                <?php if (!empty($movie['RATING'])): ?>
                  <span class="rating-badge"><?php echo $movie['RATING']; ?></span>
                <?php endif; ?>
                <button class="btn btn-sm btn-danger remove-from-favorites" data-movie-id="<?php echo $movie['ID']; ?>" onclick="event.stopPropagation();">Remove</button>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-state" style="padding:30px 20px;">
            <div class="empty-icon">♥</div>
            <h3>No favourites yet</h3>
            <p>Open any movie and tap <strong>♥ Favourite</strong> to save it here.</p>
          </div>
        <?php endif; ?>
      </div>

    </main>

    <div class="copyright-wrapper reveal">&copy; 2026 ReelFlix &#124; All rights reserved</div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js"></script>
    <?php if (!empty($genre_stats)): ?>
    <script>
    (function() {
      const labels = <?php echo json_encode(array_column($genre_stats, 'genre')); ?>;
      const values = <?php echo json_encode(array_column($genre_stats, 'cnt')); ?>;
      const palette = ['#e50914','#f5c518','#3b82f6','#10b981','#8b5cf6','#f97316','#ec4899','#06b6d4'];

      const ctx = document.getElementById('genreChart');
      if (!ctx) return;

      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels,
          datasets: [{
            data: values,
            backgroundColor: palette.slice(0, labels.length),
            borderColor: 'rgba(0,0,0,0.4)',
            borderWidth: 3,
            hoverOffset: 12
          }]
        },
        options: {
          cutout: '65%',
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: ctx => ` ${ctx.label}: ${ctx.raw} movie${ctx.raw !== 1 ? 's' : ''}`
              }
            }
          },
          animation: { animateRotate: true, duration: 1000 }
        }
      });

      // Custom legend
      const legend = document.getElementById('genreLegend');
      if (legend) {
        labels.forEach((l, i) => {
          legend.insertAdjacentHTML('beforeend',
            `<div class="chart-legend-item">
               <span class="chart-legend-dot" style="background:${palette[i]}"></span>
               <span>${l} <strong>(${values[i]})</strong></span>
             </div>`);
        });
      }
    })();
    </script>
    <?php endif; ?>
  </body>
</html>
