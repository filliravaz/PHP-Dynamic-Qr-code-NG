<?php
/**
 * Function to generate random string using cryptographically secure random_int.
 * The function takes an integer n as input and generates a string by concatenating n characters chosen randomly from a domain.
 */
function randomString($n) {

	$generated_string = "";

	$domain = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";

	$len = strlen($domain);

	// Loop to create random string
	for ($i = 0; $i < $n; $i++) {
		$index = random_int(0, $len - 1);
		$generated_string .= $domain[$index];
	}

	return $generated_string;
}

/**
 * Generate cryptographically secure hex token
 */
function getSecureRandomToken($bytes = 16) {
	return bin2hex(random_bytes($bytes));
}

/**
 * Generate CSRF token and store it in session
 */
function getCsrfToken() {
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from request
 */
function validateCsrfToken($token = null) {
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}
	if ($token === null) {
		$token = $_POST['csrf_token'] ?? '';
	}
	if (
		empty($_SESSION['csrf_token']) ||
		!is_string($_SESSION['csrf_token']) ||
		!is_string($token) ||
		$token === '' ||
		!hash_equals($_SESSION['csrf_token'], $token)
	) {
		return false;
	}
	return true;
}

/**
 * Determine whether the current request is over HTTPS.
 * Derives HTTPS from FORCE_HTTPS or public BASE_URL, direct server detection,
 * and honors X-Forwarded-Proto only when the request originates from a configured trusted proxy.
 */
function isHttpsRequest() {
	// 1. Force HTTPS configuration
	if (defined('FORCE_HTTPS') && FORCE_HTTPS) {
		return true;
	}
	$forceHttps = getenv('FORCE_HTTPS');
	if ($forceHttps !== false && filter_var($forceHttps, FILTER_VALIDATE_BOOLEAN)) {
		return true;
	}

	// 2. Derive from public BASE_URL
	if (defined('BASE_URL') && is_string(BASE_URL) && stripos(BASE_URL, 'https://') === 0) {
		return true;
	}

	// 3. Direct server detection
	if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
		return true;
	}
	if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
		return true;
	}

	// 4. Honor X-Forwarded-Proto only from a configured trusted proxy
	$trustedProxies = defined('TRUSTED_PROXIES') ? TRUSTED_PROXIES : getenv('TRUSTED_PROXIES');
	if (!empty($trustedProxies) && is_string($trustedProxies)) {
		$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
		if ($clientIp !== '') {
			$proxyList = array_map('trim', explode(',', $trustedProxies));
			$isTrusted = false;
			foreach ($proxyList as $proxy) {
				if ($proxy === '*' || $proxy === $clientIp) {
					$isTrusted = true;
					break;
				}
				if (strpos($proxy, '/') !== false) {
					[$subnet, $bits] = explode('/', $proxy, 2);
					if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
						$ipLong = ip2long($clientIp);
						$subnetLong = ip2long($subnet);
						$mask = -1 << (32 - (int)$bits);
						if (($ipLong & $mask) === ($subnetLong & $mask)) {
							$isTrusted = true;
							break;
						}
					}
				}
			}
			if ($isTrusted && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
				$forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
				if ($forwardedProto === 'https') {
					return true;
				}
			}
		}
	}

	return false;
}

/**
 * Clear Auth Cookie
 */
function clearAuthCookie() {
	$isSecure = isHttpsRequest();
	$clearOptions = [
		'expires' => time() - 3600,
		'path' => '/',
		'secure' => $isSecure,
		'httponly' => true,
		'samesite' => 'Lax'
	];
	unset($_COOKIE['series_id']);
	unset($_COOKIE['remember_token']);
	setcookie('series_id', '', $clearOptions);
	setcookie('remember_token', '', $clearOptions);
}

/**
 *
 */
function clean_input($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
	return $data;
}

function paginationLinks($current_page, $total_pages, $base_url) {

	if ($total_pages <= 1) {
		return false;
	}

	$html = '';

	if (!empty($_GET)) {
		// We must unset $_GET[page] if previously built by http_build_query function
		unset($_GET['page']);
		// To keep the query sting parameters intact while navigating to next/prev page,
		$http_query = "?" . http_build_query($_GET);
	} else {
		$http_query = "?";
	}

	$html = '<ul class="pagination pagination-sm m-0 float-right">';

	if ($current_page == 1) {

		$html .= '<li class="page-item disabled"><a class="page-link">First</a></li>';
	} else {
		$html .= '<li class="page-item"><a class="page-link" href="' . $base_url . $http_query . '&page=1">First</a></li>';
	}

	// Show pagination links

	//var i = (Number(data.page) > 5 ? Number(data.page) - 4 : 1);

	if ($current_page > 5) {
		$i = $current_page - 4;
	} else {
		$i = 1;
	}

	for (; $i <= ($current_page + 4) && ($i <= $total_pages); $i++) {
		($current_page == $i) ? $li_class = ' class="active"' : $li_class = '';

		$link = $base_url . $http_query;

		$html = $html . '<li class="page-item"' . $li_class . '><a class="page-link" href="' . $link . '&page=' . $i . '">' . $i . '</a></li>';

		if ($i == $current_page + 4 && $i < $total_pages) {

			$html = $html . '<li class="page-item disabled"><a class="page-link">...</a></li>';

		}

	}

	if ($current_page == $total_pages) {
		$html .= '<li class="page-item disabled"><a class="page-link">Last</a></li>';
	} else {

		$html .= '<li class="page-item"><a class="page-link" href="' . $base_url . $http_query . '&page=' . $total_pages . '">Last</a></li>';
	}

	$html = $html . '</ul>';

	return $html;
}

function base_url() {
    require_once(__DIR__ . '/../config/environment.php');
    if (defined('BASE_URL') && BASE_URL !== null) {
        return BASE_URL;
    } else {
        return sprintf(
            "%s://%s:%s",
            isHttpsRequest() ? 'https' : 'http',
            $_SERVER['SERVER_NAME'] ?? 'localhost',
            $_SERVER['SERVER_PORT'] ?? '80'
        );
    }
}
