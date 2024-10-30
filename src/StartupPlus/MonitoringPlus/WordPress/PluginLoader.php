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

class PluginLoader {
	/**
	 * アクション登録
	 *
	 * @return  void
	 */
	static public function init() {
		add_action('admin_menu', array('StartupPlus\MonitoringPlus\WordPress\Admin\MonitoringPage', 'addMenu'), 10, 0);
		add_action('wp_before_admin_bar_render', array('StartupPlus\MonitoringPlus\WordPress\Admin\MonitoringPage', 'addBarItem'));
		add_action('wp_print_styles', array('StartupPlus\MonitoringPlus\WordPress\Admin\MonitoringPage', 'addHeadStyles'));
		add_action('admin_print_styles', array('StartupPlus\MonitoringPlus\WordPress\Admin\MonitoringPage', 'addHeadStyles'));

		// ajaxアクション登録
		add_action('wp_ajax_supmp_get_monitor_results', array('StartupPlus\MonitoringPlus\WordPress\Admin\Ajax', 'getAjaxMonitorResults'));
		add_action('wp_ajax_supmp_get_alert_overview', array('StartupPlus\MonitoringPlus\WordPress\Admin\Ajax', 'getAjaxAlertOverview'));
		add_action('wp_ajax_supmp_get_alert', array('StartupPlus\MonitoringPlus\WordPress\Admin\Ajax', 'getAjaxAlert'));
		add_action('wp_ajax_supmp_mark_alert_read', array('StartupPlus\MonitoringPlus\WordPress\Admin\Ajax', 'markAjaxAlertRead'));
		add_action('wp_ajax_supmp_register_monitor_site', array('StartupPlus\MonitoringPlus\WordPress\Admin\Ajax', 'registerAjaxMonitorSite'));
		add_action('wp_ajax_supmp_get_access_count', array('StartupPlus\MonitoringPlus\WordPress\Admin\Ajax', 'getAjaxAccessCount'));
	}
}
