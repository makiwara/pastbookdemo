<?php

namespace PastBookSocialApp;
use \Silex\Provider\DoctrineServiceProvider;
use \Doctrine\DBAL\Schema\Schema;


class DBAL {

	public function __construct($app) {
		$this->config = $app['dbal'];
		$this->app = $app;
		$this->app->register(new \Silex\Provider\DoctrineServiceProvider(), $this->config);
		$this->conn = $this->app['db'];
		$this->platform = $this->conn->getDatabasePlatform();
	}


	// TODO: create user
	// TODO: enqueue photo
	// TODO: dequeue photo
	// TODO: update photo
	// TODO: get photos
	

	// --------------------------------------------------------------------------------
	// --------------------------------------------------------------------------------
	// --------------------------------------------------------------------------------
	// Database initialisation/destruction routines -----------------------------------
	// --------------------------------------------------------------------------------
	// --------------------------------------------------------------------------------
	// --------------------------------------------------------------------------------
	public function isInit() {
		$sm = $this->conn->getSchemaManager();
		$tables = $sm->listTables();
		foreach ($tables as $table) {
			if ($table->getName() == 'user') return true;
		}
		return false;
	}
	public function init() {
		$schema = $this->generateSchema();
		foreach ($schema->toSql($this->platform) as $q) {
			print $q.'<br>';
			$this->conn->query($q);
		}
	}
	public function drop() {
		$schema = $this->generateSchema();
		foreach ($schema->toDropSql($this->platform) as $q) {
			if (preg_match('/^DROP./', $q) > 0) { // sqlite does not work well with ALTER TABLE, hence.
				print $q.'<br>';
				$this->conn->query($q);
			}
		}
	}
	public function generateSchema() {
		$schema = new \Doctrine\DBAL\Schema\Schema();
		// user
		$userTable = $schema->createTable("user");
		$userTable->addColumn("id", "integer", array("unsigned" => true));
		$userTable->addColumn("hash",  "string", array("length" => 250));
		$userTable->addColumn("email", "string", array("length" => 250));
		$userTable->setPrimaryKey(array("id"));
		$userTable->addUniqueIndex(array("email"));
		$userTable->addUniqueIndex(array("hash"));
		// auth
		$authTable = $schema->createTable("auth");
		$authTable->addColumn("id", "integer", array("unsigned" => true));
		$authTable->addColumn("user_id", "integer", array("unsigned" => true));
		$authTable->addColumn("provider",  "string", array("length" => 250));
		$authTable->addColumn("token", "string", array("length" => 250));
		$authTable->setPrimaryKey(array("id"));
		$authTable->addForeignKeyConstraint($userTable, array("user_id"), array("id"), 
			array("onUpdate" => "CASCADE"));
		// photo
		$photoTable = $schema->createTable("photo");
		$photoTable->addColumn("id", "integer", array("unsigned" => true));
		$photoTable->addColumn("user_id", "integer", array("unsigned" => true));
		$photoTable->addColumn("url", "string", array("length" => 250));
		$photoTable->addColumn("url_thumbnail", "string", array("length" => 250));
		$photoTable->addColumn("url_original", "string", array("length" => 250));
		$photoTable->addColumn("url_thumbnail_original", "string", array("length" => 250));
		$photoTable->addColumn("state", "string", array("length" => 20));
		$photoTable->addForeignKeyConstraint($userTable, array("user_id"), array("id"), 
			array("onUpdate" => "CASCADE"));

		return $schema;
	}
}
