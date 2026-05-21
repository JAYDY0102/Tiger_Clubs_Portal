<?php
declare(strict_types=1);

session_start();
$pdo = require __DIR__ . '/../auth/db.php';
require_once __DIR__ . '/../auth/club-utils.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user = $_SESSION['user'];
$isExecutive = ($user['role'] ?? '') === 'executive';
$userEmail = isset($user['email']) ? (string) $user['email'] : '';
$isLoggedIn = isset($_SESSION['user']);

// Admins should use the full admin dashboard; this is for executives only
if (!$isExecutive) {
    header('Location: ../index.php');
    exit;
}

$projectRoot = dirname(__DIR__);
$clubsFile = $projectRoot . '/clubs.json';
$clubDirsRaw = json_decode((string) file_get_contents($clubsFile), true);
$clubDirs = is_array($clubDirsRaw) ? $clubDirsRaw : [];

// Filter to only clubs this executive manages
$execClubDirs = [];
foreach ($clubDirs as $d) {
    $dir = (string) $d;
    if (club_user_is_executive_of($dir, $userEmail)) {
        $execClubDirs[] = $dir;
    }
}

if (empty($execClubDirs)) {
    $noClubs = true;
} else {
    $noClubs = false;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Executive Dashboard — Club Management</title>
    <link rel="stylesheet" href="../styles.css" />
    <script>
        const currentAdminEmail = <?= json_encode($userEmail) ?>;
    </script>
</head>
<body>
<header class="topbar">
    <div class="brand">
        <div class="brand-mark">S</div>
        <span>Tiger Clubs Portal — Dashboard</span>
    </div>

    <nav class="nav">
        <a href="../index.php" class="inactive">Home</a>
        <a href="#" class="inactive">Feed</a>
        <a href="#" class="inactive">Calendar</a>
        <?php if ($isLoggedIn && $user['role'] === 'admin'): ?>
            <a href="dashboard.php" class="active">Admin Dashboard</a>
        <?php elseif ($isLoggedIn && $user['role'] === 'executive' ): ?>
            <a href="executive.php" class="active">Executive Dashboard</a>
        <?php elseif ($isLoggedIn && $user['role'] === 'advisor'): ?>
            <a href="advisor.php" class="active">Advisor Dashboard</a>
        <?php else: ?>
            <a href="#" class="active">Dashboard</a>
        <?php endif; ?>
    </nav>

    <div class="auth">
        <?php if ($isLoggedIn): ?>
            <a class="btn btn-ghost" >
                <?= htmlspecialchars($user['name']) ?>
            </a>
            <a class="btn btn-ghost" href="../auth/logout.php">Logout</a>
        <?php else: ?>
            <a class="btn btn-ghost" href="../auth/login.php">Log In</a>
            <a class="btn btn-primary" href="../auth/login.php">Sign Up</a>
        <?php endif; ?>
    </div>
</header>

<main class="dashboard-main">
    <?php if ($noClubs): ?>
        <div class="admin-panel">
            <h2>No Clubs Assigned</h2>
            <p>You are not assigned as an executive for any clubs yet. Contact an admin to assign you to a club.</p>
            <p><a href="../index.php" class="btn btn-primary">Back to Home</a></p>
        </div>
    <?php else: ?>
        <div class="dashboard-grid">
            <div>
                <div class="admin-panel">
                    <h2>My Clubs</h2>

                    <div class="club-selector">
                        <label for="clubSearch" class="sr-only">Search clubs</label>
                        <input id="clubSearch" class="search-input" type="text" placeholder="Search clubs..." />
                        <div class="club-list-box">
                            <label for="clubSelect" class="sr-only">Select club</label>
                            <select id="clubSelect" size="10">
                                <option value="">— Select a club —</option>
                                <?php foreach ($execClubDirs as $d):
                                    $dir = (string) $d;
                                    $label = ucwords(str_replace(['-', '_'], ' ', $dir));
                                    ?>
                                    <option value="<?= htmlspecialchars($dir) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="club-preview">
                        <div class="banner-preview" id="bannerPreview">
                            <div id="bannerPlaceholder" style="color:var(--muted);">No image</div>
                        </div>

                        <div class="club-info">
                            <div class="club-title" id="clubTitle">—</div>
                            <div class="club-meta">
                                <span id="clubType">—</span>
                                <span id="clubDay">—</span>
                                <span class="badge" id="clubMembers">0</span>
                            </div>
                        </div>

                        <div class="roles-section">
                            <div class="roles-label">Executives</div>
                            <div class="roles-list" id="execList">None assigned</div>
                        </div>

                        <div class="roles-section">
                            <div class="roles-label">Advisors</div>
                            <div class="roles-list" id="advisorList">None assigned</div>
                        </div>

                        <div class="upload-section">
                            <label class="upload-label" for="bannerInput">
                                Upload Banner
                                <input id="bannerInput" type="file" accept="image/png,image/jpeg,image/webp" />
                            </label>
                            <div id="uploadStatus" class="upload-status"></div>
                        </div>

                        <div id="resultBox" class="status-box"></div>
                    </div>
                </div>
            </div>

            <div>
                <div class="admin-panel">
                    <h2>Edit Club</h2>

                    <form id="clubEditForm" class="edit-form">
                        <div class="form-section">
                            <div class="form-section-title">Basic Information</div>
                            <div class="form-grid cols-2">
                                <div class="form-group">
                                    <label for="clubName">Club Name</label>
                                    <input id="clubName" name="name" />
                                </div>
                                <div class="form-group">
                                    <label for="clubTypeSelect">Type</label>
                                    <select id="clubTypeSelect" name="type">
                                        <option>STEM</option>
                                        <option>Academic</option>
                                        <option>Arts & Culture</option>
                                        <option>Community Service</option>
                                        <option>Journalism</option>
                                        <option>Sports</option>
                                        <option>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">Description</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="clubSummary">Summary (for grid)</label>
                                    <textarea id="clubSummary" name="summary"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="clubAbout">About (detailed)</label>
                                    <textarea id="clubAbout" name="about"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">Meeting Information</div>
                            <div class="form-grid cols-2">
                                <div class="form-group">
                                    <label for="clubDaySelect">Day</label>
                                    <select id="clubDaySelect" name="day">
                                        <option>Monday</option>
                                        <option>Wednesday</option>
                                        <option>Thursday (A)</option>
                                        <option>Thursday (B)</option>
                                        <option>Friday</option>
                                        <option>Other</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="clubMembersInput">Members</label>
                                    <input id="clubMembersInput" type="number" name="members" value="0" min="0" />
                                </div>
                            </div>
                            <div class="form-grid cols-2">
                                <div class="form-group">
                                    <label for="clubLocation">Location</label>
                                    <input id="clubLocation" name="location" />
                                </div>
                                <div class="form-group">
                                    <label for="clubTime">Time</label>
                                    <input id="clubTime" name="time" />
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title">Contact & Social</div>
                            <div class="form-grid cols-2">
                                <div class="form-group">
                                    <label for="clubAdvisor">Advisor</label>
                                    <input id="clubAdvisor" name="advisor" />
                                </div>
                                <div class="form-group">
                                    <label for="clubContactEmail">Email</label>
                                    <input id="clubContactEmail" name="contactEmail" type="email" />
                                </div>
                            </div>
                            <div class="form-grid cols-2">
                                <div class="form-group">
                                    <label for="clubInstagram">Instagram</label>
                                    <input id="clubInstagram" name="instagram" placeholder="@handle" />
                                </div>
                                <div class="form-group">
                                    <label for="clubWebsite">Website</label>
                                    <input id="clubWebsite" name="website" type="url" />
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" id="cancelEditBtn" class="btn btn-ghost">Reset</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<script>
    const el = {
        clubSearch: document.getElementById('clubSearch'),
        clubSelect: document.getElementById('clubSelect'),
        clubEditForm: document.getElementById('clubEditForm'),
        cancelEditBtn: document.getElementById('cancelEditBtn'),
        clubTitle: document.getElementById('clubTitle'),
        clubType: document.getElementById('clubType'),
        clubDay: document.getElementById('clubDay'),
        clubMembers: document.getElementById('clubMembers'),
        execList: document.getElementById('execList'),
        bannerPreview: document.getElementById('bannerPreview'),
        uploadStatus: document.getElementById('uploadStatus'),
        resultBox: document.getElementById('resultBox'),
        bannerInput: document.getElementById('bannerInput'),
        clubName: document.getElementById('clubName'),
        clubTypeSelect: document.getElementById('clubTypeSelect'),
        clubSummary: document.getElementById('clubSummary'),
        clubAbout: document.getElementById('clubAbout'),
        clubDaySelect: document.getElementById('clubDaySelect'),
        clubMembersInput: document.getElementById('clubMembersInput'),
        clubLocation: document.getElementById('clubLocation'),
        clubTime: document.getElementById('clubTime'),
        clubAdvisor: document.getElementById('clubAdvisor'),
        clubContactEmail: document.getElementById('clubContactEmail'),
        clubInstagram: document.getElementById('clubInstagram'),
        clubWebsite: document.getElementById('clubWebsite')
    };

    function imageApiUrl(path, w, h) {
        if (!path) return '';
        return `/api/image.php?path=${encodeURIComponent(path)}&w=${w}&h=${h}`;
    }

    function setStatus(box, type, text) {
        box.classList.remove('success', 'error');
        box.classList.add('show', type);
        box.textContent = text;
    }

    function clearStatus(box) {
        box.classList.remove('show', 'success', 'error');
        box.textContent = '';
    }

    function resetClubPreview() {
        el.clubTitle.textContent = '—';
        el.clubType.textContent = '—';
        el.clubDay.textContent = '—';
        el.clubMembers.textContent = '0';
        el.execList.textContent = 'None assigned';
        el.bannerPreview.innerHTML = '<div id="bannerPlaceholder" style="color:var(--muted);">No image</div>';
    }

    function resetEditForm() {
        el.clubEditForm.reset();
        el.clubMembersInput.value = 0;
    }

    function fillEditForm(data) {
        el.clubName.value = data.name || '';
        el.clubTypeSelect.value = data.type || 'Other';
        el.clubSummary.value = data.summary || '';
        el.clubAbout.value = data.about || '';
        el.clubDaySelect.value = data.day || 'Other';
        el.clubMembersInput.value = data.members || 0;
        el.clubLocation.value = data.meeting?.location || '';
        el.clubTime.value = data.meeting?.time || '';
        el.clubAdvisor.value = data.advisor || '';
        el.clubContactEmail.value = data.contactEmail || '';
        el.clubInstagram.value = data.instagram || '';
        el.clubWebsite.value = data.website || '';
    }

    function fillClubPreview(data, clubDir) {
        el.clubTitle.textContent = data.name || clubDir;
        el.clubType.textContent = data.type || 'Other';
        el.clubDay.textContent = data.day || 'Other';
        el.clubMembers.textContent = (data.members || 0) + ' members';

        const execEmails = Array.isArray(data.executiveEmails) ? data.executiveEmails : [];
        el.execList.textContent = execEmails.length ? execEmails.join(', ') : 'None assigned';

        if (data.image) {
            el.bannerPreview.innerHTML = `<img src="${imageApiUrl(data.image, 400, 200)}" alt="${data.name || clubDir}" />`;
        } else {
            el.bannerPreview.innerHTML = '<div style="color:var(--muted);">No image</div>';
        }
    }

    async function fetchJson(url, options) {
        const res = await fetch(url, options);
        let json = {};
        try {
            json = await res.json();
        } catch {
            json = {};
        }
        return { res, json };
    }

    el.clubSearch.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        for (const opt of el.clubSelect.options) {
            if (!opt.value) {
                opt.style.display = '';
                continue;
            }
            opt.style.display = opt.textContent.toLowerCase().includes(q) ? '' : 'none';
        }
    });

    el.clubSelect.addEventListener('change', async () => {
        const club = el.clubSelect.value;
        clearStatus(el.resultBox);
        el.uploadStatus.textContent = '';

        if (!club) {
            resetClubPreview();
            resetEditForm();
            return;
        }

        try {
            const { res, json } = await fetchJson(`../dashboard/get-club.php?club=${encodeURIComponent(club)}`);
            if (!res.ok || json.error) {
                alert('Error: ' + (json.error || 'Unknown'));
                return;
            }

            const data = (json && typeof json === 'object' && json.club && typeof json.club === 'object') ? json.club : {};
            fillClubPreview(data, club);
            fillEditForm(data);
        } catch (err) {
            console.error(err);
            alert('Failed to load club');
        }
    });

    el.cancelEditBtn.addEventListener('click', () => {
        el.clubSelect.value = '';
        el.clubSelect.dispatchEvent(new Event('change'));
    });

    el.clubEditForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const club = el.clubSelect.value;
        if (!club) {
            alert('No club selected');
            return;
        }

        const fd = new FormData();
        fd.append('clubDir', club);
        fd.append('data', JSON.stringify({
            name: el.clubName.value,
            type: el.clubTypeSelect.value,
            day: el.clubDaySelect.value,
            members: parseInt(el.clubMembersInput.value, 10) || 0,
            summary: el.clubSummary.value,
            about: el.clubAbout.value,
            advisor: el.clubAdvisor.value,
            contactEmail: el.clubContactEmail.value,
            instagram: el.clubInstagram.value,
            website: el.clubWebsite.value,
            meeting: {
                day: el.clubDaySelect.value,
                location: el.clubLocation.value,
                time: el.clubTime.value
            }
        }));

        try {
            const { res, json } = await fetchJson('../api/update-club.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            });

            if (!res.ok || json.error) {
                setStatus(el.resultBox, 'error', 'Error: ' + (json.error || 'Unknown error'));
                return;
            }

            setStatus(el.resultBox, 'success', 'Saved successfully');
            setTimeout(() => el.clubSelect.dispatchEvent(new Event('change')), 500);
        } catch (err) {
            console.error(err);
            setStatus(el.resultBox, 'error', 'Error: ' + err.message);
        }
    });

    el.bannerInput.addEventListener('change', async () => {
        const club = el.clubSelect.value;
        if (!club) {
            alert('Select a club first');
            return;
        }
        if (!el.bannerInput.files.length) return;

        el.uploadStatus.textContent = 'Uploading...';
        const fd = new FormData();
        fd.append('clubDir', club);
        fd.append('banner', el.bannerInput.files[0]);

        try {
            const { res, json } = await fetchJson('../api/upload-banner.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: fd
            });

            if (!res.ok || json.error) {
                el.uploadStatus.textContent = 'Error: ' + (json.error || 'Upload failed');
                return;
            }

            el.uploadStatus.textContent = 'Upload successful';
            el.bannerInput.value = '';
            setTimeout(() => el.clubSelect.dispatchEvent(new Event('change')), 500);
        } catch (err) {
            console.error(err);
            el.uploadStatus.textContent = 'Error uploading banner';
        }
    });
</script>
</body>
</html>
