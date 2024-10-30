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

class Ajax {
    /**
	 * 監視結果を取得して加工
	 *
	 * @return  void
	 */
    static public function getAjaxMonitorResults() {
        check_ajax_referer('supmp-ajaxnonce', 'supmp_nonce');

        $api = WordPress\MonitoringPlusAPI::getInstance();
        $uris = $api->getMonitorURIs();

        if (!self::checkResponse($uris))
            die();

        $response = array();

        if (empty($uris["ResponseBody"])) $uris["ResponseBody"] = array();
        
        foreach ($uris["ResponseBody"] as $uri) {
            $res = $api->getMonitorResult($uri['monitor_id'], $uri['uri_id']);

            if ($res === FALSE)
                continue;
            
            $mr = [];
            if (array_key_exists(0, $res["ResponseBody"]))
                $mr = $res["ResponseBody"][0]['monitor_points'];
                
            $response[] = array(
                'uri' => $uri['uri'],
                'title' => $uri['title'],
                'mp_site_result_uri' => $uri['links']['site_monitor_detail'],
                'mp_site_config_uri' => $uri['links']['site_monitor_edit'],
                'site_id' => $uri['monitor_id'],
                'uri_id' => $uri['uri_id'],
                'monitor_points' => $mr
            );
        }

        echo json_encode($response);
        die();
	}

    /**
	 * 新規監視アラート有無を取得
	 *
	 * @return  void
	 */
    static public function getAjaxAlertOverview() {
        check_ajax_referer('supmp-ajaxnonce', 'supmp_nonce');

		$api = WordPress\MonitoringPlusAPI::getInstance();
        $alertURIs = $api->getAlertOverview();

        if (!self::checkResponse($alertURIs))
            die();

        $newAlert = FALSE;
        
        if (empty($uris["ResponseBody"])) $uris["ResponseBody"] = array();

        foreach ($alertURIs["ResponseBody"] as $alertURI) {
            foreach ($alertURI["monitor_uris"] as $uri) {
                if ($uri["monitor_alert"]["new"] == TRUE) {
                    $newAlert = TRUE;
                    break;
                }
            }
            if ($newAlert == TRUE)
                break;
        }
        echo json_encode(array("new_alert"=>$newAlert));
        die();
	}

    /**
	 * Webサイトを監視登録
	 *
	 * @return  void
	 */
    static public function registerAjaxMonitorSite() {
        check_ajax_referer('supmp-ajaxnonce', 'supmp_nonce');

        $api = WordPress\MonitoringPlusAPI::getInstance();
        $result = $api->registerMonitorSite();
        if (!self::checkResponse($result))
            die();
        
        echo json_encode($result["ResponseBody"]);
        die();
	}

    /**
	 * 監視アラートを取得
	 *
	 * @return  void
	 */
    static public function getAjaxAlert() {
        check_ajax_referer('supmp-ajaxnonce', 'supmp_nonce');

		if (!self::validateIDs())
            die();
        
        $monitID = filter_input(INPUT_POST, "monitorId", FILTER_VALIDATE_INT);
        $urlID = filter_input(INPUT_POST, "urlId", FILTER_VALIDATE_INT);

        $api = WordPress\MonitoringPlusAPI::getInstance();
        $alerts = $api->getAlert($monitID, $urlID);
        if (!self::checkResponse($alerts))
            die();
        
        echo json_encode($alerts["ResponseBody"]);
        die();
	}

    /*
     * アクセスカウント合計を取得するメソッド
     * @return void
     */
    static public function getAjaxAccessCount() {
      check_ajax_referer('supmp-ajaxnonce', 'supmp_nonce');

      $monitorId = filter_input(INPUT_POST, 'monitorId', FILTER_VALIDATE_INT);

      $api = WordPress\MonitoringPlusAPI::getInstance();
      //リクエスト当日のデータのみを取得するため、引数に日付は不要
      $accessCounts = $api->getAccessCount($monitorId);
      if(!self::checkResponse($accessCounts)){
        die();
      }

      echo json_encode($accessCounts["ResponseBody"]);
      die();
    }

    /**
	 * 監視アラートの既読化
	 *
	 * @return  void
	 */
    static public function markAjaxAlertRead() {
        check_ajax_referer('supmp-ajaxnonce', 'supmp_nonce');

        if (!self::validateIDs())
            die();

        $monitID = filter_input(INPUT_POST, "monitorId", FILTER_VALIDATE_INT);
        $urlID = filter_input(INPUT_POST, "urlId", FILTER_VALIDATE_INT);

        $api = WordPress\MonitoringPlusAPI::getInstance();
        $res = $api->markAlertRead($monitID, $urlID);
        self::checkResponse($res);
        die();
	}

    /**
	 * 監視・URIIDのバリデート
     * パスしなかったらエラーメッセージをJSONで出力して FALSE を返す
	 *
	 * @return  bool
	 */
    static private function validateIDs() {
        $monitID = filter_input(INPUT_POST, "monitorId", FILTER_VALIDATE_INT);
        $urlID = filter_input(INPUT_POST, "urlId", FILTER_VALIDATE_INT);
        if ($monitID === FALSE || $urlID === FALSE) {
            echo '{"error":"bad_request"}';
            return FALSE;
        }

        return TRUE;
    }

    /**
	 * APIからのレスポンスチェック
     * エラーの場合はエラーメッセージをJSONで出力して FALSE を返す
	 *
	 * @return  bool
	 */
    static private function checkResponse($response, $noOutput=FALSE) {
        if ($response === FALSE) {
            if (!$noOutput) echo '{"error":"critical_error"}';
            return FALSE;
        }

        if ($response["StatusCodeType"] != WordPress\MonitoringPlusAPI::STATUS_CODE_CLIENT_ERROR &&
            $response["StatusCodeType"] != WordPress\MonitoringPlusAPI::STATUS_CODE_SERVER_ERROR)
            return TRUE;

        if (is_array($response["ResponseBody"]) && array_key_exists("error", $response["ResponseBody"]))
            if (!$noOutput) echo '{"error":"'.$response["ResponseBody"]["error"].'"}';
        else
            if (!$noOutput) echo '{"error":"unknown_error"}';

        return FALSE;
    }
}
