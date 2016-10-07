<?php
namespace HotelApp\Controllers;

use ChromePhp;
use HotelApp\Database\DB;
use HotelApp\Models\User;
use HotelApp\Services\InvertNamesToUrl;
use HotelApp\Services\SwiftMailer;
use HotelApp\Transformers\JudgmentsTransformer;

class MainController extends Controller
{
    protected $user;

    public function __construct($data = null)
    {
        parent::__construct($data);

        if (isset($_SESSION['user']) && isset($_SESSION['password'])) {
            $this->user = new User($_SESSION['user'], $_SESSION['password']);
        }
    }

    public function index($message = null, $success = null)
    {
        if ($message == null) {
            echo $this->twig->render('index.twig');
        } else {
            echo $this->twig->render('index.twig', array('errorMessage' => $message, 'success' => $success));
        }
    }

    public function register($errorMessage=null)
    {
        if (isset($errorMessage)) {
            echo $this->twig->render('register.twig', array('errorMessage'=>$errorMessage));
        } else {
            echo $this->twig->render('register.twig');
        }
    }

    public function postRegister()
    {
        $db = new DB();

        $result = $db->registerUser($_POST);

        if ($result['success']==true)
            $this->index($result['message'], $result['success']);
        else
            $this->register($result['message']);
    }

    public function login($errorMessage = null)
    {
        if (isset($errorMessage))
            echo $this->twig->render('login.twig', array('errorMessage' => $errorMessage));
        else
            echo $this->twig->render('login.twig');
    }

    public function postLogin()
    {
        $myDB = new DB();

        $user = $myDB->getUser($_POST['email'], $_POST['password']);

        if (empty($user)) {
            $errorMessage = "Wrong Credentials.";

            $this->login($errorMessage);
        } else {
            $this->user = new User($_POST['email'], $_POST['password']); //find the user from db

            $this->user->login(); //set Cookies and Session

            $this->index("Welcome Judge.", true); //show index page
        }
    }

    public function logout()
    {
        if (isset($this->user) && $this->user->IsLoggedIn()) {
            $this->user->logout();
            $errorMessage = "You have been logged out.";
            $this->login($errorMessage);
        }
    }

    public function contact($result=null)
    {
        echo $this->twig->render('contact.twig', array('result' => $result));
    }

    public function postContact()
    {
        $mailer = new SwiftMailer();

        $result = $mailer->sendEmail($_POST);

        $this->contact($result);
    }

    public function about()
    {
        echo $this->twig->render('about.twig');
    }

    public function error404()
    {
        echo $this->twig->render('error404.twig');
    }

}