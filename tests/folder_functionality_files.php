<?php
// Run with: php tests/folder_functionality_files.php
// Load production functions without bootstrapping a platform or a live drive.
$wanted = ['folder_functionality_files', 'headandfoot', 'get_content', 'path_format', 'splitfirst'];
$tokens = token_get_all(file_get_contents(__DIR__ . '/../common.php'));
for ($i = 0; $i < count($tokens); $i++) {
    if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
    $j = $i + 1;
    while (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
    if (!is_array($tokens[$j]) || !in_array($tokens[$j][1], $wanted)) continue;
    $source = '';
    $depth = 0;
    $started = false;
    do {
        $token = $tokens[$i++];
        $source .= is_array($token) ? $token[1] : $token;
        if ($token === '{') { $depth++; $started = true; }
        if ($token === '}') $depth--;
    } while (!$started || $depth > 0);
    $i--;
    eval($source);
}
function getConfig($key) { return $GLOBALS['config'][$key] ?? ''; }
function getcache($key) { return $GLOBALS['cache'][$key] ?? false; }
function savecache($key, $value) { $GLOBALS['cache'][$key] = $value; }
function curl($method, $url, ...$args) { return ['stat' => 200, 'body' => 'Global README']; }
function check($condition, $message) {
    if (!$condition) throw new Exception($message);
}
class TestDrive {
    public $calls = [];
    public $files = [];
    public function list_files($path) {
        $this->calls[] = $path;
        return $this->files[$path] ?? ['error' => ['stat' => 404]];
    }
}
$drive = new TestDrive();
$_SERVER['list_path'] = '/Public';
$folder = ['type' => 'folder', 'childcount' => 206, 'list' => []];
$readme = ['type' => 'file', 'name' => 'README.md', 'content' => ['stat' => 200, 'body' => '# Local README']];
$drive->files['/Public/phone/readme.md'] = $readme;
$resolved = folder_functionality_files('/phone', $folder);
check($resolved['list']['readme.md'] === $readme, 'README on another page must be found');
check(count($drive->calls) === 4, 'Each missing decoration is queried once');
foreach (['<!--ReadmemdStart--><div><!--ReadmemdContent--></div><!--ReadmemdEnd-->', '/*--ReadmemdStart--*/<div>/*--ReadmemdContent--*/</div>/*--ReadmemdEnd--*/'] as $template) {
    $html = 'prefix' . $template . 'suffix';
    headandfoot($html, 'Readmemd', '/phone', $resolved, 'readme.md', 'globalReadmeMdUrl');
    check($html === 'prefix<div># Local README</div>suffix', 'Both theme marker styles must render local README');
}
check(count($drive->calls) === 4, 'Rendering must reuse the fetched content');
$drive->calls = [];
folder_functionality_files('/phone', ['type' => 'folder', 'childcount' => 200, 'list' => []]);
folder_functionality_files('/phone', ['type' => 'file']);
check(!$drive->calls, 'Unpaginated folders and file previews must not add requests');
$all = $folder;
foreach (['head.md', 'readme.md', 'head.omf', 'foot.omf'] as $name) $all['list'][$name] = $readme;
folder_functionality_files('/phone', $all);
check(!$drive->calls, 'Files already in the current page must not be probed');
$drive->files = ['/Public/phone/readme.md' => ['type' => 'file', 'content' => ['stat' => 403, 'body' => 'Denied']]];
$missing = folder_functionality_files('/phone', $folder);
check(!$missing['list'], 'Failed reads must not become decorations');
$html = 'prefix<!--ReadmemdStart--><div><!--ReadmemdContent--></div><!--ReadmemdEnd-->suffix';
headandfoot($html, 'Readmemd', '/phone', $missing, 'readme.md', 'globalReadmeMdUrl');
check($html === 'prefixsuffix', 'Missing README without a global fallback must disappear');
$config['globalReadmeMdUrl'] = 'https://example.test/readme.md';
$html = 'prefix<!--ReadmemdStart--><div><!--ReadmemdContent--></div><!--ReadmemdEnd-->suffix';
headandfoot($html, 'Readmemd', '/phone', $missing, 'readme.md', 'globalReadmeMdUrl');
check($html === 'prefix<div>Global README</div>suffix', 'Global fallback must remain available');
$html = 'prefix<!--ReadmemdStart--><div><!--ReadmemdContent--></div><!--ReadmemdEnd-->suffix';
headandfoot($html, 'Readmemd', '/phone', $resolved, 'readme.md', 'globalReadmeMdUrl');
check($html === 'prefix<div># Local README</div>suffix', 'Local README must override global fallback');
echo "All folder decoration regression checks passed.\n";
