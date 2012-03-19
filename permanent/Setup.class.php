<?php



/**
 * Клас 'permanent_Setup' - Съхранява параметри и показания на обекти
 *
 *
 * @category  all
 * @package   permanent
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class permanent_Setup {
    
    
    /**
     * Версия
     */
    var $version = '0.1';
    
    
    /**
     * Контролер на връзката от менюто core_Packs
     */
    var $startCtr = 'permanent_Data';
    
    
    /**
     * Екшън на връзката от менюто core_Packs
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Перманентни данни за различни обекти";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'permanent_Data'
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        
        return "";
    }
}