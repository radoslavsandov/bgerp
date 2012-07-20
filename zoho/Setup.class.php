<?php


/**
 * Инсталаотор на плъгин за добавяне на бутона за преглед на документи в zoho.com
 * Разширения: pps,odt,ods,odp,sxw,sxc,sxi,wpd,rtf,csv,tsv
 *
 *
 * @category  vendors
 * @package   zoho
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class zoho_Setup extends core_Manager {
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = '';
    
    
    /**
     * Описание на модула
     */
    var $info = "Преглед на документи с zoho.com";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме
        $Plugins->forcePlugin('Преглед на документи с Zoho', 'zoho_Plugin', 'fileman_Files', 'private');
        $html .= "<li>Закачане на zoho_Plugin към fileman_Files (Активно)";
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Премахваме от type_Keylist полета
        $Plugins->deinstallPlugin('zoho_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'zoho_Plugin'";
        
        return $html;
    }
}