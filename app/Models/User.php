<?php
/**
 * Created by PhpStorm.
 * User: antony
 * Date: 7/7/16
 * Time: 1:17 PM
 */
namespace HotelApp\Models;

use HotelApp\Database\DB;

class User
{
    protected $myDB;

    protected $email;
    protected $password;
    protected $isAdmin;
    protected $id;

    public function __construct($email, $password)
    {
        $this->myDB = new DB();

        $this->setClassVariables($email, $password);
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getIsAdmin()
    {
        return $this->isAdmin;
    }

    public function setClassVariables($email, $password)
    {
        $user = $this->myDB->getUser($email, $password);
        
        $this->id = $user['user_id'];
        $this->email = $user['user_email'];
        $this->password = $user['user_password'];
        $this->isAdmin = $user['user_is_admin'];
    }

    public function isAdmin()
    {
        if ($this->getIsAdmin() === '1') {
            return "";
        } elseif (is_null($this->isAdmin)) {
            return "The credentials you entered are wrong";
        } elseif ($this->isAdmin === '0') {
            return "You are a user but not an admin..";
        } else {
            return "If you forgot your credentials contact support";
        }
    }

    public function isLoggedIn()
    {
        if (isset($_COOKIE['active'])) {
            return true;
        }

        if (isset($_SESSION['user_email']) && $_SESSION['user_email'] == $this->getEmail()) {
            return true;
        } else {
            return false;
        }
    }

    public function login()
    {
        //Start $_SESSION
        $status = session_status();
        if ($status == PHP_SESSION_NONE) {
            //There is no active session
            session_start();
        } elseif ($status == PHP_SESSION_DISABLED) {
            //Sessions are not available
        } elseif ($status == PHP_SESSION_ACTIVE) {
            //Destroy current and start new one
            session_destroy();
            session_start();
        }

        //Set $_SESSION variables
        $_SESSION['user_email'] = $this->getEmail();
        $_SESSION['user_id'] = $this->getId();
        $_SESSION['user_password'] = $this->getPassword();
        $_SESSION['user_is_admin'] = $this->getIsAdmin();

        //Set $_COOKIE
        if (isset($_POST['remember'])) {
            setcookie("active", $_SESSION['user'], time() + (3600 * 24 * 365));
        }
    }

    public function logout()
    {
        $status = session_status();
        if ($status == PHP_SESSION_NONE) {
            //There is no active session
            session_start();
        } elseif ($status == PHP_SESSION_DISABLED) {
            //Sessions are not available
        } elseif ($status == PHP_SESSION_ACTIVE) {
            //Destroy current and start new one
            session_destroy();
            session_start();
        }

        //Unset $_SESSION variables
        unset($_SESSION["user_email"]);
        unset($_SESSION["user_id"]);
        unset($_SESSION["user_password"]);
        unset($_SESSION["user_is_admin"]);

        //Unset $_COOKIE variables
        unset($_COOKIE['active']);
        setcookie('active', '', time() - 3600);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

}