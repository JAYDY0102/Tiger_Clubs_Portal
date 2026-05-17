<?php
// scripts/init_club_dirs.php
declare(strict_types=1);

/*
 * Usage: php scripts/init_club_dirs.php
 *
 * Reads ./clubs.json (project root) and for each slug:
 *  - creates a folder at project root (slug)
 *  - creates slug/drawer.json (defaults) if missing
 *  - creates slug/documents/ if missing
 *  - creates slug/main.png placeholder if missing (GD preferred)
 *
 * Safety: accepts only slugs matching /^[a-z0-9\-_]+$/
 * Idempotent: won't overwrite existing drawer.json or main.png
 */

$scriptDir = __DIR__;
$projectRoot = realpath($scriptDir . '/..');
if ($projectRoot === false) {
    fwrite(STDERR, "Cannot locate project root\n");
    exit(1);
}

$clubsFile = $projectRoot . '/clubs.json';
if (!file_exists($clubsFile)) {
    fwrite(STDERR, "clubs.json not found at $clubsFile\n");
    exit(1);
}

$raw = file_get_contents($clubsFile);
$clubDirs = json_decode($raw, true);
if (!is_array($clubDirs)) {
    fwrite(STDERR, "clubs.json has invalid JSON or is not an array\n");
    exit(1);
}

function is_valid_slug(string $s): bool {
    return (bool) preg_match('/^[a-z0-9\-_]+$/', $s);
}

foreach ($clubDirs as $dirName) {
    if (!is_string($dirName)) {
        fwrite(STDOUT, "Skipping non-string entry\n");
        continue;
    }
    $dirName = trim($dirName);
    if ($dirName === '') {
        fwrite(STDOUT, "Skipping empty entry\n");
        continue;
    }

    if (!is_valid_slug($dirName)) {
        fwrite(STDOUT, "Skipping invalid club name (allowed chars a-z0-9_-): $dirName\n");
        continue;
    }

    $clubPath = $projectRoot . DIRECTORY_SEPARATOR . $dirName;

    // create club folder
    if (!is_dir($clubPath)) {
        if (!mkdir($clubPath, 0755, true)) {
            fwrite(STDERR, "Failed to create directory: $clubPath\n");
            continue;
        }
        fwrite(STDOUT, "Created directory: $dirName\n");
    } else {
        fwrite(STDOUT, "Directory exists: $dirName\n");
    }

    // create documents folder
    $docsPath = $clubPath . DIRECTORY_SEPARATOR . 'documents';
    if (!is_dir($docsPath)) {
        if (mkdir($docsPath, 0755, true)) {
            fwrite(STDOUT, "  Created documents/ for $dirName\n");
        } else {
            fwrite(STDERR, "  Failed to create documents/ for $dirName\n");
        }
    } else {
        fwrite(STDOUT, "  documents/ exists for $dirName\n");
    }

    // create default drawer.json if missing
    $drawerFile = $clubPath . DIRECTORY_SEPARATOR . 'drawer.json';
    $default = [
        'name' => ucwords(str_replace(['-', '_'], ' ', $dirName)),
        'type' => 'Other',
        'day' => 'Other',
        'members' => 0,
        'summary' => '',
        'about' => '',
        'advisor' => '',
        'contactEmail' => '',
        'instagram' => '',
        'website' => '',
        'executiveEmails' => [],
        'meeting' => [
            'day' => '',
            'location' => '',
            'time' => ''
        ],
        'posts' => []
    ];

    if (!file_exists($drawerFile)) {
        $json = json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($drawerFile, $json) !== false) {
            fwrite(STDOUT, "  Created default drawer.json for $dirName\n");
        } else {
            fwrite(STDERR, "  Failed to write drawer.json for $dirName\n");
        }
    } else {
        fwrite(STDOUT, "  drawer.json exists for $dirName\n");
    }

    // create placeholder main.png if missing
    $bannerFile = $clubPath . DIRECTORY_SEPARATOR . 'main.png';
    if (!file_exists($bannerFile)) {
        // try GD placeholder
        if (function_exists('imagecreatetruecolor') && function_exists('imagestring')) {
            $w = 1000; $h = 500; // 2:1 ratio (width:height)
            $img = imagecreatetruecolor($w, $h);
            $bg = imagecolorallocate($img, 40, 44, 52);
            $fg = imagecolorallocate($img, 255, 255, 255);
            imagefilledrectangle($img, 0, 0, $w, $h, $bg);

            $text = $default['name'];
            $fontSize = 5; // built-in font
            $textBoxWidth = imagefontwidth($fontSize) * strlen($text);
            $textBoxHeight = imagefontheight($fontSize);
            $x = max(0, intval(($w - $textBoxWidth) / 2));
            $y = max(0, intval(($h - $textBoxHeight) / 2));
            imagestring($img, $fontSize, $x, $y, $text, $fg);

            if (imagepng($img, $bannerFile)) {
                fwrite(STDOUT, "  Created placeholder main.png for $dirName\n");
            } else {
                fwrite(STDERR, "  Failed to write main.png for $dirName\n");
            }
            imagedestroy($img);
        } else {
            // fallback: create a very small placeholder file to mark existence
            if (file_put_contents($bannerFile, '') !== false) {
                fwrite(STDOUT, "  Created empty main.png placeholder for $dirName (GD not available)\n");
            } else {
                fwrite(STDERR, "  Could not create main.png for $dirName (GD not available)\n");
            }
        }
    } else {
        fwrite(STDOUT, "  main.png exists for $dirName\n");
    }

    // create README.md to help editors
    $readme = $clubPath . DIRECTORY_SEPARATOR . 'README.md';
    if (!file_exists($readme)) {
        $content = "# " . ($default['name']) . "\n\nEdit `drawer.json` to update this club's details.\n";
        file_put_contents($readme, $content);
    }
}

fwrite(STDOUT, "Done.\n");
