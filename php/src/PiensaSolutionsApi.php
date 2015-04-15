<?php

/**
 * PiensaSolutions API class
 */
class PiensaSolutionsApi
{
	private $cookies = null;

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

	/**
	 * Attempt login.
	 * @param string $user
	 * @param string $password
	 * @return boolean Whether if the login attempt succeeded or not
	 */
	public function login($user, $password) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"https://secure.piensasolutions.com/cliente/default.asp");
		curl_setopt($ch, CURLOPT_POST, 3);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "email=$user&pass=$password");
		// Required by PiensaSolutions (strange protection)
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Get server response
		curl_setopt($ch, CURLOPT_HEADER, true);

		$out = curl_exec ($ch);
		$header  = curl_getinfo($ch);
		curl_close ($ch);

		if ($header['http_code'] == 200) {
			$header_content = substr($out, 0, $header['header_size']);
			$body_content = trim(str_replace($header_content, '', $rough_content));
			$pattern = "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m";
			preg_match_all($pattern, $header_content, $matches);
			$this->cookies = implode("; ", $matches['cookie']);
			return true;
		}

		return false;
	}

	/**
	 * Retrieve all domains for the active account. Previous call to login must be done.
	 * @return string[] List of domains
	 */
	public function getDomains() {
		if ($this->cookies === null) {
			throw new Exception('Login required!');
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,"https://secure.piensasolutions.com/cliente/servicios.asp");
		// Required by PiensaSolutions (strange protection)
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Get server response
		//curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_COOKIE, $this->cookies);

		$out = curl_exec ($ch);
		$header  = curl_getinfo($ch);
		curl_close ($ch);

		$domains = array();
		if ($header['http_code'] == 200) {
			// Remove parkings
			$out = preg_replace('#<form method="POST" action="https://pdc\.piensasolutions\.com/index\.php">(.*)</form>#Uis', '', $out);
			$pattern = '#<input type="hidden" name="dominio" value="(.+)">#m';
			preg_match_all($pattern, $out, $matches);
			foreach ($matches[1] as $domain) {
				$domains[] = $domain;
			}
		}
		return $domains;
	}

}

// Viking-style test
if (!count(debug_backtrace()))
{
	$api = new PiensaSolutionsApi();
	assert($api->checkDomain('akjsdhdkasddkjah', 'com') === true, 'Domain akjsdhdkasddkjah.com expected free');
	assert($api->checkDomain('google', 'com') === false, 'Domain google.com expected not-free');
	try {
		$api->checkDomain('google', 'nonexistent');
		assert(false, 'Expected exception was not thrown');
	} catch (Exception $ignored) {}
	assert($api->login("FAKE_LOGIN", "XXXXXXXXX") === false, 'Expected wrong login attempt');
	try {
		$api->getDomains();
		assert(false, 'Expected exception after wrong login attempt');
	} catch (Exception $ignored) {}
}

