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
 * Regenerate CSRF token in session
 */
function regenerateCsrfToken() {
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	return $_SESSION['csrf_token'];
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
 * Validate CSRF token from request and optionally rotate it (defaults to true)
 */
function validateCsrfToken($token = null, $rotate = true) {
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
	if ($rotate) {
		regenerateCsrfToken();
	}
	return true;
}

/**
 * Determine whether a given IP address matches configured TRUSTED_PROXIES.
 *
 * Checks explicit IP matches, wildcard '*', and CIDR subnets. Handles edge bitshifts
 * correctly on 64-bit PHP architectures (SEC-03).
 *
 * @param string|null $ip The IP address to test.
 * @return bool True if the IP matches a configured trusted proxy, false otherwise.
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
				$bitNum = (int)$bits;
				if ($bitNum === 0) {
					return true;
				}
				if ($bitNum > 0 && $bitNum <= 32) {
					$ipLong = ip2long($ip);
					$subnetLong = ip2long($subnet);
					$mask = (-1 << (32 - $bitNum)) & 0xFFFFFFFF;
					if (($ipLong & $mask) === ($subnetLong & $mask)) {
						return true;
					}
				}
			}
		}
	}
	return false;
}

/**
 * Retrieve the verified client IP address.
 *
 * Trusts X-Forwarded-For only when the direct remote address is a configured trusted proxy.
 * Traverses forwarded IP entries from right to left (most recent proxy backwards),
 * discarding trusted proxies until reaching the first untrusted upstream IP to prevent
 * header spoofing (SEC-02).
 *
 * @return string The resolved client IP address.
 */
function getClientIp() {
	$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
	if (!isTrustedProxy($remoteAddr)) {
		return $remoteAddr;
	}

	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$forwarded = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
		$reversed = array_reverse($forwarded);
		foreach ($reversed as $ip) {
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
 * Sanitize and clean raw input string data.
 *
 * @param string $data The raw input string.
 * @return string The sanitized string safe for output.
 */
function clean_input($data) {
	$data = trim($data);
	$data = stripslashes($data);
	$data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
	return $data;
}

/**
 * Generate Bootstrap pagination HTML markup for table views.
 *
 * Preserves allowed search and sorting query parameters across page changes
 * and applies proper active class styling (BUG-04).
 *
 * @param int $current_page The current active page number.
 * @param int $total_pages The total number of pages available.
 * @param string $base_url Target URL for pagination links.
 * @return string|false HTML markup string, or false if total pages <= 1.
 */
function paginationLinks($current_page, $total_pages, $base_url) {

	if ($total_pages <= 1) {
		return false;
	}

	$html = '';

	$allowedParams = ['search_str', 'order_by', 'order_dir'];
	$queryParams = [];
	foreach ($allowedParams as $param) {
		if (isset($_GET[$param]) && is_scalar($_GET[$param]) && (string)$_GET[$param] !== '') {
			$queryParams[$param] = (string)$_GET[$param];
		}
	}

	$buildUrl = function($pageNum) use ($base_url, $queryParams) {
		$params = array_merge($queryParams, ['page' => $pageNum]);
		return htmlspecialchars($base_url . '?' . http_build_query($params), ENT_QUOTES, 'UTF-8');
	};

	$html = '<ul class="pagination pagination-sm m-0 float-right">';

	if ($current_page == 1) {
		$html .= '<li class="page-item disabled"><a class="page-link">First</a></li>';
	} else {
		$html .= '<li class="page-item"><a class="page-link" href="' . $buildUrl(1) . '">First</a></li>';
	}

	if ($current_page > 5) {
		$i = $current_page - 4;
	} else {
		$i = 1;
	}

	for (; $i <= ($current_page + 4) && ($i <= $total_pages); $i++) {
		$li_class = ((int)$current_page === (int)$i) ? ' active' : '';

		$html .= '<li class="page-item' . $li_class . '"><a class="page-link" href="' . $buildUrl($i) . '">' . $i . '</a></li>';

		if ($i == $current_page + 4 && $i < $total_pages) {
			$html .= '<li class="page-item disabled"><a class="page-link">...</a></li>';
		}
	}

	if ($current_page == $total_pages) {
		$html .= '<li class="page-item disabled"><a class="page-link">Last</a></li>';
	} else {
		$html .= '<li class="page-item"><a class="page-link" href="' . $buildUrl($total_pages) . '">Last</a></li>';
	}

	$html .= '</ul>';

	return $html;
}

/**
 * Retrieve the application base URL from configuration or environment.
 *
 * @return string The base URL string.
 */
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
 * Check rate limit for authentication actions (SEC-01).
 *
 * Limits by composite (username, client IP) to prevent unauthenticated global
 * account lockouts, while also enforcing an overall per-IP threshold to deter
 * distributed dictionary attacks.
 *
 * @param string|null $username The username attempting login.
 * @param string $ip The requesting client IP address.
 * @param int $maxAttempts Maximum allowed failed attempts before locking out the composite pair.
 * @param int $decayMinutes Window duration in minutes for counting failed attempts.
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
                KEY `idx_user_time` (`username`, `attempted_at`),
                KEY `idx_user_ip_time` (`username`, `ip_address`, `attempted_at`)
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

            $maxIpThreshold = max(15, $maxAttempts * 3);
            if ($ipAttempts !== null && (int)$ipAttempts >= $maxIpThreshold) {
                return false;
            }

            // Count attempts by composite (username, IP) to prevent cross-IP account lockout (SEC-01)
            if (!empty($username)) {
                $db->where('username', $username);
                $db->where('ip_address', $ip);
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
 * Record an authentication attempt. Returns true on successful insert, false on failure.
 *
 * @param string $username The username attempted.
 * @param string $ip The client IP address.
 * @return bool True if successfully recorded, false on failure.
 */
function recordLoginAttempt($username, $ip) {
    if (!function_exists('getDbInstance')) {
        return false;
    }

    try {
        $db = getDbInstance();
        if (!$db) {
            return false;
        }

        $insertResult = $db->insert('login_attempts', [
            'ip_address' => $ip,
            'username' => (string)$username,
            'attempted_at' => date('Y-m-d H:i:s')
        ]);

        if ($insertResult === false) {
            return false;
        }

        // Periodic cleanup of attempts older than 24 hours
        try {
            if (random_int(1, 20) === 1) {
                $cutoff = date('Y-m-d H:i:s', time() - 86400);
                $db->where('attempted_at < ?', [$cutoff]);
                $db->delete('login_attempts');
            }
        } catch (\Throwable $cleanupEx) {
            error_log('recordLoginAttempt cleanup failure: ' . $cleanupEx->getMessage());
            // Cleanup failure does not change the successful insert result
        }

        return true;
    } catch (\Throwable $e) {
        error_log('recordLoginAttempt storage failure: ' . $e->getMessage());
        return false;
    }
}

/**
 * Clear recorded attempts on successful authentication (SEC-01).
 *
 * Only deletes attempts matching both username AND client IP to prevent an
 * attacker from clearing rate-limit records for other IP addresses.
 *
 * @param string|null $username The authenticated username.
 * @param string|null $ip The requesting client IP address.
 * @return void
 */
function clearLoginAttempts($username, $ip = null) {
    $normUser = (is_string($username) && trim($username) !== '') ? trim($username) : null;
    $normIp = (is_string($ip) && trim($ip) !== '') ? trim($ip) : null;

    if ($normUser === null && $normIp === null) {
        return;
    }

    if (function_exists('getDbInstance')) {
        try {
            $db = getDbInstance();
            if ($normUser !== null && $normIp !== null) {
                $db->where('username', $normUser);
                $db->where('ip_address', $normIp);
                $db->delete('login_attempts');
            } elseif ($normUser !== null) {
                $db->where('username', $normUser);
                $db->delete('login_attempts');
            } elseif ($normIp !== null) {
                $db->where('ip_address', $normIp);
                $db->delete('login_attempts');
            }
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

/**
 * Retrieve dynamic application version and release channel information.
 *
 * Channel detection hierarchy:
 * 1. Environment variables: APP_CHANNEL, APP_VERSION, APP_COMMIT
 * 2. version.json file located in BASE_PATH
 * 3. Git metadata inspection if .git repository exists
 * 4. Fallback defaults: LTS / 2.3.0
 *
 * @return array{channel: string, is_nightly: bool, version: string, commit: string, tag: string}
 */
function getAppVersionInfo(): array {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $channel = getenv('APP_CHANNEL') ?: '';
    $version = getenv('APP_VERSION') ?: '';
    $commit  = getenv('APP_COMMIT') ?: '';

    // 1. Try reading from version.json if available
    $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);
    $versionFile = $basePath . '/version.json';
    if (file_exists($versionFile)) {
        $json = @json_decode(file_get_contents($versionFile), true);
        if (is_array($json)) {
            if ($channel === '' && !empty($json['channel'])) {
                $channel = (string)$json['channel'];
            }
            if ($version === '' && !empty($json['version'])) {
                $version = (string)$json['version'];
            }
            if ($commit === '' && !empty($json['commit'])) {
                $commit = (string)$json['commit'];
            }
        }
    }

    // 2. Git inspection fallback when running in a live development git working tree
    $isDevEnv = defined('TYPE') && in_array(strtolower(TYPE), ['dev', 'development', 'debug'], true);
    if ($isDevEnv && ($commit === '' || $channel === '' || $version === '') && function_exists('shell_exec') && is_dir($basePath . '/../.git')) {
        $repoRoot = realpath($basePath . '/..');
        if ($repoRoot) {
            // Check if current HEAD matches an exact release tag
            if ($version === '' || $channel === '') {
                $tagOutput = @shell_exec('git -C ' . escapeshellarg($repoRoot) . ' describe --tags --exact-match 2>/dev/null');
                if (is_string($tagOutput) && trim($tagOutput) !== '') {
                    if ($version === '') {
                        $version = ltrim(trim($tagOutput), 'v');
                    }
                    if ($channel === '') {
                        $channel = 'LTS';
                    }
                }
            }

            // Get current short commit
            if ($commit === '') {
                $commitOutput = @shell_exec('git -C ' . escapeshellarg($repoRoot) . ' rev-parse --short HEAD 2>/dev/null');
                if (is_string($commitOutput) && trim($commitOutput) !== '') {
                    $commit = trim($commitOutput);
                }
            }
        }
    }

    // 3. Fallbacks and normalization
    if ($commit === '' && $version !== '' && preg_match('/^[0-9a-f]{7,40}$/i', $version)) {
        $commit = substr($version, 0, 7);
    }

    // Determine channel
    $isNightly = false;
    if (strcasecmp($channel, 'nightly') === 0 || stripos($version, 'nightly') !== false) {
        $isNightly = true;
        $channel = 'Nightly';
    } elseif (strcasecmp($channel, 'lts') === 0 || ($version !== '' && preg_match('/^\d+\.\d+(\.\d+)?/', $version))) {
        $isNightly = false;
        $channel = 'LTS';
    } else {
        // If we have a commit hash without an LTS semver version, it's Nightly
        if ($commit !== '' && ($version === '' || $version === $commit)) {
            $isNightly = true;
            $channel = 'Nightly';
        } else {
            $isNightly = false;
            $channel = 'LTS';
        }
    }

    if ($isNightly) {
        if ($commit === '' && $version !== '') {
            $commit = $version;
        }
        $tag = $commit !== '' ? $commit : 'nightly';
    } else {
        if ($version === '') {
            $version = '2.3.0';
        }
        $tag = $version;
    }

    $cached = [
        'channel'    => $channel,
        'is_nightly' => $isNightly,
        'version'    => $version,
        'commit'     => $commit,
        'tag'        => $tag,
    ];

    return $cached;
}
