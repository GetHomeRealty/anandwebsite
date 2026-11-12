<?php
/**
 * GHR agent website: live MLS® listings relay
 * Place at  <agent-domain>/api/listings.php  (next to index.html).
 * Fetches active listings from gethomerealty.ca and returns a small, clean JSON
 * shape the agent page understands. Sold data is never requested (VOW rules).
 *
 * Query params:  city, status (For Sale|For Lease), type (Residential|Condo|Commercial),
 *                min, max, beds, page
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

const SOURCE    = 'https://www.gethomerealty.ca/get_freeholds';
const IMG_BASE  = 'https://cdn.agentroof.com/trreb/';
const CACHE_TTL = 300; // seconds

$status = in_array($_GET['status'] ?? '', ['For Sale', 'For Lease'], true) ? $_GET['status'] : 'For Sale';
$type   = in_array($_GET['type'] ?? '', ['Residential', 'Condo', 'Commercial'], true) ? $_GET['type'] : '';
$city   = substr(preg_replace('/[^\p{L}\s\.\'-]/u', '', $_GET['city'] ?? ''), 0, 60);
$page   = max(1, min(200, (int)($_GET['page'] ?? 1)));
$min    = (int)($_GET['min'] ?? 0);
$max    = (int)($_GET['max'] ?? 0);
$beds   = max(0, min(9, (int)($_GET['beds'] ?? 0)));

$q = [
  'page' => $page, 'soldproperty' => 'false', 'currentpage' => 'listing',
  'listing_status' => $status, 'search' => $city, 'search_type' => $city ? 'city' : '',
  'min_price' => $min ?: '', 'max_price' => $max ?: '', 'beds' => $beds ?: '',
  'sqft' => '', 'baths' => '', 'sort_by' => '', 'd_o_m' => '', 'building_style' => '',
  'ownership_type' => '', 'max_sqft' => '',
];
$url = SOURCE . '?' . http_build_query($q) . ($type ? '&property_type%5B%5D=' . rawurlencode($type) : '');

// small file cache so each agent site doesn't hammer gethomerealty.ca
$cacheFile = sys_get_temp_dir() . '/ghr_listings_' . md5($url) . '.json';
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
if ($code !== 200 || !isset($j['data']['data'])) {
  http_response_code(502);
  echo json_encode(['error' => 'Listings temporarily unavailable']); exit;
}

$items = [];
foreach ($j['data']['data'] as $x) {
  $dir = $x['images']['directory'] ?? '';
  $items[] = [
    'ml' => $x['ml_num'], 'addr' => $x['full_address'], 'city' => $x['city'], 'community' => $x['community'],
    'price' => (float)$x['listed_price'], 'bed' => $x['bed'], 'bedPlus' => $x['bed_plus'], 'bath' => $x['bath'],
    'sqft' => $x['sqft'], 'type' => $x['building_type'], 'status' => $x['sale_lease'], 'slug' => $x['slug'],
    'brokerage' => $x['brokerage'], 'openHouse' => (bool)$x['open_house'], 'added' => substr($x['added_ts'] ?? '', 0, 10),
    'img' => $dir ? $dir . 'Photo' . $x['ml_num'] . '-1.jpeg' : '',
  ];
}
$out = json_encode([
  'total' => $j['data']['total'] ?? count($items), 'page' => $j['data']['current_page'] ?? $page,
  'last_page' => $j['data']['last_page'] ?? 1, 'items' => $items,
]);
@file_put_contents($cacheFile, $out);
echo $out;
