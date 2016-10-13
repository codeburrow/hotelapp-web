<?php
namespace HotelApp\Database;

use PDO;
use PDOException;

class DB
{
    protected $host;
    protected $port;
    protected $dbname;
    protected $username;
    protected $password;
    protected $conn;

    /**
     * DB constructor. Connect to Heroku's DB (ClearDB).
     */
    public function __construct()
    {
        $cleardb_url = parse_url(getenv("CLEARDB_DATABASE_URL"));

        $this->host = $cleardb_url["host"];;
        $this->port = 3306;
        $this->dbname = substr($cleardb_url["path"], 1);
        $this->username = $cleardb_url["user"];
        $this->password = $cleardb_url["pass"];

        $this->options = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'];

        $this->connect();
    }

    /**
     * Alternative DB constructor for connection to the Homestead virtual DB server
     * @param string $servername
     * @param string $port
     * @param string $dbname
     * @param string $username
     * @param string $password
     */
//    public function __construct($servername = "127.0.0.1", $port = "33060", $dbname = "hotelapp_web", $username = "homestead", $password = "secret")
//    {
//        $this->host = $servername;
//        $this->port = $port;
//        $this->dbname = $dbname;
//        $this->username = $username;
//        $this->password = $password;
//
//        $this->options = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'];
//
//        $this->connect();
//    }

    public function connect()
    {
        try {
            $conn = new PDO("mysql:host=$this->host;port:$this->port;dbname=$this->dbname;charset=utf8", $this->username, $this->password, $this->options);
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn = $conn;
//            echo "Connected successfully";
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    public function getUser($email, $password)
    {
        $stmt = $this->conn->prepare("SELECT * FROM $this->dbname.user WHERE user_email  LIKE :userEmail AND user_password LIKE :userPassword");
        $stmt->bindParam(':userEmail', $email);
        $stmt->bindParam(':userPassword', $password);
        $stmt->execute();

        // set the resulting array to associative
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetch();

        return $result;
    }

    public function registerUser($data)
    {
        try {
            $stmt = $this->conn->prepare("
INSERT INTO $this->dbname.users
(userEmail, userPassword)
VALUES (:userEmail, :userPassword)");
            $stmt->bindParam(':userEmail', $data['email']);
            $stmt->bindParam(':userPassword', $data['password']);
            $stmt->execute();


            if ($stmt->rowCount() > 0) {
                $result['success'] = true;
                $result['message'] = 'Congrats! You are now a Judge. Please Log-in.';
            } else {
                $result['success'] = false;
                $result['message'] = "Something went wrong. Try again or contact support.";
            }

            return $result;

        } catch (PDOException $e) {
            $result['success'] = false;
            $result['message'] = "This email already exists. Make sure you typed the email correctly or contact support.";

            return $result;
        }
    }

    public function getUserToken($userId)
    {
        $stmt = $this->conn->prepare("SELECT user_device, user_token FROM $this->dbname.user WHERE user_id=:user_id;");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        // set the resulting array to associative
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $result = $stmt->fetch();

        return $result;
    }

    public function setUserToken($userId, $userToken)
    {
        $stmt = $this->conn->prepare("UPDATE $this->dbname.user SET user_token = ?  WHERE user_id= ? ;");

        try{
        $stmt->bindParam(1, $userToken);
        $stmt->bindParam(2, $userId);
        $stmt->execute();

        } catch (Exception $e) {
        }
    }

}