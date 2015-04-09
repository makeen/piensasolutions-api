<?php

/**
 * PiensaSolutions API class
 */
class PiensaSolutionsApi
{
	/**
	 * Check domain status.
	 * @param string $domain
	 * @return boolean Whether if the domain is free or not
	 */
	public function checkDomain($domain, $extension)
	{
		if (!in_array($extension, array('com', 'net', 'es', 'org'))) throw new Exception("Invalid domain extension: $extension");
		$domain = urlencode($domain);
		$content = @file_get_contents("https://shop.piensasolutions.com/mainsearchxml.php?sld=$domain&$extension=1&_=" . time());
		$xml = simplexml_load_string($content);
		$json = json_encode($xml);
		$array = json_decode($json,TRUE);
		if ($array !== false and isset($array['dom_search_results']['domain']['tld']['status'])) {
			return $array['dom_search_results']['domain']['tld']['status'] === "free";
		}
		return false;
	}
}

// Viking-style test
if (!count(debug_backtrace()))
{
	$api = new PiensaSolutionsApi();
	assert($api->checkDomain('akjsdhdkasddkjah', 'com') === true, 'Domain akjsdhdkasddkjah.com expected free');
	assert($api->checkDomain('google', 'com') === false, 'Domain google.com expected not-free');
}

