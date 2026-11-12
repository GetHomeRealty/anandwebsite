<?php
/**
 * GHR agent website: featured pre-construction projects relay
 * Place at  <agent-domain>/api/projects.php  (next to index.html).
 * Fetches GHR's featured pre-construction projects from gethomerealty.ca and
 * returns a small, clean JSON shape the agent page understands.
 *
 * Query params:  page (default 1), limit (1-20, default 9)
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=900');

const SOURCE    = 'https://www.gethomerealty.ca/get-featured-precondos';
const CACHE_TTL = 900; // seconds

$page  = max(1, min(50, (int)($_GET['page'] ?? 1)));
$limit = max(1, min(20, (int)($_GET['limit'] ?? 9)));
$url   = SOURCE . '?page=' . $page;

$cacheFile = sys_get_temp_dir() . '/ghr_projects_' . md5($url . '|' . $limit) . '.json';
if (is_file($cacheFile) && time() - filemtime($cacheFile) < CACHE_TTL) {
  readfile($cacheFile); exit;
}

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12, CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Requested-With: XMLHttpRequest'],
  CURLOPT_USERAGENT => 'GHR-Agent-Site/1.0',
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$j = json_decode($body ?: '', true);
if ($code !== 200 || !isset($j['data']) || !is_array($j['data'])) {
  http_response_code(502);
  echo json_encode(['error' => 'Projects temporarily unavailable']); exit;
}

$items = [];
foreach ($j['data'] as $x) {
  $im   = $x['images'] ?? null;
  $list = $im['images'] ?? [];
  if (is_string($list)) $list = array_filter(explode(',', $list));
  $img  = ($im && $list) ? $im['directory'] . 'Photo' . $im['ml_num'] . '-' . reset($list) . '.jpeg' : '';
  $items[] = [
    'name' => rtrim(trim($x['property_name'] ?? ''), '.'), 'addr' => $x['address'] ?? '', 'city' => $x['city'] ?? '',
    'builder' => $x['builder'] ?? '', 'type' => $x['building_type'] ?? '',
    'from' => $x['price_range_from'] ? (int)$x['price_range_from'] : null,
    'to' => $x['price_range_to'] ? (int)$x['price_range_to'] : null,
    'slug' => $x['slug'] ?? '', 'img' => $img,
  ];
  if (count($items) >= $limit) break;
}
$out = json_encode(['total' => $j['total'] ?? count($items), 'items' => $items]);
@file_put_contents($cacheFile, $out);
echo $out;
