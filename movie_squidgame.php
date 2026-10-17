<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
$username  = $_SESSION['username'];
$conn      = new mysqli("127.0.0.1","root","","screenhub_db",3306);
$stmt      = $conn->prepare("SELECT avatar,first_name,last_name FROM users WHERE username=?");
$stmt->bind_param("s",$username);
$stmt->execute();
$dbUser    = $stmt->get_result()->fetch_assoc();
$stmt->close(); $conn->close();
$avatar    = $dbUser['avatar']     ?? '';
$firstName = $dbUser['first_name'] ?? '';
$lastName  = $dbUser['last_name']  ?? '';
$initials  = strtoupper(substr($firstName,0,1).substr($lastName,0,1)) ?: '?';
$fullName  = trim($firstName.' '.$lastName) ?: $username;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Squid Game | ScreenHub</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
  --red:#e50914;--gold:#f5c518;--pink:#d4006a;
  --bg:#080808;--surface:#111;--s2:#1a1a1a;
  --border:rgba(255,255,255,0.07);
  --text:#f0f0f0;--muted:#666;
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:'Barlow',sans-serif;background:var(--bg);color:var(--text);overflow-x:hidden}
body::after{content:'';position:fixed;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");pointer-events:none;z-index:9999;opacity:.35}

/* HEADER */
header{position:fixed;top:0;left:0;right:0;z-index:1000;height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 40px;background:rgba(8,8,8,.97);backdrop-filter:blur(16px);border-bottom:1px solid var(--border)}
.logo{font-family:'Bebas Neue';font-size:30px;letter-spacing:4px;color:var(--red);text-decoration:none}
nav{display:flex;gap:28px}
nav a{font-family:'Barlow Condensed';font-size:14px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.6);text-decoration:none;transition:color .2s;position:relative}
nav a::after{content:'';position:absolute;bottom:-4px;left:0;width:0;height:2px;background:var(--red);transition:width .3s}
nav a:hover{color:#fff}
nav a:hover::after,nav a.active::after{width:100%}
nav a.active{color:#fff}
.header-right{display:flex;align-items:center;gap:20px}
.av-btn{position:relative;cursor:pointer}
.av-circle{width:36px;height:36px;border-radius:50%;overflow:hidden;border:2px solid transparent;background:linear-gradient(var(--bg),var(--bg)) padding-box,linear-gradient(135deg,var(--red),var(--gold)) border-box;display:flex;align-items:center;justify-content:center;transition:transform .2s}
.av-circle:hover{transform:scale(1.08)}
.av-circle img{width:100%;height:100%;object-fit:cover;border-radius:50%;display:block}
.av-init{font-family:'Bebas Neue';font-size:14px;letter-spacing:1px;color:var(--red)}
.av-drop{position:absolute;top:calc(100% + 12px);right:0;width:220px;background:rgba(12,12,12,.98);border:1px solid var(--border);border-radius:12px;padding:8px 0;opacity:0;pointer-events:none;transform:translateY(-8px);transition:all .2s;backdrop-filter:blur(20px)}
.av-btn:hover .av-drop{opacity:1;pointer-events:all;transform:translateY(0)}
.dh{padding:12px 18px 10px;border-bottom:1px solid var(--border);margin-bottom:4px}
.dh-name{font-weight:600;font-size:14px;color:#fff}
.dh-user{font-size:12px;color:var(--muted);margin-top:2px}
.av-drop a{display:flex;align-items:center;gap:10px;padding:10px 18px;color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:all .15s}
.av-drop a i{width:16px;font-size:12px;color:var(--muted)}
.av-drop a:hover{background:rgba(255,255,255,.05);color:#fff}
.av-drop a:hover i{color:var(--red)}
.av-drop hr{border:none;border-top:1px solid var(--border);margin:4px 0}
.signout{color:#ff6b6b!important}
.signout i{color:#ff6b6b!important}

/* HERO */
.hero{position:relative;height:100vh;min-height:640px;overflow:hidden}
.hero-bg{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center 22%;filter:brightness(.38)}
.hero-grad{position:absolute;inset:0;background:linear-gradient(to right,rgba(8,8,8,.98) 0%,rgba(8,8,8,.72) 42%,rgba(8,8,8,.08) 100%),linear-gradient(to top,rgba(8,8,8,1) 0%,transparent 38%)}
.hero-left{position:absolute;bottom:90px;left:60px;max-width:555px;z-index:2}
.badge{display:inline-flex;align-items:center;gap:7px;background:var(--pink);color:#fff;font-family:'Barlow Condensed';font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;padding:4px 12px;border-radius:3px;margin-bottom:16px}
.show-title{font-family:'Bebas Neue';font-size:96px;line-height:.88;letter-spacing:4px;margin-bottom:6px;text-shadow:0 4px 28px rgba(0,0,0,.65)}
.show-title .acc{color:var(--pink)}
.show-sub{font-family:'Barlow Condensed';font-size:17px;font-weight:600;letter-spacing:4px;text-transform:uppercase;color:rgba(255,255,255,.42);margin-bottom:18px}
.meta{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:18px}
.imdb-p{background:var(--gold);color:#000;font-weight:700;font-size:12px;padding:3px 9px;border-radius:4px;font-family:'Barlow Condensed'}
.mtag{font-size:13px;color:rgba(255,255,255,.5);font-family:'Barlow Condensed'}
.mdot{color:var(--muted)}
.mbox{border:1px solid rgba(255,255,255,.25);padding:1px 7px;border-radius:3px;font-size:11px;font-weight:600;font-family:'Barlow Condensed';color:rgba(255,255,255,.42)}
.desc{font-size:15px;line-height:1.72;color:rgba(255,255,255,.67);margin-bottom:28px;max-width:480px}
.btns{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.bp{display:flex;align-items:center;gap:9px;padding:13px 30px;background:var(--red);color:#fff;border:none;border-radius:8px;font-family:'Barlow Condensed';font-size:16px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;transition:all .2s}
.bp:hover{background:#ff1a1a;transform:translateY(-2px)}
.bg{display:flex;align-items:center;gap:9px;padding:13px 24px;background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:8px;font-family:'Barlow Condensed';font-size:16px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;transition:all .2s;backdrop-filter:blur(8px)}
.bg:hover{background:rgba(255,255,255,.18);transform:translateY(-2px)}
.bic{width:46px;height:46px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.08);color:rgba(255,255,255,.65);border:1px solid rgba(255,255,255,.15);border-radius:50%;font-size:15px;cursor:pointer;transition:all .2s}
.bic:hover{background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.28)}
.hero-poster{position:absolute;right:80px;bottom:70px;z-index:3;width:210px}
.hero-poster img{width:100%;border-radius:10px;box-shadow:0 22px 55px rgba(0,0,0,.75),0 0 0 1px rgba(255,255,255,.06)}

/* CONTENT */
.content{padding:0 60px 100px}

/* TABS */
.tabs{display:flex;border-bottom:1px solid var(--border);margin-bottom:40px}
.tab{font-family:'Barlow Condensed';font-size:14px;font-weight:700;letter-spacing:2px;text-transform:uppercase;padding:18px 28px;background:none;border:none;color:rgba(255,255,255,.38);cursor:pointer;transition:color .2s;position:relative}
.tab::after{content:'';position:absolute;bottom:-1px;left:0;width:0;height:2px;background:var(--red);transition:width .3s}
.tab:hover{color:rgba(255,255,255,.75)}
.tab.on{color:#fff}
.tab.on::after{width:100%}
.pane{display:none}
.pane.on{display:block}

/* EPISODES */
.srow{display:flex;gap:10px;margin-bottom:28px}
.sbtn{padding:8px 22px;border-radius:6px;border:1px solid var(--border);background:transparent;color:rgba(255,255,255,.45);font-family:'Barlow Condensed';font-size:14px;font-weight:600;letter-spacing:1px;cursor:pointer;transition:all .2s}
.sbtn:hover,.sbtn.on{background:var(--red);border-color:var(--red);color:#fff}
.ep-list{display:flex;flex-direction:column;gap:4px}
.ep{display:flex;gap:20px;align-items:center;padding:14px 18px;border-radius:10px;cursor:pointer;transition:background .2s;border:1px solid transparent}
.ep:hover{background:var(--s2);border-color:var(--border)}
.ep-thumb{flex-shrink:0;width:158px;height:89px;border-radius:6px;overflow:hidden;position:relative;background:var(--s2)}
.ep-thumb img{width:100%;height:100%;object-fit:cover;display:block}
.ep-ov{position:absolute;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .2s}
.ep:hover .ep-ov{opacity:1}
.ep-ov i{font-size:22px;color:#fff}
.ep-bar{position:absolute;bottom:0;left:0;right:0;height:3px;background:rgba(255,255,255,.14)}
.ep-fill{height:100%;background:var(--red);border-radius:2px}
.ep-body{flex:1;min-width:0}
.ep-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:5px}
.ep-num{font-family:'Barlow Condensed';font-size:12px;font-weight:700;letter-spacing:1.2px;color:var(--muted);text-transform:uppercase}
.ep-dur{font-size:12px;color:var(--muted);font-family:'Barlow Condensed'}
.ep-title{font-family:'Barlow Condensed';font-size:17px;font-weight:700;color:#fff;margin-bottom:5px;letter-spacing:.4px}
.ep-desc{font-size:13px;color:rgba(255,255,255,.46);line-height:1.55;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}

/* DETAILS */
.dg{display:grid;grid-template-columns:2fr 1fr;gap:52px}
.db{margin-bottom:30px}
.dl{font-family:'Barlow Condensed';font-size:10px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--muted);margin-bottom:9px}
.dv{font-size:14px;line-height:1.65;color:rgba(255,255,255,.78)}

/* CAST */
.cast-row{display:flex;flex-wrap:wrap;gap:22px;margin-top:4px}
.cast-card{width:105px;text-align:center}
.cast-img{width:86px;height:86px;border-radius:50%;overflow:hidden;margin:0 auto 9px;border:2px solid var(--border);background:var(--s2)}
.cast-img img{width:100%;height:100%;object-fit:cover;object-position:center top;display:block;transition:transform .3s}
.cast-card:hover .cast-img img{transform:scale(1.08)}
.cast-name{font-size:13px;font-weight:600;color:#fff;line-height:1.3}
.cast-char{font-size:11px;color:var(--muted);margin-top:3px}

/* GENRE TAGS */
.gtags{display:flex;flex-wrap:wrap;gap:8px}
.gtag{padding:5px 14px;border-radius:20px;border:1px solid var(--border);font-family:'Barlow Condensed';font-size:12px;font-weight:600;letter-spacing:1px;color:rgba(255,255,255,.55)}

/* SIMILAR */
.sim-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(148px,1fr));gap:14px}
.sim-card{border-radius:8px;overflow:hidden;background:var(--s2);cursor:pointer;transition:transform .25s,box-shadow .25s;position:relative}
.sim-card img{width:100%;height:220px;object-fit:cover;display:block}
.sim-card:hover{transform:scale(1.05) translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,.6)}
.sim-card::after{content:'▶';position:absolute;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;font-size:28px;color:#fff;opacity:0;transition:opacity .2s;border-radius:8px}
.sim-card:hover::after{opacity:1}
.sim-label{padding:9px 10px 10px;font-family:'Barlow Condensed';font-size:13px;font-weight:600;letter-spacing:.3px;color:rgba(255,255,255,.68);line-height:1.3}

/* MODAL */
.modal{position:fixed;inset:0;z-index:8000;background:rgba(0,0,0,.92);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .3s}
.modal.open{opacity:1;pointer-events:all}
.mbox{position:relative;width:min(900px,93vw);background:var(--surface);border-radius:14px;overflow:hidden;box-shadow:0 30px 80px rgba(0,0,0,.8);transform:scale(.94);transition:transform .3s}
.modal.open .mbox{transform:scale(1)}
.mcls{position:absolute;top:14px;right:16px;z-index:10;background:rgba(0,0,0,.55);border:none;color:#fff;width:36px;height:36px;border-radius:50%;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s}
.mcls:hover{background:var(--red)}
.mvid{position:relative;padding-top:56.25%;background:#000}
.mvid iframe{position:absolute;inset:0;width:100%;height:100%;border:none}
.minfo{padding:18px 24px 22px}
.mtitle{font-family:'Bebas Neue';font-size:22px;letter-spacing:2px;margin-bottom:4px}
.msub{font-size:13px;color:var(--muted)}
</style>
</head>
<body>

<!-- HEADER -->
<header>
  <a href="dashboard.php" class="logo">SCREENHUB</a>
  <nav>
    <a href="dashboard.php">Home</a>
    <a href="#">Movies</a>
    <a href="#" class="active">TV Series</a>
    <a href="#">Discover</a>
  </nav>
  <div class="header-right">
    <div class="av-btn">
      <div class="av-circle">
        <?php if(!empty($avatar)):?>
          <img src="<?=htmlspecialchars($avatar)?>" alt="avatar">
        <?php else:?>
          <span class="av-init"><?=htmlspecialchars($initials)?></span>
        <?php endif;?>
      </div>
      <div class="av-drop">
        <div class="dh">
          <div class="dh-name"><?=htmlspecialchars($fullName)?></div>
          <div class="dh-user">@<?=htmlspecialchars($username)?></div>
        </div>
        <a href="Profile.php"><i class="fa fa-user"></i> Edit Profile</a>
        <a href="#"><i class="fa fa-gear"></i> Settings</a>
        <a href="#"><i class="fa fa-circle-question"></i> Help</a>
        <hr>
        <a href="logout.php" class="signout"><i class="fa fa-right-from-bracket"></i> Sign Out</a>
      </div>
    </div>
  </div>
</header>

<!-- HERO -->
<div class="hero">
  <img class="hero-bg"
       src="https://static.wikia.nocookie.net/thesquidgame/images/7/7d/GdWDj4ZaoAEgw26.jpg/revision/latest?cb=20241214072040"
       alt="Squid Game">
  <div class="hero-grad"></div>

  <div class="hero-left">
    <div class="badge"><i class="fa fa-star"></i>&nbsp; #1 on ScreenHub</div>
    <h1 class="show-title">SQUID<br><span class="acc">GAME</span></h1>
    <div class="show-sub">Season 1 – 2 &nbsp;·&nbsp; 2021 – 2024</div>
    <div class="meta">
      <span class="imdb-p">IMDb 8.0</span>
      <span class="mdot">·</span>
      <span class="mtag mbox">TV-MA</span>
      <span class="mdot">·</span>
      <span class="mtag">Thriller &middot; Drama &middot; Mystery</span>
      <span class="mdot">·</span>
      <span class="mtag">2 Seasons</span>
    </div>
    <p class="desc">456 cash-strapped players accept a mysterious invitation to compete in deadly children's games for a ₩45.6 billion prize. Then Gi-hun returns — not to win, but to end it all.</p>
    <div class="btns">
      <button class="bp" onclick="openTrailer()"><i class="fa fa-play"></i> Play Trailer</button>
      <button class="bg" onclick="playEp(1,1)"><i class="fa fa-list"></i> S1 Episode 1</button>
      <button class="bic" id="wlBtn" title="Add to Watchlist" onclick="toggleWL(this)"><i class="fa fa-plus" id="wlIcon"></i></button>
      <button class="bic" title="Like"><i class="fa fa-thumbs-up"></i></button>
    </div>
  </div>

  <div class="hero-poster">
    <img src="https://m.media-amazon.com/images/M/MV5BNjU4ZTllMGQtNzNjNS00NzhmLWI2NmItMTExNGFjMGUzOWFiXkEyXkFqcGc@._V1_FMjpg_UX800_.jpg" alt="Squid Game Poster">
  </div>
</div>

<!-- CONTENT -->
<div class="content">

  <div class="tabs">
    <button class="tab on" onclick="goTab('episodes',this)">Episodes</button>
    <button class="tab"    onclick="goTab('details',this)">Details</button>
    <button class="tab"    onclick="goTab('similar',this)">More Like This</button>
  </div>

  <!-- EPISODES -->
  <div class="pane on" id="pane-episodes">
    <div class="srow">
      <button class="sbtn on" onclick="goSeason(1,this)">Season 1</button>
      <button class="sbtn"    onclick="goSeason(2,this)">Season 2</button>
    </div>

    <?php
    $T = "https://static.wikia.nocookie.net/thesquidgame/images/7/7d/GdWDj4ZaoAEgw26.jpg/revision/latest?cb=20241214072040";
    $s1 = [
      [1,"Red Light, Green Light",   "456 debt-ridden contestants enter a deadly arena where a familiar childhood game carries fatal consequences.","49m",10,$T],
      [2,"Hell",                     "Survivors size each other up and form uneasy alliances as the horror of their situation fully sets in.",       "32m",0,$T],
      [3,"The Man with the Umbrella","Players tackle a dexterity game involving a honeycomb shape — and the shape they pick may decide their fate.", "37m",0,$T],
      [4,"Stick to the Team",        "A brutal tug-of-war forces groups to rely on one another. Trust and strategy become life-or-death tools.",    "55m",0,$T],
      [5,"A Fair World",             "A VIP arrives to observe. Players are paired for a marble game where only one partner survives.",               "52m",0,$T],
      [6,"Gganbu",                   "Players are paired for an intimate game that ruthlessly tests loyalty between the closest of friends.",         "63m",0,$T],
      [7,"VIPS",                     "Masked VIPs settle in as spectators, and the remaining players must cross a towering glass bridge.",           "42m",0,$T],
      [8,"Front Man",                "Alliances collapse under pressure as the final players are forced into their most desperate confrontation.",   "35m",0,$T],
      [9,"One Lucky Day",            "The final survivor confronts the game's creator and learns the full, horrifying truth behind it all.",         "57m",0,$T],
    ];
    $s2 = [
      [1,"bread and circus",  "After three years tracking the games, Gi-hun finally gets a lead — and chooses to re-enter the competition.",      "55m",0,$T],
      [2,"Halloween Party",   "As players settle in for the first night, an old face from Gi-hun's past completely upends his plans.",             "46m",0,$T],
      [3,"001",               "A shocking alliance takes shape as players begin to grasp the true, terrifying scope of the games.",                "51m",0,$T],
      [4,"Six Eyes",          "A deadly new game kicks off. Betrayals surface from every corner of the arena.",                                    "49m",0,$T],
      [5,"One More Game",     "Tempers explode as players vote on whether to continue. Not everyone wants out — and not everyone can.",            "48m",0,$T],
      [6,"Place Your Bets",   "The games grow savage and unpredictable, pushing alliances and personal morality to the absolute limit.",           "55m",0,$T],
      [7,"Friend or Foe",     "With the finale in sight, Gi-hun makes a decision that will determine the fate of every remaining player.",         "60m",0,$T],
    ];

    function eps($list, $sid, $hide=false){
      echo '<div class="ep-list" id="s'.$sid.'"'.($hide?' style="display:none"':'').'>';
      foreach($list as $e){
        echo '<div class="ep" onclick="playEp('.$e[0].','.$sid.')">';
        echo '<div class="ep-thumb">';
        echo '<img src="'.htmlspecialchars($e[5]).'" alt="ep '.$e[0].'">';
        echo '<div class="ep-ov"><i class="fa fa-play"></i></div>';
        if($e[4]>0) echo '<div class="ep-bar"><div class="ep-fill" style="width:'.$e[4].'%"></div></div>';
        echo '</div>';
        echo '<div class="ep-body">';
        echo '<div class="ep-top"><span class="ep-num">Episode '.$e[0].'</span><span class="ep-dur">'.$e[3].'</span></div>';
        echo '<div class="ep-title">'.htmlspecialchars($e[1]).'</div>';
        echo '<div class="ep-desc">'.htmlspecialchars($e[2]).'</div>';
        echo '</div></div>';
      }
      echo '</div>';
    }
    eps($s1,1);
    eps($s2,2,true);
    ?>
  </div>

  <!-- DETAILS -->
  <div class="pane" id="pane-details">
    <div class="dg">
      <div>
        <div class="db">
          <div class="dl">Synopsis</div>
          <div class="dv">Hundreds of cash-strapped players accept a mysterious invitation to compete in deadly children's games for a ₩45.6 billion prize. Season 1 follows Seong Gi-hun, a gambling addict in desperate debt. After winning, he can't move on — and in Season 2, he re-enters the arena not to win, but to dismantle the operation from within. With new allies, old enemies, and even higher stakes, the games grow more brutal than ever imagined.</div>
        </div>

        <div class="db">
          <div class="dl">Main Cast</div>
          <div class="cast-row">
            <?php
            $cast = [
              ["Lee Jung-jae",  "Gi-hun · No.456",
               "https://upload.wikimedia.org/wikipedia/commons/thumb/2/25/Lee_Jung-jae_SDCC_2022_%28cropped%29.jpg/440px-Lee_Jung-jae_SDCC_2022_%28cropped%29.jpg"],
              ["Lee Byung-hun", "Front Man",
               "https://upload.wikimedia.org/wikipedia/commons/thumb/5/5a/Lee_Byung-hun_TIFF_2013.jpg/440px-Lee_Byung-hun_TIFF_2013.jpg"],
              ["Wi Ha-joon",    "Jun-ho",
               "https://upload.wikimedia.org/wikipedia/commons/thumb/a/a4/Wi_Ha-jun_in_November_2021_%281%29.jpg/440px-Wi_Ha-jun_in_November_2021_%281%29.jpg"],
              ["Park Hae-soo",  "Sang-woo · 218",
               "https://upload.wikimedia.org/wikipedia/commons/thumb/f/f2/Park_Hae-soo_in_2021_%28cropped%29.jpg/440px-Park_Hae-soo_in_2021_%28cropped%29.jpg"],
              ["Jung Ho-yeon",  "Sae-byeok · 067",
               "https://upload.wikimedia.org/wikipedia/commons/thumb/1/16/Jung_Ho-yeon_at_2022_SAG_Awards_%28cropped%29.jpg/440px-Jung_Ho-yeon_at_2022_SAG_Awards_%28cropped%29.jpg"],
              ["Im Si-wan",     "Myung-gi · 333",
               "https://upload.wikimedia.org/wikipedia/commons/thumb/c/c5/Im_Si-wan_at_the_2015_Asia_Song_Festival_%282%29.jpg/440px-Im_Si-wan_at_the_2015_Asia_Song_Festival_%282%29.jpg"],
              ["Kang Ha-neul",  "Dae-ho · 388",
               "https://upload.wikimedia.org/wikipedia/commons/thumb/c/c8/Kang_Ha-neul_in_June_2019.jpg/440px-Kang_Ha-neul_in_June_2019.jpg"],
              ["Gong Yoo",      "The Recruiter",
               "https://upload.wikimedia.org/wikipedia/commons/thumb/9/9e/Gong_Yoo_at_the_Toronto_International_Film_Festival_2016.jpg/440px-Gong_Yoo_at_the_Toronto_International_Film_Festival_2016.jpg"],
            ];
            foreach($cast as $c):?>
            <div class="cast-card">
              <div class="cast-img">
                <img src="<?=htmlspecialchars($c[2])?>" alt="<?=htmlspecialchars($c[0])?>"
                     onerror="this.style.display='none'">
              </div>
              <div class="cast-name"><?=htmlspecialchars($c[0])?></div>
              <div class="cast-char"><?=htmlspecialchars($c[1])?></div>
            </div>
            <?php endforeach;?>
          </div>
        </div>

        <div class="db">
          <div class="dl">Genres</div>
          <div class="gtags">
            <span class="gtag">Thriller</span>
            <span class="gtag">Drama</span>
            <span class="gtag">Mystery</span>
            <span class="gtag">Survival</span>
            <span class="gtag">Korean</span>
          </div>
        </div>
      </div>

      <div>
        <div class="db"><div class="dl">Creator</div><div class="dv">Hwang Dong-hyuk</div></div>
        <div class="db"><div class="dl">Network</div><div class="dv">Netflix</div></div>
        <div class="db"><div class="dl">Seasons</div><div class="dv">2 (Season 3 announced)</div></div>
        <div class="db"><div class="dl">Language</div><div class="dv">Korean — subtitles available</div></div>
        <div class="db"><div class="dl">Rating</div><div class="dv">TV-MA · 18+</div></div>
        <div class="db"><div class="dl">IMDb Score</div><div class="dv">8.0 / 10 · 620K votes</div></div>
        <div class="db"><div class="dl">Awards</div><div class="dv">6× Emmy · SAG Award · Critics Choice · Golden Globe</div></div>
      </div>
    </div>
  </div>

  <!-- MORE LIKE THIS -->
  <div class="pane" id="pane-similar">
    <div class="sim-grid">
      <?php
      $sim = [
        ["Alice in Borderland",  "https://upload.wikimedia.org/wikipedia/en/thumb/0/05/Alice_in_Borderland_TV_series.png/250px-Alice_in_Borderland_TV_series.png"],
        ["Dark (Netflix)",       "https://m.media-amazon.com/images/M/MV5BMDE1NjNmZjgtZTg0OC00NjkxLWEzYzItMDNkMTc3YjgxZWQyXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg"],
        ["Money Heist",          "https://m.media-amazon.com/images/I/61boFr6SYZL.jpg"],
        ["Sweet Home",           "https://m.media-amazon.com/images/M/MV5BOTFmNjQwZWMtOTVhOC00YjkxLTkwNDUtMGJkNGZlNmEzNzQxXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg"],
        ["The Platform",         "https://m.media-amazon.com/images/M/MV5BYTkzODMwMjktMWFjZC00OWNlLWEzYmEtNWY2YzNlMDZiNzU1XkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg"],
        ["Stranger Things",      "https://m.media-amazon.com/images/M/MV5BMjg2NmM0NjEtYzBiZi00NWI4LTk4N2UtYjE1MTkxMDE1NjRhXkEyXkFqcGc@._V1_FMjpg_UX800_.jpg"],
      ];
      foreach($sim as $s):?>
      <div class="sim-card">
        <img src="<?=htmlspecialchars($s[1])?>" alt="<?=htmlspecialchars($s[0])?>">
        <div class="sim-label"><?=htmlspecialchars($s[0])?></div>
      </div>
      <?php endforeach;?>
    </div>
  </div>

</div><!-- /content -->

<!-- MODAL -->
<div class="modal" id="modal">
  <div class="mbox">
    <button class="mcls" onclick="closeModal()"><i class="fa fa-xmark"></i></button>
    <div class="mvid">
      <iframe id="mIframe" src="" allowfullscreen allow="autoplay; encrypted-media"></iframe>
    </div>
    <div class="minfo">
      <div class="mtitle" id="mTitle">Squid Game</div>
      <div class="msub"   id="mSub">Now Playing</div>
    </div>
  </div>
</div>

<script>
const TRAILER='j2NMu_oXgV0';
const TITLES={
  '1-1':'Red Light, Green Light','1-2':'Hell','1-3':'The Man with the Umbrella',
  '1-4':'Stick to the Team','1-5':'A Fair World','1-6':'Gganbu',
  '1-7':'VIPS','1-8':'Front Man','1-9':'One Lucky Day',
  '2-1':'bread and circus','2-2':'Halloween Party','2-3':'001',
  '2-4':'Six Eyes','2-5':'One More Game','2-6':'Place Your Bets','2-7':'Friend or Foe'
};

function goTab(id,btn){
  document.querySelectorAll('.pane').forEach(p=>p.classList.remove('on'));
  document.querySelectorAll('.tab').forEach(b=>b.classList.remove('on'));
  document.getElementById('pane-'+id).classList.add('on');
  btn.classList.add('on');
}

function goSeason(n,btn){
  document.querySelectorAll('.sbtn').forEach(b=>b.classList.remove('on'));
  btn.classList.add('on');
  document.getElementById('s1').style.display=n===1?'flex':'none';
  document.getElementById('s2').style.display=n===2?'flex':'none';
}

function setModal(ytId,title,sub){
  document.getElementById('mTitle').textContent=title;
  document.getElementById('mSub').textContent=sub;
  document.getElementById('mIframe').src='https://www.youtube.com/embed/'+ytId+'?autoplay=1&rel=0';
  document.getElementById('modal').classList.add('open');
}
function closeModal(){
  document.getElementById('modal').classList.remove('open');
  setTimeout(()=>{document.getElementById('mIframe').src='';},300);
}
function openTrailer(){setModal(TRAILER,'Squid Game — Official Trailer','Season 2 · Netflix');}
function playEp(ep,s){
  const k=s+'-'+ep;
  setModal(TRAILER,'S'+s+' E'+ep+' — '+(TITLES[k]||'Episode '+ep),'Squid Game · Season '+s);
}
document.getElementById('modal').addEventListener('click',e=>{if(e.target===e.currentTarget)closeModal();});
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal();});

function toggleWL(btn){
  const icon=document.getElementById('wlIcon');
  const on=icon.classList.contains('fa-check');
  icon.className=on?'fa fa-plus':'fa fa-check';
  btn.style.cssText=on?'':'background:rgba(229,9,20,.25);border-color:var(--red);color:var(--red)';
  btn.title=on?'Add to Watchlist':'Remove from Watchlist';
}
</script>
</body>
</html>