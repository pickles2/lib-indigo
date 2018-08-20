<?php

namespace indigo;

/**
 * メイン実行クラス
 *
 * indigo呼び出しの際に最初に呼ばれるクラス。
 *
 * 画面呼び出しの場合は、run() を呼び出す。
 * クーロン呼び出しの場合は、cron_run() を呼び出す。
 *
 * [コンストラクタ処理]
 *  パラメタ取得
 *  通常ログ、エラーログ生成
 *  作業ディレクトリ生成
 *  タイムゾーン設定
 *  データベース接続
 *  テーブル作成
 *  GitのMaster情報取得
 */
class main
{

	/**
	 * オプション
	 * 
	 * _GET,
	 * _POST,
	 * realpath_workdir,
	 *   - indigo作業用ディレクトリ（絶対パス）
	 * relativepath_resourcedir,
	 *   - リソースディレクトリ（ドキュメントルートからの相対パス）
	 * realpath_ajax_call,
	 *   - ajax呼出クラス（ドキュメントルートからの相対パス）
	 * time_zone,
	 *   - 画面表示上のタイムゾーン
	 * realpath_workdir,
	 *   - indigo作業用ディレクトリ（絶対パス）
	 * user_id,
	 *   - ユーザID
	 * db = array(
	 * 		string 'db_type',
	 *  		- db種類（'mysql' or null（nullの場合はSQLite3を使用））
	 * 		string 'mysql_db_name',
	 * 		string 'mysql_db_host',
	 * 		string 'mysql_db_user',
	 * 		string 'mysql_db_pass'
	 *  		- mysql用の設定項目
	 * ),
	 * max_reserve_record,
	 *   - 予定最大件数
	 * max_backup_generation,
	 *   - バックアップ世代管理件数
	 *
	 * server = array(
	 * 	// サーバの数だけ用意する
	 * 	array(
	 * 		string 'name':
	 * 			- サーバ名(任意)
	 * 		string 'real_path':
	 * 			- 同期先絶対パス
	 * 	)
	 * ),
	 * ignore = array(
	 * 			'例）.git',
	 * 			'例）.htaccess',
	 * 			'例）/common'
	 *  		- 同期除外のディレクトリ、またはファイル名
	 * ),
	 * git = array(
	 * 		string 'giturl':
	 * 			- Gitリポジトリのurl　		例) github.com/hk-r/px2-sample-project.git
	 * 		string 'username':
	 * 			- Gitリポジトリのユーザ名　	例) hoge
	 * 		string 'password':
	 * 			- Gitリポジトリのパスワード　	例) fuga
	 * )
	 */
	public $options;

	/** tomk79\filesystem のインスタンス */
	private $fs;

	/** indigo\common のインスタンス */
	private $common;

	/** indigo\gitManager のインスタンス */
	private $gitMgr;

	/** indigo\pdoManager のインスタンス */
	private $pdoMgr;

	/** indigo\publish のインスタンス */
	private $publish;

	/** indigo\screen\initScreen のインスタンス */
	private $initScreen;

	/** indigo\screen\historyScreen のインスタンス */
	private $historyScn;

	/** indigo\screen\backupScreen のインスタンス */
	private $backupScn;


	/**
	 * indigo\pdoManager::connect() DBインスタンス
	 */
	public $dbh;

	/**
	 * 作業ディレクトリ 絶対パス格納配列
	 */
	public $realpath_array = array('realpath_server' => '',	// 本番環境
								'realpath_backup' => '',	// バックアップ本番ソース
								'realpath_waiting' => '',	// 予定待機Gitソース
								'realpath_running' => '',	// 処理中ソース
								'realpath_released' => '',	// 処理完了ソース
								'realpath_log' => '');		// ログ

	/** indigo全体操作ログパス */
	public $process_log_path;

	/** indigoエラーログパス */
	public $error_log_path;


