<?php

namespace indigo;

use indigo\db\tsReserve as tsReserve;
use indigo\db\tsOutput as tsOutput;
use indigo\db\tsBackup as tsBackup;

class pdoManager
{

	private $main;


	// DBディレクトリパス
	const SQLITE_DB_PATH = '/sqlite/';
	// DBディレクトリパス
	const SQLITE_DB_NAME = 'indigo.db';

	/**
	 * Constructor
	 *
	 * @param object $main mainオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;

	}

	/**
	 * データベースへ接続する
	 * 
	 * mainオプションのDBタイプによって接続方法が異なります。
	 * バージョン0.1.0時点ではmysqlの動作確認は行っておりません。
	 * sqliteについては動作確認済みです。
	 *	 
	 * @return PDO $dbh PDOオブジェクト
	 * 
	 * @throws Exception sqlite格納用のディレクトリ作成が失敗した場合
	 * @throws Exception Pdo接続処理が失敗した場合
	 */
	public function connect() {
	
		$dbh = null; // 初期化

		$dsn;
		$db_user;
		$db_pass;
		$option;

		$db_type = $this->main->options->db->db_type;

		if ($db_type && $db_type == 'mysql') {

			/**
			 * mysqlの場合
			 */
			$db_name = $this->main->options->db->mysql_db_name;		// データベース名
			$db_host = $this->main->options->db->mysql_db_host;		// ホスト名

			$dsn = "mysql:dbname=" . $db_name . ";host=" . $db_host. ";charset=utf8";

			$db_user = $this->main->options->db->mysql_db_user;
			$db_pass = $this->main->options->db->mysql_db_pass;

			$option = array(
						\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '. SELF::UTF
					);

		} else {

			/**
			 * sqliteの場合 
			 */
			// sqliteディレクトリの絶対パス
			$db_real_path = $this->main->fs()->normalize_path($this->main->fs()->get_realpath($this->main->options->realpath_workdir . self::SQLITE_DB_PATH));

			// sqliteディレクトリが存在しない場合は作成
			if ( !$this->main->fs()->mkdir($db_real_path) ) {
				// エラー処理
				throw new \Exception('Creation of sqlite directory failed. path = ' . $db_real_path);
			}

			$dsn = "sqlite:" . $db_real_path . self::SQLITE_DB_NAME;

			$db_user = null;
			$db_pass = null;

			$option = array(
						\PDO::ATTR_PERSISTENT => false, // trueの場合、"持続的な接続" となる
						\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,	// エラー設定。PDOExceptionをスローする
						\PDO::ATTR_EMULATE_PREPARES => false 			// falseの場合、prepareを利用する設定となる
					);
		}
			
		try {

	  		$dbh = new \PDO(
	  			$dsn,
	  			$db_user,
	  			$db_pass,
	  			$option
	  		);
	
		$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ PDO connect success.');

		} catch (\PDOException $e) {

			$this->main->common()->put_process_log(__METHOD__, __LINE__, '■ PDO connect failed.');
			// エラー情報表示
			throw new \Exception("Pdo connection failed");
		}
		
		return $dbh;

	}

	/**
	 * データベースの接続を閉じる
	 * 
	 * @throws Exception Pdo接続クローズ処理が失敗した場合
	 */
	public function close() {
	
		try {
			// データベースの接続を閉じる
			$this->main->dbh = null;
		} catch (\PDOException $e) {
			// エラー情報表示
			throw new \Exception("Pdo connection close failed");
		}
	}


