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

class View {
    /**
	 * ページ内ヘッダ
	 *
     * @param  string $title ページタイトル
	 * @return  void
	 */
    static private function header($title) {
        $wpPluginUri = WP_PLUGIN_URL . '/monitoringplus';
        echo '<section id="supmp-main">';
		echo '<h1><img src="'.$wpPluginUri.'/static/img/page_titile_logo.png" alt="MonitoringPlus">'.$title.'</h1>';
    }

    /**
	 * ページ内フッタ
	 *
	 * @return  void
	 */
    static private function footer() {
		echo '<p>MonitoringPlusに関するお問い合わせは<a href="https://www.monitoring-plus.jp/help/contact" target="_brank">こちら</a>から。</p>';
        echo '</section>';
    }

    /**
	 * 認可用
	 *
     * @param  string $title ページタイトル
     * @param  string $authURI API側認可画面リダイレクトURI
     * @param  string $nonceField nonce field
	 * @return  void
	 */
    static public function auth($title, $authURI, $nonceField) {
        self::header($title);
        echo <<<EOT
			<section>
				<h1 class="supmp-section-title">アプリ連携</h1>
				<p>監視データを取得するために、以下のボタンよりアプリ連携を行ってください。</p>
				<form action="$authURI" method="post">
				$nonceField
				<input type="submit" id="supmp-app-authz-button" value="アプリ連携をする">
				</form>
			</section>
EOT;
        self::footer();
    }

    /**
	 * 監視結果用
	 *
     * @param  string $title ページタイトル
     * @param  string $wpPluginUri WordPress plugin URI
	 * @return  void
	 */
    static public function monitor($title, $wpPluginUri) {
        self::header($title);
        echo <<<EOT
			<section>
				<h1 class="supmp-section-title">監視結果</h1>
				<div id="supmp-monitoring_graphs">
				</div>
				<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.13.0/moment.min.js"></script>
				<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.3.0/Chart.min.js"></script>
				<script src="$wpPluginUri/static/js/monitor.js"></script>
			</section>
EOT;
        self::footer();
    }

    /**
	 * 認可画面へのリダイレクト用
	 *
     * @param  string $title ページタイトル
     * @param  string $redirectURI API側認可画面へのリダイレクトURI
	 * @return  void
	 */
    static public function authRedirect($title, $redirectURI) {
        self::header($title);
        echo <<<EOT
			<section>
				<h1 class="supmp-section-title">アプリ連携承認ページへ移動します</h1>
				<p>アプリ連携承認ページへ自動で移動します。</p>
                <p>自動で移動しない場合は<a href="$redirectURI">こちら</a>をクリックしてください。</p>
				<script>
				setTimeout("redirect()", 5000);
				function redirect(){
					location.href='$redirectURI';
				}
				</script>
			</section>
EOT;
        self::footer();
    }

    /**
	 * エラーメッセージ用
	 *
     * @param  string $message エラーメッセージ
	 * @return  void
	 */
    static public function error($message) {
        self::header("エラー");
        echo "<p>$message</p>";
        self::footer();
    }

    /**
	 * コールバック結果用
	 *
     * @param  string $title ページタイトル
     * @param  string $message メッセージ
     * @param  string $monitorURI プラグイン側監視画面URI
	 * @return  void
	 */
    static public function callbackResult($title, $message, $monitorURI=NULL) {
        self::header($title);
        $redirectMonitorPage = "";
        if (!empty($monitorURI)) {
            $redirectMonitorPage = '<script>setTimeout("redirect()", 5000);function redirect(){location.href="'.$monitorURI.'";}</script>';
        }
        echo <<<EOT
			<section>
				<h1 class="supmp-section-title">アプリ連携</h1>
				<p>$message</p>
                $redirectMonitorPage
			</section>
EOT;
        self::footer();
    }
}