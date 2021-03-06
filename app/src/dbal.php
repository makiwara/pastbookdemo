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
            $hash = md5($this->config["salt"].$email);
            $this->conn->insert('user', array('email' => $email, 'hash' => $hash));
        }
        $user = $this->conn->fetchAssoc('SELECT * FROM user WHERE email = ?', array($email));
        return $user;
    }

    // Get user by hash if only it exists
    public function getUserByHash( $hash ) {
        $user = $this->conn->fetchAssoc('SELECT * FROM user WHERE hash = ?', array($hash));
        return $user;
    }

    // Get user by id if only it exists
    public function getUserById( $id ) {
        $user = $this->conn->fetchAssoc('SELECT * FROM user WHERE id = ?', array($id));
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

    // Get photos for the user 
    public function getPhotos( $user ) {
        $photos = $this->conn->fetchAll('SELECT * FROM photo WHERE user_id = ? ORDER BY state ASC', array($user["id"]));
        $prepare = function($photo) {
            return array(
                "state" => $photo["state"],
                "url"   => $photo["url"],
                "thumb" => $photo["url_thumbnail"],
            );
        };
        return array_map($prepare, $photos);
    }

    // Dequeue one photo for uploading
    public function dequeue() {
        $photo = $this->conn->fetchAssoc('SELECT * FROM photo WHERE state = ?', array('queue'));
        $this->conn->update('photo', array("state" => "progress"), array('id'=>$photo["id"]));
        return $photo;
    }

    // Update changes in photo
    public function updatePhoto($photo, $updates) {
        $this->conn->update('photo', $updates, array('id'=>$photo["id"]));
    }

    // Check if this photo completes queue for some user
    public function isQueueComplete($photo) {
        $sql = 'SELECT COUNT(*) AS howmany FROM photo WHERE state <> ? and user_id = ? ORDER BY state ASC';
        $howmany = $this->conn->fetchColumn($sql, array('done', $photo["user_id"]), 0);
        return $howmany == 0;
    }


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
            print $q.'<br>'; // debug output
            $this->conn->query($q);
        }
    }
    public function drop() {
        $schema = $this->generateSchema();
        foreach ($schema->toDropSql($this->platform) as $q) {
            if (preg_match('/^DROP./', $q) > 0) { // sqlite does not work well with ALTER TABLE, hence.
                print $q.'<br>'; // debug output
                $this->conn->query($q);
            }
        }
    }
    public function generateSchema() {
        $schema = new \Doctrine\DBAL\Schema\Schema();
        // user
        $userTable = $schema->createTable("user");
        $userTable->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
        $userTable->addColumn("email", "string", array("length" => 250));
        $userTable->addColumn("hash", "string", array("length" => 250));
        $userTable->setPrimaryKey(array("id"));
        $userTable->addUniqueIndex(array("email"));
        $userTable->addUniqueIndex(array("hash"));
        // auth
        $authTable = $schema->createTable("auth");
        $authTable->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
        $authTable->addColumn("user_id", "integer", array("unsigned" => true));
        $authTable->addColumn("provider",  "string", array("length" => 250));
        $authTable->addColumn("token", "string", array("length" => 250));
        $authTable->setPrimaryKey(array("id"));
        $authTable->addForeignKeyConstraint($userTable, array("user_id"), array("id"), 
            array("onUpdate" => "CASCADE"));
        // photo
        $photoTable = $schema->createTable("photo");
        $photoTable->addColumn("id", "integer", array("unsigned" => true, "autoincrement" => true));
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
