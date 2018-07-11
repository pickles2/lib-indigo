<?php

namespace indigo;

class pdoManager
{

	private $main;

	private $fileManager;
	private $common;
	/**
	 * PDOインスタンス
	 */
	private $dbh;
	
	// DBディレクトリパス
	const SQLITE_DB_PATH = '/sqlite/';
	// DBディレクトリパス
	const SQLITE_DB_NAME = 'indigo.db';

	/**
	 * Constructor
	 *
	 * @param object $px Picklesオブジェクト
	 */
	public function __construct ($main){

		$this->main = $main;
		$this->fileManager = new fileManager($this);
		$this->common = new common($this);


		// // DELETE文作成
		// $delete_sql = "DELETE FROM list WHERE id = :id";
		// // パラメータ作成
		// $params = array(
		// 	':id' => '1'
		// );
		// // DELETE実行
		// $stmt = $this->select($delete_sql, $params);

		// // デバック用（直前の操作件数取得）
		// // $count = $stmt->rowCount();
	}


	/**
	 * データベースへ接続する
	 *	 
	 */
	public function connect() {
	
		$this->common->debug_echo('■ connect start');

		$dbh = null; // 初期化

		$dsn;
		$db_user;
		$db_pass;
		$option;

		$db_type = $this->main->options->db_type;

		$this->common->debug_echo('　□ db_type');
		$this->common->debug_echo($db_type);

		if ($db_type && $db_type == 'mysql') {

			$this->common->debug_echo('　□ mysql');

			/**
			 * mysqlの場合
			 */
			$db_name = $this->main->options->mysql_db_name;		// データベース名
			$db_host = $this->main->options->mysql_db_host;		// ホスト名

			$dsn = "mysql:dbname=" . $db_name . ";host=" . $db_host. ";charset=utf8";

			$db_user = $this->main->options->mysql_db_user;
			$db_pass = $this->main->options->mysql_db_pass;

			$option = array(
						\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.SELF::UTF
					);

	
		} else {

			$this->common->debug_echo('　□ sqlite');

			$this->common->debug_echo($this->main->options->indigo_workdir_path . self::SQLITE_DB_PATH);

			/**
			 * sqliteの場合 
			 */
			// dbディレクトリの絶対パス
			$db_real_path = $this->fileManager->normalize_path($this->fileManager->get_realpath($this->main->options->indigo_workdir_path . self::SQLITE_DB_PATH));

			$this->common->debug_echo('　□ db_real_path：' . $db_real_path);

			// DBディレクトリが存在しない場合は作成
			if ( !$this->fileManager->is_exists_mkdir($db_real_path) ) {

					// エラー処理
					throw new \Exception('Creation of sqlite directory failed.');
			}

			$dsn = "sqlite:" . $db_real_path . self::SQLITE_DB_NAME;

			$db_user = null;
			$db_pass = null;

			$option = array(
						\PDO::ATTR_PERSISTENT => false, // ←これをtrueにすると、"持続的な接続" になる
						\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,	// エラー表示の設定
						\PDO::ATTR_EMULATE_PREPARES => false　	// prepareを利用する
					);
		}
			
		try {

	  		$dbh = new \PDO(
	  			$dsn,
	  			$db_user,
	  			$db_pass,
	  			$option
	  		);

		} catch (\PDOException $e) {
	  		echo 'Connection failed: ' . $e->getMessage();
	  		// // 強制終了
	  		// die();
		}
			
		$this->common->debug_echo('■ connect end');

		return $dbh;

	}

	/**
	 * データベースの接続を閉じる
	 *	 
	 */
	public function close($dbh) {
	
		$this->common->debug_echo('■ close start');

		try {

			// データベースの接続を閉じる
			$dbh = null;


		} catch (\PDOException $e) {
	  		echo 'Connection failed: ' . $e->getMessage();
	  		// // 強制終了
	  		// die();
		}
		
		$this->common->debug_echo('■ close end');

	}


	/**
	 * CREATE処理関数
	 *	 
	 */
	public function create_table($dbh) {

		$this->common->debug_echo('■ create_table start');

		//============================================================
		// 公開予約テーブル作成
		//============================================================
		$create_sql = 'CREATE TABLE IF NOT EXISTS TS_RESERVE ('
			  . tsReserve::TS_RESERVE_RESERVE_ID_SEQ . ' INTEGER PRIMARY KEY AUTOINCREMENT,
			' . tsReserve::TS_RESERVE_RESERVE . ' TEXT,
			' . tsReserve::TS_RESERVE_BRANCH . ' TEXT,
			' . tsReserve::TS_RESERVE_COMMIT . ' TEXT,
			' . tsReserve::TS_RESERVE_COMMENT . ' TEXT,
			' . tsReserve::TS_RESERVE_DELETE_FLG . ' TEXT,			
			' . tsReserve::TS_RESERVE_INSERT_DATETIME . ' TEXT,
			' . tsReserve::TS_RESERVE_INSERT_USER_ID . ' TEXT,
			' . tsReserve::TS_RESERVE_UPDATE_DATETIME . ' TEXT,
			' . tsReserve::TS_RESERVE_UPDATE_USER_ID . ' TEXT
		)';

