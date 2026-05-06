<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'movies_db');
if ($conn->connect_error) die('DB error: ' . $conn->connect_error);

// ── AJAX: fix one movie ───────────────────────────────────────────────────
if (isset($_POST['fix_id'], $_POST['apikey'])) {
    $id     = intval($_POST['fix_id']);
    $apikey = trim($_POST['apikey']);
    $row    = $conn->query("SELECT ID,TITLE,RELEASE_YEAR FROM MOVIES WHERE ID=$id")->fetch_assoc();

    // Try multiple title variants in OMDB
    $variants = [
        $row['TITLE'],
        // common alternate titles
        str_replace('Indiana Jones and the ', '', $row['TITLE']),
        preg_replace('/^The\s+/i', '', $row['TITLE']),
    ];

    $posterUrl = null;
    foreach ($variants as $t) {
        $url  = 'http://www.omdbapi.com/?apikey=' . urlencode($apikey)
              . '&t=' . urlencode(trim($t))
              . ($row['RELEASE_YEAR'] ? '&y=' . intval($row['RELEASE_YEAR']) : '');
        $json = @json_decode(file_get_contents($url), true);
        if ($json && $json['Response'] === 'True' && !empty($json['Poster']) && $json['Poster'] !== 'N/A') {
            $posterUrl = $json['Poster'];
            break;
        }
        // also try without year
        if ($row['RELEASE_YEAR']) {
            $url2 = 'http://www.omdbapi.com/?apikey=' . urlencode($apikey) . '&t=' . urlencode(trim($t));
            $json2 = @json_decode(file_get_contents($url2), true);
            if ($json2 && $json2['Response'] === 'True' && !empty($json2['Poster']) && $json2['Poster'] !== 'N/A') {
                $posterUrl = $json2['Poster'];
                break;
            }
        }
    }

    if ($posterUrl) {
        // Store the URL directly — no download needed
        $stmt = $conn->prepare("UPDATE MOVIES SET POSTERURL=? WHERE ID=?");
        $stmt->bind_param('si', $posterUrl, $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['ok' => true, 'url' => $posterUrl, 'title' => $row['TITLE']]);
    } else {
        echo json_encode(['ok' => false, 'title' => $row['TITLE'], 'msg' => 'Not found in OMDB']);
    }
    $conn->close();
    exit;
}

// ── AJAX: manual URL override ─────────────────────────────────────────────
if (isset($_POST['manual_id'], $_POST['manual_url'])) {
    $id  = intval($_POST['manual_id']);
    $url = trim($_POST['manual_url']);
    if ($id && $url) {
        $stmt = $conn->prepare("UPDATE MOVIES SET POSTERURL=? WHERE ID=?");
        $stmt->bind_param('si', $url, $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['ok' => true, 'url' => $url]);
    } else {
        echo json_encode(['ok' => false]);
    }
    $conn->close();
    exit;
}

// ── AJAX: fix all broken ──────────────────────────────────────────────────
if (isset($_POST['fix_all'], $_POST['apikey'])) {
    $apikey = trim($_POST['apikey']);
    $movies = $conn->query("SELECT ID,TITLE,RELEASE_YEAR,POSTERURL FROM MOVIES ORDER BY TITLE")->fetch_all(MYSQLI_ASSOC);
    $out = [];
    foreach ($movies as $row) {
        // Quick check: try HEAD request
        $ch = curl_init($row['POSTERURL']);
        curl_setopt_array($ch, [CURLOPT_NOBODY=>true,CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_FOLLOWLOCATION=>true,CURLOPT_SSL_VERIFYPEER=>false,
            CURLOPT_TIMEOUT=>5,CURLOPT_USERAGENT=>'Mozilla/5.0']);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code === 200) { $out[] = ['id'=>$row['ID'],'ok'=>true,'msg'=>'already_ok']; continue; }

        $variants = [$row['TITLE'], str_replace('Indiana Jones and the ','',$row['TITLE']), preg_replace('/^The\s+/i','',$row['TITLE'])];
        $posterUrl = null;
        foreach ($variants as $t) {
            foreach ([$row['RELEASE_YEAR'], ''] as $yr) {
                $u = 'http://www.omdbapi.com/?apikey='.urlencode($apikey).'&t='.urlencode(trim($t)).($yr?'&y='.intval($yr):'');
                $j = @json_decode(file_get_contents($u),true);
                if ($j && $j['Response']==='True' && !empty($j['Poster']) && $j['Poster']!=='N/A') {
                    $posterUrl = $j['Poster']; break 2;
                }
            }
        }
        if ($posterUrl) {
            $stmt = $conn->prepare("UPDATE MOVIES SET POSTERURL=? WHERE ID=?");
            $stmt->bind_param('si',$posterUrl,$row['ID']); $stmt->execute(); $stmt->close();
            $out[] = ['id'=>$row['ID'],'ok'=>true,'url'=>$posterUrl,'title'=>$row['TITLE']];
        } else {
            $out[] = ['id'=>$row['ID'],'ok'=>false,'title'=>$row['TITLE'],'msg'=>'not_found'];
        }
    }
    $conn->close();
    header('Content-Type: application/json');
    echo json_encode($out);
    exit;
}

