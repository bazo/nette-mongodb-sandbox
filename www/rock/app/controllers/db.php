<?php

import("classes.BaseController");

class DbController extends BaseController {
	/** database **/
	public function doIndex() {
		$this->db = trim(xn("db"));
		
		$dbs = $this->_server->listDbs();
		$ret = array();
		foreach ($dbs["databases"] as $db) {
			if ($db["name"] == $this->db) {
				$ret = $db;
			}
		}
		
		//collections
		$db = $this->_mongo->selectDB($this->db);
		$collections = MDb::listCollections($db);
		
		$ret = array_merge($ret, $db->command(array("dbstats" => 1)));
		$ret["diskSize"] = "-";
		if (isset($ret["sizeOnDisk"])) {
			$ret["diskSize"] = $this->_formatBytes($ret["sizeOnDisk"]);
		}
		$ret["dataSize"] = $this->_formatBytes($ret["dataSize"]);
		$ret["storageSize"] = $this->_formatBytes($ret["storageSize"]);
		$ret["indexSize"] = $this->_formatBytes($ret["indexSize"]);
		
		$this->stats = array();
		$this->stats["Size"] = $ret["diskSize"];
		$this->stats["Is Empty?"] = $ret["empty"] ? "Yes" : "No";
		if (empty($collections)) {
			$this->stats["Collections"] = count($collections) . " collections:";
			$this->stats["Collections"] .= "<br/>No collections yet";
		}
		else {
			$key = "Collections<br/>[<a href=\"" . $this->path("db.dropDbCollections", array( "db" => $this->db )) . "\" onclick=\"return window.confirm('Are you sure to drop all collections in the db?')\"><u>Drop All</u></a>]<br/>[<a href=\"" . $this->path("clearDbCollections", array( "db" => $this->db )) . "\" onclick=\"return window.confirm('Are you sure to clear all records in all collections?')\"><u>Clear All</u></a>]";
			$this->stats[$key] = count($collections) . " collections:";
			foreach ($collections as $collection) {
				$this->stats[$key] .= "<br/><a href=\"" 
					. $this->path("collection.index", array( "db" => $this->db, "collection" => $collection->getName())) . "\">" . $collection->getName() . "</a>";
			}
		}
		$this->stats["Objects"] = $ret["objects"];
		$this->stats["Data Size"] = $ret["dataSize"];
		$this->stats["Storage Size"] = $ret["storageSize"];
		$this->stats["Extents"] = $ret["numExtents"];
		$this->stats["Indexes"] = $ret["indexes"];
		$this->stats["Index Size"] = $ret["indexSize"];

		$this->display();
	}
	
	/** transfer db collections from one server to another **/
	public function doDbTransfer() {
		$this->db = xn("db");
		
		$db = $this->_mongo->selectDB($this->db);
		$this->collections = $db->listCollections();
		$this->servers = $this->_admin->servers();
		
		$this->selectedCollections = array();
		if (!$this->isPost()) {
			$this->selectedCollections[] = xn("collection");
			x("copy_indexes", 1);
			$this->target_host = "";
			$this->target_port = 27017;
			$this->target_auth = 0;
			$this->target_username = "";
			$this->target_password = "";
		}
		else {
			$this->target_host = trim(xn("target_host"));
			$this->target_port = xi("target_port");
			$this->target_auth = xi("target_auth");
			$this->target_username = trim(xn("target_username"));
			$this->target_password = trim(xn("target_password"));
			
			$checkeds = xn("checked");
			if (is_array($checkeds)) {
				$this->selectedCollections = array_keys($checkeds);
			}
			if (empty($checkeds)) {
				$this->error = "Please select collections which you want to transfer.";
				$this->display();
				return;
			}
			if (empty($this->target_host)) {
				$this->error = "Target host must not be empty.";
				$this->display();
				return;
			}
			$copyIndexes = xi("copy_indexes");
			/**if ($target === "") {
				$this->error = "Please enter a valid database name.";
				$this->display();
				return;
			}**/
			
			//start to transfer
			$targetConnection = new Mongo("mongodb://" . $this->target_host . ":" . $this->target_port);
			$targetDb = $targetConnection->selectDB($this->db);
			if ($this->target_auth) {
				$targetDb->authenticate($this->target_username, $this->target_password);
			}
			$errors = array();
			foreach ($this->selectedCollections as $collectionName) {
				$ret = $targetDb->command(array(
					"cloneCollection" => $this->db . "." . $collectionName,
					"from" =>  $this->_server->uri(),
					"copyIndexes" => (bool)$copyIndexes
				));
				if (!$ret["ok"]) {
					$errors[] = MMongo::readException($ret);
					break;
				}
			}
			if (!empty($errors)) {
				$this->error = implode("<br/>", $errors);
				$this->display();
				return;
			}
			
			$this->message = "All data were transfered to '{$this->target_host}' successfully.";
		}		
		
		$this->display();
	}
	
