<?php
/**
 * Created by PhpStorm.
 * User: antony
 * Date: 10/11/16
 * Time: 11:16 AM
 */

namespace HotelApp\Services;

class PushNotifications
{
    public $androidAuthKey = "Android Auth Key Here";

    /**
     *  For Android GCM
     * 	$params["msg"] : Expected Message For GCM
     */
    private function sendMessageAndroid($registration_id, $params) {
        $this->androidAuthKey = "Android Auth Key Here";//Auth Key Herer

        ## data is different from what your app is programmed
        $data = array(
            'registration_ids' => array($registration_id),
            'data' => array(
                'gcm_msg'		=> $params["msg"]
            )
        );


        $headers = array(
            "Content-Type:application/json",
            "Authorization:key=".$this->androidAuthKey
        );


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://android.googleapis.com/gcm/send");
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $result = curl_exec($ch);
        //result sample {"multicast_id":6375780939476727795,"success":1,"failure":0,"canonical_ids":0,"results":[{"message_id":"0:1390531659626943%6cd617fcf9fd7ecd"}]}
        //http://developer.android.com/google/gcm/http.html  // refer error code
        curl_close($ch);

        $rtn["code"]	= "000";//means result OK
        $rtn["msg"]		= "OK";
        $rtn["result"]	= $result;
        return $rtn;

    }

    /**
     * For IOS APNS
     * $params["msg"] : Expected Message For APNS
     */
    private function sendMessageIos($params) {

        if ($GLOBALS['environment']=='dev'){ //Development
            $ssl_url         		= 'ssl://gateway.sandbox.push.apple.com:2195';
            $iosApnsCert = __DIR__ . "/../../HotelAppCodeBurrow.pem";
        } else { //Production
//            $ssl_url				= 'ssl://gateway.push.apple.com:2195';
            $ssl_url         		= 'ssl://gateway.sandbox.push.apple.com:2195'; //Use this for time being

            $certificate = getenv("PEM"); //Retrieve the contents of the file
            $iosApnsCert = tempnam("/", "cer"); //create a temp file
            $handle = fopen($iosApnsCert, "w"); //open it to write in it
            fwrite($handle, $certificate); //copy the contents of the cert in the temp file
            fclose($handle); //close the file
        }

        //Create payload basic info
        $payload = array();
        $payload['aps'] = array(
            'alert' => array(
                "body"=>$params["msg"],
                "action-loc-key"=>"View"
            ),
            'badge' => $params['badge'],
            'sound' => $params['sound'],
            'link_url' => $params['link_url'],
            'category' => $params['category'],
        );

        if ( isset($params['mutable-content']) ) {
            $payload['aps']['mutable-content'] = 1;
        }

        if ( isset($params['content-available']) ) {
            $payload['aps']['content-available'] = 1;
        }

        //Create payload extra info
        //$payload['extra_info'] is different from what your app is programmed, this extra_info transfer to your IOS App
        $payload['extra_info'] = array(
            'apns_msg' => $params["msg"],
            'category' => $params['category'],
        );
        $push = json_encode($payload);

        //Create stream context for Push Sever.
        $streamContext = stream_context_create();
        stream_context_set_option($streamContext, 'ssl', 'local_cert', $iosApnsCert);
        stream_context_set_option($streamContext, 'ssl', 'passphrase', getenv('PASSPHRASE'));

        $apns = stream_socket_client($ssl_url, $error, $errorString, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $streamContext);
        if (!$apns) {
            $rtn["code"]	= "001";
            $rtn["msg"]		= "Failed to connect ".$error." ".$errorString;
            return $rtn;
        }

        //echo 'error=' . $error;
        $params['token'] = str_replace('%20', '', $params['token']);
        $params['token'] = str_replace(' ', '', $params['token']);
        $apnsMessage = chr(0) . pack('n', 32) . pack('H*', $params['token']) . pack('n', strlen($push)) . $push;

        $writeResult = fwrite($apns, $apnsMessage);
        fclose($apns);

        if ($GLOBALS['environment']=="prod") {
            unlink($iosApnsCert); //delete the temp file
        }

        if (!$writeResult) {
            $rtn["code"] = "002";
            $rtn["msg"]	= "Failed to deliver".$error." ".$errorString.PHP_EOL;
        } else {
            $rtn["code"]	= "000";//means result OK
            $rtn["msg"]		= "Successful Delivery!".PHP_EOL;
        }

        return $rtn;

    }//sendMessageIos()



    /**
     * Send message to SmartPhone
     * $params [pushtype, msg, registration_id]
     */
    //ToDO: Implement with array variable??
    public function sendMessage($params){

        if($params["token"] && $params["msg"]){
            switch($params["device"]){
                case "ios":
                    return $rtn = $this->sendMessageIos($params);
                    break;
                case "android":
                    $this->sendMessageAndroid($params["registration_id"], $params);
                    break;
            }
        } else {
            $rtn["code"]	= "003";
            $rtn["msg"]		= "No device token or message set.".PHP_EOL;
            return $rtn;
        }

    }


    /*
     * Sample For database
     * register phone Id from Phone to Mysql via controllers
     * Look a tableSchema at the bottom
     * @ $params["appType"] : android or ios..
     * @ $params["appId"] : //APA91bGEGu5NSyYDYp5OMO4mZ0j1n2DznGARaNFVcCYfLHvHat..... or 6b1653ad818a89fc6937f5067a9b372aec79edeb9504d6ef....
     **/
    public function registration($params){
        $pushtype       = $params["pushtype"];
        $idphone        = $params["idphone"];

        print_r($params);
        //{insert into database}
        echo json_encode($rtn);
    }


    /**
     * Step 2.
     * Send message to each iphone from web App.
     * @params : Array() : messages ()
     */
    public function send($params){
        //$sql    = "select pushtype, idphone from gcmapns ";
        // $rows    = $CI->db->get_rows($sql);
        //get data from database and save to $rows
        if(is_array($rows)){
            foreach($rows as $key => $val){
                switch($val["pushtype"]){
                    case "ios":
                        $rtn	= $this->sendMessageIos($val["idphone"], $params);
                        break;
                    case "android":
                        $rtn	= $this->sendMessageAndroid($val["idphone"], $params);
                        break;
                }//switch($val["pushtype"]){
            }//foreach($rows as $key => $val){
        }//if(is_array($rows)){


    }//function send(){

}