		// SQL実行
		$stmt = $dbh->query($create_sql);

		if (!$stmt) {
			// エラー情報表示
			throw new \Exception($dbh->errorInfo());
		}

		$this->common->debug_echo('　□ 公開予約テーブル作成完了');

		//============================================================
		// 公開処理結果テーブル作成
		//============================================================
		$create_sql = 'CREATE TABLE IF NOT EXISTS TS_OUTPUT ('
			  . tsOutput::TS_OUTPUT_ID_SEQ . ' INTEGER PRIMARY KEY AUTOINCREMENT,
			' . tsOutput::TS_OUTPUT_RESERVE_ID . ' INTEGER,
			' . tsOutput::TS_OUTPUT_BACKUP_ID . ' INTEGER,
			' . tsOutput::TS_OUTPUT_RESERVE . ' TEXT,
			' . tsOutput::TS_OUTPUT_BRANCH . ' TEXT,
			' . tsOutput::TS_OUTPUT_COMMIT . ' TEXT,
			' . tsOutput::TS_OUTPUT_COMMENT . ' TEXT,
			' . tsOutput::TS_OUTPUT_PUBLISH_TYPE . ' TEXT,
			' . tsOutput::TS_OUTPUT_STATUS . ' TEXT,
			' . tsOutput::TS_OUTPUT_DIFF_FLG1 . ' TEXT,
			' . tsOutput::TS_OUTPUT_DIFF_FLG2 . ' TEXT,
			' . tsOutput::TS_OUTPUT_DIFF_FLG3 . ' TEXT,
			' . tsOutput::TS_OUTPUT_START . ' TEXT,
			' . tsOutput::TS_OUTPUT_END . ' TEXT,
			' . tsOutput::TS_OUTPUT_DELETE_FLG . ' TEXT,
			' . tsOutput::TS_OUTPUT_DELETE . ' TEXT,
			' . tsOutput::TS_OUTPUT_INSERT_DATETIME . ' TEXT,
			' . tsOutput::TS_OUTPUT_INSERT_USER_ID . ' TEXT,
			' . tsOutput::TS_OUTPUT_UPDATE_DATETIME . ' TEXT,
			' . tsOutput::TS_OUTPUT_UPDATE_USER_ID . ' TEXT
		)';

		// SQL実行
		$stmt = $dbh->query($create_sql);

		if (!$stmt) {
			// エラー情報表示
			throw new \Exception($dbh->errorInfo());
		}

		$this->common->debug_echo('　□ 公開処理結果テーブル作成完了');

		//============================================================
		// バックアップテーブル作成
		//============================================================
		$create_sql = 'CREATE TABLE IF NOT EXISTS TS_BACKUP (
			backup_id_seq INTEGER PRIMARY KEY AUTOINCREMENT,
			output_id INTEGER,
			backup_datetime TEXT,
			gen_delete_flg TEXT,
			gen_delete_datetime TEXT,
			insert_datetime TEXT,
			insert_user_id TEXT,
			update_datetime TEXT,
			update_user_id TEXT
		)';

		// SQL実行
		$stmt = $dbh->query($create_sql);

		if (!$stmt) {
			// エラー情報表示
			throw new \Exception($dbh->errorInfo());
		}

		$this->common->debug_echo('　□ バックアップテーブル作成完了');

		$this->common->debug_echo('■ create_table end');

		return;
	}


	/**
	 * SELECT処理関数
	 *	 
	 * @param $sql = SQL文
	 *	 
	 * @return 取得データ配列
	 */
	public function select($dbh, $sql) {

		$this->common->debug_echo('■ select start');

		$ret_array = array();
		$stmt = null;

		// $this->common->debug_echo('　□sql：');
		// $this->common->debug_var_dump($sql);

		// 実行
		if ($stmt = $dbh->query($sql)) {

			// 取得したデータを配列に格納して返す
			while ($row = $stmt->fetch(\PDO::FETCH_BOTH)) {
				$ret_array[] = $row;
			}
		}

		if (!$stmt) {
			
			// エラー情報表示
			throw new \Exception($dbh->errorInfo());
		}
		
		// $this->common->debug_echo('　□返却リストデータ：');
		// $this->common->debug_var_dump($ret_array);

		$this->common->debug_echo('■ select end');

		return $ret_array;
	}


	/**
	 * INSERT、UPDATE、DELETE処理関数
	 *	 
	 * @param $sql    = SQL文
	 * @param $params = パラメータ
	 *	 
	 * @return 画面表示用のステータス情報
	 */
	public function execute ($dbh, $sql, $params) {

		$this->common->debug_echo('■ execute start');

		// 前処理
		$stmt = $dbh->prepare($sql);

		// 実行
		$stmt->execute($params);

		if (!$stmt) {
			
			$this->common->debug_echo('　□ execute error');

			// エラー情報表示
			throw new \Exception($dbh->errorInfo());
		}

		$this->common->debug_echo('■ execute end');

		return $stmt;
	}

}