(() => {
  //グラフ表示の処理が終わるまでgif画像を表示しておく。
  //グラフ表示が完了したらコールバック関数内で追加したDOM要素を削除する
  window.addEventListener('load', () => {
    appendNowLoadingIcon(document.getElementById('supmp-monitoring_graphs'));
    //ここでpageLoad関数を呼んでいる。ここがエントリーポイント
    setTimeout(pageLoad, 1000);
  });

  //ただ単にinit関数を呼んでいるだけ。何の意味があるのか？
  function pageLoad(){
    init();
  }

  //実際に処理を行う関数で合っているはず
  function init(){
    const AjaxUri = supmp_ajaxuri,
      AjaxNonce = supmp_ajaxnonce,
      graphsSectionElement = document.getElementById('supmp-monitoring_graphs'),
      errorMessageElement = document.createElement('span'),
      monitorResultOptions = {
        type: 'POST',
        url: AjaxUri,
        data: {
          action: 'supmp_get_monitor_results',
          supmp_nonce: AjaxNonce
        },
        timeout: 10000
      };
    errorMessageElement.className = 'supmp-errmsg';
    jQuery.ajax(monitorResultOptions)
      .done((data, textStatus, jqXHR) => {
        removeNowLoadingIcon(graphsSectionElement);
        try{
          const response = JSON.parse(data);
          if('error' in response){
            switch(response['error']){
              case 'invalid_grant':
                errorMessageElement.textContent = 'トークンの有効期限が切れました。このページをリロードして再度連携承認を行ってください。';
                break;
              case 'invalid_token':
                errorMessageElement.textContent = 'トークンが無効です。このプラグインを一旦削除し、もう一度インストールしてください。';
                break;
              case 'rate_limit_exceeded':
                errorMessageElement.textContent = 'リクエスト制限を超えています。しばらく時間を置いてから再度アクセスしてください。';
                break;
              default:
                errorMessageElement.textContent = '不明なエラーが発生しました。しばらく時間をおいてから再度試してください。';
                break;
            }
            graphsSectionElement.appendChild(errorMessageElement);
          }
          const graphList = createMonitorResultGraph(response);
          graphList.forEach(elem => {
            elem.createElements();
            elem.createMonitorResultDataSets();
            elem.createAccessCountDataSets();
            elem.rendering();
          });
        }catch(error){
          errorMessageElement.textContent = '不明なエラーが発生しました。しばらく時間をおいてから再度試してください。'
          graphsSectionElement.appendChild(errorMessageElement);
          return; // 処理を終了させるためだけのreturn
        }
      })
      .fail((jqXHR, textStatus, errorThrown) => {
        removeNowLoadingIcon(graphsSectionElement);
        errorMessageElement.textContent = '読み込みに失敗しました。しばらく時間をおいてから再度試してください。';
        graphsSectionElement.appendChild(errorMessageElement);
      });
  }

  /*
   * @param array data JSONをパースしたデータ
   * @return void
   */
  function createMonitorResultGraph(data){
    if(data.length === 0){
      jQuery('<p id="supmp-notfound-monitorsite-message">監視対象が見つかりませんでした。<br>新しくこのWordPressを監視対象として登録する場合は、<a href="#" id="supmp-register-monitor-site-button">ここ</a>をクリックしてください。</p> ').insertAfter("#supmp-main > section > h1.supmp-section-title");
      jQuery("#supmp-register-monitor-site-button").click(registerMonitorSite);
    }else{
      const graphList = data.map(elem => {
        return new MonitorResultGraph(
          elem['title'],
          elem['uri'],
          elem['mp_site_result_uri'],
          elem['mp_site_config_uri'],
          elem['site_id'],
          elem['uri_id'],
          elem['monitor_points']
        );
      });
      return graphList;
    }
  }

  /*
   * @param HTMLElement element now loading icon要素の親要素
   * @return void
   */
  function removeNowLoadingIcon(element){
    //elementが子要素にnow loading iconを持っているか
    if(element.hasChildNodes()){
      const nowLoadingIconElement = document.getElementById('supmp-nowloading-icon');
      [].forEach.call(element.children, (child) => {
        if(child === nowLoadingIconElement){
          element.removeChild(nowLoadingIconElement);
        }
      });
    }
  }

  /*
   * @param HTMLElement element now loading icon要素を追加させる要素
   * @return void
   */
  function appendNowLoadingIcon(element){
    const PluginUri = supmp_pluginuri,
      nowLoadingIconElement = document.createElement('img');
    nowLoadingIconElement.id = 'supmp-nowloading-icon';
    nowLoadingIconElement.src = PluginUri + '/static/img/nowloading.gif';
    element.appendChild(nowLoadingIconElement);
  }

  /*
   * コンストラクタ呼び出しを期待されるため、関数名の頭文字は大文字にする
   * グラフ表示に関する作業を行うオブジェクト
   * @param string title,
   * @param string monitorUrl
   * @param string mpSiteResultUrl
   * @param string mpSiteConfigUrl
   * @param string monitorId
   * @param string urlId
   * @param string monitorPoints
   * @return vlid
   */
  function MonitorResultGraph(title, monitorUrl, mpSiteResultUrl, mpSiteConfigUrl, monitorId, urlId, monitorPoints){
    this.title = title;
    this.monitorUrl = monitorUrl;
    this.mpSiteResultUrl = mpSiteResultUrl;
    this.mpSiteConfigUrl = mpSiteConfigUrl;
    this.monitorPoints = monitorPoints;
    this.sectionElId = 'supmp-' + monitorId + '-' + urlId + '-section';
    this.canvasElId = 'supmp-' + monitorId + '-' + urlId + '-graph';
    this.monitorId = monitorId;
    this.urlId = urlId;

    this.resultMonitorResult = null;
    this.graph = null;
    this.openAlertSection = false;
    this.resultAccessCount = null;
  }
  MonitorResultGraph.prototype = {
    //メソッドとして呼び出されることを期待するため、thisを束縛しないアロー関数は使えない
    createElements: function(){
      this.titleEl = document.createElement('h1');
      this.titleEl.textContent = this.title;
      this.sectionEl = document.createElement('section');
      this.sectionEl.id = this.sectionElId;
      this.canvasEl = document.createElement('canvas');
      this.canvasEl.id = this.canvasElId;

      this.alertSectionEl = document.createElement('section');
      this.alertSectionEl.className = 'supmp-alert';
      const alertAnchor = document.createElement('a');
      alertAnchor.href = '#';
      alertAnchor.text = 'アラート ▼';
      const alertHeader = document.createElement('h1');
      alertHeader.appendChild(alertAnchor);
      this.alertSectionEl.appendChild(alertHeader);

      this.MPSiteResultButtomEl = document.createElement('a');
      this.MPSiteResultButtomEl.textContent = '結果の詳細を見る';
      this.MPSiteResultButtomEl.href = this.mpSiteResultUrl;
      this.MPSiteResultButtomEl.target = '_blank';

      this.MPSiteConfigButtomEl = document.createElement('a');
      this.MPSiteConfigButtomEl.textContent = '設定を変更する';
      this.MPSiteConfigButtomEl.href = this.mpSiteConfigUrl;
      this.MPSiteConfigButtomEl.target = '_blank';

      this.graphsSectionEl = document.getElementById('supmp-monitoring_graphs');
      this.graphsSectionEl.appendChild(this.sectionEl);
      this.sectionEl.appendChild(this.alertSectionEl);
      this.sectionEl.appendChild(this.titleEl);
      this.sectionEl.appendChild(this.canvasEl);
      this.sectionEl.appendChild(this.MPSiteResultButtomEl);
      this.sectionEl.appendChild(this.MPSiteConfigButtomEl);
    },
    rendering: function(){
      if(this.graph !== null){
        // グラフを再描画する際はインスタンスを破棄する必要がある
        // 破棄しないと再描画前のグラフが表示される場合がある
        this.graph.destroy()
      }
      this.graph = new Chart(this.canvasEl.getContext('2d'),
        this.configureGraph());
    },
    createMonitorResultDataSets: function(){
      const monitorPointBorderColor = ['rgba(47,126,216, 0.6)', 'rgba(13,35,3, 0.6)', 'rgba(139,188,33, 0.6)', 'rgba(145,0,0, 0.6)', 'rgba(26,173,206, 0.6)'],
        monitorPointNames = ['東京', 'シンガポール', 'アメリカ西海岸', 'アメリカ東海岸', 'アイルランド(ヨーロッパ)'],
        resultDatasets = this.monitorPoints.map(elem => {
          let labelName = monitorPointNames[elem['monitor_point'] - 1],
            datas = elem['monitor_results']['response_times'],
            results = datas.map(elem => {
              return {
                x: moment.unix(elem['date']),
                y: elem['time'] / 1000
              };
            });
          return {
            label: labelName,
            data: results,
            fill: false,
            borderColor: monitorPointBorderColor[elem['monitor_point'] - 1],
            backgroundColor: monitorPointBorderColor[elem['monitor_point'] - 1],
            pointBorderColor: monitorPointBorderColor[elem['monitor_point'] - 1],
            pointBackgroundColor: monitorPointBorderColor[elem['monitor_point'] - 1],
            pointBorderWidth: 0,
            borderWidth: 2,
            radius: 1
          };
        });
      this.resultMonitorResult = resultDatasets;
    },
    createAccessCountDataSets: function(){
      const graphsSectionElement = document.getElementById('supmp-monitoring_graphs'),
        errorMessageElement = document.createElement('span');
      getAccessCount(this.monitorId)
        .done((data, textStatus, jqXHR) => {
          removeNowLoadingIcon(graphsSectionElement);
          try{
            const response = JSON.parse(data),
              responseIsArray = Array.isArray(response);
            //if(responseIsArray && 'error' in response){
            if(!responseIsArray){
              //switch(response['error']){
              switch(response.error){
                case 'invalid_grant':
                  errorMessageElement.textContent = 'トークンの有効期限が切れました。このページをリロードして再度連携承認を行ってください。';
                  break;
                case 'invalid_token':
                  errorMessageElement.textContent = 'トークンが無効です。このプラグインを一旦削除し、もう一度インストールしてください。';
                  break;
                case 'rate_limit_exceeded':
                  errorMessageElement.textContent = 'リクエスト制限を超えています。しばらく時間を置いてから再度アクセスしてください。';
                  break;
                case 'resource_not_found':
                  errorMessageElement.textContent = 'リソースが存在しません。アクセスカウント機能を利用できない可能性があります。';
                  break;
                default:
                  errorMessageElement.textContent = '不明なエラーが発生しました。しばらく時間をおいてから再度試してください。';
                  break;
              }
              graphsSectionElement.appendChild(errorMessageElement);
            }
            if(responseIsArray){
              this.resultAccessCount = response.map(elem => {
                return {
                  x: moment(elem.datetime, 'YYYYMMDDHHmm'),
                  y: elem.visitors
                };
              });
              this.rendering(); // 再描画
            }
          }catch(error){
            errorMessageElement.textContent = '不明なエラーが発生しました。しばらく時間をおいてから再度試してください。'
            graphsSectionElement.appendChild(errorMessageElement);
            return; // 処理を終了させるためだけのreturn
          }
        })
        .fail((jqXHR, textStatus, errorThrown) => {
          removeNowLoadingIcon(graphsSectionElement);
          errorMessageElement.textContent = '読み込みに失敗しました。しばらく時間をおいてから再度試してください。';
          graphsSectionElement.appendChild(errorMessageElement);
        });
    },
    configureGraph: function(){
      const accessData = {
        label: 'アクセス合計（回）',
        data: this.resultAccessCount,
        fill: false,
        radius: 1,
        yAxisID: 'access'
      };
      let datasets = this.resultMonitorResult,
        yaxis = [{
          scaleLabel: {
            display: true,
            labelString: '応答時間（秒）'
          },
          ticks: {
            min: 0
          }
        }],
        accessAxis = {
          scaleLabel: {
            display: true,
            labelString: 'アクセス合計（回）',
          },
          ticks: {
            min: 0
          },
          id: 'access',
          position: 'right',
          gridLines: {
            display: false
          },
        };

      if(this.resultAccessCount !== null){
        datasets = this.resultMonitorResult.concat(accessData);
        yaxis.push(accessAxis);
      }
      const config = {
        type: 'line',
        data: {
          datasets: datasets
        },
        options: {
          responsive: true,
          title: {
            display: true,
            text: this.title
          },
          scales: {
            xAxes: [{
              type: 'time',
              scaleLabel: {
                display: true,
                labelString: '監視日時'
              },
              time: {
                round: 'minute',
                displayFormats: {
                  second: 'MM/DD HH:mm',
                  minute: 'MM/DD HH:mm',
                  hour: 'MM/DD HH:mm',
                  day: 'MM/DD HH:mm'
                },
                tooltipFormat: 'YYYY/MM/DD HH:mm',
                minUnit: 'minute'
              }
            }],
            yAxes: /*[{
              scaleLabel: {
                display: true,
                labelString: '応答時間（秒）'
              },
              ticks: {
                min: 0
              }
            },{
              scaleLabel: {
                display: true,
                labelString: 'アクセス合計（回）',
              },
              ticks: {
                min: 0
              },
              id: 'access',
              position: 'right',
              gridLines: {
                display: false
              },
            }]*/
            yaxis
          }
        }
      };
      return config;
    }
  }

  function registerMonitorSite() {
    jQuery("#supmp-notfound-monitorsite-message").text("このWordPressを監視登録中です。しばらく時間がかかる場合があります。");
    const graphsSectionElement = document.getElementById('supmp-monitoring_graphs'),
      AjaxUri = supmp_ajaxuri,
      AjaxNonce = supmp_ajaxnonce,
      registerOptions = {
        type: 'POST',
        url: AjaxUri,
        data: {
          'action': 'supmp_register_monitor_site',
          'supmp_nonce': AjaxNonce
        },
        timeout: 80000
      };
    appendNowLoadingIcon(graphsSectionElement);
    jQuery.ajax(registerOptions)
      .done(function (data, textStatus, jqXHR) {
        graphsSectionEl.removeChild(nowLoadingIconEl);
        try{
          var res = JSON.parse(data);
        }catch(e){
          var errMsg = document.createElement('span');
          errMsg.className = "supmp-errmsg";
          errMsg.textContent = "不明なエラーが発生しました。しばらく時間をおいてから再度試してください。";
          graphsSectionEl.appendChild(errMsg);
          return;
        }
        if ("error" in res) {
          const errMsg = document.createElement('span');
          errMsg.className = "supmp-errmsg";
          if (res["error"] === "invalid_grant") {
            errMsg.textContent = "トークンの有効期限が切れました。このページをリロードして再度連携承認を行ってください。";
            graphsSectionEl.appendChild(errMsg);
          } else if (res["error"] === "invalid_token") {
            errMsg.textContent = "トークンが無効です。このプラグインを一旦削除し、もう一度インストールしてください。";
            graphsSectionEl.appendChild(errMsg);
          } else if (res["error"] === "upper_limit") {
            errMsg.textContent = "監視登録数が上限に達していたため、登録できませんでした。上位プランへ変更していただくか、不要な監視を削除してください。";
            graphsSectionEl.appendChild(errMsg);
          } else if (res["error"] === "invalid_request") {
            errMsg.textContent = "登録に失敗しました。監視サーバよりこのWordPressへアクセスできるか確認してください。";
            graphsSectionEl.appendChild(errMsg);
          } else if (res["error"] === "rate_limit_exceeded") {
            errMsg.textContent = "リクエスト制限を超えています。しばらく時間を置いてから再度アクセスしてください。";
            graphsSectionEl.appendChild(errMsg);
          } else {
            errMsg.textContent = "不明なエラーが発生しました。しばらく時間をおいてから再度試してください。";
            graphsSectionEl.appendChild(errMsg);
          }
        } else {
          location.reload(true);
        }
      })
      .fail(function (jqXHR, textStatus, errorThrown) {
        const errMsg = document.createElement('span');
        errMsg.className = "supmp-errmsg";
        errMsg.textContent = "登録に失敗しました。お手数ですがMonitoringPlusのダッシュボードより、このWordPressを監視登録してください。";
        graphsSectionEl.appendChild(errMsg);
      });
  }

  function renderNotFoundMonitorSiteMessage() {
    jQuery('<p id="supmp-notfound-monitorsite-message">監視対象が見つかりませんでした。<br>新しくこのWordPressを監視対象として登録する場合は、<a href="#" id="supmp-register-monitor-site-button">ここ</a>をクリックしてください。</p> ').insertAfter("#supmp-main > section > h1.supmp-section-title");
    jQuery("#supmp-register-monitor-site-button").click(registerMonitorSite);
  }

  function getAccessCount(monitorId){
    const AjaxUri = supmp_ajaxuri,
      AjaxNonce = supmp_ajaxnonce,
      graphsSectionElement = document.getElementById('supmp-monitoring_graphs'),
      errorMessageElement = document.createElement('span'),
      accessCountOptions = {
        type: "POST",
        url: supmp_ajaxuri,
        data: {
          action: "supmp_get_access_count",
          supmp_nonce: supmp_ajaxnonce,
          monitorId: monitorId
        },
        timeout: 10000
      };
    return jQuery.ajax(accessCountOptions);
  }

  function getMonitorResult(){
    const AjaxUri = supmp_ajaxuri,
      AjaxNonce = supmp_ajaxnonce,
      monitorResultOptions = {
        type: 'POST',
        url: AjaxUri,
        data: {
          action: 'supmp_get_monitor_results',
          supmp_nonce: AjaxNonce
        },
        timeout: 10000
      };
    return jQuery.ajax(monitorResultOptions);
  }
})();