	/**
	 * コンストラクタ
	 * @param array $options オプション
	 */
	public function __construct($options) {

		//============================================================
		// オブジェクト生成
		//============================================================	
		$this->options = json_decode(json_encode($options));

		$this->fs = new \tomk79\filesystem(array(
		  'file_default_permission' => define::FILE_DEFAULT_PERMISSION,
		  'dir_default_pefrmission' => define::DIR_DEFAULT_PERMISSION,
		  'filesystem_encoding' 	=> define::FILESYSTEM_ENCODING
		));

		$this->common = new common($this);

		$this->gitMgr = new gitManager($this);
		$this->pdoMgr = new pdoManager($this);
		$this->publish = new publish($this);

		$this->initScn = new \indigo\screen\initScreen($this);
		$this->historyScn = new \indigo\screen\historyScreen($this);
		$this->backupScn = new \indigo\screen\backupScreen($this);


		//============================================================
		// エラーログ出力登録
		//============================================================	
		$this->error_log_path = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_LOG . 'error.log'));

		// 致命的なエラーのエラーハンドラ登録
		register_shutdown_function(
		    function(){
		        $e = error_get_last();
		        // if ($e === null) {
		        // 	return;
		        // }
		        if( $e['type'] == E_ERROR ||
		        	$e['type'] == E_WARNING ||
		            $e['type'] == E_PARSE ||
		            $e['type'] == E_CORE_ERROR ||
		            $e['type'] == E_COMPILE_ERROR ||
		            $e['type'] == E_USER_ERROR ){
		            
		            echo "エラーが発生しました。管理者にお問い合わせください。";

					if (file_exists($this->error_log_path)) {
					
						$logstr =  "***** エラー発生 *****" . "\r\n";
						$logstr .= "[ERROR]" . "\r\n";
						$logstr .= $e['file'] . " in " . $e['line'] . "\r\n";
						$logstr .= "Error message:" . $e['message'] . "\r\n";
						$this->common()->put_error_log($logstr);
					
					} else {
						echo $e['file'] . " in " . $e['line'] . "\r\n";
						echo "Error message:" . $e['message'] . "\r\n";
					}

		        }
		    }
		);


		// 致命的なエラー以外のエラーハンドラ登録
		set_error_handler(function($errno, $errstr, $errfile, $errline) {
			throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
		});

		//============================================================
		// 作業ディレクトリ絶対パス格納
		//============================================================
		// 本番環境ディレクトリの絶対パスを取得。（配列1番目のサーバを設定）
		foreach ( (array)$this->options->server as $server ) {
			$this->realpath_array['realpath_server'] = $this->fs()->normalize_path($this->fs()->get_realpath($server->real_path . "/"));
			break; // 現時点では最初の1つのみ有効なのですぐに抜ける
		}

		// backupディレクトリの絶対パスを取得。
		$this->realpath_array['realpath_backup'] = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_BACKUP));

		// waitingディレクトリの絶対パスを取得。
		$this->realpath_array['realpath_waiting'] = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_WAITING));

		// runningディレクトリの絶対パスを取得。
		$this->realpath_array['realpath_running'] = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_RUNNING));

		// releasedディレクトリの絶対パスを取得。
		$this->realpath_array['realpath_released'] = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_RELEASED));

		// logディレクトリの絶対パスを取得。
		$this->realpath_array['realpath_log'] = $this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir . define::PATH_LOG));

		//============================================================
		// 作業ディレクトリ作成
		//============================================================
		$current_dir = realpath('.');
		if (chdir($this->fs()->normalize_path($this->fs()->get_realpath($this->options->realpath_workdir)))) {

			// logファイルディレクトリが存在しない場合は作成
			$this->fs()->mkdir($this->realpath_array['realpath_log']);
			// backupディレクトリが存在しない場合は作成
			$this->fs()->mkdir($this->realpath_array['realpath_backup']);
			// waitingディレクトリが存在しない場合は作成
			$this->fs()->mkdir($this->realpath_array['realpath_waiting']);
			// runningディレクトリが存在しない場合は作成
			$this->fs()->mkdir($this->realpath_array['realpath_running']);
			// releasedディレクトリが存在しない場合は作成
			$this->fs()->mkdir($this->realpath_array['realpath_released']);

		} else {
			// ディレクトリ移動に失敗
			chdir($current_dir);
			throw new \Exception('Move to indigo work directory failed.');
		}
		chdir($current_dir);


		//============================================================
		// 通常ログ出力登録
		//============================================================	
		// ログファイル名
		$log_dirname = $this->common()->get_current_datetime_of_gmt(define::DATETIME_FORMAT_YMD);

		// ログパス
		$this->process_log_path = $this->realpath_array['realpath_log'] . 'log_process_' . $log_dirname . '.log';

		// $logstr = "[realpath]" . "\r\n";
		// $logstr .= "server -> " . $this->realpath_array['realpath_server'] . "\r\n";
		// $logstr .= "backup -> " . $this->realpath_array['realpath_backup'] . "\r\n";
		// $logstr .= "waiting -> " . $this->realpath_array['realpath_waiting'] . "\r\n";
		// $logstr .= "running -> " . $this->realpath_array['realpath_running'] . "\r\n";
		// $logstr .= "released -> " . $this->realpath_array['realpath_released'] . "\r\n";
		// $logstr .= "log -> " . $this->realpath_array['realpath_log'];
		// $this->common()->put_process_log_block($logstr);

		//============================================================
		// タイムゾーンの設定
		//============================================================
		// cron実行の場合は、タイムゾーンパラメタは存在しないので設定無し
		if (property_exists($this->options, 'time_zone')) {

			$time_zone = $this->options->time_zone;
			if (!$time_zone) {
				throw new \Exception('Parameter of timezone not found.');
			}
			date_default_timezone_set($time_zone);

			$logstr = "設定タイムゾーン：" . $time_zone;
			$this->common()->put_process_log_block($logstr);
		}

		//============================================================
		// データベース接続
		//============================================================
		$this->dbh = $this->pdoMgr->connect();


		//============================================================
		// テーブル作成（作成済みの場合はスキップ）
		//============================================================
		$this->pdoMgr->create_table();
		

		//============================================================
		// Gitのmaster情報取得
		//============================================================
		$this->gitMgr->get_git_master($this->options);

	}

	/**
	 * 実行する
	 *
	 * ボタンイベントのname値を検知し、別クラスへ記載されている処理を呼び出す。
	 * 各処理でエラーがキャッチされた場合は、$resultへ結果が格納されており、アラートメッセージの表示、エラーログへの書き込みを行う。
	 * 例外がスローされてきた場合は、こちらでキャッチし、エラーログへの書き込みを行う。
	 *
	 * @return string HTMLソースコード
	 */
	public function run() {
		
		$this->common()->put_process_log(__METHOD__, __LINE__, "■ run start");

		// 画面表示
		$disp = '';  

		// エラーメッセージ表示
		$alert_message = '';

		// ダイアログの表示
		$dialog_html = '';
		
		// 画面ロック用
		$disp_lock = '';

		// 処理実行結果格納
		$result = array('status' => true,
					    'message' => '',
					  	'dialog_html' => ''
				);

		try {

			//============================================================
			// 新規関連処理
			//============================================================
			if (isset($this->options->_POST->add)) {
				// 初期表示画面の「新規」ボタン押下

				$this->common()->put_process_log(__METHOD__, __LINE__, "==========初期表示画面の「新規」ボタン押下==========");
				$dialog_html = $this->initScn->do_disp_add_dialog();

			} elseif (isset($this->options->_POST->add_check)) {
				// 新規ダイアログの「確認」ボタン押下

				$this->common()->put_process_log(__METHOD__, __LINE__, "==========新規ダイアログの「確認」ボタン押下==========");
				$dialog_html = $this->initScn->do_check_add();

			} elseif (isset($this->options->_POST->add_confirm)) {
				// 新規確認ダイアログの「確定」ボタン押下
		
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========新規ダイアログの「確定」ボタン押下==========");
				$result = $this->initScn->do_confirm_add();	
				$alert_message = $result['message'];
				$dialog_html   = $result['dialog_html'];

			} elseif (isset($this->options->_POST->add_back)) {
				// 新規確認ダイアログの「戻る」ボタン押下
								
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========新規ダイアログの「戻る」ボタン押下==========");
				$dialog_html = $this->initScn->do_back_add_dialog();

			//============================================================
			// 変更関連処理
			//============================================================
			} elseif (isset($this->options->_POST->update)) {
				// 初期表示画面の「変更」ボタン押下
							
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========初期表示画面の「変更」ボタン押下==========");
				$dialog_html = $this->initScn->do_disp_update_dialog();

			} elseif (isset($this->options->_POST->update_check)) {
				// 変更ダイアログの「確認」ボタン押下
				
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========変更ダイアログの「確認」ボタン押下==========");
				$dialog_html = $this->initScn->do_check_update();

			} elseif (isset($this->options->_POST->update_confirm)) {
				// 変更確認ダイアログの「確定」ボタン押下
			
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========変更ダイアログの「確定」ボタン押下==========");
				$result = $this->initScn->do_confirm_update();	
				$alert_message = $result['message'];
				$dialog_html   = $result['dialog_html'];

			} elseif (isset($this->options->_POST->update_back)) {
				// 変更確認ダイアログの「戻る」ボタン押下	
				
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========変更ダイアログの「戻る」ボタン押下==========");
				$dialog_html = $this->initScn->do_back_update_dialog();


			//============================================================
			// 削除処理
			//============================================================
			} elseif (isset($this->options->_POST->delete)) {
				// 初期表示画面の「削除」ボタン押下				
			
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========初期表示画面の「削除」ボタン押下==========");
				$result = $this->initScn->do_delete();
				$alert_message = $result['message'];

			//============================================================
			// 手動復元処理
			//============================================================
			} elseif (isset($this->options->_POST->restore)) {
				// バックアップ一覧画面の「復元ボタン押下				
		
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========バックアップ一覧画面の「復元」ボタン押下==========");
				$result = $this->publish->exec_publish(define::PUBLISH_TYPE_MANUAL_RESTORE, null);

				// 画面アラート用のメッセージ			
				$alert_message = "≪手動復元公開処理≫" . $result['message'];

				if ( !$result['status'] ) {
					// 処理失敗の場合、復元処理

					$this->common()->put_process_log(__METHOD__, __LINE__, "** 手動復元公開処理エラー終了 **" . $result['message']);

					if ($result['backup_id']) {
						// バックアップが作成されている場合
						$this->common()->put_process_log(__METHOD__, __LINE__, "==========自動復元処理の呼び出し==========");
						$result = $this->publish->exec_publish(define::PUBLISH_TYPE_AUTO_RESTORE, $result['output_id']);

						// 画面アラート用のメッセージ			
						$alert_message .= "≪自動復元公開処理≫" . $result['message'];

						if ( !$result['status'] ) {
							// 処理失敗の場合、復元処理
							
							$this->common()->put_process_log(__METHOD__, __LINE__, "** 自動復元公開処理エラー終了 **" . $result['message']);
						}
					}
				}

			//============================================================
			// 即時公開処理
			//============================================================
			} elseif (isset($this->options->_POST->immediate)) {
				// 初期表示画面の「即時公開」ボタン押下				

				$this->common()->put_process_log(__METHOD__, __LINE__, "==========初期表示画面の「即時公開」ボタン押下==========");
				$dialog_html = $this->initScn->do_disp_immediate_dialog();

			} elseif (isset($this->options->_POST->immediate_check)) {
				// 即時公開ダイアログの「確認」ボタン押下
				
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========即時公開入力ダイアログの「確認」ボタン押下==========");
				$dialog_html = $this->initScn->do_check_immediate();

			} elseif (isset($this->options->_POST->immediate_confirm)) {
				// 即時公開確認ダイアログの「確定」ボタン押下	
				
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========即時公開確認ダイアログの「確定」ボタン押下==========");
				$result = $this->initScn->do_immediate_publish();

				// 画面アラート用のメッセージ			
				$alert_message = "≪即時公開処理≫" . $result['message'];
				$dialog_html   = $result['dialog_html'];

				if ( !$result['status'] ) {
					// 処理失敗の場合、復元処理

					$this->common()->put_process_log(__METHOD__, __LINE__, "** 即時公開処理エラー終了 **" . $result['message']);

					if ($result['backup_id']) {
						// バックアップが作成されている場合
						$this->common()->put_process_log(__METHOD__, __LINE__, "==========自動復元処理の呼び出し==========");
						$result = $this->publish->exec_publish(define::PUBLISH_TYPE_AUTO_RESTORE, $result['output_id']);

						// 画面アラート用のメッセージ			
						$alert_message .= "≪自動復元公開処理≫" . $result['message'];

						if ( !$result['status'] ) {
							// 処理失敗の場合、復元処理
							
							$this->common()->put_process_log(__METHOD__, __LINE__, "** 自動復元公開処理エラー終了 **" . $result['message']);
						}
					}



				}

			} elseif (isset($this->options->_POST->immediate_back)) {
				// 即時公開確認ダイアログの「戻る」ボタン押下			

				$this->common()->put_process_log(__METHOD__, __LINE__, "==========即時公開確認ダイアログの「戻る」ボタン押下==========");
				$dialog_html = $this->initScn->do_back_immediate_dialog();

			//============================================================
			// ログ表示処理
			//============================================================
			} elseif (isset($this->options->_POST->log)) {
				// 履歴表示画面の「新規」ボタン押下
				
				$this->common()->put_process_log(__METHOD__, __LINE__, "==========履歴表示画面の「ログ」ボタン押下==========");
				$dialog_html = $this->historyScn->do_disp_log_dialog();
			}

			if ( $alert_message ) {
				// 処理失敗の場合

				// $logstr = "**********************************************************************************" . "\r\n";
				// $logstr .= " ステータスエラー " . "\r\n";
				// $logstr .= "**********************************************************************************";
				// $this->common()->put_process_log_block($logstr);

				$logstr = "[アラートメッセージ]" . $alert_message;
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

				// エラーメッセージ表示
				$dialog_html = '
				<script type="text/javascript">
					alert("'.  $alert_message . '");
				</script>';

			} else {

				if ($result['dialog_html']) {
					$dialog_html = $result['dialog_html'];	
				}
			}

			if (isset($this->options->_POST->history) ||
				isset($this->options->_POST->log)) {
				// 初期表示画面の「履歴」ボタン押下
				
				$logstr = "==========履歴画面の表示==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$disp = $this->historyScn->disp_history_screen();

			} elseif (isset($this->options->_POST->backup)) {
				// 初期表示画面の「バックアップ一覧」ボタン押下

				$logstr = "==========バックアップ一覧画面の表示==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$disp = $this->backupScn->disp_backup_screen();
				
			} else {
				// 初期表示画面の表示

				$logstr = "==========初期表示画面の表示==========";
				$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);
			
				$disp = $this->initScn->do_disp_init_screen();
 
			}

			// 画面ロック用
			$disp_lock = '<div id="loader-bg"><div id="loading"></div></div>';

		} catch (\ErrorException $e) {

		    echo "エラーが発生しました。管理者にお問い合わせください。". "\r\n";

			if (file_exists($this->error_log_path)) {
				$logstr =  "***** エラー発生 *****" . "\r\n";
				$logstr .= "[ERROR]" . "\r\n";
				$logstr .= $e->getFile() . " in " . $e->getLine() . "\r\n";
				$logstr .= "Error message:" . $e->getMessage() . "\r\n";
				$this->common()->put_error_log($logstr);
			} else {
				echo $e->getFile() . " in " . $e->getLine() . "\r\n";
				echo "Error message:" . $e->getMessage() . "\r\n";
			}


		} catch (\Exception $e) {

			echo "例外エラーが発生しました。管理者にお問い合わせください。". "\r\n";

			$logstr = "** run() 例外キャッチ **" . "\r\n";
			$logstr .= $e->getMessage() . "\r\n";
			$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			$logstr =  "***** 例外エラー発生 *****" . "\r\n";
			$logstr .= "[ERROR]" . "\r\n";
			$logstr .= $e->getFile() . " in " . $e->getLine() . "\r\n";
			$logstr .= "Error message:" . $e->getMessage() . "\r\n";
			$this->common()->put_error_log($logstr);

			// データベース接続を閉じる
			$this->pdoMgr->close($this->dbh);

			$this->common()->put_process_log(__METHOD__, __LINE__, '■ cron_run error end');

			// return $dialog_html;
			return;
		}
		
		// データベース接続を閉じる
		$this->pdoMgr->close();

		$this->common()->put_process_log(__METHOD__, __LINE__, "■ run() end");

		// 画面表示
		return $disp . $disp_lock . $dialog_html;
	}

	/**
	 * クーロン実行する
	 *
	 * サーバにて登録されたクーロン処理から呼び出されるメソッド。
	 * 処理結果は$resultへ格納されており、エラーが発生した場合はエラーログへの書き込みを行う。
	 * 例外がスローされてきた場合は、こちらでキャッチし、エラーログへの書き込みを行う。
	 *
	 * @return string HTMLソースコード
	 */
    public function cron_run(){
	
		$this->common()->put_process_log(__METHOD__, __LINE__, '■ [cron] run start');

		// 処理実行結果格納
		$result = array('status' => true,
					      'message' => ''
				  );

		try {

			$logstr = "===============================================" . "\r\n";
			$logstr .= "予定公開処理開始" . "\r\n";
			$logstr .= "===============================================";
			$this->common()->put_process_log_block($logstr);

			$result = $this->publish->exec_publish(define::PUBLISH_TYPE_RESERVE, null);
	
			if ( !$result['status'] ) {
				// 予定公開処理失敗の場合

				$this->common()->put_process_log(__METHOD__, __LINE__, "** 予定公開処理エラー終了 **" . $result['message']);

				if ($result['backup_id']) {
					// バックアップが作成されている場合

					$this->common()->put_process_log(__METHOD__, __LINE__, "==========自動復元処理の呼び出し==========");

					$result = $this->publish->exec_publish(define::PUBLISH_TYPE_AUTO_RESTORE, $result['output_id']);

					if ( !$result['status'] ) {
						// 自動復元処理失敗の場合

						$this->common()->put_process_log(__METHOD__, __LINE__, "** 自動復元公開処理エラー終了 **" . $result['message']);
					}
				}
			}

		} catch (\ErrorException $e) {

		    echo "エラーが発生しました。管理者にお問い合わせください。". "\r\n";

			if (file_exists($this->error_log_path)) {
				$logstr =  "***** エラー発生 *****" . "\r\n";
				$logstr .= "[ERROR]" . "\r\n";
				$logstr .= $e->getFile() . " in " . $e->getLine() . "\r\n";
				$logstr .= "Error message:" . $e->getMessage() . "\r\n";
				$this->common()->put_error_log($logstr);
			} else {
				echo $e->getFile() . " in " . $e->getLine() . "\r\n";
				echo "Error message:" . $e->getMessage() . "\r\n";
			}

			return;

		} catch (\Exception $e) {

		    echo "例外エラーが発生しました。管理者にお問い合わせください。". "\r\n";

			$logstr = "** cron_run() 例外キャッチ **" . "\r\n";
			$logstr .= $e->getMessage() . "\r\n";
			$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

			$logstr =  "***** 例外エラー発生 *****" . "\r\n";
			$logstr .= "[ERROR]" . "\r\n";
			$logstr .= $e->getFile() . " in " . $e->getLine() . "\r\n";
			$logstr .= "Error message:" . $e->getMessage() . "\r\n";
			$this->common()->put_error_log($logstr);

			// データベース接続を閉じる
			$this->pdoMgr->close($this->dbh);

			$this->common()->put_process_log(__METHOD__, __LINE__, '■ run error end');

			return;
		}

		// データベース接続を閉じる
		$this->pdoMgr->close();

		$logstr = '□ $result->message: ' . $result['message'] . "\r\n";;
		$logstr .= "===============================================" . "\r\n";
		$logstr .= "予定公開処理終了" . "\r\n";
		$logstr .= "===============================================";
		$this->common()->put_process_log(__METHOD__, __LINE__, $logstr);

		$this->common()->put_process_log(__METHOD__, __LINE__, '■ [cron] run end');

		return;
    }

	/**
	 * `$fs` オブジェクトを取得する。
	 *
	 * `$fs`(class [tomk79\filesystem](tomk79.filesystem.html))のインスタンスを返します。
	 *
	 * @see https://github.com/tomk79/filesystem
	 * @return object $fs オブジェクト
	 */
	public function fs(){
		return $this->fs;
	}

	/**
	 * `$gitMgr` オブジェクトを取得する。
	 *
	 * @return object $gitMgr オブジェクト
	 */
	public function gitMgr(){
		return $this->gitMgr;
	}

	/**
	 * `$pdoMgr` オブジェクトを取得する。
	 *
	 * @return object $pdoMgr オブジェクト
	 */
	public function pdoMgr(){
		return $this->pdoMgr;
	}

	/**
	 * `$common` オブジェクトを取得する。
	 *
	 * @return object $common オブジェクト
	 */
	public function common(){
		return $this->common;
	}

	/**
	 * `$dbh` オブジェクトを取得する。
	 *
	 * @return object $dbh オブジェクト
	 */
	public function dbh(){
		return $this->dbh;
	}

}
