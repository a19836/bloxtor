<?php
//Sanitize CSRF (Cross-Site Request Forgery) Attacks

include_once get_lib("org.phpframework.encryption.PublicPrivateKeyHandler");

class CSRFValidator {
	//important to be true bc of CSRF attacks
	public static $REQUEST_RESTRICTED_TO_SAME_REFERER_HOST = true;
	public static $REQUEST_RESTRICTED_TO_SAME_REMOTE_ADDR = false;
	
	public static $COOKIES_EXTRA_FLAGS = array("SameSite" => "Strict", "httponly" => true);//SameSite=Strict or SameSite=Lax or SameSite=None. SameSite=None is not advisable, because of CSRF attacks. Please read https://web.dev/samesite-cookies-explained/
	public static $CLIENT_IP_VARIABLE_NAME = "dad90ad76sad23";
	public static $CLIENT_IP_CYPHER_POSITION = 11;
	public static $CLIENT_IP_CYPHER_LENGTH = 14;
	
	//private key
	public static $CLIENT_IP_ENCRYPTION_KEY = "-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCyToww/fIvA8Va
EbP8LeDfCgrsahgZ0X489MuflMYoGj5EmWtchH4IjlAZzuXon1DP8UQ74iMNPARu
TfkhButdMUhLtEFAAJxcUyPam73zy+lppr1t6Hin4BQKhWsdyLWCnfsMI+fs2MHh
XjYsnu2Ia/77BZtfoZiSQNQ0XNyB/uvPubFDRK3Z38BT1AGBIf3JCC/w45iS91nj
JavAKAdYf2kvr150ei8LfDY/3zJ+R8GyCGwQI44zO3rtZifZX6R1kXLBuPrnpgDl
5djPpic+rJqo3ILg3b+NQUP/tYVEQjrP/nUHwet2i5CYzxvJDNIOknw0f7rpo8uu
HsoFuT1XAgMBAAECggEAMYcX8dPYHa8Sdn5MXFPyDoIfnqOppiJGym/Ez8Lnd+Qy
P6PN6pjy2TWOklyiCAeYzunZZjjeO6LcKDeIZ+AgKHaz+jNLnJeO1yZQ4zw3eyy8
3RfvrkPQn/DiIDoHEvLZWDrBrRGcLnHXCN6+dY5/tFErNlbMXbfpRVa0mwbgSUsr
DYNkR3r92YaJrFOHQmCOYS8FY/9agp2CTKtlpG+K+Rixq3mY5FXCPYK1A+jujDmK
1RzclNBPvTySXnoyyLBEtAI+P2ROpgNONLmT8hs1nQGXcQ4gItTqSIBiW3LH6Hh8
2I8EuAhlPcshre2+TKVHFbSyqXTzlqP1UrGSDYDHQQKBgQDj0DqwUeMjXWbav2NE
/xknh2lB58WDuNdigBi5qRyr1lUsuJMuAATv80TwFlNWsnuFYO9d8yj1pTDdtjYp
uKdbdoIVANyHg2gcdQAV8bUvPIU8P/Rg30og9rhZdS26YzGcGyPpQ3Ifag+2HQlo
RNDed4BfsjRokOG1onn0DVynfwKBgQDIXj7LsE71lOFBHebevP0R27rZvh+71a5E
+7FkZSkt0scjfL7c82FMGVPe2RRYmgOZqB+ADg9J5MbWNiq+Wrp1mYqGZm3jWv0z
P647Tjscrmgbmiky7KLneEpTDvyF7O2rWSp2ETEQ3qI5a7+t25k4V/WzAeC16tlu
UqYQF0KWKQKBgQC4j2vciJrBfdvkAAWGUjyov5VQpVpo2ojz7d8aGp11wVCDyIzE
SZO2aZlCAHRH2pUje2Kw9FwMlmW+WO4MYuKCwMGmDmqbBqSD2W3WWVl2CUvPgeiT
ypIdnoO/RaVkSRRZ6crwIYoFVUGhQmjqpkWo1ZuU66R1ylpxck3moCSeNQKBgHWm
8VSFODf3rbSgrDnJ2wercDH+839F30heSjFbPSzNAWWTEDeJKW6XyKmn6cyE0uxc
zfJRTyTikuahc8PGXopDGBYG+ytu+BIpqFLmgss6laLviJWAYb9s4KeYuyqgjoX4
m3gsbBUtxS/WVvztXzC4ZWsxBROMzRN8sEnufojRAoGAexxixH47AGj8Ltq4LN1W
bMm8k5OB8uiSU2Hqhkh3cgczlbODZr2ho0eXrEcBwOxWI9O+nq5jvX2hoPaQ+Wfn
a/RWd49ZOIBUPEWVH8VBfFtffGbasn4r4/uR8FvddQ9U1DJTGpyTFMbVPpan7+RZ
DXW+pzUWrmScbhHynvHdKQo=
-----END PRIVATE KEY-----";
	public static $CLIENT_IP_ENCRYPTION_PASSPHRASE = "ASD87yum9D9Safggh8SA7998";
	
	public static function validateRequest() {
		$is_same_referer = !self::$REQUEST_RESTRICTED_TO_SAME_REFERER_HOST || self::isSameReferer();
		$is_same_ip = !self::$REQUEST_RESTRICTED_TO_SAME_REMOTE_ADDR || self::isSameIP();
		
		$status = $is_same_referer && $is_same_ip;
		
		if (self::$REQUEST_RESTRICTED_TO_SAME_REMOTE_ADDR)
			self::setClientIPCookie();
		
		return $status;
	}
	
	public static function isSameReferer() {
		$referer_domain = !empty($_SERVER["HTTP_REFERER"]) ? strtolower(parse_url($_SERVER["HTTP_REFERER"], PHP_URL_HOST)) : null;
		$request_host = isset($_SERVER["HTTP_HOST"]) ? explode(":", $_SERVER["HTTP_HOST"]) : null;
		$request_host = isset($request_host[0]) ? $request_host[0] : null;
		
		return $referer_domain && $referer_domain == strtolower($request_host);
	}
	
	public static function isSameIP() {
		$client_ip = self::getClientIP();
		$referer_ip = isset($_COOKIE[self::$CLIENT_IP_VARIABLE_NAME]) ? $_COOKIE[self::$CLIENT_IP_VARIABLE_NAME] : null;
		//echo "client_ip(".$_SERVER["REMOTE_ADDR"]."):$client_ip<pre>";print_r($_SERVER);die();
		
		return $referer_ip && $referer_ip == $client_ip;
	}
	
	public static function setClientIPCookie() {
		$client_ip = self::getClientIP();
		
		return CookieHandler::setSafeCookie(self::$CLIENT_IP_VARIABLE_NAME, $client_ip, 0, "/", self::$COOKIES_EXTRA_FLAGS);
	}
	
	public static function getClientIP() {
		$client_ip = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : null;
		
		if (self::$CLIENT_IP_ENCRYPTION_KEY) {
			$PublicPrivateKeyHandler = new PublicPrivateKeyHandler(true);
			$encoded_string = $PublicPrivateKeyHandler->encryptRSA($client_ip, self::$CLIENT_IP_ENCRYPTION_KEY, self::$CLIENT_IP_ENCRYPTION_PASSPHRASE);
			//echo "encoded_string:$encoded_string";die();
			$client_ip = substr($encoded_string, self::$CLIENT_IP_CYPHER_POSITION, self::$CLIENT_IP_CYPHER_LENGTH);
			//echo "client_ip:$client_ip";die();
		}
		
		return $client_ip;
	}
}
?>
