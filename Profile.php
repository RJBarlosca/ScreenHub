<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli("127.0.0.1", "root", "", "screenhub_db", 3306);

// Load full user from DB
$stmt = $conn->prepare("SELECT username, first_name, last_name, email, avatar FROM users WHERE username = ?");
$stmt->bind_param("s", $_SESSION['username']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: index.php");
    exit();
}

// Default avatar using initials if none set
$avatar    = $user['avatar'] ?? '';
$firstName = $user['first_name'] ?? '';
$lastName  = $user['last_name']  ?? '';
$initials  = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ScreenHub | Profile</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --red:     #e50914;
    --red-dim: #8b0000;
    --gold:    #f5c518;
    --bg:      #080808;
    --surface: #111111;
    --surface2:#1a1a1a;
    --border:  rgba(255,255,255,0.07);
    --text:    #f0f0f0;
    --muted:   #666;
}

* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'Barlow', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
}

/* ── FILM GRAIN OVERLAY ── */
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
    pointer-events: none;
    z-index: 9999;
    opacity: 0.4;
}

/* ── HEADER ── */
header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(8,8,8,0.95);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    padding: 0 40px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo {
    font-family: 'Bebas Neue';
    font-size: 28px;
    letter-spacing: 3px;
    color: var(--red);
    text-decoration: none;
}

.back-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--muted);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.5px;
    transition: color 0.2s;
}
.back-btn:hover { color: var(--text); }
.back-btn i { font-size: 11px; }

/* ── PAGE LAYOUT ── */
.page {
    max-width: 900px;
    margin: 0 auto;
    padding: 50px 24px 80px;
}

/* ── HERO BANNER ── */
.profile-hero {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 40px;
    height: 200px;
    background: linear-gradient(135deg, #1a0000 0%, #0d0d0d 40%, #0a0a1a 100%);
}

.profile-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        repeating-linear-gradient(
            0deg,
            transparent,
            transparent 2px,
            rgba(255,255,255,0.01) 2px,
            rgba(255,255,255,0.01) 4px
        );
}

/* Red spotlight */
.profile-hero::after {
    content: '';
    position: absolute;
    top: -60px;
    left: 50%;
    transform: translateX(-50%);
    width: 500px;
    height: 300px;
    background: radial-gradient(ellipse, rgba(229,9,20,0.15) 0%, transparent 70%);
    pointer-events: none;
}

.hero-decorations {
    position: absolute;
    inset: 0;
    overflow: hidden;
}

.film-strip {
    position: absolute;
    top: 0;
    right: 0;
    width: 80px;
    height: 100%;
    display: flex;
    flex-direction: column;
    gap: 0;
    opacity: 0.06;
}

.film-hole {
    width: 20px;
    height: 26px;
    background: #fff;
    border-radius: 3px;
    margin: 4px auto;
}

.hero-title {
    position: absolute;
    bottom: 28px;
    left: 36px;
    z-index: 2;
}

.hero-title span {
    font-family: 'Barlow Condensed';
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 4px;
    color: var(--red);
    text-transform: uppercase;
    display: block;
    margin-bottom: 6px;
}

.hero-title h1 {
    font-family: 'Bebas Neue';
    font-size: 52px;
    letter-spacing: 3px;
    line-height: 1;
    color: #fff;
}

/* ── AVATAR + INFO ROW ── */
.profile-top {
    display: flex;
    align-items: flex-end;
    gap: 28px;
    margin-top: -60px;
    margin-bottom: 40px;
    position: relative;
    z-index: 10;
    padding: 0 4px;
}

/* ── AVATAR ── */
.avatar-wrap {
    position: relative;
    flex-shrink: 0;
}

.avatar-ring {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    padding: 3px;
    background: linear-gradient(135deg, var(--red), var(--gold));
    cursor: pointer;
    position: relative;
    transition: transform 0.2s;
}
.avatar-ring:hover { transform: scale(1.04); }

.avatar-inner {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    overflow: hidden;
    background: var(--surface2);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid var(--bg);
}

