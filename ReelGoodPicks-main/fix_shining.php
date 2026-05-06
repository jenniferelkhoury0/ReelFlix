<?php
$conn = new mysqli('localhost', 'root', '', 'movies_db');
if ($conn->connect_error) die('DB error: ' . $conn->connect_error);

$saveDir  = __DIR__ . '/images/posters/';
$saveName = 'the_shining.jpg';
$savePath = $saveDir . $saveName;
$localUrl = 'images/posters/' . $saveName;

if (!is_dir($saveDir)) mkdir($saveDir, 0755, true);

$sources = [
    'https://m.media-amazon.com/images/M/MV5BZWFlYmY2MGItZjVkYi00YzU4LTg0YjQtYzY1ZGE3NTA5NGQxXkEyXkFqcGdeQXVyMTQxNzMzNDI@._V1_SX500.jpg',
    'https://upload.wikimedia.org/wikipedia/en/b/b6/The_Shining_poster.jpg',
    'https://image.tmdb.org/t/p/w500/nRj5511mZdTl4saWqf4d3QB9JUr.jpg',
];

$downloaded = false;
foreach ($sources as $src) {
    $ch = curl_init($src);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_REFERER        => 'https://www.google.com/',
        CURLOPT_HTTPHEADER     => ['Accept: image/webp,image/apng,image/*,*/*'],
    ]);
    $data = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($data && $code === 200 && strlen($data) > 5000) {
        file_put_contents($savePath, $data);
        $downloaded = true;
        break;
    }
}

if ($downloaded) {
    // Update DB
    $res = $conn->query("SELECT ID FROM MOVIES WHERE TITLE LIKE '%Shining%'");
    foreach ($res->fetch_all(MYSQLI_ASSOC) as $m) {
        $stmt = $conn->prepare("UPDATE MOVIES SET POSTERURL = ? WHERE ID = ?");
        $stmt->bind_param('si', $localUrl, $m['ID']);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        body{font-family:sans-serif;background:#03060a;color:#fff;padding:40px;text-align:center}
        img{height:340px;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.8);margin:20px 0}
        a{color:#f5c518}
    </style></head><body>';
    echo '<h2 style="color:#f5c518">Fixed!</h2>';
    echo '<img src="' . $localUrl . '?v=' . time() . '">';
    echo '<p><a href="dashboard.php">Back to Dashboard</a> &nbsp;·&nbsp; <a href="genre.php?genre=Horror">View Horror shelf</a></p>';
    echo '<p style="color:#555;font-size:.8em;margin-top:30px">Delete fix_shining.php when done.</p>';
    echo '</body></html>';
} else {
    $conn->close();
    // If curl failed, give manual upload option
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        body{font-family:sans-serif;background:#03060a;color:#fff;padding:40px}
        code{color:#f5c518;word-break:break-all}
        .btn{display:inline-block;background:#f5c518;color:#000;padding:12px 24px;border-radius:8px;
             font-weight:700;text-decoration:none;margin-top:20px;border:none;cursor:pointer;font-size:1rem}
        input[type=file]{color:#fff;margin:16px 0;display:block}
    </style></head><body>';
    echo '<h2>Automatic download failed — upload manually</h2>';
    echo '<p>Save any poster image of The Shining as a file, then upload it below:</p>';
    echo '<form method="POST" enctype="multipart/form-data">
        <input type="file" name="poster" accept="image/*">
        <button type="submit" class="btn">Upload & Fix</button>
    </form>';

    if (!empty($_FILES['poster']['tmp_name'])) {
        if (move_uploaded_file($_FILES['poster']['tmp_name'], $savePath)) {
            $conn2 = new mysqli('localhost', 'root', '', 'movies_db');
            $res2 = $conn2->query("SELECT ID FROM MOVIES WHERE TITLE LIKE '%Shining%'");
            foreach ($res2->fetch_all(MYSQLI_ASSOC) as $m) {
                $stmt = $conn2->prepare("UPDATE MOVIES SET POSTERURL = ? WHERE ID = ?");
                $stmt->bind_param('si', $localUrl, $m['ID']);
                $stmt->execute();
                $stmt->close();
            }
            $conn2->close();
            echo '<p style="color:lime">Uploaded and updated! <a href="dashboard.php" style="color:#f5c518">Go to Dashboard</a></p>';
            echo '<img src="' . $localUrl . '?v=' . time() . '" style="height:300px;border-radius:10px;margin-top:16px;display:block">';
        } else {
            echo '<p style="color:red">Upload failed. Check folder permissions on images/posters/</p>';
        }
    }
    echo '</body></html>';
}
?>
