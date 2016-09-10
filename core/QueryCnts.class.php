<?php 


/**
 * Кеш за търсения
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class core_QueryCnts extends core_Manager
{
    /**
     * Константа за записване в кеша
     */
    const CACHE_PREFIX = 'pagerCnt';


    /**
     * Колко време да се кешира информацията за броя на резултатите
     */
    const CACHE_LIFETIME = 4320; // Три дни в минути


    /**
     * Заявки, чакащи за преброяване
     */
    protected $queries = array();
    
     
    /**
     * Отложено на shutdown изчисляване броя на записите в заявката
     */
    public static function delayCount($query)
    {
        $me = cls::get('core_QueryCnts');
        $me->queries[self::getHash($query)] = $query;
    }


    /**
     * Връща кешираната стойност за броя на резултатите в заявката
     */
    public static function getFromChache($query)
    {
        return core_Cache::get(self::CACHE_PREFIX, self::getHash($query));
    }

    
    /**
     * Връща кешираната стойност за броя на резултатите в заявката
     */
    public static function set($query, $cnt)
    {
        $hash = self::getHash($query);

        $res  = core_Cache::set(self::CACHE_PREFIX, $hash, $cnt, self::CACHE_LIFETIME);

        return $res;
    }


    /**
     * Връща хеш за посочената заявка. 
     * Като страничен резултат я оптимизира за преброяване
     */
    private static function getHash($query)
    {
        $query->orderBy = array();
        $query->show('id');
        $hash = $query->getHash(TRUE);
        
        return $hash;
    }


    /**
     * Изпълнява се преди терминиране на процеса, но след изпращане на резултата към клиента
     */
    public function on_Shutdown()
    {
        foreach($this->queries as $hash => $qCnt) {
            $cnt = $qCnt->count();
            core_Cache::set(self::CACHE_PREFIX, $hash, $cnt, self::CACHE_LIFETIME);
        }
    }

    
}