.avatar-inner img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.avatar-initials {
    font-family: 'Bebas Neue';
    font-size: 36px;
    letter-spacing: 2px;
    color: var(--red);
    user-select: none;
}

.avatar-edit-badge {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--red);
    border: 2px solid var(--bg);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}
.avatar-edit-badge:hover { background: #ff2020; }
.avatar-edit-badge i { font-size: 10px; color: #fff; }

input#fileInput { display: none; }

.user-info { padding-bottom: 8px; }
.user-info h2 {
    font-family: 'Barlow Condensed';
    font-size: 26px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 4px;
}
.user-info p {
    font-size: 13px;
    color: var(--muted);
    letter-spacing: 0.3px;
}

/* ── CARDS GRID ── */
.cards-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 620px) {
    .cards-grid { grid-template-columns: 1fr; }
    .profile-top { flex-direction: column; align-items: center; margin-top: -50px; }
    .hero-title h1 { font-size: 36px; }
}

.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 28px;
}

.card-full { grid-column: 1 / -1; }

.card-label {
    font-family: 'Barlow Condensed';
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 3px;
    color: var(--red);
    text-transform: uppercase;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.card-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
}

/* ── AVATAR PICKER inside card ── */
.avatar-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    margin-bottom: 0;
}

@media (max-width: 500px) { .avatar-grid { grid-template-columns: repeat(4, 1fr); } }

.av-option {
    aspect-ratio: 1;
    border-radius: 50%;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s;
    position: relative;
    background: var(--surface2);
}

.av-option img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.av-option:hover { border-color: rgba(229,9,20,0.5); transform: scale(1.08); }
.av-option.selected { border-color: var(--red); box-shadow: 0 0 0 3px rgba(229,9,20,0.2); }
.av-option.selected::after {
    content: '✓';
    position: absolute;
    inset: 0;
    background: rgba(229,9,20,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #fff;
    font-weight: bold;
}

.av-upload {
    aspect-ratio: 1;
    border-radius: 50%;
    border: 2px dashed var(--border);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 4px;
    transition: all 0.2s;
    color: var(--muted);
    font-size: 10px;
    background: var(--surface2);
}
.av-upload:hover { border-color: var(--red); color: var(--red); }
.av-upload i { font-size: 16px; }

.av-option img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    display: block;
    transition: opacity 0.3s;
}
.av-option img[src=""] { opacity: 0; }

.av-loading {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: var(--muted);
    border-radius: 50%;
    background: var(--surface2);
}
.av-option.loaded .av-loading { display: none; }

/* Character name label */
.av-name {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.85));
    color: #fff;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-align: center;
    padding: 8px 2px 3px;
    border-radius: 0 0 50% 50%;
    opacity: 0;
    transition: opacity 0.2s;
}
.av-option:hover .av-name { opacity: 1; }

/* ── FORM FIELDS ── */
.field {
    position: relative;
    margin-bottom: 18px;
}

.field input {
    width: 100%;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-family: 'Barlow', sans-serif;
    font-size: 15px;
    padding: 20px 16px 8px;
    outline: none;
    transition: border-color 0.2s, background 0.2s;
}

.field input:focus {
    border-color: var(--red);
    background: #1e1e1e;
}

.field label {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--muted);
    font-size: 14px;
    pointer-events: none;
    transition: all 0.2s;
}

.field input:focus + label,
.field input:not(:placeholder-shown) + label {
    top: 10px;
    transform: none;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 1px;
    color: var(--red);
    text-transform: uppercase;
}

.field-icon {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--muted);
    cursor: pointer;
    font-size: 13px;
    padding: 4px;
    transition: color 0.2s;
}
.field-icon:hover { color: var(--text); }