	/**
	 * CREATE処理関数
	 *
	 * @throws Exception 各テーブルCREATE時にエラーが発生した場合
	 */
	public function create_table() {

		// $this->main->common()->put_process_log(__METHOD__, __LINE__, '■ create_table start');

		//============================================================
		// 公開予定テーブル作成
		//============================================================
		$create_sql = 'CREATE TABLE IF NOT EXISTS TS_RESERVE ('
			  . tsReserve::TS_RESERVE_ID_SEQ		. ' INTEGER PRIMARY KEY AUTOINCREMENT,
			' . tsReserve::TS_RESERVE_DATETIME		. ' TEXT,
			' . tsReserve::TS_RESERVE_BRANCH		. ' TEXT,
			' . tsReserve::TS_RESERVE_COMMIT_HASH	. ' TEXT,
			' . tsReserve::TS_RESERVE_COMMENT 		. ' TEXT,
			' . tsReserve::TS_RESERVE_STATUS 		. ' TEXT,			
			' . tsReserve::TS_RESERVE_DELETE_FLG	. ' TEXT,			
			' . tsReserve::TS_RESERVE_INSERT_DATETIME	. ' TEXT,
			' . tsReserve::TS_RESERVE_INSERT_USER_ID	. ' TEXT,
			' . tsReserve::TS_RESERVE_UPDATE_DATETIME	. ' TEXT,
			' . tsReserve::TS_RESERVE_UPDATE_USER_ID	. ' TEXT,
			' . tsReserve::TS_RESERVE_VER_NO			. ' TEXT
		)';

		// SQL実行
		$stmt = $this->main->dbh->query($create_sql);

		if (!$stmt) {
			// エラー情報表示
			throw new \Exception($this->main->dbh->errorInfo());
		}