// ── Page: scan all movies ─────────────────────────────────────────────────
$movies = $conn->query("SELECT ID,TITLE,RELEASE_YEAR,POSTERURL FROM MOVIES ORDER BY TITLE")->fetch_all(MYSQLI_ASSOC);
$conn->close();

$broken = []; $working = [];
foreach ($movies as $m) {
    $ch = curl_init($m['POSTERURL']);
    curl_setopt_array($ch,[CURLOPT_NOBODY=>true,CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_FOLLOWLOCATION=>true,CURLOPT_SSL_VERIFYPEER=>false,
        CURLOPT_TIMEOUT=>5,CURLOPT_USERAGENT=>'Mozilla/5.0']);
    curl_exec($ch); $code = curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    if ($code===200) $working[]=$m; else $broken[]=$m;
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<title>Fix Posters — ReelFlix</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:sans-serif;background:#03060a;color:#fff;padding:30px}
h1{color:#f5c518;font-size:1.7rem;margin-bottom:8px}
.note{color:#6b7280;font-size:.82rem;margin-bottom:20px}
.note a{color:#f5c518}
.summary{display:flex;gap:14px;margin-bottom:20px;flex-wrap:wrap}
.badge{padding:8px 18px;border-radius:8px;font-weight:700}
.ok{background:rgba(34,197,94,.15);color:#4ade80;border:1px solid rgba(34,197,94,.3)}
.bad{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)}
.bar{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center}
input[type=text]{flex:1;min-width:220px;padding:10px 16px;border-radius:8px;
  border:1px solid rgba(255,255,255,.1);background:rgba(15,23,36,.8);color:#fff;font-size:.95rem}
input::placeholder{color:#4b5563}
.btn{padding:10px 20px;border-radius:8px;border:none;cursor:pointer;font-weight:700;font-size:.88rem;transition:.2s}
.btn:hover{opacity:.85}
.gold{background:#f5c518;color:#000}
.red{background:#e50914;color:#fff}
.sm{padding:6px 12px;font-size:.78rem}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:12px;margin-bottom:40px}
.card{background:rgba(17,24,39,.8);border-radius:10px;overflow:hidden;border:1px solid rgba(255,255,255,.07)}
.card img{width:100%;height:210px;object-fit:cover;display:block;background:#111}
.info{padding:8px 10px 10px}
.title{font-size:.78rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:6px}
.status{font-size:.7rem;padding:2px 7px;border-radius:4px;display:inline-block;margin-bottom:6px}
.s-ok{background:rgba(34,197,94,.15);color:#4ade80}
.s-bad{background:rgba(239,68,68,.15);color:#f87171}
.s-fixed{background:rgba(245,197,24,.15);color:#f5c518}
.fix-btn{width:100%;margin-bottom:4px}
.manual-row{display:flex;gap:4px;margin-top:4px}
.manual-row input{flex:1;padding:5px 8px;border-radius:6px;border:1px solid rgba(255,255,255,.1);
  background:rgba(0,0,0,.4);color:#fff;font-size:.72rem}
.log{background:#000;border-radius:8px;padding:12px;font-family:monospace;font-size:.78rem;
  max-height:220px;overflow-y:auto;margin-bottom:24px;display:none}
.log .g{color:#4ade80}.log .r{color:#f87171}.log .m{color:#8b9bb4}
h2{color:#8b9bb4;font-size:1rem;font-weight:500;margin-bottom:12px}
</style></head><body>

<h1>Poster Fixer</h1>
<p class="note">Free OMDB key: <a href="http://www.omdbapi.com/apikey.aspx" target="_blank">omdbapi.com/apikey.aspx</a> — check your email and click the activation link, then paste the key below.</p>

<div class="summary">
  <div class="badge ok">✓ <?=count($working)?> working</div>
  <div class="badge bad">✗ <?=count($broken)?> broken</div>
</div>

<div class="bar">
  <input type="text" id="apikey" placeholder="Paste OMDB API key here…">
  <button class="btn red" onclick="fixAll()">Fix All Broken (<?=count($broken)?>)</button>
  <a href="dashboard.php" style="color:#f5c518;font-size:.9rem">← Dashboard</a>
</div>

<div class="log" id="log"></div>

<?php if($broken): ?>
<h2>Broken (<?=count($broken)?>)</h2>
<div class="grid">
<?php foreach($broken as $m): ?>
<div class="card" id="card-<?=$m['ID']?>">
  <img id="img-<?=$m['ID']?>" src="" alt="">
  <div class="info">
    <div class="title" title="<?=htmlspecialchars($m['TITLE'])?>"><?=htmlspecialchars($m['TITLE'])?></div>
    <span class="status s-bad" id="st-<?=$m['ID']?>">Broken</span><br>
    <button class="btn gold sm fix-btn" onclick="fixOne(<?=$m['ID']?>,this)">Auto Fix</button>
    <div class="manual-row">
      <input type="text" id="mu-<?=$m['ID']?>" placeholder="Paste image URL…">
      <button class="btn sm" style="background:#334155;color:#fff;white-space:nowrap" onclick="manualFix(<?=$m['ID']?>)">Set</button>
    </div>
  </div>
</div>
<?php endforeach ?>
</div>
<?php endif ?>

<?php if($working): ?>
<h2>Working (<?=count($working)?>)</h2>
<div class="grid">
<?php foreach($working as $m): ?>
<div class="card">
  <img src="<?=htmlspecialchars($m['POSTERURL'])?>" loading="lazy" onerror="this.style.opacity=.2">
  <div class="info">
    <div class="title"><?=htmlspecialchars($m['TITLE'])?></div>
    <span class="status s-ok">OK</span>
  </div>
</div>
<?php endforeach ?>
</div>
<?php endif ?>

<script>
function key(){const k=document.getElementById('apikey').value.trim();if(!k){alert('Paste your OMDB API key first.');return null;}return k;}
function log(msg,cls='m'){const el=document.getElementById('log');el.style.display='block';const d=document.createElement('div');d.className=cls;d.textContent=msg;el.appendChild(d);el.scrollTop=el.scrollHeight;}

function applyFix(id,url,title){
  document.getElementById('img-'+id).src=url+'?v='+Date.now();
  document.getElementById('st-'+id).className='status s-fixed';
  document.getElementById('st-'+id).textContent='Fixed';
  const btn=document.querySelector('#card-'+id+' .fix-btn');
  if(btn){btn.textContent='✓';btn.style.background='#4ade80';}
  log('✓ '+title,'g');
}

async function fixOne(id,btn){
  const k=key();if(!k)return;
  btn.disabled=true;btn.textContent='Searching…';
  const fd=new FormData();fd.append('fix_id',id);fd.append('apikey',k);
  const r=await fetch('fix_posters.php',{method:'POST',body:fd});
  const j=await r.json();
  if(j.ok){applyFix(id,j.url,j.title);}
  else{btn.disabled=false;btn.textContent='Not Found — Paste URL below';log('✗ '+j.title+' — use manual URL','r');}
}

async function manualFix(id){
  const url=document.getElementById('mu-'+id).value.trim();
  if(!url){alert('Paste an image URL first.');return;}
  const fd=new FormData();fd.append('manual_id',id);fd.append('manual_url',url);
  const r=await fetch('fix_posters.php',{method:'POST',body:fd});
  const j=await r.json();
  if(j.ok) applyFix(id,url,'ID '+id);
  else log('Failed to save manual URL','r');
}

async function fixAll(){
  const k=key();if(!k)return;
  document.getElementById('log').style.display='block';
  document.getElementById('log').innerHTML='';
  log('Searching OMDB for all broken posters…');
  const fd=new FormData();fd.append('fix_all',1);fd.append('apikey',k);
  const r=await fetch('fix_posters.php',{method:'POST',body:fd});
  const results=await r.json();
  results.forEach(j=>{
    if(j.msg==='already_ok')return;
    if(j.ok&&j.url){applyFix(j.id,j.url,j.title);}
    else if(j.ok===false){log('✗ '+j.title+' — not in OMDB. Paste URL manually below.','r');}
  });
  log('Done. Manually paste URLs for any still marked red.','m');
}
</script>
</body></html>
