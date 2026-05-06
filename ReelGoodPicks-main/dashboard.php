<?php
session_start();
if (!isset($_SESSION['id'])) {
    header('Location: login.html');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'movies_db');
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

$user_id  = $_SESSION['id'];
$fullname = $_SESSION['fullname'];

// Spotlight: top 6 movies with backdrops for the hero rotator
$spotlight = $conn->query(
    "SELECT m.ID, m.TITLE, m.RATING, m.RELEASE_YEAR, m.DESCRIPTION, m.POSTERURL, m.BACKDROP_URL, m.TRAILERURL,
            GROUP_CONCAT(mg.genre ORDER BY mg.genre SEPARATOR ', ') AS GENRES
     FROM MOVIES m
     LEFT JOIN MOVIE_GENRES mg ON m.id = mg.movie_id
     WHERE m.BACKDROP_URL IS NOT NULL
     GROUP BY m.ID
     ORDER BY m.RATING DESC
     LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);

// Genre rows: fetch up to 12 movies per genre
$genres = ['Action','Drama','Sci-Fi','Horror','Comedy','Thriller','Romance','Adventure'];
$genre_rows = [];
$stmt_genre = $conn->prepare(
    "SELECT m.ID, m.TITLE, m.RATING, m.RELEASE_YEAR, m.POSTERURL, m.TRAILERURL
     FROM MOVIES m
     INNER JOIN MOVIE_GENRES mg ON m.id = mg.movie_id
     WHERE mg.genre = ?
     GROUP BY m.ID
     ORDER BY m.RATING DESC
     LIMIT 20"
);
foreach ($genres as $g) {
    $stmt_genre->bind_param('s', $g);
    $stmt_genre->execute();
    $rows = $stmt_genre->get_result()->fetch_all(MYSQLI_ASSOC);
    if (!empty($rows)) $genre_rows[$g] = $rows;
}
$stmt_genre->close();

