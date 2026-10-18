<?php
// Icons: the app uses only the SVG icon collection (no Font Awesome, no emoji).   php tests/icons.php   (no database needed)
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Helpers/helpers.php';
require_once BASE_PATH . '/app/Helpers/Icon.php';
use App\Helpers\Icon;

$ok = 0; $bad = 0;
function check(string $n, $c, $d = '') { global $ok, $bad; if ($c) { $ok++; echo "  PASS $n\n"; } else { $bad++; echo "  FAIL $n  $d\n"; } }

/** every source file we ship (not vendor / uploads / generated icon files) */
function sources(): array {
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(BASE_PATH, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $p = $f->getPathname();
        if (preg_match('#/(vendor|uploads|storage|backups|\.git|public/icons)/#', $p)) continue;
        if (!preg_match('/\.(php|js|css|sql|md|sh|yml)$/', $p)) continue;
        if (str_ends_with($p, '/public/js/icons.js') || str_ends_with($p, '/app/Helpers/Icon.php') || str_ends_with($p, '/tests/icons.php')) continue;
        $out[] = $p;
    }
    return $out;
}

$names = Icon::names();
check('icon collection is not empty', count($names) > 100, count($names) . ' icons');

// 1. every icon file is a single well-formed <svg> with a viewBox
$badFiles = [];
foreach (['ui', 'social'] as $g) foreach (glob(Icon::dir() . "/$g/*.svg") as $f) {
    $s = file_get_contents($f);
    if (!preg_match('/^<svg\b[^>]*viewBox="[^"]+"[^>]*>.+<\/svg>\s*$/s', $s) || substr_count($s, '<svg') !== 1) $badFiles[] = "$g/" . basename($f);
}
check('every icon file is a well-formed single <svg> with a viewBox', !$badFiles, implode(', ', $badFiles));

// 2. rendering works for every icon, and unknown names fall back instead of breaking the page
$renderBad = array_filter($names, fn($n) => !str_contains(icon($n), 'class="ic ic-' . $n));
check('icon() renders every icon', !$renderBad, implode(', ', $renderBad));
check('unknown icon name falls back to a circle', str_contains(icon('does-not-exist'), 'ic-circle'));
check('class/style are escaped', !str_contains(icon('check', 'a"b', 'x:"y"'), '"b"'));

// 3. old stored Font Awesome names still resolve, and all targets exist
$flip = array_flip($names);
$missing = array_filter(Icon::legacyMap(), fn($v) => !isset($flip[$v]));
check('legacy name map only points at existing icons', !$missing, json_encode($missing));
check('old stored name "fa-solid fa-xmark" resolves to close', Icon::resolve('fa-solid fa-xmark') === 'close');

// 4. icons.js is in sync with the collection
$js = (string)@file_get_contents(BASE_PATH . '/public/js/icons.js');
preg_match('/^\s*var MAP = (.*);$/m', $js, $m);
$map = $m ? json_decode($m[1], true) : null;
check('public/js/icons.js lists every icon (run php bin/build-icons.php if this fails)', is_array($map) && count($map) === count($names) && !array_diff($names, array_keys($map)),
      is_array($map) ? (count($map) . ' in js, ' . count($names) . ' on disk') : 'could not parse');

// 5. no Font Awesome and no emoji left in the code base; static icon names exist
$fa = []; $emoji = []; $unknown = [];
foreach (sources() as $p) {
    $t = file_get_contents($p);
    $rel = substr($p, strlen(BASE_PATH) + 1);
    if (preg_match('/fontawesome|font-awesome|\bfa-(solid|regular|brands)\b|(?<![A-Za-z0-9])fa-[a-z]{3,}/i', $t)) $fa[] = $rel;
    if (preg_match('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}]/u', $t)) $emoji[] = $rel;
    if (preg_match_all('/\bicon\(\s*\'([a-z0-9-]+)\'/', $t, $mm)) foreach ($mm[1] as $n) if (!isset($flip[$n])) $unknown[] = "$rel: $n";
    if (preg_match_all('/Icons\.(?:svg|set)\((?:[^,()]+,\s*)?["\']([a-z0-9-]+)["\']/', $t, $mm)) foreach ($mm[1] as $n) if (!isset($flip[$n]) && !str_starts_with($n, 'ic-')) $unknown[] = "$rel: $n";
}
check('no Font Awesome references remain', !$fa, implode(', ', $fa));
check('no emoji remain', !$emoji, implode(', ', $emoji));
check('every icon name used in views/JS exists in the collection', !$unknown, implode('; ', $unknown));

echo "\n== $ok passed, $bad failed ==\n";
exit($bad ? 1 : 0);