	/** export db **/
	public function doDbExport() {
		$this->db = xn("db");
		
		$db = $this->_mongo->selectDB($this->db);
		$this->collections = MDb::listCollections($db);
		$this->selectedCollections = array();
		if (!$this->isPost()) {
			$this->selectedCollections[] = xn("collection");
		}
		else {
			$checkeds = xn("checked");
			$canDownload = xn("can_download");
			if (is_array($checkeds)) {
				$this->selectedCollections = array_keys($checkeds);
			}
			
			sort($this->selectedCollections);
			
			import("classes.VarExportor");
			$this->contents =  "";
			$this->countRows = 0;
			
			//indexes
			foreach ($this->selectedCollections as $collection) {
				$collObj = $db->selectCollection($collection);
				$infos = $collObj->getIndexInfo();
				foreach ($infos as $info) {
					$options = array();
					if (isset($info["unique"])) {
						$options["unique"] = $info["unique"];
					}
					$exportor = new VarExportor($db, $info["key"]);
					$exportor2 = new VarExportor($db, $options);
					$this->contents .= "\n/** {$collection} indexes **/\ndb.getCollection(\"" . addslashes($collection) . "\").ensureIndex(" . $exportor->export(MONGO_EXPORT_JSON) . "," . $exportor2->export(MONGO_EXPORT_JSON) . ");\n";
				}
			}
			
			//data
			foreach ($this->selectedCollections as $collection) {
				$cursor = $db->selectCollection($collection)->find();
				$this->contents .= "\n/** " . $collection  . " records **/\n";
				foreach ($cursor as $one) {
					$this->countRows ++;
					$exportor = new VarExportor($db, $one);
					$this->contents .= "db.getCollection(\"" . addslashes($collection) . "\").insert(" . $exportor->export(MONGO_EXPORT_JSON) . ");\n";
					unset($exportor);
				}
				unset($cursor);
			}
			
			if (x("can_download")) {
				$prefix = "rockmongo-export-" . urlencode($this->db) . "-" . time();
				
				//gzip
				if (x("gzip")) {
					ob_end_clean();
					header("Content-type: application/x-gzip");
					header("Content-Disposition: attachment; filename=\"{$prefix}.gz\")"); 
					echo gzcompress($this->contents, 9);
					exit();
				}
				else {
					ob_end_clean();
					header("Content-type: application/octet-stream");
					header("Content-Disposition: attachment; filename=\"{$prefix}.js\")");
					echo $this->contents;
					exit();
				}
			}
		}
		
		$this->display();
	}
	
	/** import db **/
	public function doDbImport() {
		$this->db = xn("db");
		
		if ($this->isPost()) {
			if (!empty($_FILES["json"]["tmp_name"])) {
				$tmp = $_FILES["json"]["tmp_name"];
				
				//read file by it's format
				$body = "";
				if (preg_match("/\.gz$/", $_FILES["json"]["name"])) {
					$body = gzuncompress(file_get_contents($tmp));
				}
				else {
					$body = file_get_contents($tmp);
				}
				
				$ret = $this->_mongo->selectDB($this->db)->execute('function (){ ' . $body . ' }');
				$this->message = "All data import successfully.";
			}
			else {
				$this->error = "Either no file input or file is too large to upload.";
			}
		}
		
		$this->display();
	}	
	
	/** db profiling **/
	public function doProfile() {
		$this->db = xn("db");
	
		import("lib.mongo.RQuery");
		import("lib.page.RPageStyle1");
		$query = new RQuery($this->_mongo, $this->db, "system.profile");
		$page = new RPageStyle1();
		$page->setTotal($query->count());
		$page->setSize(10);
		$page->setAutoQuery();
		$this->page = $page;
		
		$this->rows = $query
			->offset($page->offset())
			->limit($page->size())
			->desc("ts")
			->findAll();
		foreach ($this->rows as $index => $row) {
			$this->rows[$index]["text"] = $this->_highlight($row, "json");
		}
			
		$this->display();
	}
	
	/** change db profiling level **/
	public function doProfileLevel() {
		$this->db = xn("db");

		$db = $this->_mongo->selectDB($this->db);
		$query1 = $db->execute("function (){ return db.getProfilingLevel(); }");
		$this->level = $query1["retval"];
		if (x("go") == "save_level") {
			$level = xi("level");
			$slowms = xi("slowms");
			$db->execute("function(level,slowms) { db.setProfilingLevel(level,slowms); }", array($level, $slowms));
			$this->level = $level;
		}
		else {
			x("slowms", 50);
		}
		$this->display();
	}
	
