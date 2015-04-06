<?php


/**
 *  Време в което не се логва заявка от същото ip/ресурс
 */
defIfNot('VISLOG_ALLOW_SAME_IP', 5*60);


/**
 * Клас 'vislog_Setup' -
 *
 *
 * @category  vendors
 * @package   vislog
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class vislog_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'vislog_History';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Какво правят не-регистрираните потребители на сайта";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'vislog_HistoryResources',
            'vislog_History',
            'vislog_Referer',
            'vislog_IpNames',
        );
    

    var $configDescription = array(
			'VISLOG_ALLOW_SAME_IP' => array ('time', 'caption=Време за недопускане на запис за едни и същи ip/ресурс->Време'),
        );

         
    /**
     * Роли за достъп до модула
     */
    //var $roles = 'vislog';
    

    /**
     * Дефиниции на класове с интерфейси
     */
    var $classes = 'vislog_IpReports,vislog_IpResources';


    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.53, 'Сайт', 'Лог', 'vislog_History', 'default', "admin, ceo, cms"),
        );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    var $defClasses = "vislog_IpReports,vislog_IpResources";
    
    
	/**
     * Инсталиране на пакета
     */
    function install()
    {
      	$html = parent::install();
        
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Прикачаме плъгина
        $html .= $Plugins->forcePlugin('Декориране на IP', 'vislog_DecoratePlugin', 'type_Ip', 'private');
        
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