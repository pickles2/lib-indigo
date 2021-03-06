<?php

namespace pickles2\indigo\screen;

use pickles2\indigo\db\tsReserve as tsReserve;
use pickles2\indigo\db\tsOutput as tsOutput;
use pickles2\indigo\db\tsBackup as tsBackup;

use pickles2\indigo\define as define;

/**
 * 初期表示画面処理クラス
 *
 * 初期表示画面に関連する処理をまとめたクラス。
 *
 */
class initScreen
{
	/** indigo\mainオブジェクト */
	public $main;
	
	/** indigo\db\tsReserve のインスタンス */
	private $tsReserve;

	/** indigo\db\tsOutput のインスタンス */
	private $tsOutput;

	/** indigo\db\tsBackup のインスタンス */
	private $tsBackup;

	/** indigo\check のインスタンス */
	private $check;

	/** indigo\publish のインスタンス */
	private $publish;

	/**
	 * 入力画面のエラーメッセージ
	 */
	private $input_error_message = '';

	/**
	 * 入力モード
	 */
	// 追加モード
	const INPUT_MODE_ADD = 1;
	// 追加確認モード
	const INPUT_MODE_ADD_CHECK = 2;
	// 追加戻り表示モード
	const INPUT_MODE_ADD_BACK = 3;
	// 更新モード
	const INPUT_MODE_UPDATE = 4;
	// 更新確認モード
	const INPUT_MODE_UPDATE_CHECK = 5;
	// 更新戻り表示モード
	const INPUT_MODE_UPDATE_BACK = 6;
	// 即時公開モード
	const INPUT_MODE_IMMEDIATE = 7;
	// 即時公開確認モード
	const INPUT_MODE_IMMEDIATE_CHECK = 8;
	// 即時公開戻り表示モード
	const INPUT_MODE_IMMEDIATE_BACK = 9;


	/**
	 * コンストラクタ
	 *
	 * @param object $main mainオブジェクト
	 */
	public function __construct($main) {

		$this->main = $main;

		$this->tsReserve = new tsReserve($this->main);
		$this->tsOutput = new tsOutput($this->main);
		$this->tsBackup = new tsBackup($this->main);
		
		$this->check = new \pickles2\indigo\check($this->main);
		$this->publish = new \pickles2\indigo\publish($this->main);

	}

