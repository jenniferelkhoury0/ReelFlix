<?php
session_start();
if (!isset($_SESSION['id'])) {
    header('Location: login.html');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'movies_db');
if ($conn->connect_error) die('Connection failed: ' . $conn->connect_error);

// AJAX request — return JSON
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $result = $conn->query("SELECT m.*, GROUP_CONCAT(mg.genre SEPARATOR ', ') AS GENRES
                            FROM MOVIES m LEFT JOIN movie_genres mg ON m.id = mg.movie_id
                            GROUP BY m.id ORDER BY RAND() LIMIT 1");
    $movie = $result->fetch_assoc();

    $in_watchlist = false;
    if ($movie) {
        $chk = $conn->prepare("SELECT 1 FROM WATCHLIST WHERE user_id = ? AND movie_id = ?");
        $chk->bind_param('ii', $_SESSION['id'], $movie['ID']);
        $chk->execute();
        $in_watchlist = $chk->get_result()->num_rows > 0;
        $chk->close();
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'movie' => $movie, 'in_watchlist' => $in_watchlist]);
    $conn->close();
    exit();
}

// Normal page load
$result = $conn->query("SELECT m.*, GROUP_CONCAT(mg.genre SEPARATOR ', ') AS GENRES
                        FROM MOVIES m LEFT JOIN movie_genres mg ON m.id = mg.movie_id
                        GROUP BY m.id ORDER BY RAND() LIMIT 1");
$movie = $result->fetch_assoc();

$in_watchlist = false;
if ($movie) {
    $chk = $conn->prepare("SELECT 1 FROM WATCHLIST WHERE user_id = ? AND movie_id = ?");
    $chk->bind_param('ii', $_SESSION['id'], $movie['ID']);
    $chk->execute();
    $in_watchlist = $chk->get_result()->num_rows > 0;
    $chk->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Feeling indecisive? Let ReelFlix pick a great movie for you at random." />
    <title>Surprise Me! - ReelFlix</title>
    <link rel="icon" type="image/x-icon" href="images/surpriseme.png" />
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

    <main class="wheel-page-wrapper">
      <p class="wheel-eyebrow">Random pick</p>
      <h1 class="wheel-title">Spin the Movie Wheel</h1>
      <p class="wheel-subtitle">Can't decide? Let fate pick your next watch. Spin and discover.</p>

      <div class="wheel-container">
        <div class="wheel-needle"></div>
        <canvas id="wheelCanvas"></canvas>
        <button class="wheel-center-btn" id="spinBtn" type="button">SPIN</button>
      </div>

      <div class="wheel-result-card" id="wheelResult">
        <div class="wheel-result-inner" id="wheelResultInner">
          <!-- Populated by JS -->
        </div>
        <button class="wheel-spin-again" id="spinAgainBtn" type="button">Spin again</button>
      </div>
    </main>

    <div class="copyright-wrapper reveal">&copy; 2026 ReelFlix &#124; All rights reserved</div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/script.js"></script>
    <script>
    /* ========================
       Movie Wheel — canvas + spin
       ======================== */
    (function () {
      const canvas  = document.getElementById('wheelCanvas');
      const spinBtn = document.getElementById('spinBtn');
      const result  = document.getElementById('wheelResult');
      const inner   = document.getElementById('wheelResultInner');
      const again   = document.getElementById('spinAgainBtn');
      if (!canvas || !spinBtn) return;

      const ctx = canvas.getContext('2d');
      const SIZE = 460;
      canvas.width  = SIZE;
      canvas.height = SIZE;

      const segments = [
        { label: 'Action',    color: '#e50914' },
        { label: 'Drama',     color: '#6366f1' },
        { label: 'Comedy',    color: '#f59e0b' },
        { label: 'Sci-Fi',    color: '#06b6d4' },
        { label: 'Horror',    color: '#7c3aed' },
        { label: 'Thriller',  color: '#f97316' },
        { label: 'Romance',   color: '#ec4899' },
        { label: 'Adventure', color: '#10b981' },
      ];

      const N   = segments.length;
      const ARC = (Math.PI * 2) / N;
      let angle = 0;
      let spinning = false;

      function drawWheel(rot) {
        const cx = SIZE / 2;
        const cy = SIZE / 2;
        const R  = SIZE / 2 - 4;
        ctx.clearRect(0, 0, SIZE, SIZE);

        segments.forEach((seg, i) => {
          const start = rot + i * ARC;
          const end   = start + ARC;

          // Segment fill
          ctx.beginPath();
          ctx.moveTo(cx, cy);
          ctx.arc(cx, cy, R, start, end);
          ctx.closePath();
          ctx.fillStyle = seg.color;
          ctx.fill();

          // Subtle inner sheen
          const grad = ctx.createRadialGradient(cx, cy, R * 0.25, cx, cy, R);
          grad.addColorStop(0, 'rgba(255,255,255,0.08)');
          grad.addColorStop(1, 'rgba(0,0,0,0.22)');
          ctx.fillStyle = grad;
          ctx.fill();

          // Divider stroke
          ctx.beginPath();
          ctx.moveTo(cx, cy);
          ctx.arc(cx, cy, R, start, end);
          ctx.closePath();
          ctx.strokeStyle = 'rgba(0,0,0,0.35)';
          ctx.lineWidth = 1.5;
          ctx.stroke();

          // Text label
          ctx.save();
          ctx.translate(cx, cy);
          ctx.rotate(start + ARC / 2);
          ctx.textAlign    = 'right';
          ctx.textBaseline = 'middle';
          ctx.fillStyle    = '#ffffff';
          ctx.font         = 'bold 13px Outfit, sans-serif';
          ctx.shadowColor  = 'rgba(0,0,0,0.55)';
          ctx.shadowBlur   = 4;
          ctx.fillText(seg.label, R - 16, 0);
          ctx.restore();
        });

        // Outer ring
        ctx.beginPath();
        ctx.arc(cx, cy, R, 0, Math.PI * 2);
        ctx.strokeStyle = 'rgba(245,197,24,0.4)';
        ctx.lineWidth   = 4;
        ctx.stroke();
      }

      drawWheel(angle);

      function easeOut(t) { return 1 - Math.pow(1 - t, 4); }

      function spin() {
        if (spinning) return;
        spinning = true;
        spinBtn.disabled = true;
        result.style.display = 'none';

        const totalRotation = (Math.PI * 2) * (6 + Math.random() * 6) + Math.random() * Math.PI * 2;
        const duration = 4000 + Math.random() * 1500;
        const startAngle = angle;
        const startTime  = performance.now();

        function animate(now) {
          const elapsed  = now - startTime;
          const progress = Math.min(elapsed / duration, 1);
          const eased    = easeOut(progress);
          angle = startAngle + totalRotation * eased;
          drawWheel(angle);
          if (progress < 1) {
            requestAnimationFrame(animate);
          } else {
            spinning = false;
            spinBtn.disabled = false;
            fetchMovie();
          }
        }
        requestAnimationFrame(animate);
      }

      function fetchMovie() {
        inner.innerHTML = '<p style="padding:24px;color:var(--muted);text-align:center;">Finding your pick…</p>';
        result.style.display = 'block';

        fetch('random.php?ajax=1')
          .then(r => r.json())
          .then(data => {
            if (!data.success || !data.movie) {
              inner.innerHTML = '<p style="padding:24px;color:var(--muted);">No movies available. Add some to the database.</p>';
              return;
            }
            const m = data.movie;
            const inWl = data.in_watchlist;
            const watchBtn = inWl
              ? `<a href="#" class="btn btn-sm btn-danger remove-from-watchlist" id="remove-${m.ID}">✓ In Watchlist</a>`
              : `<a href="#" class="btn btn-sm btn-primary add-to-watchlist" id="add-${m.ID}">+ Watchlist</a>`;
            inner.innerHTML = `
              ${m.POSTERURL ? `<img class="wheel-result-poster" src="${escHtml(m.POSTERURL)}" alt="${escHtml(m.TITLE)}">` : ''}
              <div class="wheel-result-info">
                <h2 class="wheel-result-title">${escHtml(m.TITLE)}</h2>
                <p class="wheel-result-meta">
                  ${m.RELEASE_YEAR ? escHtml(String(m.RELEASE_YEAR)) : ''}
                  ${m.GENRES ? ' · ' + escHtml(m.GENRES) : ''}
                  ${m.RATING  ? ' · ⭐ ' + escHtml(String(m.RATING)) : ''}
                </p>
                ${m.DESCRIPTION ? `<p class="wheel-result-desc">${escHtml(m.DESCRIPTION)}</p>` : ''}
                <div class="wheel-result-actions">
                  ${watchBtn}
                  <a href="moviedetails.php?id=${encodeURIComponent(m.ID)}" class="btn btn-sm" style="border-color:var(--glass-highlight);color:var(--muted);">Details →</a>
                </div>
              </div>
            `;
          })
          .catch(() => {
            inner.innerHTML = '<p style="padding:24px;color:var(--muted);">Network error — try again.</p>';
          });
      }

      function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
      }

      spinBtn.addEventListener('click', spin);
      if (again) again.addEventListener('click', spin);
    })();
    </script>
  </body>
</html>