	/** clear profiling data **/
	public function doClearProfile() {
		$this->db = xn("db");
		$db = $this->_mongo->selectDB($this->db);
		
		$query1 = $db->execute("function (){ return db.getProfilingLevel(); }");
		$oldLevel = $query1["retval"];
		$db->execute("function(level) { db.setProfilingLevel(level); }", array(0));
		$ret = $db->selectCollection("system.profile")->drop();
		$db->execute("function(level) { db.setProfilingLevel(level); }", array($oldLevel));

		$this->redirect("db.profile", array( 
			"db" => $this->db
		));
	}
	
	/** authentication **/
	public function doAuth() {
		$this->db = xn("db");
		$db = $this->_mongo->selectDB($this->db);
		
		//users
		$collection = $db->selectCollection("system.users");
		$cursor = $collection->find();
		$this->users= array();
		while($cursor->hasNext()) {
			$this->users[] = $cursor->getNext();
		}
		
		$this->display();
	}
	
	/** delete user **/
	public function doDeleteUser() {
		$this->db = xn("db");
		$db = $this->_mongo->selectDB($this->db);
		
		$db->execute("function (username){ db.removeUser(username); }", array(xn("user")));
		$this->redirect("db.auth", array(
			"db" => $this->db
		));
	}
	
	/** add user **/
	public function doAddUser() {
		$this->db = xn("db");
		
		if (!$this->isPost()) {
			$this->display();
			return;
		}
		
		$username = trim(xn("username"));
		$password = trim(xn("password"));
		$password2 = trim(xn("password2"));
		if ($username == "") {
			$this->error = "You must supply a username for user.";
			$this->display();
			return;
		}
		if ($password == "") {
			$this->error = "You must supply a password for user.";
			$this->display();
			return;
		}
		if ($password != $password2) {
			$this->error = "Passwords you typed twice is not same.";
			$this->display();
			return;
		}
		$db = $this->_mongo->selectDB($this->db);
		$db->execute("function (username, pass, readonly){ db.addUser(username, pass, readonly); }", array(
			$username,
			$password,
			x("readonly") ? true : false
		));
		
		$this->redirect("auth", array(
			"db" => $this->db
		));
	}
	
	/** create new collection **/
	public function doNewCollection() {
		$this->db = xn("db");
		$this->name = x("name");
		$this->isCapped = xi("is_capped");
		$this->size = xi("size");
		$this->max = xi("max");
		
		if ($this->isPost()) {
			$db = new MongoDB($this->_mongo, $this->db);
			$db->createCollection($this->name, $this->isCapped, $this->size, $this->max);
			$this->message = "New collection is created.";
			
			//add index
			if (!$this->isCapped) {
				$db->selectCollection($this->name)->ensureIndex(array( "_id" => 1 ));
			}
		}
		
		$this->display();
	}
	
	/** drop all collections in a db **/
	public function doDropDbCollections() {
		$this->db = xn("db");
		$db = $this->_mongo->selectDB($this->db);
		foreach ($db->listCollections() as $collection) {
			$collection->drop();
		}
		echo '<script language="javascript">
window.parent.frames["left"].location.reload();
</script>';
		$this->redirect("db.index", array( "db" => $this->db ), true);
	}
	
	/** clear all records in all collections **/
	public function doClearDbCollections() {
		$this->db = xn("db");
		$db = $this->_mongo->selectDB($this->db);
		foreach ($db->listCollections() as $collection) {
			$collection->remove();
		}
		echo '<script language="javascript">
window.parent.frames["left"].location.reload();
</script>';
		$this->redirect("db.index", array( "db" => $this->db ), true);
	}
	
	/** repair dataase **/
	public function doRepairDatabase() {
		$this->db = xn("db");
		
		$db = $this->_mongo->selectDB($this->db);
		$ret = $db->command(array( "repairDatabase" => 1 ));
		//$ret = $db->execute('function (){ return db.repairDatabase(); }'); //occure error in current version, we did not know why?
		$this->ret = $this->_highlight($ret, "json");
		$this->display();
	}
	
	/** drop database **/
	public function doDropDatabase() {
		$this->db = xn("db");
		
		if (!x("confirm")) {
			$this->display();
			return;
		}
		
		$ret = $this->_mongo->dropDB($this->db);
		$this->ret = $this->_highlight($ret, "json");
		$this->display("dropDatabaseResult");
	}	
}

?>