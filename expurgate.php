<?php

define('CACHE_DIR', dirname(__FILE__) . '/cache');

define('MAX_AGE', 3600 * 24 * 7);
define('MAX_CACHE_SIZE', 100 * 1024 * 1024);
define('MAX_CACHE_FILES', 1024);

define('MAX_IMAGE_SIZE', 500 * 1024);

// When you want to fetch an image and don't care where it comes from, use 
// this. It'll fetch the image from the cache if it's cached, and from the 
// remote server if not.
function get_image($url) {
	$checksum = calculate_checksum($url);

	if ( is_cached($checksum) ) {
		return get_image_from_cache($checksum);
	}

	fetch_image($url);

	return get_image_from_cache($checksum);
}

// Calculates a checksum for a given URL; this checksum is used as a unique
// identifier for this image -- when caching, for example.
function calculate_checksum($url) {
	$key = get_key();

	return hash_hmac('sha256', $url, $key);
}

// Given a URL and a checksum, will return true if the checksum is the valid 
// one for that URL.
function validate_checksum($url, $checksum) {
	$valid_checksum = calculate_checksum($url);

	return $valid_checksum === $checksum;
}

// If a file called key.txt exists in the cache directory, its contents will
// be used as a key when generating checksums.
function get_key() {
	$key_file = CACHE_DIR . '/key.txt';

	if ( is_readable($key_file) ) {
		return file_get_contents($key_file);
	}

	die("I can't find a key! I won't work without one.");
}

// Given a checksum, will return an absolute file path to the cache entry
// with that checksum (whether or not it exists).
function get_cache_filename($checksum) {
	return CACHE_DIR . "/$checksum.cache.txt";
}

// Checks whether a cached version of the image already exists.
function is_cached($checksum) {
	$cache_file = get_cache_filename($checksum);

	$exists = file_exists($cache_file);
	$is_readable = is_readable($cache_file);

	return $exists && $is_readable;
}

// Reads a file from the store of cached images and outputs it to the browser,
// sending the appropriate headers.
function get_image_from_cache($checksum) {
	$cache_file = get_cache_filename($checksum);

	if ( !is_cached($checksum) ) {
		error('Problem with cache image.');
	}

	$cache_entry = json_decode(file_get_contents($cache_file));

	if ( empty($cache_entry->mime_type) || empty($cache_entry->image_data) ) {
		error('Problem with cache image.');
	}

	$image_data = base64_decode($cache_entry->image_data);

	header('Content-Type: ' . $cache_entry->mime_type);
	echo $image_data;
}

// Fetches a remote image and stores it in the cache.
function fetch_image($url) {
	if ( function_exists('curl_init') ) {
		$c = curl_init();

		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HEADER, 0);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($c, CURLOPT_MAXREDIRS, 5);
		curl_setopt($c, CURLOPT_TIMEOUT, 10);

		$image_data = curl_exec($c);
		$mime_type  = curl_getinfo($c, CURLINFO_CONTENT_TYPE);

		$image_size = curl_getinfo($c, CURLINFO_SIZE_DOWNLOAD);

		curl_close($c);
	}

	// TODO: fallback for non-cURL-enabled servers

	if ( $image_size > MAX_IMAGE_SIZE ) {
		error('Image is too large.');
	}

	if ( empty($mime_type) || !preg_match('/^image\//', $mime_type) ) {
		error('Invalid image type.');
	}

	if ( empty($image_data) ) {
		error('Invalid image content.');
	}

	$checksum = calculate_checksum($url);

	cache_image($image_data, $mime_type, $checksum);
}

// Stores an image in the cache.
function cache_image($image_data, $mime_type, $checksum) {
	$cache_file = get_cache_filename($checksum);

	$image_data = base64_encode($image_data);

	$cache_entry = json_encode(compact('image_data', 'mime_type'));

	return file_put_contents($cache_file, $cache_entry);
}

// Determines whether or not the cache needs purging -- if there are too many
// files, for example, or if they're too large in size.
function cache_is_full() {
	$cache_files = get_cache_files();

	$too_many_files = ( count($cache_files) > MAX_CACHE_FILES );

	if ( $too_many_files ) {
		return true;
	}

	$cache_size = array_reduce(
		$cache_files,
		function($size, $cache) {
			$size += $cache->size;
			return $size;
		}
	);

	$too_big = ( $cache_size > MAX_CACHE_SIZE );

	if ( $too_big ) {
		return true;
	}
}

// Removes one image from the cache. This means that, when the cache is full,
// we'll be adopting a one in, one out policy
function purge_cache() {
	$cache_files = get_cache_files();

	// For now, select a file at random.
	$to_delete = $cache_files[array_rand($cache_files)];

	unlink(CACHE_DIR . '/' . $to_delete->filename);
}

// Removes expired entries from the cache.
function purge_expired() {
	$cache_files = get_cache_files();

	array_walk(
		$cache_files,
		function($cache) {
			if ( $cache->modified + MAX_AGE < time() ) {
				unlink(CACHE_DIR . '/' . $cache->filename);
			}
		}
	);
}

// Returns a list of all the cached files.
function get_cache_files() {
	$files = glob(CACHE_DIR . '/*.cache.txt');

	$cache_files = array();
	foreach ( (array) $files as $file ) {
		$cache_files[] = (object) array(
			'filename' => basename($file),
			'modified' => filemtime($file),
			'size'     => filesize($file)
		);
	}

	return $cache_files;
}

function error($message) {
	header('HTTP/1.0 404 Not Found');
	header('Status: 404 Not Found');
	die($message);
}

if ( empty($_GET['url']) || empty($_GET['checksum']) ) {
	error('Could not find image.');
}

$url      = $_GET['url'];
$checksum = $_GET['checksum'];

$is_valid = validate_checksum($url, $checksum);

if ( !$is_valid ) {
	error('Could not find image.');
}

get_image($url);

purge_expired();
