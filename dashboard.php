<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$conn = new mysqli("127.0.0.1", "root", "", "screenhub_db", 3306);
$stmt = $conn->prepare("SELECT avatar, first_name, last_name FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$dbUser = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

$avatar    = $dbUser['avatar']     ?? '';
$firstName = $dbUser['first_name'] ?? '';
$lastName  = $dbUser['last_name']  ?? '';
$initials  = strtoupper(substr($firstName,0,1) . substr($lastName,0,1)) ?: '?';
$fullName  = trim($firstName . ' ' . $lastName) ?: $username;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ScreenHub | Browse</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --red:     #e50914;
    --gold:    #f5c518;
    --bg:      #080808;
    --surface: #111111;
    --surface2:#1a1a1a;
    --border:  rgba(255,255,255,0.07);
    --text:    #f0f0f0;
    --muted:   #666;
}

* { margin:0; padding:0; box-sizing:border-box; }

html { scroll-behavior: smooth; }

body {
    font-family: 'Barlow', sans-serif;
    background: var(--bg);
    color: var(--text);
    overflow-x: hidden;
}

/* ── FILM GRAIN ── */
body::after {
    content:'';
    position:fixed;
    inset:0;
    background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    pointer-events:none;
    z-index:9999;
    opacity:0.35;
}

/* ══════════════════════════════
   HEADER
══════════════════════════════ */
header {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 1000;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 40px;
    transition: background 0.4s, backdrop-filter 0.4s;
}
header.scrolled {
    background: rgba(8,8,8,0.97);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--border);
}

.logo {
    font-family: 'Bebas Neue';
    font-size: 30px;
    letter-spacing: 4px;
    color: var(--red);
    text-decoration: none;
    flex-shrink: 0;
}

