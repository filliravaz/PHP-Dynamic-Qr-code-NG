<?php
require_once 'config/config.php';
require_once BASE_PATH . '/lib/ICS/ICS.php';
require_once BASE_PATH . '/lib/vCard/vCard.php';

if (QRCODE_GENERATOR === "external-api.qrserver.com") {
    require_once BASE_PATH . '/lib/Qrcode/Qrcode.php';
} else {
    require_once BASE_PATH . '/lib/Qrcode/Qrcode-endroid.php';
}

class StaticQrcode {
    private $sData;         // Data for the qr code
    private $sContent;      // Content to be stored in the database
    private Qrcode $qrcode_instance;

    /**
     * StaticQrcode constructor.
     * Initializes the underlying Qrcode generator instance for static QR codes.
     */
    public function __construct() {
        $this->qrcode_instance = new Qrcode("static");
    }

    /**
     * StaticQrcode destructor.
     */
    public function __destruct()
    {
    }
    
    /**
     * Set friendly columns\' names to order tables\' entries
     */
    public function setOrderingValues()
    {
        $ordering = [
            'id' => 'ID',
            'id_owner' => 'Owner',
            'filename' => 'File Name',
            'type' => 'Type',
            'content' => 'Content',
            'qrcode' => 'Qr Code',
            'created_at' => 'Created at',
            'updated_at' => 'Updated at'
        ];

        return $ordering;
    }
    
