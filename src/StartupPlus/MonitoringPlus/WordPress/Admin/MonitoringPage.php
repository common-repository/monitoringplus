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

namespace StartupPlus\MonitoringPlus\WordPress\Admin;

use StartupPlus\MonitoringPlus\WordPress as WordPress;

class MonitoringPage {
	const CALLBACK_ERR_AUTH_FAILED = "authorization_failed";
	const CALLBACK_ERR_USER_DENIED = "user_denied";

	const PageSlug = 'MonitoringPlus';

	/**
	 * スタイルシート追加
	 *
	 * @return  void
	 */
	static public function addHeadStyles() {
		$css_url = WP_PLUGIN_URL . '/monitoringplus/static/css/style.css';
		wp_enqueue_style('wp-monitoringplus-style', $css_url, false, '1');
	}

	/**
	 * バーアイテム追加
	 *
	 * @return  void
	 */
	static public function addBarItem() {
		global $wp_admin_bar;
		$nonce = wp_create_nonce("supmp-ajaxnonce");
		$ajaxuri = admin_url('admin-ajax.php');
		$pluginuri = WP_PLUGIN_URL.'/monitoringplus';
		$js_url = WP_PLUGIN_URL . '/monitoringplus/static/js/alert_header.js';
		$monitorPage = admin_url(sprintf("admin.php?page=%s", self::PageSlug));
		$wp_admin_bar->add_menu(array(
			'id'		=>	'monitoringplus-alert-header',
			'href'		=>	$monitorPage,
			'title'		=>	'<span class="ab-icon"></span><span class="ab-label hide">?</span><script>var supmp_ajaxnonce="'.$nonce.'"; var supmp_ajaxuri="'.$ajaxuri.'";var supmp_pluginuri="'.$pluginuri.'";</script><script src="'.$js_url.'"></script>',
		));
	}

	/**
	 * メニュー追加
	 *
	 * @return  void
	 */
	static public function addMenu() {
		$classname = get_called_class();
		add_menu_page(
			'監視画面 - MonitoringPlus',
			'<span id="supmp-menu-icon"></span>監視画面',
			'manage_options',
			self::PageSlug,
			array($classname, 'exec'),
			'none',
			'61.0219'
		);
	}

	/**
	 * ページ描画
	 *
	 * @return  void
	 */
	static public function exec() {
		$mode = (string)filter_input(INPUT_GET, 'mode');
		if (!empty($mode)) {
			self::execMode($mode);
		} else if (!WordPress\DB::hasToken() || WordPress\DB::getTokenExpiredFlag()) {
			self::renderAuthPage();
		} else {
			self::renderMonitorPage();
		}
	}

	/**
	 * 初期画面描画
	 *
	 * @return  void
	 */
	static private function renderAuthPage() {
		$auth_uri = admin_url("admin.php?mode=auth&page=".self::PageSlug);
		$nonceField = wp_nonce_field("supmp-auth", "supmp_nonce", TRUE, FALSE);
		WordPress\Admin\View::auth("初期設定", $auth_uri, $nonceField);
	}

	/**
	 * 監視結果画面描画
	 *
	 * @return  void
	 */
	static private function renderMonitorPage() {
		$wpPluginUri = WP_PLUGIN_URL . '/monitoringplus';
		WordPress\Admin\View::monitor("監視画面", $wpPluginUri);
	}

	/**
	 * OAuth2用処理ページ描画
	 * mode により描画するページを切り替え
	 *
	 * @return  void
	 */
	static private function execMode($mode) {
		$api = new WordPress\MonitoringPlusAPI();

		if ($mode == "auth") {
			if (self::validateNonce() && (!WordPress\DB::hasToken() || WordPress\DB::getTokenExpiredFlag())) {
				$redirectURI = $api->getRedirectAuthorizationURI();
				if ($redirectURI === FALSE)
					WordPress\Admin\View::error("エラーが発生しました。最初からやり直してください。");
				else
					WordPress\Admin\View::authRedirect("初期設定", $redirectURI);
			} else {
				WordPress\Admin\View::error("無効なアクセスです。");
			}
		} else if ($mode == "callback") {
			$callbackType = self::validateCallback();
			if ($callbackType === FALSE) {
				WordPress\Admin\View::error("無効なアクセスです。");
				return;
			} else if ($callbackType == self::CALLBACK_ERR_USER_DENIED) {
				WordPress\Admin\View::callbackResult("初期設定", "アプリ連携をキャンセルしました。");
				return;
			} else if ($callbackType == self::CALLBACK_ERR_AUTH_FAILED) {
				WordPress\Admin\View::callbackResult("初期設定", "サーバでエラーが発生しました。お手数ですが最初からやり直してください。");
				return;
			}

			if (!$api->requestToken($callbackType)) {
				WordPress\Admin\View::callbackResult("初期設定", "承認に失敗しました。お手数ですが最初からやり直してください。");
			} else {
				WordPress\Admin\View::callbackResult("初期設定", "承認が完了しました。監視画面へ移動します。", admin_url("admin.php?page=".self::PageSlug));
			}
		} else {
			WordPress\Admin\View::error("無効なアクセスです。");
		}
	}

	/**
	 * 認可画面からのコールバックをバリデート
	 *
	 * @return  string|bool
	 */
	static private function validateCallback() {
		$code = (string)filter_input(INPUT_GET, 'code');
		if (empty($code)) {
			$error = (string)filter_input(INPUT_GET, 'error');
			if ($error == self::CALLBACK_ERR_USER_DENIED || $error == self::CALLBACK_ERR_AUTH_FAILED) {
				return $error;
			}
			return FALSE;
		}
		
		if (preg_match('/^[a-zA-Z0-9-~_\.]{43,128}$/', $code) === 1)
			return $code;

		return FALSE;
	}

	/**
	 * Nonce バリデート
	 *
	 * @return  bool
	 */
	static private function validateNonce() {
		 if (!(string)filter_input(INPUT_POST, 'supmp_nonce'))
		 	return FALSE;

		 return check_admin_referer('supmp-auth', 'supmp_nonce') && current_user_can('manage_options');
	}
}