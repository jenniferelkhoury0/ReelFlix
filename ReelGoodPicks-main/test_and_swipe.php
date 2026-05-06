<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$request = json_decode(file_get_contents('php://input'), true);
if (!is_array($request) || empty($request['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request body']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'movies_db');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database unavailable']);
    exit;
}

$MOVIE_SELECT = 'm.ID, m.TITLE, m.RELEASE_YEAR, m.DIRECTOR, m.POSTERURL, m.DESCRIPTION, m.TRAILERURL,
    m.DURATION, m.RATING, m.LANGUAGE, m.COUNTRY,
    (SELECT GROUP_CONCAT(mg2.GENRE ORDER BY mg2.GENRE SEPARATOR ", ") FROM MOVIE_GENRES mg2 WHERE mg2.MOVIE_ID = m.ID) AS GENRES';

function era_sql($era): string
{
    if ($era === null || $era === '') {
        return '';
    }
    $e = (int) $era;
    if ($e === 3000) {
        return ' AND m.RELEASE_YEAR >= 2010';
    }
    if ($e === 2010) {
        return ' AND m.RELEASE_YEAR >= 1980 AND m.RELEASE_YEAR < 2010';
    }
    if ($e === 1980) {
        return ' AND m.RELEASE_YEAR < 1980';
    }
    return '';
}

function length_sql($length): string
{
            switch ($length) {
                case 'short':
            return ' AND m.DURATION IS NOT NULL AND m.DURATION > 0 AND m.DURATION < 90';
                case 'medium':
            return ' AND m.DURATION IS NOT NULL AND m.DURATION >= 90 AND m.DURATION <= 120';
                case 'long':
            return ' AND m.DURATION IS NOT NULL AND m.DURATION > 120';
        default:
            return '';
    }
}

/** Normalise quiz mood → single genre slug matching LOWER(MOVIE_GENRES.GENRE) */
function mood_genre_slug(?string $mood): ?string
{
    $m = strtolower(trim((string) $mood));
    $ok = ['comedy', 'action', 'drama', 'horror', 'sci-fi', 'thriller', 'romance', 'adventure', 'fantasy', 'musical', 'crime', 'war', 'historical', 'biography', 'documentary', 'family'];
    return in_array($m, $ok, true) ? $m : null;
}

/** Theme answer → genre slugs present in DB */
function theme_genre_slugs(?string $theme): array
{
    $t = strtolower(trim((string) $theme));
    $map = [
        'drama' => ['drama', 'romance', 'crime', 'thriller', 'family'],
        'fantasy' => ['fantasy', 'adventure'],
        'sci-fi' => ['sci-fi'],
        'historical' => ['historical', 'war', 'biography'],
    ];
    return $map[$t] ?? [];
}

function genre_exists_sql(array $slugs): array
{
    $slugs = array_values(array_unique(array_filter(array_map('strtolower', $slugs))));
    if ($slugs === []) {
        return ['', '', []];
    }
    $ph = implode(',', array_fill(0, count($slugs), '?'));
    $sql = " AND EXISTS (
        SELECT 1 FROM MOVIE_GENRES mg
        WHERE mg.MOVIE_ID = m.ID AND LOWER(mg.GENRE) IN ($ph)
    )";
    return [$sql, str_repeat('s', count($slugs)), $slugs];
}

