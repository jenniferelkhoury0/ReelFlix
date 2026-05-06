/* ========================
   Toast Notification System
   ======================== */
function showToast(message, type = 'success') {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const icons = { success: '✓', error: '✕', info: 'ℹ' };
  const toast = document.createElement('div');
  toast.className = `toast-notification ${type}`;
  toast.innerHTML = `<span class="toast-icon">${icons[type] || '✓'}</span><span>${message}</span>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.classList.add('fade-out');
    setTimeout(() => toast.remove(), 320);
  }, 3000);
}

/* ========================
   jQuery: Watchlist & Favorites
   ======================== */
$(document).ready(function () {

  // REMOVE from watchlist
  $(document).on('click', '.remove-from-watchlist', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const movieId = $(this).attr('id').split('-')[1];
    const $btn = $(this);
    $.ajax({
      url: 'remove_from_watchlist.php',
      type: 'POST',
      dataType: 'json',
      data: { movie_id: movieId },
      success: (resp) => {
        if (resp && resp.success) {
          $btn.text('Removed').prop('disabled', true).removeClass('btn-danger').css('opacity', '0.5');
          showToast('Removed from watchlist', 'info');
        } else {
          showToast('Could not remove — try again', 'error');
        }
      },
      error: () => showToast('Network error', 'error'),
    });
  });

  // ADD to watchlist
  $(document).on('click', '.add-to-watchlist', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const movieId = $(this).attr('id').split('-')[1];
    const $btn = $(this);
    $.ajax({
      url: 'add_to_watchlist.php',
      type: 'POST',
      dataType: 'json',
      data: { movie_id: movieId },
      success: (resp) => {
        if (resp && resp.success) {
          $btn.text('Added ✓').prop('disabled', true).css('opacity', '0.7');
          showToast('Added to watchlist!', 'success');
        } else {
          showToast('Could not add — try again', 'error');
        }
      },
      error: () => showToast('Network error', 'error'),
    });
  });

  // ADD to favorites
  $(document).on('click', '.add-to-favorites', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const movieId = $(this).attr('id').split('-')[1];
    const $btn = $(this);
    $.ajax({
      url: 'add_to_favorites.php',
      type: 'POST',
      dataType: 'json',
      data: { movie_id: movieId },
      success: (resp) => {
        if (resp && resp.success) {
          $btn.text('Favorited ♥').prop('disabled', true).css('opacity', '0.7');
          showToast('Added to favorites!', 'success');
        } else {
          showToast('Could not add — try again', 'error');
        }
      },
      error: () => showToast('Network error', 'error'),
    });
  });

  // REMOVE from favorites
  $(document).on('click', '.remove-from-favorites', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const movieId = $(this).data('movie-id');
    const $btn = $(this);
    $.ajax({
      url: 'remove_from_favorites.php',
      type: 'POST',
      dataType: 'json',
      data: { movie_id: movieId },
      success: (resp) => {
        if (resp && resp.success) {
          $btn.closest('.movie-card').fadeOut(300, function () { $(this).remove(); });
          showToast('Removed from favorites', 'info');
        } else {
          showToast('Could not remove — try again', 'error');
        }
      },
      error: () => showToast('Network error', 'error'),
    });
  });

  // RANDOM movie button
  $(document).on('click', '.btn-get-another', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const movieCard = document.querySelector('.movie-card');
    if (movieCard) {
      movieCard.innerHTML = `
        <div class="skeleton skeleton-poster"></div>
        <div class="skeleton skeleton-line wide"></div>
        <div class="skeleton skeleton-line mid"></div>
        <div class="skeleton skeleton-line short"></div>
      `;
    }
    $.ajax({
      url: 'random.php?ajax=1',
      method: 'GET',
      dataType: 'json',
      success: (data) => {
        const movie = data.movie;
        const inWatchlist = data.in_watchlist;
        const movieCard = document.querySelector('.movie-card');
        if (!movieCard) return;
        movieCard.innerHTML = `
          <img src="${movie.POSTERURL}" alt="${movie.TITLE}" style="max-width:300px;">
          <h3>${movie.TITLE}</h3>
          ${movie.RATING ? `<span class="rating-badge">${movie.RATING}</span>` : ''}
          <p><strong>Year:</strong> ${movie.RELEASE_YEAR}</p>
          <p><strong>Director:</strong> ${movie.DIRECTOR}</p>
          <p><strong>Genre:</strong> ${movie.GENRES}</p>
          <p>${movie.DESCRIPTION}</p>
          <div class="button-group" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
            <a href="#" class="btn btn-primary btn-get-another">Get Another Movie</a>
            ${!inWatchlist
              ? `<a href="#" class="btn btn-sm btn-primary add-to-watchlist" id="add-${movie.ID}" onclick="event.stopPropagation();">Add to Watchlist</a>`
              : `<a href="#" class="btn btn-danger remove-from-watchlist" id="remove-${movie.ID}" onclick="event.stopPropagation();">Remove from Watchlist</a>`
            }
          </div>
        `;
      },
      error: () => showToast('Could not fetch a movie', 'error'),
    });
  });

  // ========================
  // Movie Quiz + Swipe
  // ========================
  if (document.getElementById('testSection')) {
    const questions = [
      {
        question: 'What kind of story do you want tonight?',
        subtitle: 'We use this as your primary genre filter.',
        options: [
          { text: 'Comedy', sub: 'Light tone, laughs', value: 'comedy' },
          { text: 'Action', sub: 'Pace and stakes', value: 'action' },
          { text: 'Drama', sub: 'Character and emotion', value: 'drama' },
          { text: 'Horror', sub: 'Tension and fear', value: 'horror' },
          { text: 'Sci‑Fi', sub: 'Ideas and spectacle', value: 'sci-fi' },
        ],
      },
      {
        question: 'How much time do you have?',
        subtitle: 'Runtime filter on your catalogue.',
        options: [
          { text: 'Under 90 minutes', sub: 'Tight sitting', value: 'short' },
          { text: '90–120 minutes', sub: 'Standard feature', value: 'medium' },
          { text: 'Over 120 minutes', sub: 'Epic-length OK', value: 'long' },
        ],
      },
      {
        question: 'Which release era?',
        subtitle: 'Rough decade bands.',
        options: [
          { text: 'Classic — before 1980', sub: '', value: 1980 },
          { text: 'Modern — 1980 to 2009', sub: '', value: 2010 },
          { text: 'Recent — 2010 onward', sub: '', value: 3000 },
        ],
      },
      {
        question: 'What setting fits the night?',
        subtitle: 'Second genre axis — we widen if too few matches.',
        options: [
          { text: 'Grounded / real-world', sub: 'Drama, crime, romance', value: 'drama' },
          { text: 'Fantasy & adventure', sub: '', value: 'fantasy' },
          { text: 'Science fiction', sub: '', value: 'sci-fi' },
          { text: 'Historical / epic', sub: 'History, war, biography', value: 'historical' },
        ],
      },
    ];

    let currentQuestion = 0;
    const answers = {};
    let currentMovieIndex = 0;
    let movies = [];
    let matchLabel = '';

    function esc(s) {
      const d = document.createElement('div');
      d.textContent = s == null ? '' : String(s);
      return d.innerHTML;
    }

    function updateProgress() {
      const pct = ((currentQuestion + 1) / questions.length) * 100;
      const bar = document.getElementById('progressBar');
      const num = document.getElementById('questionNumber');
      if (bar) bar.style.width = `${pct}%`;
      if (num) num.textContent = `Step ${currentQuestion + 1} of ${questions.length}`;
    }

    function displayQuestion() {
      const q = questions[currentQuestion];
      const titleEl = document.getElementById('questionText');
      titleEl.innerHTML = `<span class="quiz-q-main">${esc(q.question)}</span>${q.subtitle ? `<span class="quiz-q-sub">${esc(q.subtitle)}</span>` : ''}`;
      const container = document.getElementById('optionsContainer');
      container.innerHTML = '';
      q.options.forEach((opt) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn-quiz';
        btn.innerHTML = opt.sub
          ? `<span class="btn-quiz__title">${esc(opt.text)}</span><span class="btn-quiz__sub">${esc(opt.sub)}</span>`
          : `<span class="btn-quiz__title">${esc(opt.text)}</span>`;
        btn.addEventListener('click', () => selectOption(opt.value));
        container.appendChild(btn);
      });
      updateProgress();
    }

    const genreGlowColors = {
      comedy:    'rgba(245,158,11,0.16)',
      action:    'rgba(239,68,68,0.16)',
      drama:     'rgba(99,102,241,0.16)',
      horror:    'rgba(124,58,237,0.16)',
      'sci-fi':  'rgba(6,182,212,0.16)',
      thriller:  'rgba(249,115,22,0.16)',
      romance:   'rgba(236,72,153,0.16)',
      adventure: 'rgba(16,185,129,0.16)',
    };

    function selectOption(value) {
      answers[`q${currentQuestion + 1}`] = value;
      // Genre color shift on mood question
      if (currentQuestion === 0 && genreGlowColors[value]) {
        document.documentElement.style.setProperty('--bg-glow-color', genreGlowColors[value]);
      }
      if (currentQuestion < questions.length - 1) {
        currentQuestion += 1;
        displayQuestion();
      } else {
        fetchMovies();
      }
    }

    function fetchMovies() {
      const titleEl = document.getElementById('questionText');
      titleEl.innerHTML = '<span class="quiz-q-main">Building your shortlist</span><span class="quiz-q-sub">Matching against genres, runtime, and year…</span>';
      document.getElementById('optionsContainer').innerHTML =
        '<p class="quiz-loading-msg">Querying the catalogue…</p>';
      fetch('test_and_swipe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'getMovies',
          mood: answers.q1,
          length: answers.q2,
          era: answers.q3,
          setting: answers.q4,
        }),
      })
        .then((r) => r.json())
        .then((data) => {
          if (!data.success || !data.movies || data.movies.length === 0) {
            showToast('No titles returned — check your database or relax filters and try again.', 'error');
            currentQuestion = 0;
            Object.keys(answers).forEach((k) => delete answers[k]);
            displayQuestion();
            return;
          }
          matchLabel = data.match_label || '';
          startSwipe(data.movies);
        })
        .catch(() => {
          showToast('Could not reach the quiz service.', 'error');
          currentQuestion = 0;
          displayQuestion();
        });
    }

    function startSwipe(movieList) {
      movies = movieList;
      currentMovieIndex = 0;

      const testSection = document.getElementById('testSection');
      const swipe = document.getElementById('swipeSection');

      testSection.classList.add('quiz-slide-out');
      setTimeout(() => {
        testSection.style.display = 'none';
        testSection.classList.remove('quiz-slide-out');
        if (swipe) {
          swipe.style.display = 'block';
          swipe.classList.add('quiz-slide-in');
          swipe.addEventListener('animationend', () => swipe.classList.remove('quiz-slide-in'), { once: true });
        }
        const hdr = document.getElementById('quizResultsHead');
        if (hdr) {
          hdr.innerHTML = `
            <h2 class="quiz-results__title">Your shortlist</h2>
            <p class="quiz-results__meta">${esc(matchLabel)} · ${movies.length} title${movies.length === 1 ? '' : 's'}</p>
            <p class="quiz-results__hint">Skip or save to watchlist (sign in required to save).</p>
          `;
        }
        const btns = document.querySelector('.swipe-buttons');
        if (btns) btns.style.display = 'flex';
        displayCurrentMovie();
        bindQuizSwipeDeck();
      }, 360);
    }

    function displayCurrentMovie() {
      const movie = movies[currentMovieIndex];
      const content = document.getElementById('movieContent');
      if (!content) return;
      if (!movie) {
        content.innerHTML = `
          <div class="quiz-done-card">
            <h2 class="quiz-done-card__title">End of shortlist</h2>
            <p class="quiz-done-card__text">You have seen every match from this run.</p>
            <a href="test.html" class="btn btn-primary quiz-done-card__btn">Run quiz again</a>
            <a href="dashboard.php" class="btn btn-landing-ghost quiz-done-card__btn quiz-done-card__btn--secondary">Back to home</a>
          </div>
        `;
        const btns = document.querySelector('.swipe-buttons');
        if (btns) btns.style.display = 'none';
        return;
      }
      content.innerHTML = `
        <div class="skeleton quiz-card-skel-poster"></div>
        <div class="skeleton quiz-card-skel-line quiz-card-skel-line--lg"></div>
        <div class="skeleton quiz-card-skel-line quiz-card-skel-line--sm"></div>
        <div class="skeleton quiz-card-skel-line"></div>
      `;

      const poster = movie.POSTERURL || '';
      const renderCard = (showPoster) => {
        const genres = movie.GENRES ? `<p class="quiz-movie-genres">${esc(movie.GENRES)}</p>` : '';
        const rating = movie.RATING
          ? `<span class="rating-badge quiz-movie-rating">${esc(movie.RATING)}</span>`
          : '';
        const desc = movie.DESCRIPTION
          ? `<p class="quiz-movie-desc">${esc(movie.DESCRIPTION)}</p>`
          : '';
        const posterBlock = showPoster && poster
          ? `<img class="quiz-movie-poster" src=${JSON.stringify(poster)} alt=${JSON.stringify(movie.TITLE)} width="280" height="420" loading="lazy">`
          : '<div class="quiz-movie-poster quiz-movie-poster--missing">No poster</div>';
        content.innerHTML = `
          ${posterBlock}
          <h3 class="quiz-movie-title">${esc(movie.TITLE)}</h3>
          ${rating}
          <p class="quiz-movie-meta"><strong>Year</strong> ${esc(movie.RELEASE_YEAR)} · <strong>Runtime</strong> ${movie.DURATION ? `${esc(movie.DURATION)} min` : '—'}</p>
          <p class="quiz-movie-meta"><strong>Director</strong> ${esc(movie.DIRECTOR || '—')}</p>
          ${genres}
          ${desc}
          <a href="moviedetails.php?id=${encodeURIComponent(movie.ID)}" class="quiz-movie-details-link">Full details →</a>
        `;
      };
      if (!poster) {
        renderCard(false);
      } else {
        const img = new Image();
        img.onload = () => renderCard(true);
        img.onerror = () => renderCard(false);
        img.src = poster;
      }
    }

    function handleSwipe(like) {
      const cur = movies[currentMovieIndex];
      if (like && cur) {
        fetch('test_and_swipe.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'addToWatchlist', movie_id: cur.ID }),
        })
          .then((r) => r.json())
          .then((data) => {
            if (data.success) showToast('Saved to watchlist.', 'success');
            else if (data.message) showToast(data.message, 'info');
          })
          .catch(() => showToast('Could not update watchlist.', 'error'));
      }
      currentMovieIndex += 1;
      displayCurrentMovie();
    }

    let swipeDeckBound = false;
    function bindQuizSwipeDeck() {
      const deck = document.getElementById('quizSwipeDeck');
      if (!deck || swipeDeckBound) return;
      swipeDeckBound = true;
      let active = false;
      let startX = 0;
      let dx = 0;

      deck.style.touchAction = 'none';
      deck.addEventListener('pointerdown', (e) => {
        if (!movies.length || currentMovieIndex >= movies.length) return;
        active = true;
        startX = e.clientX;
        deck.style.transition = 'none';
      });
      document.addEventListener('pointermove', (e) => {
        if (!active) return;
        dx = e.clientX - startX;
        const rot = dx * 0.04;
        deck.style.transform = `translateX(${dx}px) rotate(${rot}deg)`;
      });
      document.addEventListener('pointerup', () => {
        if (!active) return;
        active = false;
        deck.style.transition = 'transform 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
        if (dx > 110) {
          deck.style.transform = 'translateX(120vw) rotate(14deg)';
          setTimeout(() => {
            handleSwipe(true);
            deck.style.transition = 'none';
            deck.style.transform = '';
          }, 280);
        } else if (dx < -110) {
          deck.style.transform = 'translateX(-120vw) rotate(-14deg)';
          setTimeout(() => {
            handleSwipe(false);
            deck.style.transition = 'none';
            deck.style.transform = '';
          }, 280);
        } else {
          deck.style.transform = '';
        }
        dx = 0;
      });
    }

    const yesBtn = document.getElementById('yesButton');
    const noBtn = document.getElementById('noButton');
    if (yesBtn) yesBtn.addEventListener('click', () => handleSwipe(true));
    if (noBtn) noBtn.addEventListener('click', () => handleSwipe(false));

    displayQuestion();
  }
});

/* ========================
   Cursor Glow Tracking (gold radial gradient follows mouse)
   ======================== */
(function () {
  const mq = window.matchMedia('(prefers-reduced-motion: reduce)');
  function onMove(e) {
    document.documentElement.style.setProperty('--mouse-x', `${e.clientX}px`);
    document.documentElement.style.setProperty('--mouse-y', `${e.clientY}px`);
  }
  function bind() {
    if (!mq.matches) document.addEventListener('mousemove', onMove, { passive: true });
    else document.removeEventListener('mousemove', onMove);
  }
  bind();
  if (mq.addEventListener) mq.addEventListener('change', bind);
})();

/* ========================
   Shelf Hover Trailer Preview
   ======================== */
(function () {
  let popup = null, hoverTimer = null, activeCard = null;

  function ytEmbed(url) {
    if (!url) return '';
    const m = url.match(/(?:v=|youtu\.be\/)([^&?/]+)/);
    return m ? `https://www.youtube.com/embed/${m[1]}?autoplay=1&mute=1&controls=0&rel=0&modestbranding=1` : '';
  }

  function createPopup() {
    popup = document.createElement('div');
    popup.className = 'shelf-trailer-popup';
    popup.innerHTML = '<iframe allowfullscreen allow="autoplay"></iframe><div class="shelf-trailer-popup-title"></div>';
    document.body.appendChild(popup);
    popup.addEventListener('mouseenter', () => { clearTimeout(hoverTimer); });
    popup.addEventListener('mouseleave', hidePopup);
  }

  function positionPopup(card) {
    const rect = card.getBoundingClientRect();
    const pw = 320, ph = 210;
    let left = rect.left + rect.width / 2 - pw / 2;
    let top  = rect.top - ph - 8 + window.scrollY;
    if (top < window.scrollY + 10) top = rect.bottom + window.scrollY + 8;
    if (left < 8) left = 8;
    if (left + pw > window.innerWidth - 8) left = window.innerWidth - pw - 8;
    popup.style.left = left + 'px';
    popup.style.top  = top + 'px';
  }

  function showPopup(card) {
    const trailer = card.dataset.trailer;
    const embed   = ytEmbed(trailer);
    if (!embed) return;
    if (!popup) createPopup();
    popup.querySelector('iframe').src = embed;
    popup.querySelector('.shelf-trailer-popup-title').textContent = card.dataset.title || '';
    positionPopup(card);
    activeCard = card;
    requestAnimationFrame(() => popup.classList.add('visible'));
  }

  function hidePopup() {
    if (!popup) return;
    popup.classList.remove('visible');
    setTimeout(() => {
      if (popup && !popup.classList.contains('visible')) {
        popup.querySelector('iframe').src = '';
      }
    }, 250);
    activeCard = null;
  }

  document.addEventListener('mouseover', e => {
    const card = e.target.closest('.shelf-card[data-trailer]');
    if (!card || card === activeCard) return;
    clearTimeout(hoverTimer);
    hoverTimer = setTimeout(() => showPopup(card), 1400);
  }, { passive: true });

  document.addEventListener('mouseout', e => {
    const card = e.target.closest('.shelf-card[data-trailer]');
    if (!card) return;
    clearTimeout(hoverTimer);
    const to = e.relatedTarget;
    if (to && (to.closest('.shelf-card') === card || to.closest('.shelf-trailer-popup'))) return;
    hoverTimer = setTimeout(hidePopup, 300);
  }, { passive: true });
})();

