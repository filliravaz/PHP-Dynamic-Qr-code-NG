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
 * Determine whether a given IP address matches configured TRUSTED_PROXIES.
 */
function isTrustedProxy($ip) {
	if (empty($ip) || !is_string($ip)) {
		return false;
	}
	$trustedProxies = defined('TRUSTED_PROXIES') ? TRUSTED_PROXIES : getenv('TRUSTED_PROXIES');
	if (empty($trustedProxies) || !is_string($trustedProxies)) {
		return false;
	}
	$proxyList = array_map('trim', explode(',', $trustedProxies));
	foreach ($proxyList as $proxy) {
		if ($proxy === '*' || $proxy === $ip) {
			return true;
		}
		if (strpos($proxy, '/') !== false) {
			[$subnet, $bits] = explode('/', $proxy, 2);
			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
				$ipLong = ip2long($ip);
				$subnetLong = ip2long($subnet);
				$mask = -1 << (32 - (int)$bits);
				if (($ipLong & $mask) === ($subnetLong & $mask)) {
					return true;
				}
			}
		}
	}
	return false;
}

/**
 * Retrieve the client IP address.
 * Trusts X-Forwarded-For only when the direct remote address is trusted,
 * selecting the first valid non-trusted address while otherwise returning the remote address.
 */
