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

// アンインストール以外（WP_UNINSTALL_PLUGIN未定義）で呼び出されたら、404を返し終了する
if (!defined('WP_UNINSTALL_PLUGIN') || !WP_UNINSTALL_PLUGIN) {
	http_response_code(404);
	exit();
}

require_once(__DIR__ . '/src/StartupPlus/MonitoringPlus/WordPress/MonitoringPlusAPI.php');
require_once(__DIR__ . '/src/StartupPlus/MonitoringPlus/WordPress/Admin/MonitoringPage.php');
require_once(__DIR__ . '/src/StartupPlus/MonitoringPlus/WordPress/DB.php');
// トークン無効化
StartupPlus\MonitoringPlus\WordPress\MonitoringPlusAPI::getInstance()->revokeToken();

// アクセストークン削除
StartupPlus\MonitoringPlus\WordPress\DB::deleteAccessToken();
StartupPlus\MonitoringPlus\WordPress\DB::deleteRefreshToken();
StartupPlus\MonitoringPlus\WordPress\DB::deleteCodeVerifier();
StartupPlus\MonitoringPlus\WordPress\DB::deleteExpiresIn();
StartupPlus\MonitoringPlus\WordPress\DB::deleteRefreshTokenFlag();
StartupPlus\MonitoringPlus\WordPress\DB::deleteTokenExpiredFlag();