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

class DB {
	static private $DBKEY_ACCESSTOKEN = 'supmp_api_accesstoken';
	static private $DBKEY_REFRESHTOKEN = 'supmp_api_refreshtoken';
	static private $DBKEY_CODEVERIFIER = 'supmp_api_code_verifier';
	static private $DBKEY_EXPIRESIN = 'supmp_api_expires_in';
	static private $DBKEY_REFRESHTOKEN_FLAG = 'supmp_api_refresh_token_flag';
	static private $DBKEY_TOKENEXPIRED_FLAG = 'supmp_api_token_expired_flag';
	
	/**
	 * リフレッシュトークン取得
	 *
	 * @return  string|bool
	 */
	static public function getRefreshToken() {
		return get_option(self::$DBKEY_REFRESHTOKEN);
	}

	/**
	 * アクセストークン取得
	 *
	 * @return  string|bool
	 */
	static public function getAccessToken() {
		return get_option(self::$DBKEY_ACCESSTOKEN);
	}

	/**
	 * リフレッシュトークンセット
	 *
	 * @param  string $token セットするリフレッシュトークン
	 * @return  void
	 */
	static public function setRefreshToken($token) {
		add_option(self::$DBKEY_REFRESHTOKEN);
		update_option(self::$DBKEY_REFRESHTOKEN, $token);
	}

	/**
	 * アクセストークンセット
	 *
	 * @param  string $token セットするアクセストークン
	 * @return  void
	 */
	static public function setAccessToken($token) {
		add_option(self::$DBKEY_ACCESSTOKEN);
		update_option(self::$DBKEY_ACCESSTOKEN, $token);
	}

	/**
	 * リフレッシュトークン削除
	 *
	 * @return  void
	 */
	static public function deleteRefreshToken() {
		delete_option(self::$DBKEY_REFRESHTOKEN);
	}

	/**
	 * アクセストークン削除
	 *
	 * @return  void
	 */
	static public function deleteAccessToken() {
		delete_option(self::$DBKEY_ACCESSTOKEN);
	}
	
	/**
	 * PKCEコード取得
	 *
	 * @return  string|bool
	 */
	static public function getCodeVerifier() {
		return get_option(self::$DBKEY_CODEVERIFIER);
	}

	/**
	 * PKCEコードセット
	 *
	 * @param  string $code PKCEコード
	 * @return  void
	 */
	static public function setCodeVerifier($code) {
		add_option(self::$DBKEY_CODEVERIFIER);
		update_option(self::$DBKEY_CODEVERIFIER, $code);
	}

	/**
	 * PKCEコード削除
	 *
	 * @return  void
	 */
	static public function deleteCodeVerifier() {
		delete_option(self::$DBKEY_CODEVERIFIER);
	}

	/**
	 * アクセストークン有効期限取得
	 *
	 * @return  string|bool
	 */
	static public function getExpiresIn() {
		return get_option(self::$DBKEY_EXPIRESIN);
	}

	/**
	 * アクセストークン有効期限セット
	 *
	 * @param  string $t 有効期限
	 * @return  void
	 */
	static public function setExpiresIn($t) {
		add_option(self::$DBKEY_EXPIRESIN);
		update_option(self::$DBKEY_EXPIRESIN, $t);
	}

	/**
	 * アクセストークン有効期限削除
	 *
	 * @return  void
	 */
	static public function deleteExpiresIn() {
		delete_option(self::$DBKEY_EXPIRESIN);
	}

	/**
	 * PKCEコードを保持しているか
	 *
	 * @return  bool
	 */
	static public function hasCodeVerifier() {
		return self::getCodeVerifier() === FALSE ? FALSE : TRUE;
	}

	/**
	 * アクセス・リフレッシュトークンを保持しているか
	 *
	 * @return  bool
	 */
	static public function hasToken() {
		return self::getAccessToken() === FALSE ? FALSE : TRUE and self::getRefreshToken() === FALSE ? FALSE : TRUE;
	}

	/**
	 * アクセストークンリフレッシュ開始
	 *
	 * @return  void
	 */
	static public function setRefreshTokenStart() {
		add_option(self::$DBKEY_REFRESHTOKEN_FLAG);
		update_option(self::$DBKEY_REFRESHTOKEN_FLAG, 1);
	}
	
	/**
	 * アクセストークンリフレッシュ終了
	 *
	 * @return  void
	 */
	static public function setRefreshTokenEnd() {
		add_option(self::$DBKEY_REFRESHTOKEN_FLAG);
		update_option(self::$DBKEY_REFRESHTOKEN_FLAG, 0);
	}

	/**
	 * アクセストークンリフレッシュフラグ取得
	 *
	 * @return  int
	 */
	static public function getRefreshTokenFlag() {
		return get_option(self::$DBKEY_REFRESHTOKEN_FLAG);
	}

	/**
	 * アクセストークンリフレッシュフラグ削除
	 *
	 * @return  void
	 */
	static public function deleteRefreshTokenFlag() {
		delete_option(self::$DBKEY_REFRESHTOKEN_FLAG);
	}

	/**
	 * トークン期限切れ
	 *
	 * @return  void
	 */
	static public function setTokenExpiredFlag() {
		add_option(self::$DBKEY_TOKENEXPIRED_FLAG);
		update_option(self::$DBKEY_TOKENEXPIRED_FLAG, 1);
	}
	
	/**
	 * トークン期限切れリセット
	 *
	 * @return  void
	 */
	static public function resetTokenExpiredFlag() {
		add_option(self::$DBKEY_TOKENEXPIRED_FLAG);
		update_option(self::$DBKEY_TOKENEXPIRED_FLAG, 0);
	}

	/**
	 * トークン期限切れフラグ取得
	 *
	 * @return  int
	 */
	static public function getTokenExpiredFlag() {
		return get_option(self::$DBKEY_TOKENEXPIRED_FLAG);
	}

	/**
	 * トークン期限切れフラグ削除
	 *
	 * @return  void
	 */
	static public function deleteTokenExpiredFlag() {
		delete_option(self::$DBKEY_TOKENEXPIRED_FLAG);
	}
}