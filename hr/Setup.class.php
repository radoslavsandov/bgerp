<?php

/**
 *  class dma_Setup
 *
 *  Инсталиране/Деинсталиране на
 *  мениджъри свързани с DMA
 *
 */
class hr_Setup
{
    /**
     *  @todo Чака за документация...
     */
    var $version = '0.1';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startCtr = 'hr_EmployeeContracts';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $startAct = 'default';
    

    /**
     * Описание на модула
     */
    var $info = "Човешки ресурси";
    
    /**
     *  Инсталиране на пакета
     */
    function install()
    {
        $managers = array(
            'hr_WorkingCycles',
            'hr_Shifts',
            'hr_Departments',
            'hr_Positions',
            'hr_ContractTypes',
            'hr_EmployeeContracts',
        );
        
        // Роля за power-user на този модул
        $role = 'hr';
        $html = core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }
        
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(2, 'Персонал', 'HR', 'hr_EmployeeContracts', 'default', "{$role}, admin");
        
        return $html;
    }
    
    
    /**
     *  Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);

        return $res;
    }
}