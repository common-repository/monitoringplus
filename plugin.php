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

/*
Plugin Name: MonitoringPlus
Plugin URI: https://www.monitoring-plus.jp/plugin/wordpress/
Description: <a href="https://www.monitoring-plus.jp/">サイト監視サービス（MonitoringPlus）</a>と連携し、WordPres管理画面で監視結果や監視アラートを見ることができます。
Version: 1.1.0
Author: Startup Plus, Inc.
Author URI: https://www.startup-plus.com/
License: MIT
*/

// 直接このファイルが呼ばれたら、404を返して終了
if (!function_exists('add_action')) {
	http_response_code(404);
	exit();
}

require_once(dirname( __FILE__ ).'/autoload.php');

add_action(
	'plugins_loaded',
	array('\StartupPlus\MonitoringPlus\WordPress\PluginLoader', 'init'),
	0,	// 優先度
	0	// 関数引数の数
);