/* ── STATUS MESSAGES ── */
.msg {
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 13px;
    margin-bottom: 16px;
    display: none;
    align-items: center;
    gap: 10px;
}
.msg.error   { background: rgba(229,9,20,0.1);  border: 1px solid rgba(229,9,20,0.3);  color: #ff6b6b; display: none; }
.msg.success { background: rgba(70,211,105,0.1); border: 1px solid rgba(70,211,105,0.3); color: #46d369; display: none; }
.msg.show { display: flex; }

/* ── SAVE BUTTON ── */
.save-btn {
    width: 100%;
    padding: 15px;
    background: var(--red);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-family: 'Barlow Condensed';
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 4px;
}
.save-btn:hover { background: #ff1a1a; transform: translateY(-1px); }
.save-btn:active { transform: translateY(0); }
.save-btn:disabled { background: #444; cursor: not-allowed; transform: none; }

/* ── DANGER ZONE ── */
.danger-label {
    font-family: 'Barlow Condensed';
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 3px;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.danger-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }

.logout-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: transparent;
    border: 1px solid rgba(229,9,20,0.3);
    border-radius: 8px;
    color: #ff6b6b;
    font-family: 'Barlow Condensed';
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
}
.logout-btn:hover { background: rgba(229,9,20,0.1); border-color: var(--red); }

/* ── SAVE ANIMATION ── */
@keyframes spin { to { transform: rotate(360deg); } }
.spinning { animation: spin 0.8s linear infinite; }
</style>
</head>
<body>

<header>
    <a href="dashboard.php" class="logo">SCREENHUB</a>
    <a href="dashboard.php" class="back-btn"><i class="fa fa-chevron-left"></i> Back to Browse</a>
</header>

<div class="page">

    <!-- HERO BANNER -->
    <div class="profile-hero">
        <div class="hero-decorations">
            <div class="film-strip">
                <?php for($i=0;$i<12;$i++) echo '<div class="film-hole"></div>'; ?>
            </div>
        </div>
        <div class="hero-title">
            <span>Member Settings</span>
            <h1>Your Profile</h1>
        </div>
    </div>

    <!-- AVATAR + NAME ROW -->
    <div class="profile-top">
        <div class="avatar-wrap">
            <div class="avatar-ring" onclick="document.getElementById('fileInput').click()">
                <div class="avatar-inner" id="avatarDisplay">
                    <?php if (!empty($avatar)): ?>
                        <img id="currentAvatarImg" src="<?php echo $avatar; ?>" alt="Avatar">
                    <?php else: ?>
                        <span class="avatar-initials" id="initialsDisplay"><?php echo $initials ?: '?'; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="avatar-edit-badge" onclick="document.getElementById('fileInput').click()">
                <i class="fa fa-camera"></i>
            </div>
            <input type="file" id="fileInput" accept="image/*">
        </div>
        <div class="user-info">
            <h2><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
            <p>@<?php echo htmlspecialchars($user['username']); ?> &nbsp;·&nbsp; <?php echo htmlspecialchars($user['email']); ?></p>
        </div>
    </div>

    <div class="cards-grid">

        <!-- AVATAR PICKER CARD -->
        <div class="card card-full">
            <div class="card-label">Choose Avatar</div>
            <p style="font-size:12px;color:#555;margin:-12px 0 16px;letter-spacing:0.3px;">Select your favourite anime character</p>
            <div class="avatar-grid" id="avatarGrid">
                <!-- Avatars loaded from Jikan API (MyAnimeList) — real anime character images -->
                <!-- MAL character IDs: Naruto=17, Sasuke=13, Goku=246, Luffy=40, Zoro=62,
                     Levi=65009, Itachi=26, Tanjiro=163144, Gojo=153750, Ichigo=5 -->
                <div class="av-option" data-charid="17"     data-name="Naruto">  <img src="" alt="Naruto">  <div class="av-name">Naruto</div>  <div class="av-loading">...</div></div>
                <div class="av-option" data-charid="13"     data-name="Sasuke">  <img src="" alt="Sasuke">  <div class="av-name">Sasuke</div>  <div class="av-loading">...</div></div>
                <div class="av-option" data-charid="246"    data-name="Goku">    <img src="" alt="Goku">    <div class="av-name">Goku</div>    <div class="av-loading">...</div></div>
                <div class="av-option" data-charid="40"     data-name="Luffy">   <img src="" alt="Luffy">   <div class="av-name">Luffy</div>   <div class="av-loading">...</div></div>
                <div class="av-option" data-charid="62"     data-name="Zoro">    <img src="" alt="Zoro">    <div class="av-name">Zoro</div>    <div class="av-loading">...</div></div>
                <div class="av-option" data-charid="65009"  data-name="Levi">    <img src="" alt="Levi">    <div class="av-name">Levi</div>    <div class="av-loading">...</div></div>
                <div class="av-option" data-charid="26"     data-name="Itachi">  <img src="" alt="Itachi">  <div class="av-name">Itachi</div>  <div class="av-loading">...</div></div>
                <div class="av-option" data-charid="163144" data-name="Tanjiro"> <img src="" alt="Tanjiro"> <div class="av-name">Tanjiro</div> <div class="av-loading">...</div></div>
                <div class="av-option" data-charid="153750" data-name="Gojo">    <img src="" alt="Gojo">    <div class="av-name">Gojo</div>    <div class="av-loading">...</div></div>
                <div class="av-option" data-charid="5"      data-name="Ichigo">  <img src="" alt="Ichigo">  <div class="av-name">Ichigo</div>  <div class="av-loading">...</div></div>

                <!-- Upload your own -->
                <div class="av-upload" onclick="document.getElementById('fileInput').click()">
                    <i class="fa fa-upload"></i>
                    <span>Upload</span>
                </div>
            </div>
        </div>

        <!-- ACCOUNT INFO CARD -->
        <div class="card">
            <div class="card-label">Account Info</div>

            <div class="field">
                <input type="text" id="username" placeholder=" " value="<?php echo htmlspecialchars($user['username']); ?>" required>
                <label>Username</label>
            </div>
            <div class="field">
                <input type="email" id="email" placeholder=" " value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="opacity:0.5;cursor:not-allowed;">
                <label>Email (cannot change)</label>
            </div>
        </div>

        <!-- CHANGE PASSWORD CARD -->
        <div class="card">
            <div class="card-label">Change Password</div>

            <div class="field">
                <input type="password" id="newPassword" placeholder=" ">
                <label>New Password</label>
                <button class="field-icon" type="button" onclick="toggleVis('newPassword',this)">
                    <i class="fa fa-eye"></i>
                </button>
            </div>
            <div class="field">
                <input type="password" id="confirmPassword" placeholder=" ">
                <label>Confirm Password</label>
                <button class="field-icon" type="button" onclick="toggleVis('confirmPassword',this)">
                    <i class="fa fa-eye"></i>
                </button>
            </div>
        </div>

        <!-- SAVE SECTION -->
        <div class="card card-full">
            <div id="errorMsg"   class="msg error">  <i class="fa fa-circle-exclamation"></i> <span id="errorText"></span></div>
            <div id="successMsg" class="msg success"><i class="fa fa-circle-check"></i> <span>Profile updated successfully!</span></div>

            <button class="save-btn" id="saveBtn" onclick="saveProfile()">
                <i class="fa fa-floppy-disk"></i> Save Changes
            </button>
        </div>

        <!-- DANGER ZONE -->
        <div class="card card-full" style="border-color:rgba(229,9,20,0.15);">
            <div class="danger-label">Session</div>
            <a href="logout.php" class="logout-btn"><i class="fa fa-right-from-bracket"></i> Sign Out</a>
        </div>

    </div>
</div>

<script>
// ── State ──
let selectedAvatar = <?php echo json_encode($avatar ?: ''); ?>;

// ── Load real anime images from Jikan API (MyAnimeList) ──
async function loadAnimeAvatars() {
    const opts = document.querySelectorAll('.av-option[data-charid]');

    // Load one at a time to respect Jikan rate limit (3 req/sec)
    for (const opt of opts) {
        const id  = opt.dataset.charid;
        const img = opt.querySelector('img');
        try {
            const res  = await fetch(`https://api.jikan.moe/v4/characters/${id}`);
            const data = await res.json();
            const url  = data?.data?.images?.jpg?.image_url || '';
            if (url) {
                img.src = url;
                img.onload = () => {
                    opt.classList.add('loaded');
                    // If this is the saved avatar, mark as selected
                    if (selectedAvatar === url) {
                        document.querySelectorAll('.av-option').forEach(o => o.classList.remove('selected'));
                        opt.classList.add('selected');
                    }
                };
                opt.dataset.imgurl = url; // store for click handler
            }
        } catch(e) {
            opt.classList.add('loaded'); // hide loader even on error
        }
        // Small delay to avoid hitting rate limit
        await new Promise(r => setTimeout(r, 400));
    }
}
loadAnimeAvatars();

// ── Highlight saved avatar on load ──
document.querySelectorAll('.av-option').forEach(opt => {
    if (opt.dataset.imgurl && opt.dataset.imgurl === selectedAvatar) {
        opt.classList.add('selected');
    }
});

// ── Avatar preset click ──
document.querySelectorAll('.av-option').forEach(opt => {
    opt.addEventListener('click', () => {
        document.querySelectorAll('.av-option').forEach(o => o.classList.remove('selected'));
        opt.classList.add('selected');
        const url = opt.dataset.imgurl || opt.querySelector('img')?.src;
        if (url && url !== window.location.href) {
            selectedAvatar = url;
            updateAvatarPreview(url);
        }
    });
});

// ── File upload ──
document.getElementById('fileInput').addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {
            const canvas = document.createElement('canvas');
            const max    = 200;
            const ratio  = Math.min(max / img.width, max / img.height);
            canvas.width  = img.width  * ratio;
            canvas.height = img.height * ratio;
            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
            const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
            selectedAvatar = dataUrl;
            document.querySelectorAll('.av-option').forEach(o => o.classList.remove('selected'));
            updateAvatarPreview(dataUrl);
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
});

// ── Update the big avatar display at top ──
function updateAvatarPreview(src) {
    const display = document.getElementById('avatarDisplay');
    display.innerHTML = `<img src="${src}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
}

// ── Toggle password visibility ──
function toggleVis(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa fa-eye';
    }
}

// ── Save ──
async function saveProfile() {
    const btn         = document.getElementById('saveBtn');
    const errorMsg    = document.getElementById('errorMsg');
    const successMsg  = document.getElementById('successMsg');
    const errorText   = document.getElementById('errorText');
    const username    = document.getElementById('username').value.trim();
    const newPassword = document.getElementById('newPassword').value;
    const confirmPass = document.getElementById('confirmPassword').value;

    // Hide previous messages
    errorMsg.classList.remove('show');
    successMsg.classList.remove('show');

    // Validate
    if (!username) {
        errorText.textContent = 'Username cannot be empty.';
        errorMsg.classList.add('show');
        return;
    }
    if (newPassword && newPassword.length < 8) {
        errorText.textContent = 'Password must be at least 8 characters.';
        errorMsg.classList.add('show');
        return;
    }
    if (newPassword && newPassword !== confirmPass) {
        errorText.textContent = 'Passwords do not match.';
        errorMsg.classList.add('show');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner spinning"></i> Saving...';

    try {
        const res    = await fetch('update_profile.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                username: username,
                password: newPassword,
                avatar:   selectedAvatar
            })
        });
        const data = await res.json();

        if (data.success) {
            successMsg.classList.add('show');
            btn.innerHTML = '<i class="fa fa-check"></i> Saved!';
            // Redirect to dashboard after short delay so user sees the success message
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 1200);
        } else {
            errorText.textContent = data.error || 'Something went wrong.';
            errorMsg.classList.add('show');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-floppy-disk"></i> Save Changes';
        }
    } catch(e) {
        errorText.textContent = 'Connection error. Is XAMPP running?';
        errorMsg.classList.add('show');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-floppy-disk"></i> Save Changes';
    }
}
</script>
</body>
</html>
