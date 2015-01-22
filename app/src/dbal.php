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

    // Register user by email and return hash; no duplicates allowed
    public function getUserByEmail( $email ) {
        // check if there is already a user
        $sql = "SELECT COUNT(*) AS is_already FROM user WHERE email = ?";
        $is_already = $this->conn->fetchColumn($sql, array($email), 0);
        if ($is_already == 0) {
            // create new user
            $this->conn->insert('user', array('email' => $email));
        }
        $user = $this->conn->fetchAssoc('SELECT * FROM user WHERE email = ?', array($email));
        return $user;
    }

    // Update user OAuth token
    public function updateUserToken( $user, $provider, $token ) {
        $this->conn->delete('auth', array( 
            'user_id' => $user["id"],
            'provider' => $provider,
        ));
        $this->conn->insert('auth', array(
            'user_id' => $user["id"],
            'provider' => $provider,
            'token' => $token,
        ));
    }

    // Enqueue photos for upload
    public function enqueue( $user, $photos ) {
        foreach ($photos as $data) {
            $this->conn->insert('photo', array(
                'user_id' => $user["id"],
                'url_thumbnail_original' => $data[0],
                'url_original' => $data[1],
                'state' => 'queue',
            ));
        }
    }

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
        $userTable->addColumn("email", "string", array("length" => 250));
        $userTable->setPrimaryKey(array("id"));
        $userTable->addUniqueIndex(array("email"));
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
        $photoTable->addColumn("url", "string", array("length" => 250, "default" => ""));
        $photoTable->addColumn("url_thumbnail", "string", array("length" => 250, "default" => ""));
        $photoTable->addColumn("url_original", "string", array("length" => 250));
        $photoTable->addColumn("url_thumbnail_original", "string", array("length" => 250));
        $photoTable->addColumn("state", "string", array("length" => 20));
        $photoTable->setPrimaryKey(array("id"));
        $photoTable->addForeignKeyConstraint($userTable, array("user_id"), array("id"), 
            array("onUpdate" => "CASCADE"));

        return $schema;
    }
}
