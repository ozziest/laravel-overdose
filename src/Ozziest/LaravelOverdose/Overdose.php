<?php namespace Ozziest\LaravelOverdose;

use Closure, Session, Exception;

class Overdose  {

    /**
     * Key 
     *
     * @var object
     */
    private $key;

    /**
     * Allowed second 
     *
     * @var int
     */
    private $acceptable = 1;

    /** 
     * Safe second
     *
     * @var int
     */
    private $safe = 10;

    /**
     * Allowed overdose count 
     *
     * @var int
     */
    private $max = 25;

    /**
     * Recreation time 
     *
     * @var int
     */
    private $recreation = 60;

    /**
     * Overdose constructer
     *
     * This method is setting keys of values for request numbers.
     *
     * @return null
     */
    public function __construct()
    {
        $this->key = (object) [
                'request'    => "ozziest_oversode_request",
                'overdose'   => "ozziest_oversode_overdose",
                'recreation' => "ozziest_oversode_recreation",
                'critical'   => "ozziest_oversode_critical"
            ];
    }
    /**
     * Run the request filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure                  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $this->recreation();
            list($requestCount, $overdose) = $this->getDatas();
            $before = time() - $requestCount;
            if ($this->increase($before, $overdose)) {
                $this->decrease($before, $overdose);
            }
            Session::put($this->key->request, time());
            $this->overdose($overdose);
            return $next($request);
        } catch (Exception $e) {
            return redirect('overdose', ['remain' => $e->getMessage()]);
        }
    }

/**
     * Getting remain time 
     *
     * @return integer
     */
    public function getRemainTime()
    {
        $recreation = Session::get($this->key->recreation);
        return $recreation - time();
    }
    /**
     * Recreation
     *
     * @return true;
     */
    private function recreation()
    {
        $recreation = Session::get($this->key->recreation);
        if ($recreation > time()) {
            $remain = $this->getRemainTime();
            throw new Exception($remain);
        }
    }
    /**
     * Checking overdose 
     *
     * @param  integer      $count
     * @return null
     */
    private function overdose($count)
    {
        if ($count >= $this->max) {
            // Kaç kez overdose olunmuş öğreniliyor.
            $critical = $this->getOrSet($this->key->critical, 0);
            // Overdose sayısı arttırılıyor.
            $critical++;
            $allowedTime = time() + (($critical * $critical) * $this->recreation);
            Session::put($this->key->recreation, $allowedTime);
            Session::put($this->key->overdose, 0);
            Session::put($this->key->critical, $critical);
            $remain = $this->getRemainTime();            
            throw new Exception($remain);
        }
    }
    /**
     * Overdose is decreasing
     *
     * @param  integer      $before 
     * @param  pointer      $overdose 
     */
    private function decrease($before, &$overdose)
    {
        if ($before > $this->safe) {
            $overdose--;
            Session::put($this->key->overdose, $overdose);
        }
    }
    /**
     * Overdose is increasing
     *
     * @param  integer      $before 
     * @param  pointer      $overdose 
     */
    private function increase($before, &$overdose)
    {
        if ($before < $this->acceptable) {
            $overdose++;
            Session::put($this->key->overdose, $overdose);
            return false;
        }
        return true;
    }
    /**
     * Getting datas 
     *
     * @return array
     */
    private function getDatas()
    {
        return [
                $this->getOrSet($this->key->request, time()),
                $this->getOrSet($this->key->overdose, 0)
            ];
    }
    /**
     * Getting or setting session data
     *
     * @param  string       $key 
     * @param  string       $default
     * @return string
     */
    private function getOrSet($key, $default)
    {
        $value = Session::get($key);
        if ($value === null) {
            $value = $default;
            Session::put($key, $value);
        }
        return $value;
    }

}
