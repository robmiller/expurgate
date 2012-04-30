# expurgate

Running an SSL site? Don't want mixed content warnings? Expurgate lets you serve external, non-HTTPS content over HTTPS!

## How it works

Server-side, you route all your HTTP calls to images to an HTTPS call to expurgate, which might look like:

	https://example.com/expurgate.php?checksum=foo&url=http://example.net/foo.jpg

Expurgate will then fetch http://example.net/foo.jpg and serve it over SSL — meaning that every request on your page is still encrypted, and your users see no mixed content warnings.

## What's this checksum?

So that not just anyone can request images — which would make you, in effect, a free image hosting service — the code calling expurgate is expected to generate a checksum to authenticate its request. This is an SHA-256 [HMAC][] value, based on a shared secret known by both the calling code and expurgate — but not by the viewer of the page.

[HMAC]: http://en.wikipedia.org/wiki/HMAC

So, to generate the checksum in the above example, the calling code would look like:

	<?php
	$url = 'http://example.net/foo.jpg';

	$key = file_get_contents('cache/key.txt');

	$checksum = hash_hmac('sha256', $url, $key);
	?>

	<img src="https://example.com/expurgate.php?checksum=<?php echo $checksum ?>&url=<?php echo urlencode($url) ?>" />

## Requirements

PHP >5.2.0 with hash functions.