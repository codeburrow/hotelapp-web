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

    public function push()
    {
        if ($GLOBALS['environment']=="dev"){
            $local_cert = __DIR__ . "/../../HotelAppCodeBurrow.pem";
        } else {
            $certificate = file_get_contents(getenv('PEM'));
//            $certificate = file_get_contents(__DIR__ . "/../../HotelAppCodeBurrow.pem");
            var_dump($certificate);
            $tmpfname = tempnam("/", "cer");

            $handle = fopen($tmpfname, "w");
            fwrite($handle, $certificate);
            fclose($handle);
            var_dump($tmpfname);
        }

        // Put your device token here (without spaces):
        $deviceToken = getenv('LUT_DEVICE_TOKEN');

        // Put your private key's passphrase here:
        $passphrase = getenv('PASSPHRASE');

        $message = "Not_3";
        $url = "http://www.w3schools.com/w3css/w3css_colors.asp";

        if (!$message || !$url)
            exit('Example Usage: $php newspush.php \'Breaking News!\' \'https://raywenderlich.com\'' . "\n");

////////////////////////////////////////////////////////////////////////////////

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $tmpfname);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

// Open a connection to the APNS server
        $fp = stream_socket_client(
            'ssl://gateway.sandbox.push.apple.com:2195', $err,
            $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp)
            exit("Failed to connect: $err $errstr" . PHP_EOL);

        echo 'Connected to APNS' . PHP_EOL;

// Create the payload body
        $body['aps'] = array(
            'alert' => $message,
            'sound' => 'default',
            'link_url' => $url,
        );

// Encode the payload as JSON
        $payload = json_encode($body);

// Build the binary notification
        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

// Send it to the server
        $result = fwrite($fp, $msg, strlen($msg));

        if (!$result)
            echo 'Message not delivered' . PHP_EOL;
        else
            echo 'Message successfully delivered' . PHP_EOL;

        fclose($fp); //Close the connection to the server
        unlink($tmpfname);
    }

}