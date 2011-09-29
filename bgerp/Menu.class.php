<?php

/**
 * Връзки в основното меню
 */
class bgerp_Menu extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, bgerp_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Елементи на менюто';
    
    // Права
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('row', 'int', 'caption=Ред, mandatory');
        $this->FLD('menu', 'varchar(64)', 'caption=Меню, mandatory');
        $this->FLD('subMenu', 'varchar(64)', 'caption=Под меню, mandatory');
        $this->FLD('ctr', 'varchar(128)', 'caption=Контролер,mandatory');
        $this->FLD('act', 'varchar(128)', 'caption=Екшън');
        $this->FLD('autoHide', 'enum(no=Не,yes=Да)', 'caption=Авто скриване');
        $this->FLD('accessByRoles', 'keylist(mvc=core_Roles,select=role)', 'caption=Роли');
        
        $this->setDbUnique('menu,subMenu');
        $this->setDbUnique('ctr,act');
    }
    
    
    /**
     * Връща обект - меню
     */
    function getMenuObject()
    {
        // Ако няма нито един запис в Менюто, но имаме права за администратор, 
        // и текущия контролер не е core_Packs, редирекваме към core_Packs
        $ctr = Request::get('Ctr');
        
        if((!$this->fetch('1=1')) && ( substr($ctr,0,5) != 'core_') && haveRole('admin')) {
            redirect(array('core_Packs'));
        }
        
        $query = $this->getQuery();
        
        $query->orderBy("#id", "ASC");

        while($rec = $query->fetch()) {
            $rec->menuTr = tr($rec->menu);
            $rec->subMenuTr = tr($rec->subMenu);
            $ctrArr = explode('_', $rec->ctr);
            $rec->pack = $ctrArr[0];
            $rec->act = $rec->act ? $rec->act : 'default';
            $manuObj[$rec->menu . ':' . $rec->subMenu] = $rec;
        }
        
        return $manuObj;
    }
    
    
    /**
     * Намира активния запис
     */
    function getActiveItem($menuObj)
    {
        // Опит за определяне на активното меню от Mode
        $menu = Mode::get('pageMenu');
        $subMenu = Mode::get('pageSubMenu');
        $subMenu = $subMenu ? $subMenu : $menu;
        $key = "{$menu}:{$subMenu}";
        
        if(isset($menuObj[$key])) return $key;
        
        $ctr = Request::get('Ctr');
        
        if ($ctr) {
        	$ctr = cls::getClassName($ctr);
        	$mvc = cls::get($ctr);
        	
        	if ($mvc->menuPage && $menuObj[$mvc->menuPage]) {
        		return $mvc->menuPage;
        	}
        }
        $act = Request::get('Act');
        $act = $act ? $act : 'default';
        $ctrArr = explode('_', $ctr);
        $pack = $ctrArr[0];
        
        $bestW = 0;
        $bestKey = NULL;
        
        if(count($menuObj)) {
            foreach($menuObj as $key => $rec) {
                
                if($rec->ctr == $ctr && $rec->act == $act) return $key;
                
                $w = 1.0 * ($rec->pack == $pack) +
                1.0 * ($rec->ctr == $ctr) +
                max(0.7 * ($rec->act == $act), 0.5 * ($rec->act == 'default' || $rec->act == 'list'));
                
                if($w >= 1) {
                    if($w > $bestW) {
                        $bestKey = $key;
                        $bestW = $w;
                    }
                }
            }
        }
        
        return $bestKey;
    }
    
    
    /**
     * Поставя елементите на менюто в шаблона
     */
    function place($tpl)
    {
        $menuObj = $this->getMenuObject();
        
        $active = $this->getActiveItem($menuObj);
        
        $activeArr = explode(':', $active);
        
        if(count($menuObj)) {
            foreach($menuObj as $key => $rec)
            {
                // state: 3 - active, 2 - normal, 1 - disabled, 0 - hidden
                // $mainMenuItems[$pageMenu] = TRUE; Дали това главно меню вече е показано
                
                // Първоначално задаваме 'нормално' състояние на елемента от менюто
                $rec->state = 2;
                
                if(!haveRole($rec->accessByRoles)) {

                    // Менютата, които се скриват при недостатъчно права, не се обработват
                    if($rec->autoHide == 'yes') continue;
                    
                    $rec->state = 1; //disabled
                }
                
                // Определяме дали състоянието на елемента от менюто не е 'активно'
                if(($activeArr[0] == $rec->menu) && ($activeArr[1] == $rec->subMenu)) {
                    $rec->state = 3;
                }
                
                // Дали да влезе в списъка с под-менюта?
                if($activeArr[0] == $rec->menu) {
                    $subMenus[$rec->subMenu] = $rec;
                }
                
                // Дали да влезе в списъка с менюта?
                if((!isset($menus[$rec->menu])) || $menus[$rec->menu]->state < $rec->state) {
                    $menus[$rec->menu] = $rec;
                }
            }
        }
        
        // До тук имаме определени два списъка $menus (с главните менюта) и $subMenus (с под-менютата);
        // Ако имаме само едно подчинено меню, то името на подчиненото меню, става име на главното и подчиненото изчезва
        if(count($subMenus) == 1) {
            //   $rec = current($subMenus);
            //   $menus[$rec->menu]->menuTr = $rec->subMenuTr;
            $subMenus = array();
        }
        
        if( Mode::is('screenMode', 'narrow') ) {
            
            $menuLink = ht::createLink(EF_APP_TITLE, array($this, 'Show'));
            
            $tpl->append($menuLink , "MENU_ROW");
            
            if(count($menus)) {
                foreach($menus as $key => $rec) {
                    if($rec->state == 3) {
                        $tpl->append( "&nbsp;»&nbsp;", "MENU_ROW");
                        $link = ht::createLink($rec->menuTr, array($rec->ctr, $rec->act));
                        $tpl->append($link, "MENU_ROW");
                    }
                }
            }
            
            if(count($subMenus)) {
                foreach($subMenus as $key => $rec) {
                    $link = $this->createLink($rec->subMenuTr, $rec);
                    $tpl->append($link, 'SUB_MENU');
                    $tpl->append("&nbsp;", 'SUB_MENU');
                }
            }
        } else {
            // Ако сме в широк формат
            // Отпечатваме менютата
            if(count($menus)) {
                foreach($menus as $key => $rec) {
                    $link = $this->createLink($rec->menuTr, $rec);
                    $row = 'MENU_ROW' . $rec->row;
                    
                    if($notFirstInFor[$rec->row]) {
                        $tpl->append( "\n . ", $row);
                    } else {
                        $tpl->append( "\n»&nbsp;", $row);
                    }
                    
                    $tpl->append($link, 'MENU_ROW' . $rec->row);
                    
                    $notFirstInFor[$rec->row] = TRUE;
                }
            }
            
            if(count($subMenus)) {
                foreach($subMenus as $key => $rec) {
                    $link = $this->createLink($rec->subMenuTr, $rec);
                    $tpl->append("&nbsp;", 'SUB_MENU');
                    $tpl->append($link, 'SUB_MENU');
                }
            }
        }
    }
    
    
    /**
     * Създава връзка отговаряща на състоянието на посочение ред
     */
    function createLink($title, $rec)
    {
        if($rec->state == 3) {
            $attr['class'] = 'menuItem selected';
            $url = array($rec->ctr, $rec->act);
        } elseif ($rec->state == 2) {
            $attr['class'] = 'menuItem';
            $url = array($rec->ctr, $rec->act);
        } else {
            $attr['class'] = 'menuItem';
        }
        
        return ht::createLink($title, $url, '', $attr);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function act_Show()
    {
        requireRole('user');
        
        if(!Mode::is('screenMode', 'narrow')) redirect(array('bgerp_Portal', 'Show'));
        
        $tpl = new ET(
        "<div class='menuPage'>
                        <div class=\"menuRow\" style='float:left;width:140px;'>[#MENU_ROW1#] </div>
                        <div class=\"menuRow\" style='float:left;width:140px;'>[#MENU_ROW2#] </div>
                        <div class=\"menuRow\" style='float:left;width:140px;'>[#MENU_ROW3#] </div>
                        <div style='clear:both;'></div>
                    </div>
                ");
        
        $menuObj = $this->getMenuObject();
        
        foreach($menuObj as $key => $rec)
        {
            if(!isset($menu[$rec->menu])) {
                $menu[$rec->menu] = $rec;
            }
        }
        
        foreach($menu as $rec) {
            $link = ht::createLink($rec->menuTr, array($rec->ctr, $rec->act));
            $row = 'MENU_ROW' . $rec->row;
            
            $tpl->append($link, $row);
        }
        
        return $tpl;
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function addItem($row, $menu, $subMenu, $ctr, $act, $accessByRoles = 'user', $autoHide = 'no')
    {
        $rec->row = $row;
        $rec->menu = $menu;
        $rec->subMenu = $subMenu;
        $rec->ctr = $ctr;
        $rec->act = $act;
        $rec->autoHide = $autoHide;
        $rec->createdBy = -1; // По този начин, системният потребител е автор на менюто
        
        $Roles = cls::get('core_Roles');
        $rec->accessByRoles = $Roles->keylistFromVerbal($accessByRoles);
        
        // expect( (count(explode('|', $rec->accessByRoles)) - 2) == count(explode(',', $accessByRoles)));
        
        $id = $this->save($rec, NULL, 'IGNORE');
        
        if($id) {
            return "<li style='color:green;'> Добавен е елемент на менюто: {$rec->menu} -> {$rec->subMenu}</li>";
        }
    }
    
    
    /**
     * Добавя бутон за премахване на всички записи, видим само в режим Debug
     */
    function on_AfterPrepareListToolbar($mvc, $data)
    {
        if(isDebug()) {
            $data->toolbar->addBtn('Изпразване', array($this, 'DeleteAll'), array(
                'class' => 'btn-delete',
                'warning' => 'Наистина ли желаете да премахнете всички записи?'));
        }
    }
    
    
    /**
     * Изтрива всички записи от менюто
     */
    function act_DeleteAll()
    {
        if(haveRole('admin')) {
            
            $cnt = $this->delete('1=1');
            
            return new Redirect(array($this), "Бяха изтрити {$cnt} записа");
        }
    }

    
    /**
     * Изтриване на елементите на менюто, които са поставени от системния потребител
     */
    function on_AfterSetupMvc($mvc, $res)
    {
        $cnt = $mvc->delete('#createdBy = -1');

        $res .= "<li style='color:green;'>Бяха изтрити {$cnt} записа от менюто на системата";
    }

}