		//============================================================
		// 公開処理結果テーブル作成
		//============================================================
		$create_sql = 'CREATE TABLE IF NOT EXISTS TS_OUTPUT ('
			  . tsOutput::TS_OUTPUT_ID_SEQ		 . ' INTEGER PRIMARY KEY AUTOINCREMENT,
			' . tsOutput::TS_OUTPUT_RESERVE_ID 		. ' INTEGER,
			' . tsOutput::TS_OUTPUT_BACKUP_ID 		. ' INTEGER,
			' . tsOutput::TS_OUTPUT_RESERVE 		. ' TEXT,
			' . tsOutput::TS_OUTPUT_BRANCH 			. ' TEXT,
			' . tsOutput::TS_OUTPUT_COMMIT_HASH 	. ' TEXT,
			' . tsOutput::TS_OUTPUT_COMMENT 		. ' TEXT,
			' . tsOutput::TS_OUTPUT_PUBLISH_TYPE 	. ' TEXT,
			' . tsOutput::TS_OUTPUT_STATUS 			. ' TEXT,
			' . tsOutput::TS_OUTPUT_SRV_BK_DIFF_FLG	. ' TEXT,
			' . tsOutput::TS_OUTPUT_START 			. ' TEXT,
			' . tsOutput::TS_OUTPUT_END 			. ' TEXT,
			' . tsOutput::TS_OUTPUT_GEN_DELETE_FLG	. ' TEXT,
			' . tsOutput::TS_OUTPUT_GEN_DELETE		. ' TEXT,
			' . tsOutput::TS_OUTPUT_INSERT_DATETIME . ' TEXT,
			' . tsOutput::TS_OUTPUT_INSERT_USER_ID 	. ' TEXT,
			' . tsOutput::TS_OUTPUT_UPDATE_DATETIME . ' TEXT,
			' . tsOutput::TS_OUTPUT_UPDATE_USER_ID 	. ' TEXT
		)';

		// SQL実行
		$stmt = $this->main->dbh->query($create_sql);

		if (!$stmt) {
			// エラー情報表示
			throw new \Exception($this->main->dbh->errorInfo());
		}

		//============================================================
		// バックアップテーブル作成
		//============================================================
		$create_sql = 'CREATE TABLE IF NOT EXISTS TS_BACKUP ('
			  . tsBackup::TS_BACKUP_ID_SEQ				. ' INTEGER PRIMARY KEY AUTOINCREMENT,
			' . tsBackup::TS_BACKUP_OUTPUT_ID			. ' INTEGER,
			' . tsBackup::TS_BACKUP_DATETIME			. ' TEXT,
			' . tsBackup::TS_BACKUP_GEN_DELETE_FLG		. ' TEXT,
			' . tsBackup::TS_BACKUP_GEN_DELETE_DATETIME	. ' TEXT,
			' . tsBackup::TS_BACKUP_INSERT_DATETIME		. ' TEXT,			
			' . tsBackup::TS_BACKUP_INSERT_USER_ID		. ' TEXT,			
			' . tsBackup::TS_BACKUP_UPDATE_DATETIME		. ' TEXT,
			' . tsBackup::TS_BACKUP_UPDATE_USER_ID		. ' TEXT
		)';

		// SQL実行
		$stmt = $this->main->dbh->query($create_sql);

		if (!$stmt) {
			// エラー情報表示
			throw new \Exception($this->main->dbh->errorInfo());
		}

		// $this->main->common()->put_process_log(__METHOD__, __LINE__, '■ create_table end');
	}

	/**
	 * 引数のバインド指定によるPDOセレクト処理メソッド
	 *	 
	 * 取得結果を配列に格納して返却します。
	 * 該当データが存在しない場合はnullを返却します。
	 *
	 * @param PDO $dbh DB接続情報
	 * @param PDOStatement $stmt ステートメント
	 *	 
	 * @return array[] $ret_array 取得データ格納配列
	 *
	 * @throws Exception PDOによるセレクトエラーが発生した場合
	 */
	public function execute_select ($dbh, $stmt) {

		$ret_array = null;

		$this->main->common()->put_process_log_block('[SQL]');
		$this->main->common()->put_process_log_block($stmt->queryString);

		// 実行
		if ($stmt->execute()) {
			// 取得したデータを配列に格納して返す
			while ($row = $stmt->fetch(\PDO::FETCH_BOTH)) {
				$ret_array[] = $row;
			}
		} else {	
			// エラー情報表示
			throw new \Exception($dbh->errorInfo());
		}

		return $ret_array;
	}

	/**
	 * 引数のバインド指定によるPDOセレクト処理メソッド（1件のみ）
	 *	 
	 * 取得結果を配列に格納して返却します。
	 * 該当データが存在しない場合はnullを返却します。
	 *
	 * @param PDO $dbh DB接続情報
	 * @param PDOStatement $stmt ステートメント
	 *	 
	 * @return array[] $ret_array 取得データ格納配列
	 *
	 * @throws Exception PDOによるセレクトエラーが発生した場合
	 * @throws Exception データが2件以上取得された場合
	 */
	public function execute_select_one ($dbh, $stmt) {

		$ret_array = null;
		$rowcount = 0;

		$this->main->common()->put_process_log_block('[SQL]');
		$this->main->common()->put_process_log_block($stmt->queryString);

		// 実行
		if ($stmt->execute()) {
			// 取得したデータを配列に格納して返す
			while ($row = $stmt->fetch(\PDO::FETCH_BOTH)) {
				$ret_array = $row;
				$rowcount++;
			}
		} else {	
			// エラー情報表示
			throw new \Exception($dbh->errorInfo());
		}

		if ($rowcount > 1) {
			throw new \Exception('More than 2 items of data were acquired.');
		}

		return $ret_array;
	}

	/**
	 * PDO処理実行メソッド
	 *	 
	 * INSERT、UPDATE、DELETE処理に使用しています。
	 *
	 * @param PDO $dbh DB接続情報
	 * @param PDOStatement $stmt ステートメント
	 *	 
	 * @return PDOStatement $stmt ステートメント処理結果
	 *
	 * @throws Exception PDOによる実行エラーが発生した場合
	 */
	public function execute ($dbh, $stmt) {

		$this->main->common()->put_process_log_block('[SQL]');
		$this->main->common()->put_process_log_block($stmt->queryString);

		// 実行
		$stmt->execute();

		if (!$stmt) {
			// エラー情報表示
			throw new \Exception($dbh->errorInfo());
		}

		return $stmt;
	}

}