	/**
	 * 初期表示画面のHTML作成
	 *	 
	 * @return string $ret HTMLソースコード
	 */
	public function do_disp_init_screen() {
		
		// 公開予定一覧を取得
		$data_list = $this->tsReserve->get_ts_reserve_list();

		$ret = '<div class="scr_content">'
			. '<form id="form_table" method="post">'
			. $this->main->get_additional_params()
			. '<div class="button_contents" style="float:left">'
			. '<ul>'
			. '<li><input type="submit" id="add_btn" name="add" class="px2-btn" value="新規配信予約"/></li>'
			. '</ul>'
			. '</div>'
			. '<div class="button_contents" style="float:right;">'
			. '<ul>'
			. '<li><input type="submit" id="update_btn" name="update" class="px2-btn" value="変更"/></li>'
			. '<li><input type="submit" id="delete_btn" name="delete" class="px2-btn px2-btn--danger" value="削除"/></li>'
			. '<li><input type="submit" id="immediate_btn" name="immediate" class="px2-btn px2-btn--primary" value="即時公開"/></li>'
			. '<li><input type="submit" id="history_btn" name="history" class="px2-btn" value="履歴"/></li>'
			. '<li><input type="submit" id="backup_btn" name="backup" class="px2-btn" value="バックアップ一覧"/></li>'
			. '</ul>'
			. '</div>';

		// テーブルヘッダー
		$ret .= '<div>'
			. '<table name="list_tbl" class="table table-striped">'
			. '<thead>'
			. '<tr>'
			. '<th scope="row"></th>'
			. '<th scope="row">公開予定日時</th>'
			. '<th scope="row">コミット</th>'
			. '<th scope="row">ブランチ</th>'
			. '<th scope="row">コメント</th>'
			. '<th scope="row">登録ユーザ</th>'
			. '<th scope="row">登録日時</th>'
			. '<th scope="row">更新ユーザ</th>'
			. '<th scope="row">更新日時</th>'
			. '</tr>'
			. '</thead>'
			. '<tbody>';

		// テーブルデータリスト
		foreach ((array)$data_list as $array) {
			
			$ret .= '<tr>'
				. '<td class="p-center"><input type="radio" name="target" value="' . \htmlspecialchars($array[tsReserve::RESERVE_ENTITY_ID_SEQ]) . '"/></td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsReserve::RESERVE_ENTITY_RESERVE_DISP]) . '</td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsReserve::RESERVE_ENTITY_COMMIT_HASH]) . '</td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsReserve::RESERVE_ENTITY_BRANCH]) . '</td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsReserve::RESERVE_ENTITY_COMMENT]) . '</td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsReserve::RESERVE_ENTITY_INSERT_USER_ID]) . '</td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsReserve::RESERVE_ENTITY_INSERT_DATETIME]) . '</td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsReserve::RESERVE_ENTITY_UPDATE_USER_ID]) . '</td>'
				. '<td class="p-center">' . \htmlspecialchars($array[tsReserve::RESERVE_ENTITY_UPDATE_DATETIME]) . '</td>'
				. '</tr>';
		}

		$ret .= '</tbody></table>'
			. '</div>'
			. '</form>'
			. '</div>';

		return $ret;
	}

	/**
	 * 新規入力ダイアログの表示
	 *	 
	 * @return string $dialog_html 新規入力ダイアログHTML
	 */
	public function do_disp_add_dialog() {

		// ダイアログHTMLの作成
		$dialog_html= $this->create_input_dialog_html(self::INPUT_MODE_ADD);

		return $dialog_html;
	}


	/**
	 * 新規確認処理
	 *
	 * @return string $dialog_html 新規入力ダイアログHTML、または、新規確認ダイアログHTML
	 */
	public function do_check_add() {
		
		$dialog_html;

		$form = $this->get_form_value();
		$gmt_reserve_datetime = $this->combine_to_gmt_date_and_time($form['reserve_date'], $form['reserve_time']);

		// 入力チェック処理
		$this->input_error_message = $this->do_validation_check(self::INPUT_MODE_ADD, $form, $gmt_reserve_datetime);

		if ($this->input_error_message) {
			// エラーがあるので入力ダイアログのまま
			$dialog_html = $this->create_input_dialog_html(self::INPUT_MODE_ADD_BACK);

		} else {
			// エラーがないので確認ダイアログへ遷移
			$dialog_html = $this->create_check_add_dialog_html($form);
		}

		return $dialog_html;
	}

	/**
	 * 新規ダイアログの確定処理
	 *
	 * @return array $result
	 * 			bool   $result['status'] 		処理成功時に `true`、失敗時に `false` を返します。
	 * 			string $result['message'] 		メッセージを返します。
	 * 			string $result['dialog_html'] 	ダイアログのHTMLを返します。
	 */
	public function do_confirm_add() {
		
		$form = $this->get_form_value();
		$gmt_reserve_datetime = $this->combine_to_gmt_date_and_time($form['reserve_date'], $form['reserve_time']);

		// 入力チェック処理
		$this->input_error_message = $this->do_validation_check(self::INPUT_MODE_ADD_CHECK, $form, $gmt_reserve_datetime);

		$result = array('status' => true,
						'message' => '',
						'dialog_html' => '');

		if ($this->input_error_message) {
			// エラーがあるので入力ダイアログへ戻る
			$result['dialog_html'] = $this->create_input_dialog_html(self::INPUT_MODE_ADD_BACK);

		} else {
			// エラーがないので確定処理へ進む
			$ret = $this->confirm_add($form, $gmt_reserve_datetime);
			
			$result['status'] = $ret['status'];
			$result['message'] = $ret['message'];
		}

		return $result;
	}

	/**
	 * 新規入力ダイアログへの戻り表示
	 *	 
	 * @return string $dialog_html 新規入力ダイアログHTML
	 */
	public function do_back_add_dialog() {
		
		// 入力ダイアログへ戻る
		$dialog_html = $this->create_input_dialog_html(self::INPUT_MODE_ADD_BACK);

		return $dialog_html;
	}

	/**
	 * 変更入力ダイアログの表示
	 *	 
	 * @return string $dialog_html 変更入力ダイアログHTML
	 */
	public function do_disp_update_dialog() {
		
		// 入力ダイアログHTMLの作成
		$dialog_html = $this->create_input_dialog_html(self::INPUT_MODE_UPDATE);

		return $dialog_html;
	}

	/**
	 * 変更確認処理
	 *	 
	 * @return string $dialog_html 変更入力ダイアログHTML、または、変更確認ダイアログHTML
	 */
	public function do_check_update() {
	
		$dialog_html;

		$form = $this->get_form_value();
		$gmt_reserve_datetime = $this->combine_to_gmt_date_and_time($form['reserve_date'], $form['reserve_time']);

		// 入力チェック処理
		$this->input_error_message = $this->do_validation_check(self::INPUT_MODE_UPDATE, $form, $gmt_reserve_datetime);

		if ($this->input_error_message) {
			// エラーがあるので入力ダイアログのまま
			$dialog_html = $this->create_input_dialog_html(self::INPUT_MODE_UPDATE_BACK);
		} else {
			// エラーがないので確認ダイアログへ遷移
			$dialog_html = $this->create_check_update_dialog_html($form);
		}

		return $dialog_html;
	}

	/**
	 * 変更ダイアログの確定処理
	 *
	 * @return array $result
	 * 			bool   $result['status'] 		処理成功時に `true`、失敗時に `false` を返します。
	 * 			string $result['message'] 		メッセージを返します。
	 * 			string $result['dialog_html'] 	ダイアログのHTMLを返します。
	 */
	public function do_confirm_update() {

		$form = $this->get_form_value();
		$gmt_reserve_datetime = $this->combine_to_gmt_date_and_time($form['reserve_date'], $form['reserve_time']);

		// 入力チェック処理
		$this->input_error_message = $this->do_validation_check(self::INPUT_MODE_UPDATE_CHECK, $form, $gmt_reserve_datetime);

		$result = array('status' => true,
						'message' => '',
						'dialog_html' => '');

		if ($this->input_error_message) {
			// エラーがあるので入力ダイアログへ戻る
			$result['dialog_html'] = $this->create_input_dialog_html(self::INPUT_MODE_UPDATE_BACK);

		} else {
			// エラーがないので確定処理へ進む
			$ret = $this->confirm_update($form, $gmt_reserve_datetime);

			$result['status'] = $ret['status'];
			$result['message'] = $ret['message'];
		}

		return $result;
	}

	/**
	 * 変更入力ダイアログへの戻り表示
	 *	 
	 * @return string $dialog_html 変更入力ダイアログHTML
	 */
	public function do_back_update_dialog() {
		
		// 入力ダイアログHTMLの作成
		$dialog_html = $this->create_input_dialog_html(self::INPUT_MODE_UPDATE_BACK);

		return $dialog_html;
	}

	/**
	 * 即時公開入力ダイアログの表示
	 *	 
	 * @return string $dialog_html 即時公開入力ダイアログHTML
	 */
	public function do_disp_immediate_dialog() {
	
		// ダイアログHTMLの作成
		$dialog_html = $this->create_input_dialog_html(self::INPUT_MODE_IMMEDIATE);

		return $dialog_html;
	}

	/**
	 * 即時公開確認処理
	 *	 
	 * @return string $dialog_html 即時公開入力ダイアログHTML、または、即時公開確認ダイアログHTML
	 */
	public function do_check_immediate() {
		
		$dialog_html;

		$form = $this->get_form_value();
		$gmt_reserve_datetime = $this->combine_to_gmt_date_and_time($form['reserve_date'], $form['reserve_time']);

		// 入力チェック処理
		$this->input_error_message = $this->do_validation_check(self::INPUT_MODE_IMMEDIATE, $form, $gmt_reserve_datetime);

		if ($this->input_error_message) {
			// エラーがあるので入力ダイアログのまま
			$dialog_html = $this->create_input_dialog_html(self::INPUT_MODE_IMMEDIATE_BACK);
		} else {
			// エラーがないので確認ダイアログへ遷移
			$dialog_html = $this->create_check_immediate_dialog_html($form);
		}

		return $dialog_html;
	}

	/**
	 * 即時公開ボタン押下
	 *	 
	 * @return array $result
	 * 			bool   $result['status'] 		処理成功時に `true`、失敗時に `false` を返します。
	 * 			string $result['message'] 		メッセージを返します。
	 * 			string $result['dialog_html'] 	ダイアログのHTMLを返します。
	 */
	public function do_immediate_publish() {
		
		$form = $this->get_form_value();

		// 入力チェック処理
		$this->input_error_message = $this->do_validation_check(self::INPUT_MODE_IMMEDIATE_CHECK, $form, null);

		$result = array('status' => true,
						'message' => '',
						'output_id' => '',
						'backup_id' => '',
						'dialog_html' => '');

		if ($this->input_error_message) {
			// エラーがあるので入力ダイアログへ戻る
			$result['dialog_html'] = $this->create_input_dialog_html(self::INPUT_MODE_IMMEDIATE_BACK);
		} else {
			// エラーがないので即時公開処理へ進む
			$ret = $this->publish->exec_publish(define::PUBLISH_TYPE_IMMEDIATE, null);

			$result['status'] = $ret['status'];
			$result['message'] = $ret['message'];
			$result['output_id'] = $ret['output_id'];
			$result['backup_id'] = $ret['backup_id'];
		}

		return $result;
	}

	/**
	 * 即時公開入力ダイアログへの戻り表示
	 *	 
	 * @return string $dialog_html 即時公開入力ダイアログHTML
	 */
	public function do_back_immediate_dialog() {
		
		// 入力ダイアログHTMLの作成
		$dialog_html = $this->create_input_dialog_html(self::INPUT_MODE_IMMEDIATE_BACK);

		return $dialog_html;
	}

	/**
	 * 新規ダイアログの確定処理
	 *	 
	 * 公開予定データの登録と、予定ディレクトリを作成しGitの情報をコピーします。
	 *
	 * @param array  $form 		 フォーム格納配列
	 * @param string $gmt_reserve_datetime GMT公開予定日時
	 * 
	 * @return array $result
	 * 			bool   $result['status'] 		処理成功時に `true`、失敗時に `false` を返します。
	 * 			string $result['message'] 		メッセージを返します。
	 */
	private function confirm_add($form, $gmt_reserve_datetime) {
		
		$result = array('status' => true,
						'message' => '');

		try {

			//============================================================
			// 指定ブランチのGit情報を「waiting」ディレクトリへコピー
			//============================================================
			// waitingディレクトリの絶対パスを取得。
			$realpath_waiting = $this->main->realpath_array['realpath_waiting'];

			// 公開予定ディレクトリ名の取得
			$dirname = $this->main->utils()->get_reserve_dirname($gmt_reserve_datetime);

	 		$this->main->utils()->put_process_log(__METHOD__, __LINE__, '　-----Git情報をwaitingへコピー-----');
			$this->main->gitMgr()->git_file_copy($this->main->options, $realpath_waiting, $dirname);

		
			//============================================================
			// 入力情報を公開予定テーブルへ登録
			//============================================================
	 		$this->main->utils()->put_process_log(__METHOD__, __LINE__, '　-----公開処理結果テーブルの登録処理-----');
			$this->tsReserve->insert_ts_reserve($form, $gmt_reserve_datetime, $this->main->user_id);
			
		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = '追加処理が失敗しました。';

			$logstr =  "***** ERROR *****" . "\r\n";
			$logstr .= "[ERROR]" . "\r\n";
			$logstr .= $e->getFile() . " in " . $e->getLine() . "\r\n";
			$logstr .= "Error message:" . $e->getMessage() . "\r\n";
			$this->main->utils()->put_error_log($logstr);

			return $result;
		}

		$result['status'] = true;

		return $result;
	}


	/**
	 * 変更ダイアログの確定処理
	 *	 
	 * 公開予定データの更新と、既存の予定ディレクトリを削除し、再作成後Gitの情報をコピーします。
	 *
	 * @param array  $form 		 フォーム格納配列
	 * @param string $gmt_reserve_datetime GMT公開予定日時
	 * 
	 * @return array $result
	 * 			bool   $result['status'] 		処理成功時に `true`、失敗時に `false` を返します。
	 * 			string $result['message'] 		メッセージを返します。
	 */
	private function confirm_update($form, $gmt_reserve_datetime) {
	
		$result = array('status' => true,
						'message' => '');

		try {

			// waitingディレクトリの絶対パスを取得。
			$realpath_waiting = $this->main->realpath_array['realpath_waiting'];

			//============================================================
			// 「waiting」ディレクトリの変更前の公開ソースディレクトリを削除
			//============================================================
			// 変更前の公開予定ディレクトリ名の取得
			$before_dirname = $this->main->utils()->get_reserve_dirname($this->main->options->_POST->before_gmt_reserve_datetime);

			$this->main->utils()->put_process_log(__METHOD__, __LINE__, '　□ 変更前の公開予定ディレクトリ：');
			$this->main->utils()->put_process_log(__METHOD__, __LINE__, $before_dirname);

			// 変更前削除
			$this->main->gitMgr()->file_delete($realpath_waiting, $before_dirname);


			//============================================================
			// 変更後ブランチのGit情報を「waiting」ディレクトリへコピー
			//============================================================
			// 公開予定ディレクトリ名の取得
			$dirname = $this->main->utils()->get_reserve_dirname($gmt_reserve_datetime);

	 		$this->main->utils()->put_process_log(__METHOD__, __LINE__, '　-----Git情報をwaitingへコピー-----');
			$this->main->gitMgr()->git_file_copy($this->main->options, $realpath_waiting, $dirname);
		

			//============================================================
			// 入力情報を公開予定テーブルへ更新
			//============================================================
	 		$this->main->utils()->put_process_log(__METHOD__, __LINE__, '　-----公開処理結果テーブルの更新処理-----');
			$this->tsReserve->update_ts_reserve($form['selected_id'], $form, $gmt_reserve_datetime, $this->main->user_id);
			
		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = '変更処理が失敗しました。';

			$logstr =  "***** ERROR *****" . "\r\n";
			$logstr .= "[ERROR]" . "\r\n";
			$logstr .= $e->getFile() . " in " . $e->getLine() . "\r\n";
			$logstr .= "Error message:" . $e->getMessage() . "\r\n";
			$this->main->utils()->put_error_log($logstr);

			return $result;
		}

		$result['status'] = true;

		return $result;
	}

	/**
	 * 削除処理
	 *	 
	 * 公開予定データの論理削除と、Gitコピーディレクトリの削除を行います。
	 *	 
	 * @return array $result
	 * 			bool   $result['status'] 		処理成功時に `true`、失敗時に `false` を返します。
	 * 			string $result['message'] 		メッセージを返します。
	 */
	public function do_delete() {
		
		$result = array('status' => true,
						'message' => '');

		try {

			// 選択ID
			$selected_id =  $this->main->options->_POST->selected_id;

			// waitingディレクトリの絶対パスを取得。
			$realpath_waiting = $this->main->realpath_array['realpath_waiting'];


			try {

				/* トランザクションを開始する。オートコミットがオフになる */
				$this->main->dbh()->beginTransaction();

				//============================================================
				// 公開予定情報の論理削除
				//============================================================

				$this->main->utils()->put_process_log(__METHOD__, __LINE__, '　□ -----公開予定情報の論理削除処理-----');

				$this->tsReserve->delete_reserve_table($this->main->user_id, $selected_id);

				//============================================================
				// 「waiting」ディレクトリの変更前の公開ソースディレクトリを削除
				//============================================================
				// 公開予定ディレクトリ名の取得
				$selected_ret = $this->tsReserve->get_selected_ts_reserve($selected_id);
				$dirname = $this->main->utils()->get_reserve_dirname($selected_ret[tsReserve::RESERVE_ENTITY_RESERVE_GMT]);
				
				// コピー処理
				$this->main->gitMgr()->file_delete($realpath_waiting, $dirname);


				/* 変更をコミットする */
				$this->main->dbh()->commit();
				/* データベース接続はオートコミットモードに戻る */

			} catch (\Exception $e) {
			
			  /* 変更をロールバックする */
			  $this->main->dbh()->rollBack();
		 
			  throw $e;
			}

		} catch (\Exception $e) {

			$result['status'] = false;
			$result['message'] = '削除処理が失敗しました。';

			$logstr =  "***** ERROR *****" . "\r\n";
			$logstr .= "[ERROR]" . "\r\n";
			$logstr .= $e->getFile() . " in " . $e->getLine() . "\r\n";
			$logstr .= "Error message:" . $e->getMessage() . "\r\n";
			$this->main->utils()->put_error_log($logstr);

			return $result;
		}

		$result['status'] = true;

		return $result;
	}

	/**
	 * 新規・変更・即時公開の入力ダイアログHTMLの作成
	 *	 
	 * @param string $input_mode 入力モード
	 *
	 * @return string $ret ダイアログHTML
	 */
	private function create_input_dialog_html($input_mode) {

		$ret = '<div class="dialog" id="modal_dialog">'
			  . '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			  . '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			  . '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			  . '<div class="dialog_box">';

		 if ($this->input_error_message) {
		 	// エラーメッセージの出力
			$ret .= '<div class="alert_box">'
				. $this->input_error_message
				. '</div>';
		 }

		// 入力モードによってタイトル変更
		if ( ($input_mode == self::INPUT_MODE_ADD) || ($input_mode == self::INPUT_MODE_ADD_BACK)) {
			$ret .= '<h4>新規</h4>';

		} elseif ( ($input_mode == self::INPUT_MODE_UPDATE) || ($input_mode == self::INPUT_MODE_UPDATE_BACK) ) {
		  	$ret .= '<h4>変更</h4>';

		} elseif ( ($input_mode == self::INPUT_MODE_IMMEDIATE) || ($input_mode == self::INPUT_MODE_IMMEDIATE_BACK) ) {
		  	$ret .= '<h4>即時公開</h4>';

		} else {
			throw new \Exception("Input mode is not found.");
		}

		$form = array('branch_select_value' => '',
						'reserve_date' => '',
						'reserve_time' => '',
						'commit_hash' => '',
						'comment' => '',
						'ver_no' => '',
						'selected_id' => ''
					);


		if (($input_mode == self::INPUT_MODE_ADD_BACK) || 
			($input_mode == self::INPUT_MODE_UPDATE_BACK) ||
			($input_mode == self::INPUT_MODE_IMMEDIATE_BACK)) {

			// 戻り表示の場合のパラメタ取得
			$form = $this->get_form_value();

		} elseif ($input_mode == self::INPUT_MODE_UPDATE) {

			// 画面選択された公開予定情報を取得
			$form['selected_id'] = $this->main->options->_POST->selected_id;

			$selected_data = $this->tsReserve->get_selected_ts_reserve($form['selected_id']);

			if ($selected_data) {

				$form['branch_select_value'] = $selected_data[tsReserve::RESERVE_ENTITY_BRANCH];
				$form['reserve_date'] = $selected_data[tsReserve::RESERVE_ENTITY_RESERVE_DATE];
				$form['reserve_time'] = $selected_data[tsReserve::RESERVE_ENTITY_RESERVE_TIME];
				$form['commit_hash'] = $selected_data[tsReserve::RESERVE_ENTITY_COMMIT_HASH];
				$form['comment'] = $selected_data[tsReserve::RESERVE_ENTITY_COMMENT];
				$form['ver_no'] = $selected_data[tsReserve::RESERVE_ENTITY_VER_NO];
			}
		}

		$ret .= '<form method="post">';
		$ret .= $this->main->get_additional_params();

		// hidden項目
		$ret .= '<input type="hidden" name="selected_id" value="' . \htmlspecialchars($form['selected_id']) . '"/>';
		$ret .= '<input type="hidden" name="ver_no" value="' . \htmlspecialchars($form['ver_no']) . '"/>';
		// ajax呼出クラス絶対パス
		$ret .= '<input type="hidden" id="url_ajax_call" value="' . \htmlspecialchars($this->main->options->url_ajax_call) . '"/>';
		// indigo作業用ディレクトリ絶対パス
		$ret .= '<input type="hidden" id="realpath_workdir" value="' . \htmlspecialchars($this->main->options->realpath_workdir) . '"/>';

		
		$ret .= '<table class="table table-striped">'
			  . '<tr>';

		// 「ブランチ」項目
		$ret .= '<td class="dialog_thead">ブランチ</td>'
			  . '<td><select id="branch_list" class="form-control" name="branch_select_value">';

				// ブランチリストを取得
				$branch_list = $this->main->gitMgr()->get_branch_list($this->main->options);

				foreach ((array)$branch_list as $branch) {
					$ret .= '<option value="' . \htmlspecialchars($branch) . '" ' . $this->compare_to_selected_value(\htmlspecialchars($form['branch_select_value']), \htmlspecialchars($branch)) . '>' . \htmlspecialchars($branch) . '</option>';
				}

		$ret .= '</select></td>'
			  . '</tr>';
		
		// 「コミット」項目
		$ret .= '<tr>'
			  . '<td class="dialog_thead">コミット</td>'
			  . '<td id="result">' . \htmlspecialchars($form['commit_hash']) . '</td>'
			  . '<input type="hidden" id="commit_hash" name="commit_hash" value="' . \htmlspecialchars($form['commit_hash']) . '"/>'
			  . '</tr>';

		// 「公開予定日時」項目
		if ( ($input_mode == self::INPUT_MODE_IMMEDIATE) || ($input_mode == self::INPUT_MODE_IMMEDIATE_BACK) ) {

			$ret .= '<tr>'
				  . '<td class="dialog_thead">公開予定日時</td>'
				  . '<td scope="row"><span style="margin-right:10px;color:#B61111">即時</span></td>'
				  . '</tr>';
		
		} else {

			$ret .= '<tr>'
				  . '<td class="dialog_thead">公開予定日時</td>'
				  . '<td scope="row"><span style="margin-right:10px;"><input type="text" id="datepicker" name="reserve_date" value="'. \htmlspecialchars($form['reserve_date']) . '" autocomplete="off" /></span>'
				  . '<input type="time" id="reserve_time" name="reserve_time" value="'. \htmlspecialchars($form['reserve_time']) . '" /></td>'
				  . '</tr>';
		}

		// 「コメント」項目
		$ret .= '<tr>'
			  . '<td class="dialog_thead">コメント</td>'
			  . '<td><input type="text" id="comment" name="comment" size="50" value="' . \htmlspecialchars($form['comment']) . '" /></td>'
			  . '</tr>'
			  . '</tbody></table>'

			  . '<div class="button_contents_box">'
			  . '<div class="button_contents">'
			  . '<ul>';
		
		// 「確認」ボタン（入力モードによってidとnameを変更）
		if ( ($input_mode == self::INPUT_MODE_ADD) || ($input_mode == self::INPUT_MODE_ADD_BACK)) {
			$ret .= '<li><input type="submit" id="add_check_btn" name="add_check" class="px2-btn px2-btn--primary" value="確認"/></li>';

		} elseif ( ($input_mode == self::INPUT_MODE_UPDATE) || ($input_mode == self::INPUT_MODE_UPDATE_BACK) ) {
		  	$ret .= '<li><input type="submit" id="update_check_btn" name="update_check" class="px2-btn px2-btn--primary" value="確認"/></li>';

		} elseif ( ($input_mode == self::INPUT_MODE_IMMEDIATE) ||  ($input_mode == self::INPUT_MODE_IMMEDIATE_BACK) ) {
		  	$ret .= '<li><input type="submit" id="immediate_check_btn" name="immediate_check" class="px2-btn px2-btn--danger" value="確認"/></li>';

		} else {
			throw new \Exception("Input mode is not found.");
		}

		// 「キャンセル」ボタン
		$ret .= '<li><input type="submit" id="close_btn" class="px2-btn" value="キャンセル"/></li>';
		
		$ret .= '</ul>'
			  . '</div>'
			  . '</div>'
			  . '</form>'
			  . '</div>'

			  . '</div>'
			  . '</div>'
			  . '</div></div>';
		
		return $ret;
	}

	/**
	 * 新規確認ダイアログの表示
	 *	 
	 * @param array $form フォーム格納配列
	 *	 
	 * @return string $ret ダイアログHTML
	 */
	private function create_check_add_dialog_html($form) {
		
		$ret = '<div class="dialog" id="modal_dialog">'
			. '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			. '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			. '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			. '<div class="dialog_box">';
		
		$ret .= '<h4>追加確認</h4>';

		$ret .= '<form method="post">'
			. $this->main->get_additional_params()
			. '<table class="table table-striped">'

			// hidden項目
			. '<input type="hidden" name="ver_no" value="' . \htmlspecialchars($form['ver_no']) . '"/>';

		// 「ブランチ」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . \htmlspecialchars($form['branch_select_value'])
			. '<input type="hidden" name="branch_select_value" value="' . \htmlspecialchars($form['branch_select_value']) . '"/>'
			. '</td>'
			. '</tr>';

		// 「コミット」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コミット' . '</td>'
			. '<td>' . \htmlspecialchars($form['commit_hash']) . '</td>'
			. '<input type="hidden" name="commit_hash" value="' . \htmlspecialchars($form['commit_hash']) . '"/>'
			. '</tr>';

		// 「公開予定日時」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . '公開予定日時' . '</td>'
			. '<td>' . \htmlspecialchars($form['reserve_date']) . ' ' . \htmlspecialchars($form['reserve_time'])
			. '<input type="hidden" name="reserve_date" value="' . \htmlspecialchars($form['reserve_date']) . '"/>'
			. '<input type="hidden" name="reserve_time" value="' . \htmlspecialchars($form['reserve_time']) . '"/>'
			. '</td>'
			. '</tr>';

		// 「コメント」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . \htmlspecialchars($form['comment']) . '</td>'
			. '<input type="hidden" name="comment" value="' . \htmlspecialchars($form['comment']) . '"/>'
			. '</tr>'

			. '</tbody></table>'
			
			. '<div class="unit">'
			. '<div class="text-center">';

		$ret .= '<div class="button_contents_box">'
			. '<div class="button_contents">'
			. '<ul>';

		// 「確定」ボタン
		$ret .= '<li><input type="submit" id="confirm_btn" name="add_confirm" class="px2-btn px2-btn--primary" value="確定"/></li>';
		
		// 「キャンセル」ボタン
		$ret .= '<li><input type="submit" id="back_btn" name="add_back" class="px2-btn" value="戻る"/></li>';

		$ret .= '</ul>'
			. '</div>'
			. '</div>'

			. '</div>'
			. '</div>'

			. '</form>'
			. '</div>'
			. '</div></div></div>';

		return $ret;
	}


	/**
	 * 変更確認ダイアログの表示
	 *	 
	 * @param array $form フォーム格納配列
	 *	 
	 * @return string $ret ダイアログHTML
	 */
	private function create_check_update_dialog_html($form) {
	
		$before_branch_select_value = "";
		$before_reserve_date = "";
		$before_reserve_time = "";
		$before_commit_hash = "";
		$before_comment = "";
		$before_gmt_reserve_datetime = "";

		// 画面選択された変更前の公開予定情報を取得
		$selected_id =  $this->main->options->_POST->selected_id;
		$selected_data = $this->tsReserve->get_selected_ts_reserve($selected_id);

		if ($selected_data) {

			$before_branch_select_value = $selected_data[tsReserve::RESERVE_ENTITY_BRANCH];
			$before_reserve_date = $selected_data[tsReserve::RESERVE_ENTITY_RESERVE_DATE];
			$before_reserve_time = $selected_data[tsReserve::RESERVE_ENTITY_RESERVE_TIME];
			$before_commit_hash = $selected_data[tsReserve::RESERVE_ENTITY_COMMIT_HASH];
			$before_comment = $selected_data[tsReserve::RESERVE_ENTITY_COMMENT];
			$before_gmt_reserve_datetime = $selected_data[tsReserve::RESERVE_ENTITY_RESERVE_GMT];
		}

		$ret = '<div class="dialog" id="modal_dialog">'
			. '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			. '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			. '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			. '<div class="dialog_box">';
		
		$ret .= '<h4>変更確認</h4>'
			. '<form method="post">'
			. $this->main->get_additional_params()
			. '<div class="colum_3">'
			. '<div class="left_box">';

		$ret .= '<table class="table table-striped">';
	
		// 「ブランチ」項目（変更前）
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . \htmlspecialchars($before_branch_select_value) . '</td>'
			. '</tr>';
		
		// 「コミット」項目（変更前）
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コミット' . '</td>'
			. '<td>' . \htmlspecialchars($before_commit_hash) . '</td>'
			. '</tr>';
		
		// 「公開予定日時」項目（変更前）
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . '公開予定日時' . '</td>'
			. '<td>' . \htmlspecialchars($before_reserve_date) . ' ' . \htmlspecialchars($before_reserve_time) . '</td>'
			. '<input type="hidden" name="before_gmt_reserve_datetime" value="' . \htmlspecialchars($before_gmt_reserve_datetime) . '"/>'
			. '</tr>';
		
		// 「コメント」項目（変更前）
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . \htmlspecialchars($before_comment) . '</td>'
			. '</tr>'
			. '</tbody></table>'
			
			. '</div>'

			. '<div class="center_box">'
			. '<img src="'. $this->main->options->relativepath_resourcedir . "/images/arrow_right.png" .'"/>'
			. '</div>'

			. '<div class="right_box">'
			. '<table class="table table-striped" style="width: 100%">'

			// hidden項目
			. '<input type="hidden" name="selected_id" value="' . \htmlspecialchars($form['selected_id']) . '"/>'
			. '<input type="hidden" name="ver_no" value="' . \htmlspecialchars($form['ver_no']) . '"/>'

			// 「ブランチ」項目（変更後）
			. '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . \htmlspecialchars($form['branch_select_value']) . '</td>'
			. '<input type="hidden" name="branch_select_value" value="' . \htmlspecialchars($form['branch_select_value']) . '"/>'
			. '</tr>'

			// 「コミット」項目（変更後）			
			. '<tr>'
			. '<td class="dialog_thead">' . 'コミット' . '</td>'
			. '<td>' . \htmlspecialchars($form['commit_hash']) . '</td>'
			. '<input type="hidden" name="commit_hash" value="' . \htmlspecialchars($form['commit_hash']) . '"/>'	
			. '</tr>'

			// 「公開日時」項目（変更後）
			. '<tr>'
			. '<td class="dialog_thead">' . '公開予定日時' . '</td>'
			. '<td>' . \htmlspecialchars($form['reserve_date']) . ' ' . \htmlspecialchars($form['reserve_time']) . '</td>'
			. '<input type="hidden" name="reserve_date" value="' . \htmlspecialchars($form['reserve_date']) . '"/>'
			. '<input type="hidden" name="reserve_time" value="' . \htmlspecialchars($form['reserve_time']) . '"/>'	 
			. '</tr>'

			// 「コメント」項目（変更後）
			. '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . \htmlspecialchars($form['comment']) . '</td>'
			. '<input type="hidden" name="comment" value="' . \htmlspecialchars($form['comment']) . '"/>'
			. '</tr>'

			. '</tbody></table>'
			. '</div>'
		 	. '</div>'

			. '<div class="button_contents_box">'
			. '<div class="button_contents">'
			. '<ul>';

		$ret .= '<li><input type="submit" id="confirm_btn" name="update_confirm" class="px2-btn px2-btn--primary" value="確定"/></li>'
			. '<li><input type="submit" id="back_btn" name="update_back" class="px2-btn" value="戻る"/></li>';

		$ret .= '</ul>'
			. '</div>'
			. '</div>'
			. '</form>'
			. '</div>'

			. '</div>'
			. '</div>'
			. '</div></div>';

		return $ret;
	}

	/**
	 * 即時公開確認ダイアログの表示
	 *
	 * @param array  $form 		 フォーム格納配列
	 *
	 * @return string $ret ダイアログHTML
	 */
	private function create_check_immediate_dialog_html($form) {
		
		$ret = '<div class="dialog" id="modal_dialog">'
			. '<div class="contents" style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; z-index: 10000;">'
			. '<div style="position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; overflow: hidden; background: rgb(0, 0, 0); opacity: 0.5;"></div>'
			. '<div style="position: absolute; left: 0px; top: 0px; padding-top: 4em; overflow: auto; width: 100%; height: 100%;">'
			. '<div class="dialog_box">';
		
		$ret .= '<h4>即時公開確認</h4>';

		$ret .= '<form method="post">'
			. $this->main->get_additional_params()
			. '<table class="table table-striped">';

		// 「ブランチ」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'ブランチ' . '</td>'
			. '<td>' . \htmlspecialchars($form['branch_select_value'])
			. '<input type="hidden" name="branch_select_value" value="' . \htmlspecialchars($form['branch_select_value']) . '"/>'
			. '</td>'
			. '</tr>';

		// 「コミット」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コミット' . '</td>'
			. '<td>' . \htmlspecialchars($form['commit_hash']) . '</td>'
			. '<input type="hidden" name="commit_hash" value="' . \htmlspecialchars($form['commit_hash']) . '"/>'
			. '</tr>';

		// 「公開予定日時」項目
		$ret .= '<tr>'
			  . '<td class="dialog_thead">公開予定日時</td>'
			  . '<td scope="row"><span style="margin-right:10px;color:#B61111">即時</span></td>'
			  . '</tr>';

		// 「コメント」項目
		$ret .= '<tr>'
			. '<td class="dialog_thead">' . 'コメント' . '</td>'
			. '<td>' . \htmlspecialchars($form['comment']) . '</td>'
			. '<input type="hidden" name="comment" value="' . \htmlspecialchars($form['comment']) . '"/>'
			. '</tr>'

			. '</tbody></table>'
			
			. '<div class="unit">'
			. '<div class="text-center">';

		$ret .= '<div class="button_contents_box">'
			. '<div class="button_contents">'
			. '<ul>';

		// 「確定」ボタン
		$ret .= '<li><input type="submit" id="confirm_btn" name="immediate_confirm" class="px2-btn px2-btn--danger" value="確定（注意：本番環境への公開処理が開始されます）"/></li>';
		
		// 「キャンセル」ボタン
		$ret .= '<li><input type="submit" id="back_btn" name="immediate_back" class="px2-btn" value="戻る"/></li>';

		$ret .= '</ul>'
			. '</div>'
			. '</div>'

			. '</div>'
			. '</div>'

			. '</form>'
			. '</div>'
			. '</div></div></div>';

		return $ret;
	}


	/**
	 * 入力チェック処理
	 *  
	 * [入力チェックの内容]
	 *  必須チェック：ブランチ
	 *  必須チェック：コミット（ブランチ選択時にajaxにより自動取得されるため内部エラーが発生しない限りは入力されている）
	 *  必須チェック：公開予定日付
	 *  必須チェック：公開予定時刻
	 *  重複チェック：ブランチ（予定データの中に、同名ブランチが存在していないか）
	 *  重複チェック：公開予定日時（予定データの中に、同じ公開予定日時が存在していないか）
	 *  公開予定の最大件数チェック（パラメタで設定された件数を超える場合エラーとする）
	 *  日付の妥当性チェック（yyyy-mm-ddの形式となっているか、存在する日付であるか）
	 *  未来日チェック（公開予定日時が未来時刻であるか）
	 * 
	 * @param string $input_mode 入力モード
	 * @param array  $form 		 フォーム格納配列
	 * @param string $gmt_reserve_datetime GMT公開予定日時
	 *
	 * @return string $ret エラーメッセージHTML
	 */
	private function do_validation_check($input_mode, $form, $gmt_reserve_datetime) {
		
		$ret = "";

		$date_required_error = true;
		$branch_required_error = true;

		/**
 		* 公開予定一覧を取得
		*/ 
		$data_list = $this->tsReserve->get_ts_reserve_list();

		// 必須チェック
		if (!$this->check->is_null_branch($form['branch_select_value'])) {
			$ret .= '<p class="error_message">ブランチを選択してください。</p>';
			$branch_required_error = false;
		}
		if (!$this->check->is_null_commit_hash($form['commit_hash'])) {
			$ret .= '<p class="error_message">コミットが取得されておりません。</p>';
		}

		if ($input_mode != self::INPUT_MODE_IMMEDIATE &&
			$input_mode != self::INPUT_MODE_IMMEDIATE_CHECK) {

			if (!$this->check->is_null_reserve_date($form['reserve_date'])) {
				$ret .= '<p class="error_message">日付を選択してください。</p>';
				$date_required_error = false;
			}
			if (!$this->check->is_null_reserve_time($form['reserve_time'])) {
				$ret .= '<p class="error_message">時刻を選択してください。</p>';
				$date_required_error = false;
			}

			if ($date_required_error) {
				// 日付と時刻が共に入力されている場合にのみチェックする
				// 日付の妥当性チェック
				if (!$this->check->check_date($form['reserve_date'])) {
					$ret .= '<p class="error_message">「公開予定日時」の日付が有効ではありません。</p>';
				// 以下、日付の妥当性チェックがOKの場合にのみチェックする
				} else {

					// 未来の日付であるかチェック
					if (!$this->check->check_future_date($gmt_reserve_datetime)) {
						$ret .= '<p class="error_message">「公開予定日時」は未来日時を設定してください。</p>';
					}

					// 公開予定日時の重複チェック
					if (!$this->check->check_exist_reserve($data_list, $gmt_reserve_datetime, $form['selected_id'])) {
						$ret .= '<p class="error_message">入力された日時はすでに公開予定が作成されています。</p>';
					}
				}
			}
			
			if ($branch_required_error) {
				// ブランチの重複チェック
				if (!$this->check->check_exist_branch($data_list, $form['branch_select_value'], $form['selected_id'])) {
					$ret .= '<p class="error_message">1つのブランチで複数の公開予定を作成することはできません。</p>';
				}
			}
		}

		if ($input_mode == self::INPUT_MODE_ADD ||
			$input_mode == self::INPUT_MODE_ADD_CHECK) {


			// パラメタの最大予定件数
			$max = $this->main->options->max_reserve_record;

			// 公開予定の最大件数チェック
			if (!$this->check->check_reserve_max_record($data_list, $max)) {
				$ret .= '<p class="error_message">公開予定は最大' . $max . '件までの登録になります。</p>';
			}
		}
		
		if (!$ret) {
			$this->main->utils()->put_process_log(__METHOD__, __LINE__, '入力チェック結果 -->' . $ret);
		}

		return $ret;
	}

	/**
	 * プルダウンで選択状態(selected)となる値であるか比較する
	 *	 
	 * @param string $selected 選択値
	 * @param string $value    比較対象の値
	 *	 
	 * @return  string $ret
	 *  		一致する場合："selected"
	 *  		一致しない場合：空文字
	 */
	private function compare_to_selected_value($selected, $value) {

		$ret = "";

		if (!empty($selected) && $selected == $value) {
			// 選択状態とする
			$ret = "selected";
		}

		return $ret;
	}

	/**
	 * 引数の日付と日時を結合し、GMTの日時へ変換する
	 *	 
	 * @param string $date 設定タイムゾーンの日付
	 * @param string $time 設定タイムゾーンの時刻
	 *	 
	 * @return string $ret GMT変換された日時
	 */
	private function combine_to_gmt_date_and_time($date, $time) {
	
		$ret = '';

		if (isset($date) && isset($time)) {

			// サーバのタイムゾーン取得
			$timezone = \date_default_timezone_get();
			$t = new \DateTime($date . ' ' . $time, new \DateTimeZone($timezone));

			// タイムゾーン変更
			$t->setTimeZone(new \DateTimeZone('GMT'));
			$ret = $t->format(define::DATETIME_FORMAT);
		}
			
		return $ret;
	}

	/**
	 * フォーム値の設定
	 *	 
	 * @return array $form
	 * 			string $result['branch_select_value'] ブランチ名
	 * 			string $result['reserve_date'] 	公開予定日付
	 * 			string $result['reserve_time'] 	公開予定時刻
	 * 			string $result['commit_hash'] 	コミットハッシュ値
	 * 			string $result['comment']	 	コメント
	 * 			string $result['ver_no'] 		バージョンNO
	 * 			string $result['selected_id'] 	選択ID
	 */
	private function get_form_value() {

		$form = array('branch_select_value' => '',
						'reserve_date' => '',
						'reserve_time' => '',
						'commit_hash' => '',
						'comment' => '',
						'ver_no' => '',
						'selected_id' => ''
					);

		// フォームパラメタが設定されている場合変数へ設定
		if (isset($this->main->options->_POST->branch_select_value)) {
			$form['branch_select_value'] = $this->main->options->_POST->branch_select_value;
		}
		if (isset($this->main->options->_POST->reserve_date)) {
			$form['reserve_date'] = $this->main->options->_POST->reserve_date;
		}
		if (isset($this->main->options->_POST->reserve_time)) {
			$form['reserve_time'] = $this->main->options->_POST->reserve_time;
		}
		if (isset($this->main->options->_POST->commit_hash)) {
			$form['commit_hash'] = $this->main->options->_POST->commit_hash;
		}
		if (isset($this->main->options->_POST->comment)) {
			$form['comment'] = $this->main->options->_POST->comment;
		}
		if (isset($this->main->options->_POST->ver_no)) {
			$form['ver_no'] = $this->main->options->_POST->ver_no;
		}
		if (isset($this->main->options->_POST->selected_id)) {
			$form['selected_id'] = $this->main->options->_POST->selected_id;
		}


		$this->main->utils()->put_process_log_block('[form]');
		$this->main->utils()->put_process_log_block('branch_select_value:' .  $form['branch_select_value']);
		$this->main->utils()->put_process_log_block('reserve_date:' . $form['reserve_date']);
		$this->main->utils()->put_process_log_block('reserve_time:' . $form['reserve_time']);
		$this->main->utils()->put_process_log_block('commit_hash:' . $form['commit_hash']);
		$this->main->utils()->put_process_log_block('comment:' . $form['comment']);
		$this->main->utils()->put_process_log_block('ver_no:' . $form['ver_no']);
		$this->main->utils()->put_process_log_block('selected_id:' . $form['selected_id']);


		return $form;
	}

}
