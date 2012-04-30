# expurgate

Running an SSL site? Don't want mixed content warnings? Expurgate lets you serve external, non-HTTPS content over HTTPS!

## How it works

On your HTTPS pages, instead of requesting an image over HTTP — which would throw up a mixed content warning in the user's browser — you change the link server-side so that it's passed through expurgate instead. So, instead of:

	<img src="http://example.net/foo.jpg">

You'd request:

	<img src="https://example.com/expurgate.php?checksum=foo&url=http://example.net/foo.jpg">

Expurgate will then fetch http://example.net/foo.jpg and serve it over SSL — meaning that every request on your page is still encrypted, and your users see no mixed content warnings.

## Use WordPress?

If you use WordPress, there’s a plugin that will convert all of your in-post images for you: [wp-expurgate][].

[wp-expurgate]: https://github.com/robmiller/wp-expurgate

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

PHP >= v5.3.0 with hash functions.
