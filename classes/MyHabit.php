<?php
/**
 * Created by PhpStorm.
 * User: aurelienschiltz
 * Date: 23/03/2016
 * Time: 01:46
 */


class MyHabit
{
    private $ch;
    private $mail = "";
    private $pass = "";
    private $referrer = "";
    private $URLConnect = "https://www.myhabit.com/signin/ref=topnav_signin?ie=UTF8&amp;pageFlowType=SITE_ACCESS&amp;redirectProtocol=http";
    private $URLAddress = "https://www.myhabit.com/addressbook/ref=qd_ya_main_menu";
    private $zipcodes = [];
    private $phones = [];

    public function getZipCodes()
    {
        return $this->zipcodes;
    }

    public function getPhones()
    {
        return $this->phones;
    }

    function __construct($mail, $pass)
    {
        $this->mail = $mail;
        $this->pass = $pass;
        if (file_exists("myhabitcookie.txt"))
            unlink('myhabitcookie.txt');
        $this->ch  = curl_init();

        curl_setopt($this->ch, CURLOPT_URL, $this->URLConnect);
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, 'myhabitcookie.txt');
       // curl_setopt($this->ch, CURLOPT_PROXY, Proxy::getInstance()->getProxy());
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, 'myhabitcookie.txt');
       // curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        curl_setopt($this->ch, CURLOPT_USERAGENT, random_user_agent());
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_HEADER, 1);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
    }

    function getSecurityAnswersUK()
    {
         curl_setopt($this->ch, CURLOPT_URL, $this->URLAddress);
        curl_setopt($this->ch, CURLOPT_REFERER, $this->URLConnect);
        $page = curl_exec($this->ch);
        if ($page == false)
            $page = curl_exec($this->ch);
        $html = str_get_html($page);
        preg_match("/fullName(.*?)prefs/", $html, $array);
        if (!isset($array[1]))
            return false;
        $fn = $array[1];
        $array = explode(' ', $fn);

        $array[0] = substr($array[0], 3);
        $array[count($array)-1] = explode('"', $array[count($array)-1]);
        $array[count($array)-1] = $array[count($array)-1][0];
        /* FIRST LOOP TO FIND ZIP CODES ACCORDING TO THE CORRECT NAMES */
        foreach ($array as $nametofind)
        {
            $nametofind = strtolower($nametofind);
            foreach($html->find('.addressBookEntry ') as $people)
            {
                $fullName = $people->find(".fullName");
                if (strpos(strtolower($fullName[0]->plaintext), $nametofind) !== false) {
                    $zip = $people->find('.cityStateZip ');
                    preg_match("/^(GIR ?0AA|[A-PR-UWYZ]([0-9]{1,2}|([A-HK-Y][0-9]([0-9ABEHMNPRV-Y])?)|[0-9][A-HJKPS-UW]) ?[0-9][ABD-HJLNP-UW-Z]{2})$/", $zip[0]->plaintext, $array);
                    if (empty($array))
                        preg_match("/[0-9]{5}/", $zip[0]->plaintext, $array);
                    if (!empty($array) && !in_array($array[0], $this->zipcodes))
                        $this->zipcodes[] = $array[0];
                    unset($array);
                    $phone = $people->find('.phone ');
                    if (!empty($phone) && !in_array($phone[0]->plaintext, $this->phones))
                        $this->phones[] = $phone[0]->plaintext;
                }
                if (count($this->zipcodes) == 3)
                    return;
            }
        }

        /* LAST LOOP FOR OTHER ZIP CODES RANDOMLY */
        foreach($html->find('.addressBookEntry ') as $people)
        {
            $zip = $people->find('.cityStateZip ');
            preg_match("/^(GIR ?0AA|[A-PR-UWYZ]([0-9]{1,2}|([A-HK-Y][0-9]([0-9ABEHMNPRV-Y])?)|[0-9][A-HJKPS-UW]) ?[0-9][ABD-HJLNP-UW-Z]{2})$/", $zip[0]->plaintext, $array);
            if (empty($array))
                preg_match("/[0-9]{5}/", $zip[0]->plaintext, $array);
            if (!empty($array) && !in_array($array[0], $this->zipcodes))
                $this->zipcodes[] = $array[0];
            unset($array);
            $phone = $people->find('.phone ');
            if (!empty($phone) && !in_array($phone[0]->plaintext, $this->phones))
                $this->phones[] = $phone[0]->plaintext;
            if (count($this->zipcodes) == 3)
                return;
        }

    }

    function getSecurityAnswersUS()
    {
        curl_setopt($this->ch, CURLOPT_URL, $this->URLAddress);
        curl_setopt($this->ch, CURLOPT_REFERER, $this->URLConnect);
        $page = curl_exec($this->ch);
        if ($page == false)
            $page = curl_exec($this->ch);
        $html = str_get_html($page);
        preg_match("/fullName(.*?)prefs/", $html, $array);
        if (!isset($array[1]))
            return false;
        $fn = $array[1];
        $array = explode(' ', $fn);

        $array[0] = substr($array[0], 3);
        $array[count($array)-1] = explode('"', $array[count($array)-1]);
        $array[count($array)-1] = $array[count($array)-1][0];
        /* FIRST LOOP TO FIND ZIP CODES ACCORDING TO THE CORRECT NAMES */
        foreach ($array as $nametofind)
        {
            $nametofind = strtolower($nametofind);
            foreach($html->find('.addressBookEntry ') as $people)
            {
                $fullName = $people->find(".fullName");
                if (strpos(strtolower($fullName[0]->plaintext), $nametofind) !== false) {
                    $zip = $people->find('.cityStateZip ');
                    preg_match("/[0-9]{5}/", $zip[0]->plaintext, $array);
                    if (empty($array))
                        preg_match("/^(GIR ?0AA|[A-PR-UWYZ]([0-9]{1,2}|([A-HK-Y][0-9]([0-9ABEHMNPRV-Y])?)|[0-9][A-HJKPS-UW]) ?[0-9][ABD-HJLNP-UW-Z]{2})$/", $zip[0]->plaintext, $array);
                    if (!empty($array) && !in_array($array[0], $this->zipcodes))
                        $this->zipcodes[] = $array[0];
                    unset($array);
                    $phone = $people->find('.phone ');
                    if (!empty($phone) && !in_array($phone[0]->plaintext, $this->phones))
                        $this->phones[] = $phone[0]->plaintext;
                }
                if (count($this->zipcodes) == 3)
                    return;
            }
        }

        /* LAST LOOP FOR OTHER ZIP CODES RANDOMLY */
        foreach($html->find('.addressBookEntry ') as $people)
        {
            $zip = $people->find('.cityStateZip ');
            preg_match("/[0-9]{5}/", $zip[0]->plaintext, $array);
            if (empty($array))
                preg_match("/^(GIR ?0AA|[A-PR-UWYZ]([0-9]{1,2}|([A-HK-Y][0-9]([0-9ABEHMNPRV-Y])?)|[0-9][A-HJKPS-UW]) ?[0-9][ABD-HJLNP-UW-Z]{2})$/", $zip[0]->plaintext, $array);
            if (!empty($array) && !in_array($array[0], $this->zipcodes))
                $this->zipcodes[] = $array[0];
            unset($array);
            $phone = $people->find('.phone ');
            if (!empty($phone) && !in_array($phone[0]->plaintext, $this->phones))
                $this->phones[] = $phone[0]->plaintext;
            if (count($this->zipcodes) == 3)
                return;
        }


    }

    function validatePrefs($page)
    {
        if (!preg_match('/<form id="customerPreferenceForm" name="customerPreferenceForm".*?<\/form>/is', $page, $form)) {
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
        $postFields["defaultDepartment"] = "women";
        $post = '';
        // convert to string, this won't work as an array, form will not accept multipart/form-data, only application/x-www-form-urlencoded
        foreach($postFields as $key => $value) {
            $post .= $key . '=' . urlencode($value) . '&';
        }

        $post = substr($post, 0, -1);
        // set additional curl options using our previous options
        curl_setopt($this->ch, CURLOPT_URL, "http://www.myhabit.com/".$URL2);
        curl_setopt($this->ch, CURLOPT_REFERER, "https://www.myhabit.com/preferences?ie=UTF8&redirectUrl=%2Fhomepage&redirectQuery=SignInRefresh%252FT1%26hash%3D&redirectProtocol=http");
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post); // make request
        $paged = curl_exec($this->ch);
        return $paged;

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
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post); // make request
        $paged = curl_exec($this->ch);
        if ($paged == false)
            $paged = curl_exec($this->ch);
        $this->referrer = $URL2;
        return $paged;
    }

    function connect()
    {


       $page = curl_exec($this->ch);
          //  curl_setopt($this->ch, CURLOPT_PROXY, Proxy::getInstance()->getNextProxy());


        $page = $this->login($page);
        $html = str_get_html($page);
        $isProxyCaptched = $html->find("div#message_warning");
        if (!empty($isProxyCaptched))
        {
            $client = new DeathByCaptcha_SocketClient("kangoo13", "kangoo1313");
            $image = $html->find("div#ap_captcha_img");
            $image = $image[0]->find("img");
            $image = $image[0]->src;
            if ($captcha = $client->decode(str_replace("&amp;","&", $image))) {
                $page = $this->login($page, $captcha['text']);
                // Report the CAPTCHA if solved incorrectly.
                // Make sure the CAPTCHA was in fact incorrectly solved!
                //if ( ... ) {
                //  $client->report($captcha['captcha']);
                //}
            }
           // Proxy::getInstance()->needToChange = true;

        }
        if (preg_match("/OOPS! JAVASCRIPT IS/", $page)) {
            curl_setopt($this->ch, CURLOPT_URL, "https://www.myhabit.com/preferences?ie=UTF8&redirectUrl=%2Fhomepage&redirectQuery=SignInRefresh%252FT1%26hash%3D&redirectProtocol=http");
            curl_setopt($this->ch, CURLOPT_POST, 0);
            $page = curl_exec($this->ch);
            if ($page == false)
                $page = curl_exec($this->ch);
            $this->validatePrefs($page);
        }
        return true;
    }
}