    /**
     * create a qr code of type "text"
     * @string text -> required
     */
    public function textQrcode($text)
    {
        if($text != NULL){
            $this->sData = $text;
            $this->sContent = '<strong>Text:</strong> ' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            $this->addQrcode("text");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "email"
     * @string email -> required
     * @string subject
     * @string message -> required
     */
    public function emailQrcode($email, $subject, $message)
    {
        if($email != NULL && $message != NULL){
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->failure('Invalid email address.');
            }
            $escape = function($v) {
                return addcslashes((string)$v, ';:\\');
            };
            $this->sData = 'MATMSG:TO:'.$escape($email).';SUB:'.$escape($subject).';BODY:'.$escape($message).';;';
            $this->sContent = '<strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Subject:</strong> ' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Message:</strong> ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("email");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "phone"
     * @int country code -> required
     * @string phone number -> required
     */
    public function phoneQrcode($country_code, $phone_number)
    {
        if($phone_number != NULL){
            $this->sData = 'TEL:'.$country_code.$phone_number;  
            $this->sContent = '<strong>Phone number:</strong> ' . htmlspecialchars($country_code . $phone_number, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("phone");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "sms"
     * @int country code -> required
     * @string phone number -> required
     * @string message -> required
     */
    public function smsQrcode($country_code, $phone_number, $message)
    {
        if($phone_number != NULL && $message != NULL){
            $this->sData = 'SMSTO:'.$country_code.$phone_number.':'.$message;  
            $this->sContent = '<strong>Phone number:</strong> ' . htmlspecialchars($country_code . $phone_number, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Message:</strong> ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("sms");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "whatsapp"
     * @int country code -> required
     * @string phone number -> required
     * @string message
     */
    public function whatsappQrcode($country_code, $phone_number, $message)
    {
        if($phone_number != NULL){
            $this->sData = 'https://wa.me/' . rawurlencode($country_code . $phone_number) . '?text=' . rawurlencode($message);  
            $this->sContent = '<strong>Phone number:</strong> ' . htmlspecialchars($country_code . $phone_number, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Message:</strong> ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("whatsapp");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "skype"
     * @string skype username -> required
     */
    public function skypeQrcode($skype_username)
    {
        if($skype_username != NULL){
            $this->sData = 'skype:'.$skype_username.'?call';
            $this->sContent = '<strong>Skype username:</strong> ' . htmlspecialchars($skype_username, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("skype");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "location"
     * @int latitude -> required
     * @int longitude -> required
     */
    public function locationQrcode($latitude, $longitude)
    {
        if($latitude != NULL && $longitude != NULL){
            $lat = filter_var($latitude, FILTER_VALIDATE_FLOAT);
            $lon = filter_var($longitude, FILTER_VALIDATE_FLOAT);
            if ($lat === false || $lon === false || $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
                $this->failure('Invalid coordinates.');
            }
            $this->sData = 'GEO:'.$lat.','.$lon.';';
            $this->sContent = '<strong>Latitude:</strong> ' . htmlspecialchars((string)$lat, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Longitude:</strong> ' . htmlspecialchars((string)$lon, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("location");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "vcard"
     * 
     */
    /**
     * Create a QR code of type "vcard" containing full contact card information.
     *
     * Catches avatar processing errors gracefully without fatal termination (ERR-03).
     *
     * @param string|null $fullname Full name of the contact.
     * @param string|null $nickname Nickname of the contact.
     * @param string|null $email Primary email address.
     * @param string|null $website Website URL.
     * @param string|null $phone Mobile phone number.
     * @param string|null $home_phone Home telephone number.
     * @param string|null $work_phone Work telephone number.
     * @param string|null $company Organization or company name.
     * @param string|null $role Job title or role.
     * @param string|null $categories Contact categories.
     * @param string|null $note Additional notes.
     * @param string|null $photo Avatar photo URL.
     * @param string|null $address Street address.
     * @param string|null $city City.
     * @param string|null $postcode Postal code.
     * @param string|null $state State or region.
     * @return void
     */
    public function vcardQrcode($fullname, $nickname, $email, $website, $phone, $home_phone, $work_phone, $company, $role, $categories, $note, $photo, $address, $city, $postcode, $state)
    {
        if($fullname != NULL && $phone != NULL){
            
            $vcard = new vCard;
            $vcard->name($fullname);
            $vcard->nickName($nickname); 
            $vcard->email($email); 
            $vcard->url($website); 
            $vcard->cellPhone($phone); 
            $vcard->homePhone($home_phone); 
            $vcard->workPhone($work_phone); 
            $vcard->organization($company); 
            $vcard->role($role); 
            $vcard->categories($categories); 
            $vcard->note($note); 
            try {
                $vcard->photo($photo); 
            } catch (\Throwable $e) {
                // Gracefully ignore avatar loading failure rather than terminating (ERR-03)
            }
            $vcard->address($address, $city, $postcode, $state); 
            $vcard->create();
            
            $this->sData = $vcard->get();
            $this->sContent = '<div class="row"><div class="col-sm-4">';
            
                $this->sContent .= '<strong>Full name:</strong> ' . htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Nickname:</strong> ' . htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Website:</strong> ' . htmlspecialchars($website, ENT_QUOTES, 'UTF-8') . '</div>';
            
            $this->sContent .= '<div class="col-sm-4">';
            
                $this->sContent .= '<strong>Company:</strong> ' . htmlspecialchars($company, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Role:</strong> ' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Categories:</strong> ' . htmlspecialchars($categories, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Note:</strong> ' . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . '</div>';
                
            $this->sContent .= '<div class="col-sm-4">';
            
                $this->sContent .= '<strong>Phone:</strong> ' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Home Phone:</strong> ' . htmlspecialchars($home_phone, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Work phone:</strong> ' . htmlspecialchars($work_phone, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Address:</strong> ' . htmlspecialchars($address, ENT_QUOTES, 'UTF-8') . '&nbsp;' . htmlspecialchars($city, ENT_QUOTES, 'UTF-8') . '&nbsp;' . htmlspecialchars($postcode, ENT_QUOTES, 'UTF-8') . '&nbsp;' . htmlspecialchars($state, ENT_QUOTES, 'UTF-8') . '</div>';
            
            $this->sContent .= '</div>';

            $this->addQrcode("vcard");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * Create a QR code of type "event" (iCalendar / ICS format).
     *
     * Validates start and end datetime and timezone strings with comprehensive
     * error handling against malformed inputs (ERR-02).
     *
     * @param string|null $title Event title/summary (required).
     * @param string|null $start Start datetime string (required).
     * @param string|null $end End datetime string (required).
     * @param string|null $timezone Timezone identifier (e.g. Europe/Berlin).
     * @param string|null $location Event physical or virtual location.
     * @param string|null $description Event description text.
     * @param string|null $url Event related web link.
     * @return void
     */
    public function eventQrcode($title, $start, $end, $timezone, $location, $description, $url)
    {
        if($title != NULL && $start != NULL && $end != NULL){
            $timezoneFrom = $timezone;

            if(empty($timezoneFrom)){
                $timezoneFrom = 'Europe/Berlin';
            }

            try {
                $tmpStart = new DateTime($start, new DateTimeZone($timezoneFrom));
                $tmpEnd = new DateTime($end, new DateTimeZone($timezoneFrom));
                $tmpStart->setTimezone(new DateTimeZone("UTC"));
                $tmpEnd->setTimezone(new DateTimeZone("UTC"));
            } catch (\Throwable $e) {
                $this->failure('Invalid date or timezone format.');
            }

            $ics = new ICS(array(
                'location' => $location,
                'description' => $description,
                'dtstart' => $tmpStart->format("Y-m-d H:i:s"),
                'dtend' => $tmpEnd->format("Y-m-d H:i:s"),
                'summary' => $title,
                'url' => $url
            ));
            
            $this->sData = $ics->to_string();
            $this->sContent = '<div class="row"><div class="col-sm-4">';
            $this->sContent .= '<strong>Title:</strong> ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '<br>' .
                               '<strong>Start event:</strong> ' . htmlspecialchars($start, ENT_QUOTES, 'UTF-8') . '<br>' .
                               '<strong>End event:</strong> ' . htmlspecialchars($end, ENT_QUOTES, 'UTF-8') . '<br>' .
                               '<strong>Time zone:</strong> ' . htmlspecialchars($timezone, ENT_QUOTES, 'UTF-8') . '<br></div>';
            $this->sContent .= '<div class="col-sm-4">';
            $this->sContent .= '<strong>Location:</strong> ' . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . '<br>' .
                               '<strong>Description:</strong> ' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '<br>' .
                               '<strong>URL:</strong> ' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</div>';
            $this->sContent .= '</div>';

            $this->addQrcode("event");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "bookmark"
     * @string title
     * @string url -> required
     */
    public function bookmarkQrcode($url, $title)
    {
        if($url != NULL){
            $this->sData = 'MEBKM:TITLE:'.$title.';URL:'.$url.';';  
            $this->sContent = '<strong>Title:</strong> ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Url:</strong> ' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("bookmark");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "wifi"
     * @string encryption -> required
     * @string ssid -> required
     * @string password
     */
    public function wifiQrcode($encryption, $ssid, $password)
    {
        if($ssid != NULL){
            $allowedEnc = ['WPA', 'WEP', 'WPA2', 'NOPASS', ''];
            $encUpper = strtoupper(trim((string)$encryption));
            if (!in_array($encUpper, $allowedEnc, true)) {
                $encUpper = 'WPA';
            }
            $emittedType = ($encUpper === 'NOPASS' || $encUpper === '') ? 'nopass' : $encUpper;
            $escape = function($v) {
                return addcslashes((string)$v, ';:\"\\,');
            };
            $this->sData = 'WIFI:T:' . $emittedType . ';S:' . $escape($ssid) . ';P:' . $escape($password) . ';;';  
            $this->sContent = '<strong>Encryption:</strong> ' . htmlspecialchars($emittedType, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>SSID:</strong> ' . htmlspecialchars($ssid, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Password:</strong> ' . htmlspecialchars($password, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("wifi");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "paypal"
     * @string payment type -> required
     * @string email -> required
     * @string item_name -> required
     * @int item_id
     * @int amount -> required
     * @string currency -> required
     * @int shipping
     * @int tax_rate
     */
    public function paypalQrcode($payment_type, $email, $item_name, $item_id, $amount, $currency, $shipping, $tax_rate)
    {
        if($email != NULL && $item_name != NULL && $amount != NULL){
            $currencyUpper = strtoupper(trim((string)$currency));
            $zeroDecimalCurrencies = ['HUF', 'JPY', 'TWD'];
            $maxDecimals = in_array($currencyUpper, $zeroDecimalCurrencies, true) ? 0 : 2;

            $cleanAmount = str_replace(',', '.', trim((string)$amount));
            if (!is_numeric($cleanAmount) || (float)$cleanAmount < 0) {
                $this->failure('Invalid payment amount provided.');
            }
            $amountDecimals = 0;
            if (strpos($cleanAmount, '.') !== false) {
                $amountDecimals = strlen(substr($cleanAmount, strpos($cleanAmount, '.') + 1));
            }
            if ($amountDecimals > $maxDecimals) {
                $this->failure("Amount exceeds maximum allowed decimal places ({$maxDecimals}) for currency {$currencyUpper}.");
            }

            $cleanShipping = ($shipping !== null && trim((string)$shipping) !== '') ? str_replace(',', '.', trim((string)$shipping)) : '';
            if ($cleanShipping !== '') {
                if (!is_numeric($cleanShipping) || (float)$cleanShipping < 0) {
                    $this->failure('Invalid shipping amount provided.');
                }
                $shippingDecimals = 0;
                if (strpos($cleanShipping, '.') !== false) {
                    $shippingDecimals = strlen(substr($cleanShipping, strpos($cleanShipping, '.') + 1));
                }
                if ($shippingDecimals > $maxDecimals) {
                    $this->failure("Shipping exceeds maximum allowed decimal places ({$maxDecimals}) for currency {$currencyUpper}.");
                }
            }

            $this->sData = 'https://www.paypal.com/webapps/xorouter?' . http_build_query([
                'cmd' => $payment_type,
                'business' => $email,
                'item_name' => $item_name,
                'item_number' => $item_id,
                'amount' => $cleanAmount,
                'currency_code' => $currency,
                'shipping' => $cleanShipping !== '' ? $cleanShipping : $shipping,
                'tax_rate' => $tax_rate
            ]);
            
            $this->sContent = '<div class="row"><div class="col-sm-4">';
            
                $this->sContent .= '<strong>Payment type:</strong> ' . htmlspecialchars($payment_type, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Item name:</strong> ' . htmlspecialchars($item_name, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Item id:</strong> ' . htmlspecialchars($item_id, ENT_QUOTES, 'UTF-8') . '</div>';
            
            $this->sContent .= '<div class="col-sm-4">';
            
                $this->sContent .= '<strong>Amount:</strong> ' . htmlspecialchars($cleanAmount, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Currency:</strong> ' . htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Shipping:</strong> ' . htmlspecialchars($cleanShipping !== '' ? $cleanShipping : $shipping, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Tax rate:</strong> ' . htmlspecialchars($tax_rate, ENT_QUOTES, 'UTF-8') . '</div>';
                
            $this->sContent .= '</div>';

            $this->addQrcode("paypal");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "bitcoin"
     * @string address -> required
     * @int amount -> required
     * @string label
     * @string message
     */
    public function bitcoinQrcode($address, $amount, $label, $message)
    {
        if($address != NULL && $amount != NULL){
            $cleanAmount = str_replace(',', '.', trim((string)$amount));
            if (!preg_match('/^(?:0|[1-9]\d*)(?:\.\d+)?$/', $cleanAmount) || (float)$cleanAmount < 0.00000001) {
                $this->failure('Invalid Bitcoin amount provided. Minimum amount is 0.00000001 (1 satoshi).');
            }
            if (strpos($cleanAmount, '.') !== false) {
                $decimalPart = substr($cleanAmount, strpos($cleanAmount, '.') + 1);
                if (strlen($decimalPart) > 8) {
                    $this->failure('Bitcoin amount cannot exceed 8 decimal places (1 satoshi precision).');
                }
            }

            $btcParams = ['amount' => $cleanAmount];
            if ($label !== null && $label !== '') {
                $btcParams['label'] = $label;
            }
            if ($message !== null && $message !== '') {
                $btcParams['message'] = $message;
            }
            $this->sData = 'bitcoin:' . rawurlencode($address) . '?' . http_build_query($btcParams);
            $this->sContent = '<strong>BTC address:</strong> ' . htmlspecialchars($address, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Amount:</strong> ' . htmlspecialchars($cleanAmount, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Label:</strong> ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Message:</strong> ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        
            $this->addQrcode("bitcoin");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "2FA"
     * @string algorithms -> required
     * @string secret -> required
     * @string label -> required
     * @string issuer
     * otpauth://TYPE/LABEL?PARAMETERS
     * otpauth://totp/Example:alice@google.com?secret=JBSWY3DPEHPK3PXP&issuer=Example
     */
    public function twofaQrcode($algorithms, $secret, $label, $issuer)
    {
        if($algorithms != NULL && $secret != NULL && $label != NULL){
            $allowed_algos = ['totp', 'hotp'];
            $cleanAlgo = strtolower(trim((string)$algorithms));
            if (!in_array($cleanAlgo, $allowed_algos, true)) {
                $this->failure('Invalid OTP algorithm.');
            }
            $cleanSecret = preg_replace('/[^A-Z2-7=]/i', '', strtoupper(trim((string)$secret)));
            if (empty($cleanSecret)) {
                $this->failure('Invalid secret key (must be Base32).');
            }
            $this->sData = 'otpauth://' . $cleanAlgo . '/' . $label . '?secret=' . $cleanSecret;

            if (!empty($issuer)) {
                $this->sData .= '&issuer=' . $issuer;
            }

            $this->sContent = '<strong>Type:</strong> ' . htmlspecialchars($cleanAlgo, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Secret:</strong> ' . htmlspecialchars($cleanSecret, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Label:</strong> ' . htmlspecialchars(rawurldecode($label), ENT_QUOTES, 'UTF-8');

            if (!empty($issuer)) {
                $this->sContent .= '<br><strong>Issuer:</strong> ' . htmlspecialchars(rawurldecode($issuer), ENT_QUOTES, 'UTF-8');
            }

            $this->addQrcode("2fa");
        }
        else
            $this->requiredFieldsError();
    }

    /**
     * Creates a QR code of type "telegram" linking to a Telegram user or group chat.
     *
     * @param string $username Telegram username or handle.
     * @param string $message Optional pre-filled message text.
     * @return void
     */
    public function telegramQrcode($username, $message = '')
    {
        $cleanUser = ltrim(trim($username), '@');
        if (!empty($cleanUser)) {
            if (!preg_match('/^[A-Za-z0-9_]+$/', $cleanUser)) {
                $this->failure('Invalid Telegram handle provided.');
            }
            $url = 'https://t.me/' . $cleanUser;
            if (!empty($message)) {
                $url .= '?text=' . urlencode(trim($message));
            }
            $this->sData = $url;
            $this->sContent = '<strong>Telegram Username:</strong> @' . htmlspecialchars($cleanUser, ENT_QUOTES, 'UTF-8');
            if (!empty($message)) {
                $this->sContent .= '<br><strong>Message:</strong> ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            }
            $this->addQrcode("telegram");
        } else {
            $this->requiredFieldsError();
        }
    }

    /**
     * Validates that a string is a valid HTTP or HTTPS URL with a non-empty host.
     *
     * @param string $url The URL string to validate.
     * @return bool True if the URL is valid HTTP/HTTPS, false otherwise.
     */
    private function isValidHttpUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }
        $scheme = strtolower($parts['scheme']);
        return ($scheme === 'http' || $scheme === 'https') && !empty(trim($parts['host']));
    }

    /**
     * Creates a QR code of type "social" (YouTube, Instagram, LinkedIn, X, TikTok).
     *
     * @param string $platform Social network platform name.
     * @param string $username Profile username or handle.
     * @param string $custom_url Optional direct custom profile URL.
     * @return void
     */
    public function socialQrcode($platform, $username, $custom_url = '')
    {
        $platform = strtolower(trim($platform));
        $username = trim($username);
        $custom_url = trim($custom_url);

        $url = '';
        if (!empty($custom_url)) {
            if (!$this->isValidHttpUrl($custom_url)) {
                $this->requiredFieldsError();
                return;
            }
            $url = $custom_url;
        } else if (!empty($username)) {
            $cleanUser = ltrim($username, '@');
            switch ($platform) {
                case 'youtube':
                    if (!preg_match('/^[A-Za-z0-9_.\-]{3,30}$/', $cleanUser)) {
                        $this->failure('Invalid username provided for the selected platform.');
                    }
                    $url = 'https://youtube.com/@' . $cleanUser;
                    break;
                case 'instagram':
                    if (!preg_match('/^[A-Za-z0-9_.]{1,30}$/', $cleanUser)) {
                        $this->failure('Invalid username provided for the selected platform.');
                    }
                    $url = 'https://instagram.com/' . $cleanUser;
                    break;
                case 'linkedin':
                    if (!preg_match('/^[A-Za-z0-9_\-]{3,100}$/', $cleanUser)) {
                        $this->failure('Invalid username provided for the selected platform.');
                    }
                    $url = 'https://linkedin.com/in/' . $cleanUser;
                    break;
                case 'twitter':
                case 'x':
                    if (!preg_match('/^[A-Za-z0-9_]{1,15}$/', $cleanUser)) {
                        $this->failure('Invalid username provided for the selected platform.');
                    }
                    $url = 'https://x.com/' . $cleanUser;
                    break;
                case 'tiktok':
                    if (!preg_match('/^[A-Za-z0-9_.]{2,24}$/', $cleanUser)) {
                        $this->failure('Invalid username provided for the selected platform.');
                    }
                    $url = 'https://tiktok.com/@' . $cleanUser;
                    break;
                default:
                    if (!preg_match('/^[A-Za-z0-9_.\-]+$/', $cleanUser)) {
                        $this->failure('Invalid username provided for the selected platform.');
                    }
                    $url = 'https://' . $cleanUser;
                    break;
            }
        }

        if (!empty($url)) {
            $this->sData = $url;
            $this->sContent = '<strong>Platform:</strong> ' . htmlspecialchars(ucfirst($platform), ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Profile / Target:</strong> ' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $this->addQrcode("social");
        } else {
            $this->requiredFieldsError();
        }
    }

    /**
     * Creates a QR code of type "crypto" (Ethereum, Solana, Monero).
     *
     * @param string $currency Cryptocurrency identifier (e.g. 'ethereum', 'solana', 'monero').
     * @param string $address Wallet destination address.
     * @param string $amount Optional transfer amount.
     * @param string $message Optional transaction message or note.
     * @return void
     */
    public function cryptoQrcode($currency, $address, $amount = '', $message = '')
    {
        $currency = strtolower(trim($currency));
        $address = trim($address);
        $amount = trim($amount);
        $message = trim($message);

        if (!empty($address)) {
            switch ($currency) {
                case 'ethereum':
                case 'eth':
                    $data = 'ethereum:' . $address;
                    $params = [];
                    if (!empty($amount)) {
                        $cleanAmount = str_replace(',', '.', $amount);
                        if (!preg_match('/^(?:0|[1-9]\d*)(?:\.\d+)?$/', $cleanAmount) || (float)$cleanAmount <= 0) {
                            $this->failure('Invalid amount provided.');
                        }
                        if (strpos($cleanAmount, '.') !== false) {
                            $cleanAmount = rtrim(rtrim($cleanAmount, '0'), '.');
                        }
                        $params['value'] = $cleanAmount . 'e18';
                    }
                    if (!empty($message)) $params['message'] = $message;
                    if (!empty($params)) $data .= '?' . http_build_query($params);
                    break;

                case 'solana':
                case 'sol':
                    $data = 'solana:' . $address;
                    $params = [];
                    if (!empty($amount)) {
                        $cleanAmount = str_replace(',', '.', $amount);
                        if (!preg_match('/^(?:0|[1-9]\d*)(?:\.\d+)?$/', $cleanAmount) || (float)$cleanAmount <= 0) {
                            $this->failure('Invalid amount provided.');
                        }
                        $params['amount'] = $cleanAmount;
                    }
                    if (!empty($message)) $params['message'] = $message;
                    if (!empty($params)) $data .= '?' . http_build_query($params);
                    break;

                case 'monero':
                case 'xmr':
                    $data = 'monero:' . $address;
                    $params = [];
                    if (!empty($amount)) {
                        $cleanAmount = str_replace(',', '.', $amount);
                        if (!preg_match('/^(?:0|[1-9]\d*)(?:\.\d+)?$/', $cleanAmount) || (float)$cleanAmount <= 0) {
                            $this->failure('Invalid amount provided.');
                        }
                        $params['tx_amount'] = $cleanAmount;
                    }
                    if (!empty($message)) $params['tx_description'] = $message;
                    if (!empty($params)) $data .= '?' . http_build_query($params);
                    break;

                default:
                    $data = $address;
                    break;
            }

            $this->sData = $data;
            $this->sContent = '<strong>Cryptocurrency:</strong> ' . htmlspecialchars(strtoupper($currency), ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Address:</strong> ' . htmlspecialchars($address, ENT_QUOTES, 'UTF-8');
            if (!empty($amount)) {
                $this->sContent .= '<br><strong>Amount:</strong> ' . htmlspecialchars($amount, ENT_QUOTES, 'UTF-8');
            }
            if (!empty($message)) {
                $this->sContent .= '<br><strong>Message:</strong> ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            }
            $this->addQrcode("crypto");
        } else {
            $this->requiredFieldsError();
        }
    }

    /**
     * Validates an IBAN against ISO 13616 country formats and mod-97 checksum.
     *
     * @param string $iban International Bank Account Number string.
     * @return bool True if the IBAN format and checksum are valid, false otherwise.
     */
    private function isValidIban(string $iban): bool
    {
        $iban = strtoupper(str_replace(' ', '', $iban));
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {
            return false;
        }

        $country = substr($iban, 0, 2);
        static $ibanLengths = [
            'AL' => 28, 'AD' => 24, 'AT' => 20, 'AZ' => 28, 'BH' => 22,
            'BY' => 28, 'BE' => 16, 'BA' => 20, 'BR' => 29, 'BG' => 22,
            'CR' => 22, 'HR' => 21, 'CY' => 28, 'CZ' => 24, 'DK' => 18,
            'DO' => 28, 'EE' => 20, 'FO' => 18, 'FI' => 18, 'FR' => 27,
            'GE' => 22, 'DE' => 22, 'GI' => 23, 'GR' => 27, 'GL' => 18,
            'GT' => 28, 'HU' => 28, 'IS' => 26, 'IE' => 22, 'IL' => 23,
            'IT' => 27, 'JO' => 30, 'KZ' => 20, 'XK' => 20, 'KW' => 30,
            'LV' => 21, 'LB' => 28, 'LI' => 21, 'LT' => 20, 'LU' => 20,
            'MK' => 19, 'MT' => 31, 'MR' => 27, 'MU' => 30, 'MD' => 24,
            'MC' => 27, 'ME' => 22, 'NL' => 18, 'NO' => 15, 'PK' => 24,
            'PS' => 29, 'PL' => 28, 'PT' => 25, 'QA' => 29, 'RO' => 24,
            'LC' => 32, 'SM' => 27, 'ST' => 25, 'SA' => 24, 'RS' => 22,
            'SC' => 31, 'SK' => 24, 'SI' => 19, 'ES' => 24, 'SE' => 24,
            'CH' => 21, 'TL' => 23, 'TN' => 24, 'TR' => 26, 'UA' => 29,
            'AE' => 23, 'GB' => 22, 'VA' => 22, 'VG' => 24,
            'AO' => 25, 'BF' => 28, 'BI' => 27, 'BJ' => 28, 'CF' => 27,
            'CG' => 27, 'CI' => 28, 'CM' => 27, 'CV' => 25, 'DJ' => 27,
            'DZ' => 26, 'EG' => 29, 'FK' => 18, 'GA' => 27, 'GQ' => 27,
            'GW' => 25, 'HN' => 28, 'IQ' => 23, 'IR' => 26, 'KM' => 27,
            'LY' => 25, 'MA' => 28, 'MG' => 27, 'ML' => 28, 'MN' => 20,
            'MZ' => 25, 'NE' => 28, 'NI' => 32, 'OM' => 23, 'RU' => 33,
            'SD' => 18, 'SN' => 28, 'SO' => 23, 'SV' => 28, 'TD' => 27,
            'TG' => 28
        ];

        if (!isset($ibanLengths[$country]) || strlen($iban) !== $ibanLengths[$country]) {
            return false;
        }

        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        $numeric = '';
        $len = strlen($rearranged);
        for ($i = 0; $i < $len; $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numeric .= (ord($char) - 55);
            } else {
                $numeric .= $char;
            }
        }

        $remainder = 0;
        $numLen = strlen($numeric);
        for ($i = 0; $i < $numLen; $i += 7) {
            $chunk = $remainder . substr($numeric, $i, 7);
            $remainder = (int)$chunk % 97;
        }

        return $remainder === 1;
    }

    /**
     * Checks if a two-letter ISO country code belongs to the SEPA zone.
     *
     * @param string $country Two-letter ISO country code.
     * @return bool True if country is in SEPA, false otherwise.
     */
    private function isSepaCountry(string $country): bool
    {
        static $sepaCountries = [
            'AD', 'AL', 'AT', 'BE', 'BG', 'CH', 'CY', 'CZ', 'DE', 'DK',
            'EE', 'ES', 'FI', 'FR', 'GB', 'GI', 'GR', 'HR', 'HU', 'IE',
            'IS', 'IT', 'LI', 'LT', 'LU', 'LV', 'MC', 'MD', 'ME', 'MK',
            'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'RS', 'SE', 'SI', 'SK',
            'SM', 'VA'
        ];
        return in_array(strtoupper($country), $sepaCountries, true);
    }

    /**
     * Checks if a two-letter ISO country code belongs to the European Economic Area (EEA).
     *
     * @param string $country Two-letter ISO country code.
     * @return bool True if country is in EEA, false otherwise.
     */
    private function isEeaCountry(string $country): bool
    {
        static $eeaCountries = [
            'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI',
            'FR', 'GR', 'HR', 'HU', 'IE', 'IS', 'IT', 'LI', 'LT', 'LU',
            'LV', 'MT', 'NL', 'NO', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'
        ];
        return in_array(strtoupper($country), $eeaCountries, true);
    }

    /**
     * Validates a SWIFT/BIC code according to ISO 9362 (8 or 11 alphanumeric characters).
     *
     * @param string $bic Business Identifier Code.
     * @return bool True if valid BIC format, false otherwise.
     */
    private function isValidBic(string $bic): bool
    {
        $bic = strtoupper(trim($bic));
        $len = strlen($bic);
        if ($len !== 8 && $len !== 11) {
            return false;
        }
        return (bool)preg_match('/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $bic);
    }

    /**
     * Validates a Pix key against supported DICT key formats:
     * - CPF (11 digits)
     * - CNPJ (14 digits)
     * - Email (valid email, up to 77 characters)
     * - Phone (E.164 format, e.g. +5511999999999)
     * - EVP / Random key (UUID: 36 characters)
     *
     * @param string $key Pix key string.
     * @return bool True if valid Pix key, false otherwise.
     */
    private function isValidPixKey(string $key): bool
    {
        $key = trim($key);
        if ($key === '') {
            return false;
        }
        if (preg_match('/^\d{11}$/', $key)) {
            return true;
        }
        if (preg_match('/^\d{14}$/', $key)) {
            return true;
        }
        if (strlen($key) <= 77 && filter_var($key, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        if (preg_match('/^\+[1-9]\d{7,14}$/', $key)) {
            return true;
        }
        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $key)) {
            return true;
        }
        return false;
    }

    /**
     * Creates a QR code of type "sepa" adhering to the European Payments Council standard.
     *
     * @param string $name Beneficiary account holder name.
     * @param string $iban International Bank Account Number.
     * @param string $bic Optional Business Identifier Code (SWIFT/BIC).
     * @param string $amount Optional payment amount in EUR.
     * @param string $remittance Optional payment remittance reference/information.
     * @return void
     */
    public function sepaQrcode($name, $iban, $bic = '', $amount = '', $remittance = '')
    {
        $name = trim($name);
        $iban = strtoupper(str_replace(' ', '', trim($iban)));
        $bic = strtoupper(trim($bic));
        $amount = trim($amount);
        $remittance = trim($remittance);

        if (!empty($name) && !empty($iban)) {
            if (!$this->isValidIban($iban)) {
                $this->failure('Invalid IBAN provided.');
            }
            $country = substr($iban, 0, 2);
            if (!$this->isSepaCountry($country)) {
                $this->failure('IBAN must belong to a SEPA country.');
            }

            if ($bic === '') {
                if (!$this->isEeaCountry($country)) {
                    $this->failure('BIC is required for non-EEA SEPA participants.');
                }
            } else {
                if (!$this->isValidBic($bic)) {
                    $this->failure('Invalid BIC provided.');
                }
            }

            $formattedAmount = '';
            if (!empty($amount)) {
                $cleanAmount = str_replace(',', '.', $amount);
                if (!preg_match('/^(?:0|[1-9]\d*)(?:\.\d{1,2})?$/', $cleanAmount)) {
                    $this->failure('Invalid amount provided.');
                }
                $val = (float)$cleanAmount;
                if ($val < 0.01 || $val > 999999999.99) {
                    $this->failure('Invalid amount provided.');
                }
                $num = number_format($val, 2, '.', '');
                $formattedAmount = 'EUR' . $num;
            }

            // EPC069-08 Quick Response Code Guidelines
            $lines = [
                'BCD',
                '002',
                '1',
                'SCT',
                $bic,
                mb_substr($name, 0, 70),
                $iban,
                $formattedAmount,
                '',
                '',
                mb_substr($remittance, 0, 140),
                ''
            ];

            $this->sData = rtrim(implode("\n", $lines), "\r\n");
            $this->sContent = '<strong>Beneficiary:</strong> ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>IBAN:</strong> ' . htmlspecialchars($iban, ENT_QUOTES, 'UTF-8');
            if (!empty($bic)) {
                $this->sContent .= '<br><strong>BIC:</strong> ' . htmlspecialchars($bic, ENT_QUOTES, 'UTF-8');
            }
            if (!empty($amount)) {
                $this->sContent .= '<br><strong>Amount:</strong> ' . htmlspecialchars($formattedAmount, ENT_QUOTES, 'UTF-8');
            }
            if (!empty($remittance)) {
                $this->sContent .= '<br><strong>Remittance:</strong> ' . htmlspecialchars($remittance, ENT_QUOTES, 'UTF-8');
            }

            $this->addQrcode("sepa");
        } else {
            $this->requiredFieldsError();
        }
    }

    /**
     * Helper for EMV TLV (Type-Length-Value) string construction.
     *
     * @param string $id Tag identifier (2 characters).
     * @param string $value Data element value.
     * @return string Formatted TLV string.
     */
    private function emvTlv(string $id, string $value): string {
        return sprintf('%02s%02d%s', $id, strlen($value), $value);
    }

    /**
     * Computes CRC16-CCITT checksum (polynomial 0x1021, init 0xFFFF).
     *
     * @param string $data Input string data.
     * @return string 4-character uppercase hexadecimal CRC16 checksum.
     */
    private function computeCrc16(string $data): string {
        $crc = 0xFFFF;
        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }
        return strtoupper(sprintf('%04X', $crc));
    }

    /**
     * Transliterates a Unicode string to plain ASCII characters.
     *
     * @param string $text Input text with possible Unicode characters.
     * @return string Transliterated ASCII string.
     */
    private function transliterateToAscii(string $text): string
    {
        if (function_exists('transliterator_transliterate')) {
            $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII;', $text);
            if ($transliterated !== false) {
                return $transliterated;
            }
        }
        if (function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($transliterated !== false) {
                return $transliterated;
            }
        }
        return $text;
    }

    /**
     * Creates a QR code of type "pix" conforming to the Brazilian Instant Payments EMV standard.
     *
     * @param string $pix_key Pix key (CPF, CNPJ, email, phone, or random key).
     * @param string $merchant_name Beneficiary / Merchant name.
     * @param string $merchant_city Merchant city name.
     * @param string $amount Optional transaction amount in BRL.
     * @param string $txid Optional transaction identifier.
     * @return void
     */
    public function pixQrcode($pix_key, $merchant_name, $merchant_city, $amount = '', $txid = '')
    {
        $pix_key = trim($pix_key);
        $merchant_name = trim($merchant_name);
        $merchant_city = trim($merchant_city);
        $amount = trim($amount);
        $txid = trim($txid) ?: '***';

        if (!empty($pix_key) && !empty($merchant_name) && !empty($merchant_city)) {
            if (!$this->isValidPixKey($pix_key)) {
                $this->failure('Invalid Pix key provided.');
            }

            // Field 26: Merchant Account Information
            $field26 = $this->emvTlv('00', 'br.gov.bcb.pix') . $this->emvTlv('01', $pix_key);

            $payload = $this->emvTlv('00', '01') .                     // Format Indicator
                       $this->emvTlv('26', $field26) .                 // Merchant Account Information
                       $this->emvTlv('52', '0000') .                   // Merchant Category Code
                       $this->emvTlv('53', '986');                     // Currency BRL

            if (!empty($amount)) {
                $cleanAmount = str_replace(',', '.', $amount);
                if (!preg_match('/^(?:0|[1-9]\d*)(?:\.\d{1,2})?$/', $cleanAmount) || (float)$cleanAmount <= 0) {
                    $this->failure('Invalid amount provided.');
                }
                $payload .= $this->emvTlv('54', number_format((float)$cleanAmount, 2, '.', ''));
            }

            $cleanName = mb_substr(preg_replace('/[^A-Za-z0-9 ]/', '', $this->transliterateToAscii($merchant_name)), 0, 25);
            $cleanName = trim($cleanName);
            if ($cleanName === '') {
                $cleanName = 'NA';
            }
            $cleanCity = mb_substr(preg_replace('/[^A-Za-z0-9 ]/', '', $this->transliterateToAscii($merchant_city)), 0, 15);
            $cleanCity = trim($cleanCity);
            if ($cleanCity === '') {
                $cleanCity = 'NA';
            }

            $payload .= $this->emvTlv('58', 'BR') .                    // Country Code
                        $this->emvTlv('59', $cleanName) .              // Merchant Name
                        $this->emvTlv('60', $cleanCity);               // Merchant City

            // Additional Data Field (txid)
            if ($txid === '' || $txid === '***') {
                $cleanTxid = '***';
            } else {
                $sanitizedTxid = mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $this->transliterateToAscii($txid)), 0, 25);
                $cleanTxid = ($sanitizedTxid !== '') ? $sanitizedTxid : '***';
            }

            $payload .= $this->emvTlv('62', $this->emvTlv('05', $cleanTxid));

            // Append CRC placeholder then compute checksum
            $payloadWithoutCrc = $payload . '6304';
            $crc = $this->computeCrc16($payloadWithoutCrc);
            $finalPixPayload = $payloadWithoutCrc . $crc;

            $this->sData = $finalPixPayload;
            $this->sContent = '<strong>Pix Key:</strong> ' . htmlspecialchars($pix_key, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Name:</strong> ' . htmlspecialchars($merchant_name, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>City:</strong> ' . htmlspecialchars($merchant_city, ENT_QUOTES, 'UTF-8');
            if (!empty($amount)) {
                $this->sContent .= '<br><strong>Amount:</strong> R$ ' . htmlspecialchars(number_format((float)$cleanAmount, 2, '.', ''), ENT_QUOTES, 'UTF-8');
            }
            if ($cleanTxid !== '***') {
                $this->sContent .= '<br><strong>TxID:</strong> ' . htmlspecialchars($cleanTxid, ENT_QUOTES, 'UTF-8');
            }

            $this->addQrcode("pix");
        } else {
            $this->requiredFieldsError();
        }
    }

    /**
     * Creates a QR code of type "meeting" (Zoom, Teams, FaceTime).
     *
     * @param string $platform Meeting service platform name.
     * @param string $meeting_id Meeting identifier or dial-in number.
     * @param string $password Optional passcode/password for the meeting.
     * @param string $url Optional direct invitation web URL.
     * @return void
     */
    public function meetingQrcode($platform, $meeting_id, $password = '', $url = '')
    {
        $platform = strtolower(trim($platform));
        $meeting_id = trim($meeting_id);
        $password = trim($password);
        $url = trim($url);

        if (empty($meeting_id) && empty($url)) {
            $this->requiredFieldsError();
            return;
        }

        $targetUrl = '';
        if (!empty($url)) {
            if (!$this->isValidHttpUrl($url)) {
                $this->requiredFieldsError();
                return;
            }
            $targetUrl = $url;
        } else if ($platform === 'teams') {
            $this->requiredFieldsError();
            return;
        } else if ($platform === 'zoom' && !empty($meeting_id)) {
            $cleanMeetingId = preg_replace('/[^0-9]/', '', $meeting_id);
            if ($cleanMeetingId === '') {
                $this->failure('Invalid Zoom meeting ID.');
                return;
            }
            $targetUrl = 'https://zoom.us/j/' . $cleanMeetingId;
            if (!empty($password)) {
                $targetUrl .= '?pwd=' . urlencode($password);
            }
        } else if ($platform === 'facetime' && !empty($meeting_id)) {
            $cleanFacetime = preg_replace('/[^0-9+\-@.a-zA-Z]/', '', $meeting_id);
            if (empty($cleanFacetime)) {
                $this->failure('Invalid FaceTime ID.');
                return;
            }
            $targetUrl = 'facetime:' . $cleanFacetime;
        } else if (!empty($meeting_id) && $platform !== 'teams') {
            if (!$this->isValidHttpUrl($meeting_id)) {
                $this->failure('Meeting ID must be a valid HTTP/HTTPS URL for unknown platforms.');
                return;
            }
            $targetUrl = $meeting_id;
        }

        if (!empty($targetUrl)) {
            $this->sData = $targetUrl;
            $this->sContent = '<strong>Meeting Platform:</strong> ' . htmlspecialchars(ucfirst($platform), ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Target / Link:</strong> ' . htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8');
            $this->addQrcode("meeting");
        } else {
            $this->requiredFieldsError();
        }
    }

    /**
     * Creates a QR code of type "mecard" conforming to the mobile contact standard.
     *
     * @param string $name Contact full name.
     * @param string $reading Optional name pronunciation/reading sound.
     * @param string $tel Optional phone number.
     * @param string $email Optional email address.
     * @param string $memo Optional note or memo.
     * @param string $address Optional postal address.
     * @param string $url Optional website URL.
     * @return void
     */
    public function mecardQrcode($name, $reading = '', $tel = '', $email = '', $memo = '', $address = '', $url = '')
    {
        $name = trim($name);
        if (!empty($name)) {
            /**
             * Escapes reserved characters for MECARD formatted strings.
             *
             * @param string $value Text to escape.
             * @param bool $escapeComma Whether commas should be escaped.
             * @return string Escaped string.
             */
            $escapeMecard = function(string $value, bool $escapeComma = true): string {
                $value = str_replace('\\', '\\\\', $value);
                $value = str_replace(';', '\;', $value);
                $value = str_replace(':', '\:', $value);
                if ($escapeComma) {
                    $value = str_replace(',', '\,', $value);
                }
                return $value;
            };

            $fields = ['N:' . $escapeMecard($name, false)];
            if (!empty($reading)) $fields[] = 'SOUND:' . $escapeMecard(trim($reading));
            if (!empty($tel))     $fields[] = 'TEL:' . $escapeMecard(trim($tel));
            if (!empty($email))   $fields[] = 'EMAIL:' . $escapeMecard(trim($email));
            if (!empty($memo))    $fields[] = 'NOTE:' . $escapeMecard(trim($memo));
            if (!empty($address)) $fields[] = 'ADR:' . $escapeMecard(trim($address));
            if (!empty($url))     $fields[] = 'URL:' . $escapeMecard(trim($url));

            $this->sData = 'MECARD:' . implode(';', $fields) . ';;';
            $this->sContent = '<strong>MeCard Contact:</strong> ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            if (!empty($tel))   $this->sContent .= '<br><strong>Tel:</strong> ' . htmlspecialchars($tel, ENT_QUOTES, 'UTF-8');
            if (!empty($email)) $this->sContent .= '<br><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
            if (!empty($url))   $this->sContent .= '<br><strong>Website:</strong> ' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("mecard");
        } else {
            $this->requiredFieldsError();
        }
    }

    /**
     * Creates a QR code of type "medical" representing an Emergency Medical ID profile.
     *
     * @param string $name Patient full name.
     * @param string $blood_type Optional blood group/type.
     * @param string $allergies Optional known allergies.
     * @param string $medications Optional current medications.
     * @param string $emergency_contact Optional emergency contact details.
     * @param string $notes Optional medical conditions or instructions.
     * @return void
     */
    public function medicalQrcode($name, $blood_type = '', $allergies = '', $medications = '', $emergency_contact = '', $notes = '')
    {
        $name = trim($name);
        if (!empty($name)) {
            $text = "--- EMERGENCY MEDICAL ID ---\n";
            $text .= "Name: " . $name . "\n";
            if (!empty($blood_type))        $text .= "Blood Type: " . trim($blood_type) . "\n";
            if (!empty($allergies))         $text .= "Allergies: " . trim($allergies) . "\n";
            if (!empty($medications))       $text .= "Medications: " . trim($medications) . "\n";
            if (!empty($emergency_contact)) $text .= "Emergency Contact: " . trim($emergency_contact) . "\n";
            if (!empty($notes))             $text .= "Notes: " . trim($notes) . "\n";

            $this->sData = $text;
            $this->sContent = '<strong>Medical ID:</strong> ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            if (!empty($blood_type))        $this->sContent .= '<br><strong>Blood Type:</strong> ' . htmlspecialchars($blood_type, ENT_QUOTES, 'UTF-8');
            if (!empty($allergies))         $this->sContent .= '<br><strong>Allergies:</strong> ' . htmlspecialchars($allergies, ENT_QUOTES, 'UTF-8');
            if (!empty($emergency_contact)) $this->sContent .= '<br><strong>Emergency Contact:</strong> ' . htmlspecialchars($emergency_contact, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("medical");
        } else {
            $this->requiredFieldsError();
        }
    }

    /**
     * Retrieves a static QR code database record by its ID.
     *
     * @param int|string $id The QR code database identifier.
     * @return array The associative array containing QR code record data.
     */
    public function getQrcode($id) {
        return $this->qrcode_instance->getQrcode($id);
    }
    
    /**
     * Add qr code
     * Check out http://goqr.me/api/ for more information
     * We save the file obtained with the chosen name and in the selected folder
     * We save into db the url of qrcode image
     */
    private function addQrcode($type) {
        if ($_SESSION['type'] === 'super') {
            $data_to_db['id_owner'] = !empty($_POST['id_owner']) ? (int)$_POST['id_owner'] : NULL;
        } else {
            $data_to_db['id_owner'] = (int)$_SESSION['user_id'];
        }

        $data_to_db['created_at'] = date('Y-m-d H:i:s');
        $data_to_db['created_by'] = (int)$_SESSION['user_id'];
        $data_to_db['filename'] = $this->qrcode_instance->sanitizeFilename($_POST['filename'] ?? '');
        $data_to_db['type'] = $type;
        $data_to_db['format'] = strtolower(trim($_POST['format'] ?? 'png'));
        $data_to_db['qrcode'] = $data_to_db['filename'].'.'.$data_to_db['format'];
        $data_to_db['content'] = $this->sContent;

        if(isset($_POST['level']))
            $input_data["level"] = $_POST['level'];

        if(isset($_POST['size']))
            $input_data["size"] = $_POST['size'];

        if(isset($_POST['round_block']))
            $input_data["round_block"] = $_POST['round_block'];

        if(isset($_POST['logo_scale']))
            $input_data["logo_scale"] = $_POST['logo_scale'];

        if(isset($_POST['preset_logo']))
            $input_data["preset_logo"] = $_POST['preset_logo'];
        foreach(['logo_shape','gradient_type','gradient_color','gradient_angle','gradient_balance','gradient_direction','custom_eyes_enabled','eye_outer','eye_inner'] as $k) { if(isset($_POST[$k])) $input_data[$k] = $_POST[$k]; }

        $input_data["foreground"] = $_POST['foreground'] ?? '000000';
        $input_data["background"] = $_POST['background'] ?? 'ffffff';

        $data_to_qrcode = urlencode($this->sData);

        $this->qrcode_instance->addQrcode($input_data, $data_to_db, $data_to_qrcode);
    }
    
    /**
     * Edit static QR code metadata and update database record (BUG-01).
     *
     * @param array $input_data Form input parameters including id, filename, and owner.
     * @return void
     */
    public function editQrcode($input_data) {
        $existing = $this->getQrcode($input_data["id"]);
        if ($_SESSION['type'] !== 'super') {
            if (!isset($existing['id_owner']) || (int)$existing['id_owner'] !== (int)$_SESSION['user_id']) {
                $this->failure("You cannot edit this qrcode because it belongs to another user");
            }
            $data_to_db['id_owner'] = (int)$_SESSION['user_id'];
        } else {
            $data_to_db['id_owner'] = !empty($input_data['id_owner']) ? (int)$input_data['id_owner'] : NULL;
        }

        $data_to_db['filename'] = $this->qrcode_instance->sanitizeFilename($input_data['filename']);
        $data_to_db['updated_at'] = date('Y-m-d H:i:s');

        $this->qrcode_instance->editQrcode($input_data, $data_to_db);
    }
    
    /**
     * Delete static QR code entry and remove underlying generated file.
     *
     * @param int|string $id QR code identifier.
     * @param bool $async Whether to suppress user redirect on asynchronous deletion.
     * @return void
     */
    public function deleteQrcode($id, $async = false) {
        if($_SESSION['type'] === "super") {
            $this->qrcode_instance->deleteQrcode($id, $async);
        } else if ($_SESSION['type'] === "admin") {
            $qrcode = $this->getQrcode($id);

            if(!isset($qrcode["id_owner"]))
                $this->failure("You cannot delete this qrcode");

            require_once BASE_PATH . '/lib/Users/Users.php';
            $users = new Users();
            $user = $users->getUser($_SESSION['user_id']);

            if(isset($user['id']) && (int)$user["id"] === (int)$qrcode["id_owner"])
                $this->qrcode_instance->deleteQrcode($id, $async);
            else
                $this->failure("You cannot delete this qrcode because it's of another user");
        } else {
            $this->failure("Unauthorized");
        }
    }
    
    /**
     * Flash message Failure process
     */
    private function failure($message) {
        $_SESSION['failure'] = $message;
        header('Location: static_qrcodes.php');
    	exit();
    }
    
    /**
     * Flash message Success process
     */
    private function success($message) {
        $_SESSION['success'] = $message;
        header('Location: static_qrcodes.php');
    	exit();
    }
    
    /**
     * Flash message Info process
     */
    private function info($message) {
        $_SESSION['info'] = $message;
        header('Location: static_qrcodes.php');
    	exit();
    }
    
    /**
     * Error message if not filled in all the fields required by the type of the qr code
     */
    private function requiredFieldsError() {
        $this->failure('The qr code cannot be created if you do not fill in all the required fields (*)');
    }

    /**
     * Dumps debug data and terminates script execution.
     *
     * @param mixed $data Data to inspect.
     * @return void
     */
    private function debug($data) {
        if (defined('TYPE') && in_array(strtolower(TYPE), ['dev', 'development', 'debug'], true)) {
            echo '<pre>' . htmlspecialchars(var_export($data, true), ENT_QUOTES, 'UTF-8') . '</pre>';
            exit();
        }
    }
}
?>