function run_movie_query(mysqli $conn, string $where_sql, string $types, array $params): array
{
    global $MOVIE_SELECT;
    $sql = "SELECT DISTINCT $MOVIE_SELECT FROM MOVIES m WHERE 1=1 $where_sql ORDER BY m.RATING DESC, RAND() LIMIT 48";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

function quiz_fetch_movies(mysqli $conn, ?string $mood, ?string $length, $era, ?string $theme): array
{
    $era_sql = era_sql($era);
    $len_full = length_sql($length);

    $mood_slug = mood_genre_slug($mood);
    $theme_slugs = theme_genre_slugs($theme);

    [$mood_sql, $mood_types, $mood_params] = $mood_slug ? genre_exists_sql([$mood_slug]) : ['', '', []];
    [$theme_sql, $theme_types, $theme_params] = $theme_slugs !== [] ? genre_exists_sql($theme_slugs) : ['', '', []];

    $strategies = [];

    if ($mood_slug && $theme_slugs !== []) {
        $strategies[] = ['label' => 'Mood + theme + runtime + era', 'where' => $mood_sql . $theme_sql . $len_full . $era_sql, 'types' => $mood_types . $theme_types, 'params' => array_merge($mood_params, $theme_params)];
        $strategies[] = ['label' => 'Mood + theme + era', 'where' => $mood_sql . $theme_sql . $era_sql, 'types' => $mood_types . $theme_types, 'params' => array_merge($mood_params, $theme_params)];
        $strategies[] = ['label' => 'Mood + theme', 'where' => $mood_sql . $theme_sql, 'types' => $mood_types . $theme_types, 'params' => array_merge($mood_params, $theme_params)];
    }
    if ($mood_slug) {
        $strategies[] = ['label' => 'Mood + runtime + era', 'where' => $mood_sql . $len_full . $era_sql, 'types' => $mood_types, 'params' => $mood_params];
        $strategies[] = ['label' => 'Mood + era', 'where' => $mood_sql . $era_sql, 'types' => $mood_types, 'params' => $mood_params];
        $strategies[] = ['label' => 'Mood only', 'where' => $mood_sql, 'types' => $mood_types, 'params' => $mood_params];
    }
    if ($theme_slugs !== []) {
        $strategies[] = ['label' => 'Theme + runtime + era', 'where' => $theme_sql . $len_full . $era_sql, 'types' => $theme_types, 'params' => $theme_params];
        $strategies[] = ['label' => 'Theme + era', 'where' => $theme_sql . $era_sql, 'types' => $theme_types, 'params' => $theme_params];
        $strategies[] = ['label' => 'Theme only', 'where' => $theme_sql, 'types' => $theme_types, 'params' => $theme_params];
    }

    $strategies[] = ['label' => 'Runtime + era (any genre)', 'where' => $len_full . $era_sql, 'types' => '', 'params' => []];
    $strategies[] = ['label' => 'Popular catalogue picks', 'where' => '', 'types' => '', 'params' => []];

    foreach ($strategies as $s) {
        $movies = run_movie_query($conn, $s['where'], $s['types'], $s['params']);
        if ($movies !== []) {
            return ['movies' => $movies, 'match_label' => $s['label']];
        }
    }

    return ['movies' => [], 'match_label' => 'No matches'];
}

if ($request['action'] === 'getMovies') {
    $mood = $request['mood'] ?? null;
    $length = $request['length'] ?? null;
    $era = $request['era'] ?? null;
    $theme = $request['setting'] ?? ($request['theme'] ?? null);

    $result = quiz_fetch_movies($conn, $mood, $length, $era, $theme);
    echo json_encode([
        'success' => $result['movies'] !== [],
        'movies' => $result['movies'],
        'match_label' => $result['match_label'],
        'count' => count($result['movies']),
    ]);
    $conn->close();
    exit;
}

if ($request['action'] === 'addToWatchlist') {
    $movie_id = isset($request['movie_id']) ? (int) $request['movie_id'] : 0;
    $user_id = $_SESSION['id'] ?? null;
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Sign in to save titles to your watchlist.']);
        $conn->close();
        exit;
    }
    if ($movie_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid film']);
        $conn->close();
        exit;
    }
    $stmt = $conn->prepare('INSERT IGNORE INTO WATCHLIST (user_id, movie_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $user_id, $movie_id);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => $ok]);
    exit;
}

$conn->close();
echo json_encode(['success' => false, 'message' => 'Unknown action']);