nav {
    display: flex;
    gap: 28px;
    align-items: center;
}
nav a {
    font-family: 'Barlow Condensed';
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.6);
    text-decoration: none;
    transition: color 0.2s;
    position: relative;
}
nav a::after {
    content:'';
    position:absolute;
    bottom:-4px; left:0;
    width:0; height:2px;
    background: var(--red);
    transition: width 0.3s;
}
nav a:hover { color:#fff; }
nav a:hover::after { width:100%; }
nav a.active { color:#fff; }
nav a.active::after { width:100%; }

/* Header right */
.header-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.search-toggle {
    background: none;
    border: none;
    color: rgba(255,255,255,0.6);
    font-size: 16px;
    cursor: pointer;
    padding: 6px;
    transition: color 0.2s;
}
.search-toggle:hover { color: #fff; }

/* Avatar button */
.avatar-btn {
    position: relative;
    cursor: pointer;
}

.avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid transparent;
    background: linear-gradient(var(--bg), var(--bg)) padding-box,
                linear-gradient(135deg, var(--red), var(--gold)) border-box;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s;
}
.avatar-circle:hover { transform: scale(1.08); }
.avatar-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    display: block;
}
.avatar-initials {
    font-family: 'Bebas Neue';
    font-size: 14px;
    letter-spacing: 1px;
    color: var(--red);
}

/* Dropdown */
.avatar-dropdown {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    width: 220px;
    background: rgba(12,12,12,0.98);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 8px 0;
    opacity: 0;
    pointer-events: none;
    transform: translateY(-8px);
    transition: all 0.2s;
    backdrop-filter: blur(20px);
}
.avatar-btn:hover .avatar-dropdown {
    opacity: 1;
    pointer-events: all;
    transform: translateY(0);
}

.dropdown-header {
    padding: 12px 18px 10px;
    border-bottom: 1px solid var(--border);
    margin-bottom: 4px;
}
.dropdown-header .dh-name {
    font-weight: 600;
    font-size: 14px;
    color: #fff;
}
.dropdown-header .dh-user {
    font-size: 12px;
    color: var(--muted);
    margin-top: 2px;
}

.avatar-dropdown a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 18px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-size: 13px;
    transition: all 0.15s;
}
.avatar-dropdown a i {
    width: 16px;
    font-size: 12px;
    color: var(--muted);
}
.avatar-dropdown a:hover {
    background: rgba(255,255,255,0.05);
    color: #fff;
}
.avatar-dropdown a:hover i { color: var(--red); }
.avatar-dropdown hr {
    border: none;
    border-top: 1px solid var(--border);
    margin: 4px 0;
}
.sign-out { color: #ff6b6b !important; }
.sign-out i { color: #ff6b6b !important; }

/* ══════════════════════════════
   HERO
══════════════════════════════ */
.hero {
    position: relative;
    height: 100vh;
    min-height: 600px;
    overflow: hidden;
}

.hero-video {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-gradient {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(to right, rgba(8,8,8,0.95) 0%, rgba(8,8,8,0.6) 50%, transparent 100%),
        linear-gradient(to top, rgba(8,8,8,1) 0%, transparent 40%);
}

.hero-content {
    position: absolute;
    bottom: 15%;
    left: 60px;
    max-width: 540px;
    z-index: 2;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--red);
    color: #fff;
    font-family: 'Barlow Condensed';
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    padding: 4px 12px;
    border-radius: 3px;
    margin-bottom: 16px;
}

.hero-title {
    font-family: 'Bebas Neue';
    font-size: 72px;
    line-height: 0.95;
    letter-spacing: 2px;
    margin-bottom: 16px;
    text-shadow: 0 2px 20px rgba(0,0,0,0.5);
}

.hero-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}
.hero-meta span {
    font-size: 13px;
    color: rgba(255,255,255,0.65);
    display: flex;
    align-items: center;
    gap: 4px;
}
.hero-meta .imdb {
    background: var(--gold);
    color: #000;
    font-weight: 700;
    font-size: 11px;
    padding: 2px 7px;
    border-radius: 3px;
    font-family: 'Barlow Condensed';
}
.hero-meta .dot { color: var(--muted); }

.hero-desc {
    font-size: 15px;
    line-height: 1.6;
    color: rgba(255,255,255,0.7);
    margin-bottom: 28px;
    max-width: 460px;
}

.hero-actions {
    display: flex;
    gap: 12px;
}

.btn-play {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 28px;
    background: var(--red);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-family: 'Barlow Condensed';
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-play:hover { background: #ff1a1a; transform: translateY(-2px); }

.btn-info {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 13px 24px;
    background: rgba(255,255,255,0.1);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 8px;
    font-family: 'Barlow Condensed';
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.2s;
    backdrop-filter: blur(8px);
}
.btn-info:hover { background: rgba(255,255,255,0.18); transform: translateY(-2px); }

/* ══════════════════════════════
   MAIN CONTENT
══════════════════════════════ */
main {
    position: relative;
    z-index: 2;
    padding: 0 0 80px;
}

/* ── Filter bar ── */
.filter-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 60px;
    border-bottom: 1px solid var(--border);
    gap: 16px;
    flex-wrap: wrap;
    background: rgba(8,8,8,0.8);
    backdrop-filter: blur(10px);
}

.genres {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.genre-pill {
    padding: 6px 16px;
    border-radius: 20px;
    border: 1px solid var(--border);
    background: transparent;
    color: rgba(255,255,255,0.55);
    font-family: 'Barlow Condensed';
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.8px;
    cursor: pointer;
    transition: all 0.2s;
}
.genre-pill:hover,
.genre-pill.active {
    background: var(--red);
    border-color: var(--red);
    color: #fff;
}

.search-wrap {
    position: relative;
    flex-shrink: 0;
}
.search-wrap i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-size: 13px;
    pointer-events: none;
}
.search-wrap input {
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-family: 'Barlow', sans-serif;
    font-size: 14px;
    padding: 9px 16px 9px 38px;
    outline: none;
    width: 240px;
    transition: border-color 0.2s, width 0.3s;
}
.search-wrap input:focus {
    border-color: rgba(229,9,20,0.5);
    width: 280px;
}
.search-wrap input::placeholder { color: var(--muted); }

/* ── Section ── */
.section { padding: 40px 60px 0; }

.section-header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-bottom: 20px;
}
.section-title {
    font-family: 'Barlow Condensed';
    font-size: 22px;
    font-weight: 700;
    letter-spacing: 1px;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 10px;
}
.section-title::before {
    content:'';
    width: 4px;
    height: 20px;
    background: var(--red);
    border-radius: 2px;
    display: inline-block;
}
.see-all {
    font-family: 'Barlow Condensed';
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted);
    text-decoration: none;
    transition: color 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.see-all:hover { color: var(--red); }

/* ── Highlight cards ── */
.highlight-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 0;
}