function getClientIp() {
	$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
	if (!isTrustedProxy($remoteAddr)) {
		return $remoteAddr;
	}

	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$forwarded = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
		foreach ($forwarded as $ip) {
			if (filter_var($ip, FILTER_VALIDATE_IP)) {
				if (!isTrustedProxy($ip)) {
					return $ip;
				}
			}
		}
	}

	return $remoteAddr;
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
	$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
	if ($remoteAddr !== '' && isTrustedProxy($remoteAddr) && !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
		$forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
		if ($forwardedProto === 'https') {
			return true;
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

/**
 * Check rate limit for authentication actions.
 * Limits by client IP (max 5 per 15 min) and by username (max 5 per 15 min).
 *
 * @param string|null $username
 * @param string $ip
 * @param int $maxAttempts
 * @param int $decayMinutes
 * @return bool True if allowed, false if rate limit exceeded.
 */
function checkLoginRateLimit($username, $ip, $maxAttempts = 5, $decayMinutes = 15) {
    if (function_exists('getDbInstance')) {
        $db = getDbInstance();
        $cutoff = date('Y-m-d H:i:s', time() - ($decayMinutes * 60));

        $createTableAndRetry = function() use ($db) {
            $tableName = DATABASE_PREFIX . 'login_attempts';
            $db->rawQuery("CREATE TABLE IF NOT EXISTS `{$tableName}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `ip_address` varchar(45) NOT NULL,
                `username` varchar(50) NOT NULL,
                `attempted_at` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_ip_time` (`ip_address`, `attempted_at`),
                KEY `idx_user_time` (`username`, `attempted_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        };

        $isTableMissing = function(\Throwable $e, $db) {
            $msg = strtolower($e->getMessage());
            $dbErr = strtolower((string)$db->getLastError());
            return (
                strpos($msg, "doesn't exist") !== false ||
                strpos($msg, "unknown table") !== false ||
                strpos($dbErr, "doesn't exist") !== false ||
                strpos($dbErr, "unknown table") !== false ||
                (method_exists($e, 'getCode') && (int)$e->getCode() === 1146)
            );
        };

        try {
            $ipAttempts = null;
            try {
                // Count attempts by IP
                $db->where('ip_address', $ip);
                $db->where('attempted_at >= ?', [$cutoff]);
                $ipAttempts = $db->getValue('login_attempts', 'count(*)');
            } catch (\Throwable $e) {
                // Catch missing-table exception, create table and retry query
                if ($isTableMissing($e, $db)) {
                    $createTableAndRetry();
                    $db->where('ip_address', $ip);
                    $db->where('attempted_at >= ?', [$cutoff]);
                    $ipAttempts = $db->getValue('login_attempts', 'count(*)');
                } else {
                    throw $e;
                }
            }

            // Retain existing null-result fallback if reachable without throwing
            if ($ipAttempts === null && strpos(strtolower((string)$db->getLastError()), "doesn't exist") !== false) {
                $createTableAndRetry();
                $db->where('ip_address', $ip);
                $db->where('attempted_at >= ?', [$cutoff]);
                $ipAttempts = $db->getValue('login_attempts', 'count(*)');
            }

            if ($ipAttempts !== null && (int)$ipAttempts >= $maxAttempts) {
                return false;
            }

            // Count attempts by username
            if (!empty($username)) {
                $db->where('username', $username);
                $db->where('attempted_at >= ?', [$cutoff]);
                $userAttempts = $db->getValue('login_attempts', 'count(*)');
                if ($userAttempts !== null && (int)$userAttempts >= $maxAttempts) {
                    return false;
                }
            }

            return true;
        } catch (\Throwable $e) {
            error_log('checkLoginRateLimit storage failure: ' . $e->getMessage());
            // Fail closed whenever persistent storage is unavailable
            return false;
        }
    }

    // Fail closed if database service is unavailable
    return false;
}

/**
 * Record a failed authentication attempt.
 */
function recordLoginAttempt($username, $ip) {
    if (function_exists('getDbInstance')) {
        try {
            $db = getDbInstance();
            $db->insert('login_attempts', [
                'ip_address' => $ip,
                'username' => (string)$username,
                'attempted_at' => date('Y-m-d H:i:s')
            ]);

            // Periodic cleanup of attempts older than 24 hours
            if (random_int(1, 20) === 1) {
                $cutoff = date('Y-m-d H:i:s', time() - 86400);
                $db->where('attempted_at < ?', [$cutoff]);
                $db->delete('login_attempts');
            }
        } catch (\Throwable $e) {
            error_log('recordLoginAttempt storage failure: ' . $e->getMessage());
        }
    }
}

/**
 * Clear recorded attempts on successful authentication.
 */
function clearLoginAttempts($username, $ip = null) {
    if (empty($username) || !is_string($username) || trim($username) === '') {
        return;
    }

    if (function_exists('getDbInstance')) {
        try {
            $db = getDbInstance();
            $db->where('username', trim($username));
            $db->delete('login_attempts');
        } catch (\Throwable $e) {
            error_log('clearLoginAttempts storage failure: ' . $e->getMessage());
        }
    }
}

/**
 * Safely parse and render static QR code display content without brittle regex tag stripping (SEC-09).
 */
function renderSafeStaticContent($rawContent) {
    if ($rawContent === null || $rawContent === '') {
        return '';
    }

    // Check if stored as JSON
    $decoded = json_decode((string)$rawContent, true);
    if (is_array($decoded)) {
        $html = '';
        foreach ($decoded as $key => $val) {
            $html .= '<strong>' . htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') . ':</strong> ' . htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') . '<br>';
        }
        return $html;
    }

    if (!class_exists('DOMDocument')) {
        return htmlspecialchars((string)$rawContent, ENT_QUOTES, 'UTF-8');
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    // Wrap in standard root node and load HTML
    $dom->loadHTML('<?xml encoding="utf-8"?><div>' . (string)$rawContent . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $allowedTags = ['strong', 'b', 'br', 'div', 'span', 'p'];
    $allowedClasses = ['row', 'col-sm-4', 'col-sm-6', 'col-sm-12'];

    $cleanNode = function($node, &$cleanNode) use ($allowedTags, $allowedClasses) {
        if ($node->nodeType === XML_ELEMENT_NODE) {
            $tagName = strtolower($node->nodeName);
            if (!in_array($tagName, $allowedTags, true)) {
                // Replace disallowed elements with their safe text content
                $textNode = $node->ownerDocument->createTextNode($node->textContent);
                $node->parentNode->replaceChild($textNode, $node);
                return;
            }

            // Strip all attributes, preserving only strictly whitelisted structural class names
            $class = $node->getAttribute('class');
            while ($node->attributes->length > 0) {
                $node->removeAttribute($node->attributes->item(0)->name);
            }
            if ($class !== '') {
                $classParts = array_filter(explode(' ', $class));
                $safeClasses = array_intersect($classParts, $allowedClasses);
                if (!empty($safeClasses)) {
                    $node->setAttribute('class', implode(' ', $safeClasses));
                }
            }
        }

        // Copy child nodes list as node replacement mutates live list
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            $cleanNode($child, $cleanNode);
        }
    };

    $root = $dom->getElementsByTagName('div')->item(0);
    if ($root) {
        $cleanNode($root, $cleanNode);
        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }
        return $result;
    }

    return htmlspecialchars((string)$rawContent, ENT_QUOTES, 'UTF-8');
}
