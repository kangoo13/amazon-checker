<?php
/**
 * Created by PhpStorm.
 * User: aurelienschiltz
 * Date: 18/03/2016
 * Time: 18:28
 */


require "classes/includes.php";
set_time_limit(60);
ini_set('memory_limit', '1000M');
$accounts = file_get_contents("test");

$already = new AlreadyExist();

$accounts = explode(PHP_EOL, $accounts);
foreach ($accounts as $account) {
    $theacc = explode(":", $account);

    try {
        $amazon = new AmazonUS($theacc[0], $theacc[1]);
        if (!$amazon->connect())
            echo 'Non-working account US: ' . $theacc[0] . '<br />';
        else {
            $amazon->parseAccount();
            $amazon->displayAccount();
        }
        unset($amazon);

    } catch (Exception $e) {
        unset($amazon);
        echo 'Already present in US : '.$theacc[0].'<br />';
    }

    try {
        $amazon = new AmazonUK($theacc[0], $theacc[1]);
        if (!$amazon->connect())
            echo 'Non-working account UK: ' . $theacc[0] . '<br />';
        else {
            $amazon->parseAccount();
            $amazon->displayAccount();
        }
        unset($amazon);
    } catch (Exception $e) {
        unset($amazon);
        echo 'Already present in UK : '.$theacc[0].'<br />';
    }
    unset($theacc);
}
