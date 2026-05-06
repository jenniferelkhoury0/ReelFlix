<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'movies_db');
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

$movie_id = isset($_GET['id']) ? intval($_GET['id']) : null;

$stmt = $conn->prepare(
    "SELECT m.*, GROUP_CONCAT(mg.genre ORDER BY mg.genre SEPARATOR ', ') AS GENRES
     FROM MOVIES m
     LEFT JOIN MOVIE_GENRES mg ON m.id = mg.movie_id
     WHERE m.id = ? GROUP BY m.id"
);
$stmt->bind_param('i', $movie_id);
$stmt->execute();
$movie = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$movie) { header('Location: dashboard.php'); exit(); }

$actors_stmt = $conn->prepare(
    "SELECT a.name AS actor_name, ma.role AS actor_role
     FROM MOVIE_ACTORS ma INNER JOIN ACTORS a ON ma.actor_id = a.id
     WHERE ma.movie_id = ?"
);
$actors_stmt->bind_param('i', $movie_id);
$actors_stmt->execute();
$actors = $actors_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$actors_stmt->close();

// Community rating
$cr = $conn->prepare("SELECT ROUND(AVG(rating),1) as avg_rating, COUNT(*) as total FROM MOVIE_REVIEWS WHERE movie_id = ?");
$cr->bind_param('i', $movie_id);
$cr->execute();
$community = $cr->get_result()->fetch_assoc();
$cr->close();

// Recent reviews
$rv = $conn->prepare(
    "SELECT r.rating, r.review_text, r.created_at, u.USERNAME
     FROM MOVIE_REVIEWS r JOIN USERS u ON r.user_id = u.id
     WHERE r.movie_id = ? ORDER BY r.created_at DESC LIMIT 5"
);
$rv->bind_param('i', $movie_id);
$rv->execute();
$reviews = $rv->get_result()->fetch_all(MYSQLI_ASSOC);
$rv->close();

$in_watchlist = $in_favorites = false;
$my_rating    = 0;
if (isset($_SESSION['id'])) {
    $chk = $conn->prepare("SELECT 1 FROM WATCHLIST WHERE user_id = ? AND movie_id = ?");
    $chk->bind_param('ii', $_SESSION['id'], $movie_id);
    $chk->execute();
    $in_watchlist = $chk->get_result()->num_rows > 0;
    $chk->close();

    $chk2 = $conn->prepare("SELECT 1 FROM USER_FAVORITES WHERE user_id = ? AND movie_id = ?");
    $chk2->bind_param('ii', $_SESSION['id'], $movie_id);
    $chk2->execute();
    $in_favorites = $chk2->get_result()->num_rows > 0;
    $chk2->close();

    $mr = $conn->prepare("SELECT rating FROM MOVIE_REVIEWS WHERE user_id = ? AND movie_id = ?");
    $mr->bind_param('ii', $_SESSION['id'], $movie_id);
    $mr->execute();
    $row = $mr->get_result()->fetch_assoc();
    $my_rating = $row ? intval($row['rating']) : 0;
    $mr->close();
}
$conn->close();

