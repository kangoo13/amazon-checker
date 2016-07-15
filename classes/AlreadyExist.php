<?php

/**
 * Created by PhpStorm.
 * User: aurelienschiltz
 * Date: 23/03/2016
 * Time: 02:19
 */

class AlreadyExist {
    private $accUS = array();
    private $accUK = array();
    /**
     * @var Proxy
     * @access private
     * @static
     */
    private static $_instance = null;

    /**
     * Constructeur de la classe
     *
     * @param void
     * @return void
     */
    public function __construct() {
        $alreadyhave = file_get_contents("./txt/saved");
        $aheof = explode(PHP_EOL, $alreadyhave);
        $acc = array();
        foreach ($aheof as $account)
        {
            $ah = explode("||", $account);
            if (isset($ah[1])) {
                $ah = explode(":", $ah[1]);
                $acc[] = $ah[0];
            }
        }
        $this->accUS = $acc;
        $alreadyhavea = file_get_contents("./txt/savedUK");
        $aheofa = explode(PHP_EOL, $alreadyhavea);
        $acca = array();
        foreach ($aheofa as $accounta)
        {
            $aha = explode("||", $accounta);
            if (isset($aha[1])) {
                $aha = explode(":", $aha[1]);
                $acca[] = $aha[0];
            }
        }
        $this->accUK = $acca;

    }

    public function findAccUk($acc)
    {
        return in_array($acc, $this->accUK);
    }

    public function findAccUs($acc)
    {
        return in_array($acc, $this->accUS);
    }

    /**
     * Méthode qui crée l'unique instance de la classe
     * si elle n'existe pas encore puis la retourne.
     *
     * @param void
     * @return Proxy
     */
    public static function getInstance() {

        if(is_null(self::$_instance)) {
            self::$_instance = new AlreadyExist();
        }

        return self::$_instance;
    }
}
