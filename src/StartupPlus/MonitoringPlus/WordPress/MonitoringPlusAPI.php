<?php
/* 
The MIT License (MIT)
Copyright (c) 2016 Startup Plus Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in the
Software without restriction, including without limitation the rights to use, copy,
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
and to permit persons to whom the Software is furnished to do so, subject to the
following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace StartupPlus\MonitoringPlus\WordPress;
use StartupPlus\MonitoringPlus\WordPress as WordPress;

class MonitoringPlusAPI {
	const STATUS_CODE_UNKNOWN = 0;
	const STATUS_CODE_OK = 2;
	const STATUS_CODE_REDIRECT = 3;
	const STATUS_CODE_CLIENT_ERROR = 4;
	const STATUS_CODE_SERVER_ERROR = 5;

	static private $instance;

	private $apiBaseURI;
	private $adminURI;
	private $redirectURI;
	private $cmsBaseURI;
	private $clientID;
	private $siteName;

	function __construct() {
		$this->apiBaseURI = 'https://api.monitoring-plus.jp/v1';
		$this->adminURI = admin_url(sprintf("admin.php?page=%s", WordPress\Admin\MonitoringPage::PageSlug));
		$this->redirectURI = sprintf("%s&mode=callback", $this->adminURI);
		$this->cmsBaseURI = site_url('/');
		$this->clientID = 'WWtHUhNotCSV2vdT';
		$this->siteName = get_bloginfo("name");
	}

	static public function getInstance() {
		if (self::$instance === null)
			self::$instance = new self();

		return self::$instance;
	}

	/**
	 * アラート概要取得
	 *
	 * @return  array|bool
	 */
	public function getAlertOverview() {
		return $this->callEndpoint("/monitor/alerts", TRUE);
	}

	/**
	 * アラート取得
	 *
	 * @param  string $monitorID 監視ID
	 * @param string $uriID
	 * @return  array|bool
	 */
	public function getAlert($monitrID, $uriID) {
		return $this->callEndpoint("/monitor/alerts/".$monitrID."/".$uriID, TRUE);
	}

	/**
	 * アラート既読
	 *
	 * @param  string $monitorID 監視ID
	 * @param string $uriID URIID
	 * @return  array|bool
	 */
	public function markAlertRead($monitrID, $uriID) {
		return $this->callEndpoint("/monitor/alerts/".$monitrID."/".$uriID, TRUE, '{"read":true}', array('Content-Type: application/json; charset=utf-8'));
	}

	/**
	 * 監視結果取得
	 *
	 * @param  string $monitorID 監視ID
	 * @param string $uriID URIID NULLの場合は監視IDに紐付く全ての監視結果を取得
	 * @return  array|bool
	 */
	public function getMonitorResult($monitorID, $uriID = NULL) {
		if (empty($uriID))
			return $this->callEndpoint("/monitor/results/".$monitorID, TRUE);

		return $this->callEndpoint("/monitor/results/".$monitorID."/".$uriID, TRUE);
	}

	/**
	 * 登録済み全監視サイトのURIを取得
	 *
	 * @return  array|bool
	 */
	public function getMonitorURIs() {
		return $this->callEndpoint("/monitor/uris", TRUE);
	}

	/**
	 * WordPressサイトを監視対象として登録
	 *
	 * @return  array|bool
	 */
	public function registerMonitorSite() {
		$querys = array(
			"site_name" => $this->siteName,
			"site_uri" => $this->cmsBaseURI,
			"unique" => TRUE
		);
		$jsonQuery = json_encode($querys);
		if ($jsonQuery === FALSE)
			return FALSE;
		
		$response = $this->callEndpoint("/monitor/uris", TRUE, $jsonQuery, array('Content-Type: application/json; charset=utf-8'));

		return $response;
	}

	/*
	 * アクセスカウント合計を取得するメソッド
	 *
	 * @param  string $monitorID 監視ID
	 * @param string $date 取得したい情報の日付。nullの場合はリクエスト当日の情報を取得する
	 * @return  array|bool
	 */
	public function getAccessCount($monitorID, $date = NULL) {
		if (empty($date))
			return $this->callEndpoint("/monitor/trackings/accesses/visitors/".$monitorID, TRUE);

		return $this->callEndpoint("/monitor/trackings/accesses/visitors/".$monitorID."/".$date, TRUE);
	}

	/**
	 * アクセストークン・リフレッシュトークン発行
	 *
	 * @param  string $authCode 認可コード
	 * @return  array|bool
	 */
	public function requestToken($authCode) {
		if (!WordPress\DB::hasCodeVerifier())
			return FALSE;

		$querys = array(
			"grant_type" => "authorization_code",
			"client_id" => $this->clientID,
			"redirect_uri" => urlencode($this->redirectURI),
			"cms_base_uri" => urlencode($this->cmsBaseURI),
			"code_verifier" => WordPress\DB::getCodeVerifier(),
			"code" => $authCode
		);

		WordPress\DB::deleteCodeVerifier();

		$response = $this->callEndpoint("/oauth2/token", FALSE, $querys);

		if ($response === FALSE)
			return FALSE;

		if ($response["StatusCodeType"] == self::STATUS_CODE_CLIENT_ERROR || $response["StatusCodeType"] == self::STATUS_CODE_SERVER_ERROR)
			return $response;

		if (!$this->validateResponseToken($response["ResponseBody"]))
			return FALSE;

		WordPress\DB::setAccessToken($response["ResponseBody"]["access_token"]);
		WordPress\DB::setRefreshToken($response["ResponseBody"]["refresh_token"]);
		WordPress\DB::setExpiresIn($response["ResponseBody"]["expires_in"]);
		WordPress\DB::resetTokenExpiredFlag();

		return array(
			"StatusCode" => 204,
			"StatusCodeType" => self::STATUS_CODE_OK,
			"ResponseBody" => NULL
		);
	}

	/**
	 * アクセストークン・リフレッシュトークン無効化
	 *
	 * @return  array|bool
	 */
	public function revokeToken() {
		if (!WordPress\DB::hasToken() && WordPress/DB::getTokenExpiredFlag())
			return FALSE;

		$querys = array(
			"client_id" => $this->clientID,
			"cms_base_uri" => $this->cmsBaseURI,
			"token" => WordPress\DB::getRefreshToken()
		);

		$response = $this->callEndpoint("/oauth2/revoke", FALSE, $querys);

		return $response;
	}

	/**
	 * アクセストークンリフレッシュ
     *
	 * @access  private
	 * @return  array|bool
	 */
	private function refreshToken() {
		// すでにリフレッシュ処理中の場合は、リフレッシュ処理完了後まで待機する。
		if (WordPress\DB::getRefreshTokenFlag() == 1) {
			while (WordPress\DB::getRefreshTokenFlag() == 1)
				sleep(1);
			return array(
				"StatusCode" => 204,
				"StatusCodeType" => self::STATUS_CODE_OK,
				"ResponseBody" => NULL
			);
		}

		WordPress\DB::setRefreshTokenStart();

		$querys = array(
			"grant_type" => "refresh_token",
			"client_id" => $this->clientID,
			"cms_base_uri" => $this->cmsBaseURI,
			"refresh_token" => WordPress\DB::getRefreshToken()
		);

		$response = $this->callEndpoint("/oauth2/token", FALSE, $querys);
		if ($response === FALSE) {
			WordPress\DB::setRefreshTokenEnd();
			return FALSE;
		}

		if ($response["StatusCodeType"] == self::STATUS_CODE_CLIENT_ERROR || $response["StatusCodeType"] == self::STATUS_CODE_SERVER_ERROR) {
			if (array_key_exists("error", $response["ResponseBody"]) && $response["ResponseBody"]["error"] == "invalid_grant") {
				WordPress\DB::setTokenExpiredFlag();
			}
			WordPress\DB::setRefreshTokenEnd();
			return $response;
		}
			
		if (!$this->validateRefreshResponse($response["ResponseBody"])) {
			WordPress\DB::setRefreshTokenEnd();
			return FALSE;
		}
		
		WordPress\DB::setAccessToken($response["ResponseBody"]["access_token"]);
		WordPress\DB::setExpiresIn($response["ResponseBody"]["expires_in"]);

		WordPress\DB::setRefreshTokenEnd();
		return array(
			"StatusCode" => 204,
			"StatusCodeType" => self::STATUS_CODE_OK,
			"ResponseBody" => NULL
		);
	}

	/**
	 * APIエンドポイント呼び出し
	 *
	 * @param  string $endpoint APIエンドポイント
	 * @param  bool $auth 呼び出すAPIエンドポイントへアクセストークンを付加するか
	 * @param  array $querys リクエストクエリ
	 * @param  array $headers リクエストヘッダ
	 * @param  bool $noRefreshToken アクセストークン無効のレスポンスでもアクセストークンのリフレッシュを行わないか
	 * @return  array|bool
	 */
	private function callEndpoint($endpoint, $auth=FALSE, $querys=array(), $headers=array(), $noRefreshToken=FALSE) {
		$heds = $headers;
		if ($auth) {
			if (!WordPress\DB::hasToken())
				return FALSE;
			
			if (WordPress\DB::getTokenExpiredFlag())
				return array(
					"StatusCode" => 400,
					"StatusCodeType" => self::STATUS_CODE_CLIENT_ERROR,
					"ResponseBody" => array(
						"error" => "invalid_grant"
					)
				);
			$heds[] = "Authorization: Bearer ".WordPress\DB::getAccessToken();
		}

		$curl = curl_init($this->apiBaseURI.$endpoint);

		curl_setopt($curl, CURLOPT_FORBID_REUSE, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		if (!empty($heds))
			curl_setopt($curl, CURLOPT_HTTPHEADER, $heds);
		if (!empty($querys)) {
			if (is_array($querys))
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($querys));
			else
				curl_setopt($curl, CURLOPT_POSTFIELDS, $querys);
		}

		$responseBody = curl_exec($curl);
		$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		
		if ($responseBody === FALSE || $statusCode === FALSE)
			return FALSE;

		$statusCodeType = $this->getStatusCodeType($statusCode);

		if (empty($responseBody) || $responseBody === "null") {
			return array(
				"StatusCode" => $statusCode,
				"StatusCodeType" => $statusCodeType,
				"ResponseBody" => NULL
			);
		}

		$json = json_decode($responseBody, TRUE);

		if ($json === NULL)
			return FALSE;

		if (!$auth || !($statusCode == 403 && array_key_exists("error", $json) && $json["error"] == "invalid_token") || $noRefreshToken)
			return array(
				"StatusCode" => $statusCode,
				"StatusCodeType" => $statusCodeType,
				"ResponseBody" => $json
			);

		$refreshTokenResult = $this->refreshToken();
		if ($refreshTokenResult === FALSE)
			return FALSE;
		if ($refreshTokenResult["StatusCodeType"] == self::STATUS_CODE_CLIENT_ERROR || $refreshTokenResult["StatusCodeType"] == self::STATUS_CODE_SERVER_ERROR)
			return $refreshTokenResult;
		
		return $this->callEndpoint($endpoint, $auth, $querys, $headers, TRUE);
	}

	/**
	 * 認可画面へのリダイレクトURI取得
	 *
	 * @return  string|bool
	 */
	public function getRedirectAuthorizationURI() {
		$codeChallenge = $this->createCodeChallenge();
		if (!$codeChallenge) {
			return FALSE;
		}
		return
			sprintf("%s/oauth2/auth?response_type=code&client_id=%s&redirect_uri=%s&cms_base_uri=%s&code_challenge=%s",
				$this->apiBaseURI,
				$this->clientID,
				urlencode($this->redirectURI),
				urlencode($this->cmsBaseURI),
				$codeChallenge
			);
	}

	/**
	 * トークンリフレッシュのレスポンスボディバリデーション
	 *
	 * @param  string $res レスポンスボディ
	 * @return  bool
	 */
	private function validateRefreshResponse($res) {
		if ( !(array_key_exists("token_type", $res) &&
			array_key_exists("access_token", $res) &&
			array_key_exists("expires_in", $res)) )
			return FALSE;

		if ($res["token_type"] != "bearer")
			return FALSE;

		return ctype_digit($res["expires_in"]) and preg_match('/^[a-zA-Z0-9-~_\.\+\/]{1,256}$/', $res["access_token"]) === 1;
	}

	/**
	 * アクセストークン発行のレスポンスボディバリデーション
	 *
	 * @param  string $res レスポンスボディ
	 * @return  bool
	 */
	private function validateResponseToken($res) {
		if (!((array_key_exists("error", $res) || array_key_exists("error_type", $res)) xor
			(array_key_exists("token_type", $res) &&
			array_key_exists("access_token", $res) &&
			array_key_exists("refresh_token", $res) &&
			array_key_exists("expires_in", $res))))
			return FALSE;

		if (array_key_exists("error", $res) || array_key_exists("error_type", $res))
			return TRUE;

		if ($res["token_type"] != "bearer")
			return FALSE;

		return ctype_digit($res["expires_in"]) and
			preg_match('/^[a-zA-Z0-9-~_\.\+\/]{1,256}$/', $res["access_token"]) === 1 and
			preg_match('/^[a-zA-Z0-9-~_\.\+\/]{1,256}$/', $res["refresh_token"]) === 1;
	}

	/**
	 * コードチャレンジ生成
	 *
	 * @return  string|bool
	 */
	private function createCodeChallenge() {
		$code = $this->generateCode(32);
		if (!$code)
			return FALSE;
		WordPress\DB::setCodeVerifier($code);
		return rtrim(strtr(base64_encode(hash("sha256", $code)), '+/', '-_'), '=');
	}

	/**
	 * コード生成
	 *
	 * @param  int $length コード長さ（バイト）
	 * @return  string|bool
	 */
	private function generateCode($length = 32) {
		$code = "";
		for ($i = 0; $i<1024; $i++) {
			$byte = hexdec(bin2hex(openssl_random_pseudo_bytes(1)));
			if (!(
				($byte >= 48 && $byte <= 57) ||	// 0-9
				($byte >= 65 && $byte <= 90) ||	// A-Z
				($byte >= 97 && $byte <= 122) ||// a-z
				$byte == 45 ||	// -
				$byte == 46 ||	// .
				$byte == 95	||	// _
				$byte == 126	// ~
			)) continue;
			$code .= chr($byte);
			if (strlen($code) == $length) break;
		}
		if (strlen($code) == $length)
			return $code;
		return FALSE;
	}

	/**
	 * レスポンスステータスコードタイプ取得
	 *
	 * @param  string $statusCode ステータスコード
	 * @return  int
	 */
	private function getStatusCodeType($statusCode) {
		$codeType = (int)floor((int)$statusCode / 100);
		if ($codeType >= 1 && $codeType <= 5)
			return $codeType;
		return self::STATUS_CODE_UNKNOWN;
	}
}
