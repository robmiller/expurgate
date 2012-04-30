<?php

// Calculates a checksum for a given URL; this checksum is used as a unique
// identifier for this image -- when caching, for example.
function calculate_checksum($url) {

}

// Reads a file from the store of cached images and outputs it to the browser,
// sending the appropriate headers.
function get_image_from_cache($checksum) {

}

// Fetches a remote image and stores it in the cache.
function fetch_image($url) {

}

// Stores an image in the cache.
function cache_image($image_data, $mime_type, $checksum) {

}

// Determines whether or not the cache needs purging -- if there are too many
// files, for example, or if they're too large in size.
function cache_is_full() {

}

// Removes one image from the cache. This means that, when the cache is full,
// we'll be adopting a one in, one out policy
function purge_cache() {

}