.highlight-card {
    position: relative;
    height: 340px;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    background: var(--surface2);
    background-size: cover;
    background-position: center top;
}
.highlight-card::before {
    content:'';
    position:absolute;
    inset:0;
    background: linear-gradient(to top, rgba(0,0,0,0.92) 0%, rgba(0,0,0,0.3) 50%, transparent 100%);
    transition: opacity 0.3s;
}
.highlight-card:hover::before { opacity: 0.7; }
.highlight-card:hover { transform: scale(1.01); transition: transform 0.3s; }

.highlight-content {
    position: absolute;
    bottom: 0;
    left: 0; right: 0;
    padding: 24px;
    z-index: 2;
}
.hc-title {
    font-family: 'Bebas Neue';
    font-size: 28px;
    letter-spacing: 1px;
    margin-bottom: 6px;
}
.hc-meta {
    font-size: 12px;
    color: rgba(255,255,255,0.55);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.hc-meta .imdb {
    background: var(--gold);
    color: #000;
    font-weight: 700;
    font-size: 10px;
    padding: 1px 5px;
    border-radius: 2px;
}
.hc-desc {
    font-size: 13px;
    color: rgba(255,255,255,0.6);
    line-height: 1.5;
    margin-bottom: 14px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.hc-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 18px;
    background: var(--red);
    color: #fff;
    border: none;
    border-radius: 6px;
    font-family: 'Barlow Condensed';
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    transition: background 0.2s;
}
.hc-btn:hover { background: #ff1a1a; }

/* ── Movie row ── */
.movie-row {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding: 4px 0 16px;
    scrollbar-width: none;
    cursor: grab;
    user-select: none;
}
.movie-row::-webkit-scrollbar { display: none; }
.movie-row:active { cursor: grabbing; }

.movie-card {
    flex-shrink: 0;
    width: 148px;
    border-radius: 8px;
    overflow: hidden;
    background: var(--surface2);
    position: relative;
    transition: transform 0.25s, box-shadow 0.25s;
    cursor: pointer;
}
a.movie-card { text-decoration: none; display: block; }
.movie-card img {
    width: 100%;
    height: 222px;
    object-fit: cover;
    display: block;
    pointer-events: none;
}
.movie-card:hover {
    transform: scale(1.06) translateY(-4px);
    box-shadow: 0 16px 40px rgba(0,0,0,0.6);
    z-index: 2;
}

/* hover overlay on cards */
.movie-card::after {
    content: '▶';
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.55);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #fff;
    opacity: 0;
    transition: opacity 0.2s;
    border-radius: 8px;
}
.movie-card:hover::after { opacity: 1; }
</style>
</head>
<body>

<!-- ════ HEADER ════ -->
<header id="mainHeader">
    <a href="dashboard.php" class="logo">SCREENHUB</a>

    <nav>
        <a href="#" class="active">Home</a>
        <a href="#">Movies</a>
        <a href="#">TV Series</a>
        <a href="#">Discover</a>
    </nav>

    <div class="header-right">
        <!-- Search -->
        <button class="search-toggle"><i class="fa fa-magnifying-glass"></i></button>

        <!-- Avatar dropdown -->
        <div class="avatar-btn">
            <div class="avatar-circle">
                <?php if (!empty($avatar)): ?>
                    <img src="<?php echo $avatar; ?>" alt="avatar">
                <?php else: ?>
                    <span class="avatar-initials"><?php echo $initials; ?></span>
                <?php endif; ?>
            </div>

            <div class="avatar-dropdown">
                <div class="dropdown-header">
                    <div class="dh-name"><?php echo htmlspecialchars($fullName); ?></div>
                    <div class="dh-user">@<?php echo htmlspecialchars($username); ?></div>
                </div>
                <a href="Profile.php"><i class="fa fa-user"></i> Edit Profile</a>
                <a href="#"><i class="fa fa-gear"></i> Settings</a>
                <a href="#"><i class="fa fa-circle-question"></i> Help</a>
                <hr>
                <a href="logout.php" class="sign-out"><i class="fa fa-right-from-bracket"></i> Sign Out</a>
            </div>
        </div>
    </div>
</header>

<!-- ════ HERO ════ -->
<section class="hero">
    <video autoplay muted loop playsinline class="hero-video">
        <source src="Predator Badlands  Official Trailer - 20th Century Studios (1080p, h264).mp4" type="video/mp4">
    </video>
    <div class="hero-gradient"></div>
    <div class="hero-content">
        <div class="hero-badge"><i class="fa fa-fire"></i> Now Trending</div>
        <h1 class="hero-title">Predator:<br>Badlands</h1>
        <div class="hero-meta">
            <span class="imdb">IMDb 7.3</span>
            <span class="dot">·</span>
            <span>2025</span>
            <span class="dot">·</span>
            <span>Action, Sci-Fi</span>
            <span class="dot">·</span>
            <span>1h 52m</span>
        </div>
        <p class="hero-desc">Banished from his group, a young Predator forms an unexpected partnership with a human survivor deep in the dangerous Badlands.</p>
        <div class="hero-actions">
            <button class="btn-play"><i class="fa fa-play"></i> Play Now</button>
            <button class="btn-info"><i class="fa fa-circle-info"></i> More Info</button>
        </div>
    </div>
</section>

<!-- ════ MAIN ════ -->
<main>
    <!-- Filter bar -->
    <div class="filter-bar">
        <div class="genres">
            <button class="genre-pill active">All</button>
            <button class="genre-pill">Action</button>
            <button class="genre-pill">Horror</button>
            <button class="genre-pill">Comedy</button>
            <button class="genre-pill">Drama</button>
            <button class="genre-pill">Sci-Fi</button>
            <button class="genre-pill">Animation</button>
        </div>
        <div class="search-wrap">
            <i class="fa fa-magnifying-glass"></i>
            <input type="text" placeholder="Search movies, shows...">
        </div>
    </div>

    <!-- Highlights -->
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">Featured</h2>
            <a href="#" class="see-all">View All <i class="fa fa-chevron-right"></i></a>
        </div>
        <div class="highlight-grid">
            <div class="highlight-card" style="background-image:url('predator_badlands_ver5.jpg')">
                <div class="highlight-content">
                    <div class="hc-title">Predator: Badlands</div>
                    <div class="hc-meta"><span class="imdb">7.3</span> Action · Sci-Fi · 1h 52m</div>
                    <div class="hc-desc">Banished from his group, a young Predator forms an unexpected partnership with a human survivor.</div>
                    <button class="hc-btn"><i class="fa fa-play"></i> Watch Now</button>
                </div>
            </div>
            <div class="highlight-card" style="background-image:url('frankenstein_ver9.jpg')">
                <div class="highlight-content">
                    <div class="hc-title">Frankenstein</div>
                    <div class="hc-meta"><span class="imdb">7.5</span> Drama · Horror · 2h 29m</div>
                    <div class="hc-desc">Dr. Victor Frankenstein defies the laws of nature in his relentless pursuit of scientific glory.</div>
                    <button class="hc-btn"><i class="fa fa-play"></i> Watch Now</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Movies -->
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">Latest Movies</h2>
            <a href="#" class="see-all">View All <i class="fa fa-chevron-right"></i></a>
        </div>
        <div class="movie-row">
            <div class="movie-card"><img src="toy_story_five.jpg" alt="Toy Story 5"></div>
            <div class="movie-card"><img src="captain_america_brave_new_world.jpg" alt="Captain America"></div>
            <div class="movie-card"><img src="thunderbolts.jpg" alt="Thunderbolts"></div>
            <div class="movie-card"><img src="ballerina.jpg" alt="Ballerina"></div>
            <div class="movie-card"><img src="avengers_doomsday.jpg" alt="Avengers Doomsday"></div>
            <div class="movie-card"><img src="conjuring_last_rites.jpg" alt="Conjuring"></div>
            <div class="movie-card"><img src="smile_two.jpg" alt="Smile 2"></div>
            <div class="movie-card"><img src="scream_seven.jpg" alt="Scream 7"></div>
            <div class="movie-card"><img src="running_man.jpg" alt="Running Man"></div>
            <div class="movie-card"><img src="wicked_ver2.jpg" alt="Wicked"></div>
            <div class="movie-card"><img src="sinister.jpg" alt="Sinister"></div>
            <div class="movie-card"><img src="it.jpg" alt="IT"></div>
            <div class="movie-card"><img src="john_wick.jpg" alt="John Wick"></div>
            <div class="movie-card"><img src="deliver_us_from_evil.jpg" alt="Deliver Us"></div>
            <div class="movie-card"><img src="shining_ver2.jpg" alt="The Shining"></div>
        </div>
    </div>

    <!-- Popular -->
    <div class="section">
        <div class="section-header">
            <h2 class="section-title">Popular on ScreenHub</h2>
            <a href="#" class="see-all">View All <i class="fa fa-chevron-right"></i></a>
        </div>
        <div class="movie-row">
            <a href="movie_squidgame.php" class="movie-card"><img src="https://static.wikia.nocookie.net/thesquidgame/images/7/7d/GdWDj4ZaoAEgw26.jpg/revision/latest?cb=20241214072040" alt="Squid Game"></a>
            <div class="movie-card"><img src="https://m.media-amazon.com/images/M/MV5BMDE1NjNmZjgtZTg0OC00NjkxLWEzYzItMDNkMTc3YjgxZWQyXkEyXkFqcGc@._V1_FMjpg_UX1000_.jpg" alt="Show"></div>
            <div class="movie-card"><img src="https://upload.wikimedia.org/wikipedia/en/6/6c/The_Crown_season_1.jpeg" alt="The Crown"></div>
            <div class="movie-card"><img src="https://m.media-amazon.com/images/I/61boFr6SYZL.jpg" alt="Show"></div>
            <div class="movie-card"><img src="https://upload.wikimedia.org/wikipedia/en/1/10/Godzilla_%282014%29_poster.jpg" alt="Godzilla"></div>
            <div class="movie-card"><img src="https://upload.wikimedia.org/wikipedia/en/2/29/Movie_poster_for_%22Scary_Movie%22.jpg" alt="Scary Movie"></div>
            <div class="movie-card"><img src="https://m.media-amazon.com/images/M/MV5BYjA3NDkwNzktNjJkYi00ODNhLWFhYzQtYzk5NjU4MDM0OWZmXkEyXkFqcGc@._V1_.jpg" alt="Show"></div>
            <div class="movie-card"><img src="https://resizing.flixster.com/lpJkDxnEFNQT1OWJjnmYfvpAHJ0=/ems.cHJkLWVtcy1hc3NldHMvdHZzZXJpZXMvUlRUVjI2NjgyOS53ZWJw" alt="Show"></div>
            <div class="movie-card"><img src="https://dnm.nflximg.net/api/v6/mAcAr9TxZIVbINe88xb3Teg5_OA/AAAABQy-HH9EQheOt3yhvkFNJ6UD581ekiegoq_jibPvlouqFRGBZIUY2bfVJ-KtifSrspzV9jBfK7O-BYEeEswFA3UWz2zJv5Il3cuC.jpg?r=7d3" alt="Show"></div>
           <div class="movie-card"><img src="https://a.ltrbxd.com/resized/film-poster/5/0/9/4/2/8/509428-deliver-us-from-evil-0-230-0-345-crop.jpg?v=94b9cc6485" alt="Deliver Us From Evil (2020)"></div>
           <div class="movie-card"><img src="https://upload.wikimedia.org/wikipedia/en/thumb/b/b2/The_Unholy_2021_Film_Poster.png/250px-The_Unholy_2021_Film_Poster.png" alt="The Unholy"></div>
           <div class="movie-card"><img src="https://upload.wikimedia.org/wikipedia/en/thumb/6/68/The_Devil%27s_Light_Poster.jpeg/250px-The_Devil%27s_Light_Poster.jpeg" alt="Pray For The Devil"></div>
        </div>
    </div>
</main>

<script>
// ── Header scroll effect ──
const header = document.getElementById('mainHeader');
window.addEventListener('scroll', () => {
    header.classList.toggle('scrolled', window.scrollY > 80);
}, { passive: true });
// Always show header bg on load if not at top
if (window.scrollY > 80) header.classList.add('scrolled');
else header.classList.add('scrolled'); // always show for usability

// ── Drag to scroll ──
document.querySelectorAll('.movie-row').forEach(row => {
    let down = false, startX, scrollLeft;
    row.addEventListener('mousedown', e => {
        down = true;
        startX = e.pageX - row.offsetLeft;
        scrollLeft = row.scrollLeft;
    });
    row.addEventListener('mouseleave', () => down = false);
    row.addEventListener('mouseup',    () => down = false);
    row.addEventListener('mousemove',  e => {
        if (!down) return;
        e.preventDefault();
        row.scrollLeft = scrollLeft - (e.pageX - row.offsetLeft - startX) * 1.6;
    });
});

// ── Genre pills ──
document.querySelectorAll('.genre-pill').forEach(pill => {
    pill.addEventListener('click', () => {
        document.querySelectorAll('.genre-pill').forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
    });
});
</script>
</body>
</html>