// Convert YouTube watch URL → embed URL
function yt_embed($url) {
    preg_match('/(?:v=|youtu\.be\/)([^&?\/]+)/', $url ?? '', $m);
    return isset($m[1]) ? "https://www.youtube.com/embed/{$m[1]}?rel=0&modestbranding=1" : '';
}
$embed_url = yt_embed($movie['TRAILERURL'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="<?php echo htmlspecialchars(substr($movie['DESCRIPTION'], 0, 150)); ?>" />
    <title><?php echo htmlspecialchars($movie['TITLE']); ?> - ReelFlix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body>

    <?php if (!empty($movie['BACKDROP_URL'])): ?>
    <div class="details-hero-backdrop" style="background-image:url('<?php echo htmlspecialchars($movie['BACKDROP_URL']); ?>')"></div>
    <?php endif; ?>

    <nav class="nav-bar">
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
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            </ul>
          </div>
        </li>
      </ul>
    </nav>

    <main class="details-main">
      <div class="movie-details-container reveal">

        <div class="movie-poster">
          <img src="<?php echo htmlspecialchars($movie['POSTERURL']); ?>"
               alt="<?php echo htmlspecialchars($movie['TITLE']); ?>">
        </div>

        <div class="movie-info">
          <?php if (!empty($movie['GENRES'])): ?>
            <div class="details-genre-tags">
              <?php foreach (explode(', ', $movie['GENRES']) as $g): ?>
                <span class="genre-tag"><?php echo htmlspecialchars(trim($g)); ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <h1><?php echo htmlspecialchars($movie['TITLE']); ?></h1>

          <div class="details-ratings-row">
            <?php if ($movie['RATING']): ?>
              <div class="details-rating-pill imdb-pill">
                <span class="pill-label">IMDb</span>
                <span class="pill-value"><?php echo $movie['RATING']; ?>/10</span>
              </div>
            <?php endif; ?>
            <?php if ($community['total'] > 0): ?>
              <div class="details-rating-pill community-pill" id="communityPill">
                <span class="pill-label">Community</span>
                <span class="pill-value" id="communityAvg">★ <?php echo $community['avg_rating']; ?></span>
                <span class="pill-count" id="communityCount">(<?php echo $community['total']; ?>)</span>
              </div>
            <?php endif; ?>
          </div>

          <div class="movie-meta">
            <p><strong>Year</strong><?php echo htmlspecialchars($movie['RELEASE_YEAR']); ?></p>
            <p><strong>Director</strong><?php echo htmlspecialchars($movie['DIRECTOR']); ?></p>
            <p><strong>Duration</strong><?php echo htmlspecialchars($movie['DURATION']); ?> min</p>
            <p><strong>Language</strong><?php echo htmlspecialchars($movie['LANGUAGE'] ?? '—'); ?></p>
            <p><strong>Country</strong><?php echo htmlspecialchars($movie['COUNTRY'] ?? '—'); ?></p>
            <?php if (!empty($movie['BOX_OFFICE']) && $movie['BOX_OFFICE'] > 0): ?>
              <p><strong>Box Office</strong>$<?php echo number_format($movie['BOX_OFFICE']); ?></p>
            <?php endif; ?>
          </div>

          <?php if ($embed_url): ?>
          <section class="trailer-section trailer-section--inline reveal" id="trailer">
            <h2 class="section-heading">Official trailer</h2>
            <div class="trailer-frame-wrapper">
              <iframe src="<?php echo htmlspecialchars($embed_url); ?>"
                      title="<?php echo htmlspecialchars($movie['TITLE']); ?> trailer"
                      frameborder="0"
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                      allowfullscreen
                      loading="eager"></iframe>
            </div>
          </section>
          <?php endif; ?>

          <div class="movie-description">
            <h3>Description</h3>
            <p><?php echo htmlspecialchars($movie['DESCRIPTION']); ?></p>
          </div>

          <?php if (!empty($actors)): ?>
          <div class="movie-cast">
            <h3>Cast</h3>
            <ul>
              <?php foreach ($actors as $actor): ?>
                <li>
                  <?php echo htmlspecialchars($actor['actor_name']); ?>
                  <span style="color:var(--accent);">as</span>
                  <?php echo htmlspecialchars($actor['actor_role']); ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>

          <div class="movie-actions">
            <?php if (isset($_SESSION['id'])): ?>
              <?php if (!$in_watchlist): ?>
                <a href="#" class="btn btn-primary add-to-watchlist" id="add-<?php echo $movie['ID']; ?>">+ Watchlist</a>
              <?php else: ?>
                <a href="#" class="btn btn-danger remove-from-watchlist" id="remove-<?php echo $movie['ID']; ?>">✓ In Watchlist</a>
              <?php endif; ?>
              <?php if (!$in_favorites): ?>
                <a href="#" class="btn add-to-favorites" id="fav-<?php echo $movie['ID']; ?>"
                   style="background:rgba(245,197,24,0.12);border-color:rgba(245,197,24,0.4);color:var(--accent);">Favourite</a>
              <?php else: ?>
                <a href="#" class="btn remove-from-favorites" data-movie-id="<?php echo $movie['ID']; ?>"
                   style="background:rgba(245,197,24,0.25);border:1px solid var(--accent);color:var(--accent);">Favourited</a>
              <?php endif; ?>
            <?php endif; ?>
            <a href="javascript:history.back()" class="btn" style="border-color:var(--glass-highlight);color:var(--muted);">Back</a>
          </div>
        </div>
      </div>

      <?php if (isset($_SESSION['id'])): ?>
      <!-- ======= USER RATING ======= -->
      <section class="rating-section reveal">
        <h2 class="section-heading">Rate This Film</h2>
        <div class="user-rating-widget" data-movie-id="<?php echo $movie['ID']; ?>">
          <div class="star-rating-interactive" id="starRating">
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <button class="star-btn <?php echo ($my_rating >= $i) ? 'active' : ''; ?>"
                      data-val="<?php echo $i; ?>" aria-label="<?php echo $i; ?> stars">★</button>
            <?php endfor; ?>
          </div>
          <textarea id="reviewText" class="review-textarea"
                    placeholder="Leave a review (optional)…" rows="3"></textarea>
          <button class="btn btn-primary" id="submitRating" style="margin-top:10px;">
            <?php echo $my_rating ? 'Update Rating' : 'Submit Rating'; ?>
          </button>
          <p id="ratingMsg" style="color:var(--muted);font-size:0.85rem;margin-top:8px;"></p>
        </div>
      </section>
      <?php endif; ?>

      <?php if (!empty($reviews)): ?>
      <!-- ======= REVIEWS ======= -->
      <section class="reviews-section reveal">
        <h2 class="section-heading">Community Reviews</h2>
        <div class="reviews-list">
          <?php foreach ($reviews as $rev): ?>
          <div class="review-card">
            <div class="review-header">
              <span class="review-username">@<?php echo htmlspecialchars($rev['USERNAME']); ?></span>
              <span class="review-stars"><?php echo str_repeat('★', $rev['rating']) . str_repeat('☆', 5 - $rev['rating']); ?></span>
              <span class="review-date"><?php echo date('M j, Y', strtotime($rev['created_at'])); ?></span>
            </div>
            <?php if (!empty($rev['review_text'])): ?>
              <p class="review-text"><?php echo htmlspecialchars($rev['review_text']); ?></p>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

    </main>

    <div class="copyright-wrapper">&copy; 2026 ReelFlix &#124; All rights reserved</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js"></script>
    <script>
    // ---- Interactive Star Rating ----
    (function() {
      const stars   = document.querySelectorAll('.star-btn');
      const submit  = document.getElementById('submitRating');
      const msg     = document.getElementById('ratingMsg');
      const movieId = document.querySelector('.user-rating-widget')?.dataset.movieId;
      if (!stars.length || !submit) return;

      let selected = <?php echo $my_rating ?: 0; ?>;

      function paint(n) {
        stars.forEach(s => s.classList.toggle('active', parseInt(s.dataset.val) <= n));
      }

      // stars are rendered 5→1 in DOM; clicking highlights up to value
      stars.forEach(s => {
        s.addEventListener('mouseenter', () => paint(parseInt(s.dataset.val)));
        s.addEventListener('mouseleave', () => paint(selected));
        s.addEventListener('click', () => { selected = parseInt(s.dataset.val); paint(selected); });
      });

      submit.addEventListener('click', () => {
        if (!selected) { msg.textContent = 'Please select a star rating.'; return; }
        const text = document.getElementById('reviewText')?.value || '';
        submit.disabled = true;
        fetch('rate_movie.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ movie_id: parseInt(movieId), rating: selected, review_text: text })
        })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            msg.textContent = `Thanks! Community average: ★ ${data.avg_rating} (${data.total} ratings)`;
            msg.style.color = 'var(--accent)';
            submit.textContent = 'Update Rating';
            const pill = document.getElementById('communityPill');
            if (pill) {
              document.getElementById('communityAvg').textContent = `★ ${data.avg_rating}`;
              document.getElementById('communityCount').textContent = `(${data.total})`;
            } else {
              // inject pill if it didn't exist
              const row = document.querySelector('.details-ratings-row');
              if (row) row.insertAdjacentHTML('beforeend',
                `<div class="details-rating-pill community-pill" id="communityPill">
                   <span class="pill-label">Community</span>
                   <span class="pill-value" id="communityAvg">★ ${data.avg_rating}</span>
                   <span class="pill-count" id="communityCount">(${data.total})</span>
                 </div>`);
            }
          } else {
            msg.textContent = 'Could not save rating — please try again.';
            msg.style.color = 'var(--accent-strong)';
          }
          submit.disabled = false;
        })
        .catch(() => { msg.textContent = 'Network error.'; submit.disabled = false; });
      });
    })();
    </script>
  </body>
</html>
