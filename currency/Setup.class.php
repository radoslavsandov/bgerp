<?php

/**
 * Задаване на основна валута
 */
defIfNot('BGERP_BASE_CURRENCY', 'BGN');



/**
 * class currency_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъра Currency
 *
 *
 * @category  bgerp
 * @package   currency
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class currency_Setup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'currency_Currencies';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Валути и техните курсове";
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
            
            //Задаване на основна валута
            'BGERP_BASE_CURRENCY' => array ('varchar', 'mandatory'),
         
        );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'currency_Currencies',
            'currency_CurrencyGroups',
            'currency_CurrencyRates',
            'currency_FinIndexes'
        );
        
        // Роля за power-user на този модул
        $role = 'currency';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(2, 'Финанси', 'Валути', 'currency_Currencies', 'default', "{$role}, admin");
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}