/* ========================
   DOMContentLoaded init
   ======================== */
document.addEventListener('DOMContentLoaded', () => {

  // Scroll reveal
  const revealObserver = new IntersectionObserver((entries, obs) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('active');
        obs.unobserve(entry.target);
      }
    });
  }, { root: null, threshold: 0.08, rootMargin: '0px 0px 60px 0px' });

  document.querySelectorAll('.reveal').forEach((el) => revealObserver.observe(el));

  // Active nav link highlight
  const currentPage = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-bar a').forEach((link) => {
    const href = link.getAttribute('href');
    if (href && href !== '#' && href === currentPage) {
      link.classList.add('nav-active');
    }
  });

  // Mobile hamburger nav
  const navbar = document.querySelector('.nav-bar');
  if (navbar) {
    const navList = navbar.querySelector('ul');
    if (navList) {
      const hamburger = document.createElement('button');
      hamburger.className = 'hamburger-btn';
      hamburger.setAttribute('aria-label', 'Toggle navigation');
      hamburger.innerHTML = '<span></span><span></span><span></span>';
      navbar.insertBefore(hamburger, navList);
      hamburger.addEventListener('click', () => {
        navList.classList.toggle('nav-open');
        hamburger.classList.toggle('active');
      });
    }
  }

  // Password show/hide toggles
  document.querySelectorAll('.toggle-password').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = btn.previousElementSibling;
      if (!input) return;
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      btn.textContent = isPassword ? '🙈' : '👁';
    });
  });

  // --- Phase 2: Micro-sounds ---
  const AudioContext = window.AudioContext || window.webkitAudioContext;
  let audioCtx;
  function playTick() {
    if (!audioCtx) audioCtx = new AudioContext();
    if (audioCtx.state === 'suspended') audioCtx.resume();
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.type = 'sine';
    osc.frequency.setValueAtTime(800, audioCtx.currentTime);
    osc.frequency.exponentialRampToValueAtTime(1200, audioCtx.currentTime + 0.05);
    gain.gain.setValueAtTime(0.02, audioCtx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.05);
    osc.start();
    osc.stop(audioCtx.currentTime + 0.05);
  }

  document.querySelectorAll('a, .btn, .movie-card, .genreCard').forEach(el => {
    el.addEventListener('mouseenter', playTick);
  });

  // --- Phase 2: Ambient Glow Injection ---
  document.querySelectorAll('.movie-card img:not(.ambient-glow)').forEach(img => {
    if (img.parentNode.querySelector('.ambient-glow')) return;
    const glow = document.createElement('img');
    glow.src = img.src;
    glow.alt = '';
    glow.className = 'ambient-glow';
    glow.setAttribute('aria-hidden', 'true');
    img.parentNode.insertBefore(glow, img);
  });

  // --- Phase 2: 3D Tilt with Glass Glare ---
  function applyTilt(card) {
    card.addEventListener('mousemove', e => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      const cx = rect.width / 2;
      const cy = rect.height / 2;
      const rotX = ((y - cy) / cy) * -10;
      const rotY = ((x - cx) / cx) * 10;
      card.style.transform = `perspective(1000px) rotateX(${rotX}deg) rotateY(${rotY}deg) scale3d(1.02, 1.02, 1.02)`;
      card.style.setProperty('--glare-x', `${(x / rect.width) * 100}%`);
      card.style.setProperty('--glare-y', `${(y / rect.height) * 100}%`);
    });
    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
      card.style.removeProperty('--glare-x');
      card.style.removeProperty('--glare-y');
    });
  }
  document.querySelectorAll('.movie-card, .genreCard').forEach(applyTilt);

  // Stagger card entrance animation delays
  document.querySelectorAll('.movie-card').forEach((card, i) => {
    card.style.animationDelay = `${i * 60}ms`;
  });

  // --- Phase 2: Page Transitions ---
  document.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', e => {
      const href = link.getAttribute('href');
      if (!href) return;
      const isExternal = href.startsWith('http') || href.startsWith('//') || href.startsWith('mailto:') || href.startsWith('tel:');
      if (!isExternal && !href.startsWith('#') && !href.startsWith('javascript:') && link.target !== '_blank') {
        e.preventDefault();
        document.body.classList.add('fade-out');
        setTimeout(() => { window.location.href = href; }, 180);
      }
    });
  });

  // --- Ripple Effect on Buttons ---
  document.addEventListener('click', e => {
    const btn = e.target.closest('.btn, .btn-quiz, .wheel-center-btn');
    if (!btn) return;
    const rect = btn.getBoundingClientRect();
    const ripple = document.createElement('span');
    ripple.className = 'ripple';
    ripple.style.left = (e.clientX - rect.left) + 'px';
    ripple.style.top  = (e.clientY - rect.top)  + 'px';
    btn.style.position = 'relative';
    btn.style.overflow = 'hidden';
    btn.appendChild(ripple);
    ripple.addEventListener('animationend', () => ripple.remove());
  }, { passive: true });

  // --- Magnetic Buttons ---
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!reducedMotion) {
    document.querySelectorAll('.btn-landing-primary, .btn-landing-ghost, .btn-primary, .btn-danger, .nav-bar a:not(.dropdown-item)').forEach(btn => {
      btn.addEventListener('mousemove', e => {
        const rect = btn.getBoundingClientRect();
        const cx = rect.left + rect.width  / 2;
        const cy = rect.top  + rect.height / 2;
        const dx = (e.clientX - cx) * 0.22;
        const dy = (e.clientY - cy) * 0.22;
        btn.style.transform = `translate(${dx}px, ${dy}px)`;
      });
      btn.addEventListener('mouseleave', () => {
        btn.style.transform = '';
      });
    });
  }

  // --- Floating Particles ---
  if (!reducedMotion) {
    const canvas = document.createElement('canvas');
    canvas.id = 'particleCanvas';
    document.body.insertBefore(canvas, document.body.firstChild);
    const ctx = canvas.getContext('2d');
    let W = canvas.width  = window.innerWidth;
    let H = canvas.height = window.innerHeight;
    const COUNT = 55;
    const particles = Array.from({ length: COUNT }, () => ({
      x: Math.random() * W,
      y: Math.random() * H,
      r: Math.random() * 1.4 + 0.3,
      dx: (Math.random() - 0.5) * 0.25,
      dy: (Math.random() - 0.5) * 0.25,
      a: Math.random() * 0.45 + 0.08,
      col: Math.random() < 0.6 ? '245,197,24' : '229,9,20',
    }));
    function drawParticles() {
      ctx.clearRect(0, 0, W, H);
      particles.forEach(p => {
        p.x += p.dx;
        p.y += p.dy;
        if (p.x < 0) p.x = W;
        else if (p.x > W) p.x = 0;
        if (p.y < 0) p.y = H;
        else if (p.y > H) p.y = 0;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(${p.col},${p.a})`;
        ctx.fill();
      });
      requestAnimationFrame(drawParticles);
    }
    drawParticles();
    window.addEventListener('resize', () => {
      W = canvas.width  = window.innerWidth;
      H = canvas.height = window.innerHeight;
    });
  }

  // --- Count-Up Animation ---
  const countObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el = entry.target;
      const target = parseFloat(el.dataset.countTarget);
      const decimals = String(target).includes('.') ? 1 : 0;
      let start = 0;
      const duration = 1400;
      const startTime = performance.now();
      function step(now) {
        const progress = Math.min((now - startTime) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const val = start + (target - start) * eased;
        el.textContent = val.toFixed(decimals);
        if (progress < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
      countObserver.unobserve(el);
    });
  }, { threshold: 0.5 });
  document.querySelectorAll('[data-count-target]').forEach(el => countObserver.observe(el));

});