// Check watchlist for spotlight movies
$watchlist_ids = [];
$wl = $conn->prepare("SELECT movie_id FROM WATCHLIST WHERE user_id = ?");
$wl->bind_param('i', $user_id);
$wl->execute();
foreach ($wl->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $watchlist_ids[] = $r['movie_id'];
$wl->close();

// "Because you liked" — find user's top genre from watchlist
$because_rows = [];
$top_genre_res = $conn->prepare(
    "SELECT mg.genre, COUNT(*) AS cnt
     FROM WATCHLIST w
     JOIN MOVIE_GENRES mg ON w.movie_id = mg.movie_id
     WHERE w.user_id = ?
     GROUP BY mg.genre
     ORDER BY cnt DESC
     LIMIT 1"
);
$top_genre_res->bind_param('i', $user_id);
$top_genre_res->execute();
$top_genre_row = $top_genre_res->get_result()->fetch_assoc();
$top_genre_res->close();
$because_genre = $top_genre_row ? $top_genre_row['genre'] : null;

if ($because_genre) {
    $bg = $conn->prepare(
        "SELECT m.ID, m.TITLE, m.RATING, m.RELEASE_YEAR, m.POSTERURL, m.TRAILERURL
         FROM MOVIES m
         INNER JOIN MOVIE_GENRES mg ON m.id = mg.movie_id
         WHERE mg.genre = ? AND m.ID NOT IN (SELECT movie_id FROM WATCHLIST WHERE user_id = ?)
         GROUP BY m.ID
         ORDER BY m.RATING DESC
         LIMIT 20"
    );
    $bg->bind_param('si', $because_genre, $user_id);
    $bg->execute();
    $because_rows = $bg->get_result()->fetch_all(MYSQLI_ASSOC);
    $bg->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="ReelFlix — your personal cinematic universe." />
    <title>Dashboard - ReelFlix</title>
    <link rel="icon" type="image/x-icon" href="images/clapperboard.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  </head>
  <body>

    <!-- Cinematic intro — only on first visit per session -->
    <div id="dash-intro" aria-hidden="true">
      <div class="dash-intro__line"></div>
      <div class="dash-intro__logo"><span class="reel">REEL</span><span class="flix">FLIX</span></div>
      <p class="dash-intro__tagline">Your cinematic universe</p>
      <div class="dash-intro__line"></div>
    </div>
    <script>
    (function () {
      const el = document.getElementById('dash-intro');
      if (!el) return;
      if (sessionStorage.getItem('rf_intro_shown')) { el.remove(); return; }
      sessionStorage.setItem('rf_intro_shown', '1');
      requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('intro-visible')));
      setTimeout(() => {
        el.classList.add('intro-fade-out');
        el.addEventListener('transitionend', () => el.remove(), { once: true });
        setTimeout(() => el.remove(), 1000);
      }, 2600);
    })();
    </script>

    <nav class="nav-bar">
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

    <!-- ===================== SPOTLIGHT HERO ===================== -->
    <?php if (!empty($spotlight)): ?>
    <section class="spotlight-hero" id="spotlightHero">
      <?php foreach ($spotlight as $i => $s): ?>
      <div class="spotlight-slide <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>">
        <div class="spotlight-bg" style="background-image:url('<?php echo htmlspecialchars($s['BACKDROP_URL']); ?>')"></div>
        <div class="spotlight-overlay"></div>
        <div class="spotlight-content">
          <div class="spotlight-genres"><?php echo htmlspecialchars($s['GENRES'] ?? ''); ?></div>
          <h1 class="spotlight-title"><?php echo htmlspecialchars($s['TITLE']); ?></h1>
          <div class="spotlight-meta">
            <?php if ($s['RATING']): ?><span class="rating-badge" style="font-size:0.95rem;">★ <?php echo $s['RATING']; ?></span><?php endif; ?>
            <span><?php echo $s['RELEASE_YEAR']; ?></span>
          </div>
          <p class="spotlight-desc"><?php echo htmlspecialchars(substr($s['DESCRIPTION'], 0, 180)); ?>…</p>
          <div class="spotlight-actions">
            <a href="moviedetails.php?id=<?php echo $s['ID']; ?>" class="btn btn-primary spotlight-btn">More Info</a>
            <?php if (!empty($s['TRAILERURL'])): ?>
              <button class="btn btn-danger spotlight-btn trailer-btn"
                      data-trailer="<?php echo htmlspecialchars($s['TRAILERURL']); ?>">Watch Trailer</button>
            <?php endif; ?>
            <?php if (!in_array($s['ID'], $watchlist_ids)): ?>
              <a href="#" class="btn spotlight-btn add-to-watchlist" id="add-<?php echo $s['ID']; ?>"
                 style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.3);color:#fff;">+ Watchlist</a>
            <?php else: ?>
              <a href="#" class="btn spotlight-btn remove-from-watchlist" id="remove-<?php echo $s['ID']; ?>"
                 style="background:rgba(229,9,20,0.2);border-color:rgba(229,9,20,0.5);color:#fff;">In Watchlist</a>
            <?php endif; ?>
          </div>
        </div>
        <div class="spotlight-poster">
          <img src="<?php echo htmlspecialchars($s['POSTERURL']); ?>" alt="<?php echo htmlspecialchars($s['TITLE']); ?>">
        </div>
      </div>
      <?php endforeach; ?>

      <!-- Dot indicators -->
      <div class="spotlight-dots">
        <?php foreach ($spotlight as $i => $s): ?>
          <button class="spotlight-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-target="<?php echo $i; ?>"></button>
        <?php endforeach; ?>
      </div>

      <!-- Progress bar -->
      <div class="spotlight-progress"><div class="spotlight-progress-bar" id="spotlightBar"></div></div>
    </section>
    <?php endif; ?>

    <!-- ===================== SEARCH + GENRE SHORTCUT ===================== -->
    <div class="dashboard-controls reveal">
      <form action="search.php" method="GET" class="search-bar-wrapper" style="margin:0;max-width:460px;">
        <input type="text" name="q" placeholder="Search by title, director, or genre…" />
        <button type="submit">Search</button>
      </form>
      <a href="genre.php" class="btn" style="border-color:rgba(245,197,24,0.35);color:var(--accent);background:rgba(245,197,24,0.06);font-size:0.82rem;letter-spacing:0.1em;">Browse Genres</a>
    </div>

    <!-- ===================== NETFLIX GENRE ROWS ===================== -->
    <main class="dashboard-main">
      <?php foreach ($genre_rows as $genre => $movies): ?>
      <section class="genre-row reveal">
        <div class="genre-row-header">
          <h2 class="genre-row-title"><?php echo htmlspecialchars($genre); ?></h2>
          <a href="genre.php?genre=<?php echo urlencode($genre); ?>" class="genre-row-more">See all →</a>
        </div>
        <div class="genre-row-track" data-genre="<?php echo htmlspecialchars($genre); ?>">
          <?php foreach ($movies as $m): ?>
          <div class="shelf-card"
               data-id="<?php echo $m['ID']; ?>"
               data-title="<?php echo htmlspecialchars($m['TITLE'], ENT_QUOTES); ?>"
               data-trailer="<?php echo htmlspecialchars($m['TRAILERURL'] ?? '', ENT_QUOTES); ?>"
               onclick="window.location.href='moviedetails.php?id=<?php echo $m['ID']; ?>'">
            <div class="shelf-poster">
              <img src="<?php echo htmlspecialchars($m['POSTERURL']); ?>" alt="<?php echo htmlspecialchars($m['TITLE']); ?>" loading="lazy">
              <div class="shelf-hover-overlay">
                <span class="shelf-play-btn">▶</span>
              </div>
            </div>
            <div class="shelf-info">
              <p class="shelf-title"><?php echo htmlspecialchars($m['TITLE']); ?></p>
              <?php if ($m['RATING']): ?>
                <span class="shelf-rating">★ <?php echo $m['RATING']; ?></span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endforeach; ?>

      <?php if (!empty($because_rows)): ?>
      <section class="genre-row reveal">
        <div class="genre-row-header">
          <h2 class="genre-row-title">Because you liked <?php echo htmlspecialchars($because_genre); ?></h2>
          <a href="genre.php?genre=<?php echo urlencode($because_genre); ?>" class="genre-row-more">See all →</a>
        </div>
        <div class="genre-row-track">
          <?php foreach ($because_rows as $m): ?>
          <div class="shelf-card"
               data-id="<?php echo $m['ID']; ?>"
               data-title="<?php echo htmlspecialchars($m['TITLE'], ENT_QUOTES); ?>"
               data-trailer="<?php echo htmlspecialchars($m['TRAILERURL'] ?? '', ENT_QUOTES); ?>"
               onclick="window.location.href='moviedetails.php?id=<?php echo $m['ID']; ?>'">
            <div class="shelf-poster">
              <img src="<?php echo htmlspecialchars($m['POSTERURL']); ?>" alt="<?php echo htmlspecialchars($m['TITLE']); ?>" loading="lazy">
              <div class="shelf-hover-overlay">
                <span class="shelf-play-btn">▶</span>
              </div>
            </div>
            <div class="shelf-info">
              <p class="shelf-title"><?php echo htmlspecialchars($m['TITLE']); ?></p>
              <?php if ($m['RATING']): ?>
                <span class="shelf-rating">★ <?php echo $m['RATING']; ?></span>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>
    </main>

    <!-- ===================== TRAILER MODAL ===================== -->
    <div id="trailerModal" class="trailer-modal" aria-hidden="true">
      <div class="trailer-modal-backdrop"></div>
      <div class="trailer-modal-box">
        <button class="trailer-close" id="trailerClose" aria-label="Close trailer">&#215;</button>
        <div class="trailer-embed">
          <iframe id="trailerFrame" src="" frameborder="0"
            allow="autoplay; encrypted-media; picture-in-picture"
            allowfullscreen></iframe>
        </div>
      </div>
    </div>

    <div class="copyright-wrapper">&copy; 2026 ReelFlix &#124; All rights reserved</div>

    <script>
    // ---- Spotlight Rotator ----
    (function() {
      const slides   = document.querySelectorAll('.spotlight-slide');
      const dots     = document.querySelectorAll('.spotlight-dot');
      const bar      = document.getElementById('spotlightBar');
      if (!slides.length) return;
      let current = 0, timer, barAnim;
      const DURATION = 8000;

      function go(n) {
        slides[current].classList.remove('active');
        dots[current].classList.remove('active');
        current = (n + slides.length) % slides.length;
        slides[current].classList.add('active');
        dots[current].classList.add('active');
        resetBar();
      }

      function resetBar() {
        if (bar) { bar.style.transition = 'none'; bar.style.width = '0%'; }
        clearTimeout(timer);
        requestAnimationFrame(() => requestAnimationFrame(() => {
          if (bar) { bar.style.transition = `width ${DURATION}ms linear`; bar.style.width = '100%'; }
          timer = setTimeout(() => go(current + 1), DURATION);
        }));
      }

      dots.forEach(d => d.addEventListener('click', () => go(parseInt(d.dataset.target))));
      resetBar();
    })();

    // ---- Trailer Modal ----
    (function() {
      function ytEmbed(url) {
        const m = url.match(/(?:v=|youtu\.be\/)([^&?/]+)/);
        return m ? `https://www.youtube.com/embed/${m[1]}?autoplay=1&rel=0` : '';
      }
      const modal = document.getElementById('trailerModal');
      const frame = document.getElementById('trailerFrame');
      const closeBtn = document.getElementById('trailerClose');

      document.querySelectorAll('.trailer-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          const embed = ytEmbed(btn.dataset.trailer);
          if (!embed) return;
          frame.src = embed;
          modal.classList.add('open');
          document.body.style.overflow = 'hidden';
        });
      });

      function closeModal() {
        modal.classList.remove('open');
        frame.src = '';
        document.body.style.overflow = '';
      }
      closeBtn.addEventListener('click', closeModal);
      document.querySelector('.trailer-modal-backdrop').addEventListener('click', closeModal);
      document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    })();
    </script>
  </body>
</html>
