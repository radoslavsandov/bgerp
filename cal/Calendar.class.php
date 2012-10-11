<?php



/**
 * Календар - всички събития
 *
 *
 * @category  bgerp
 * @package   cal
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_Calendar extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Календар на събития и празници";
    
    
    /**
     * Класове за автоматично зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, cal_Wrapper, plg_Sorting, plg_State, bgerp_plg_GroupByDate, cal_View';
    

    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    var $groupByDateField = 'time';
    

    /**
     * Полетата, които ще видим в таблицата
     */
    var $listFields = 'time,event=Събитие';
    
    // var $listFields = 'date,event=Събитие,type,url';
    
    
    /**
     *  @todo Чака за документация...
     */
    // var $searchFields = '';
    
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Кой може да чете
     */
    var $canRead = 'user,cal,admin';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Уникален ключ за събитието
        $this->FLD('key', 'varchar(32)', 'caption=Ключ');

        // Дата на събититието
        $this->FLD('time', new type_Datetime(array('cellAttr' => 'class="portal-date"', 'format' => 'smartTime')), 'caption=Време');
        
        // Продължителност на събитието
        $this->FLD('duration', 'time', 'caption=Продължителност');

        // Тип на събититето. Той определя и иконата на събититето
        $this->FLD('type', 'varchar(32)', 'caption=Тип');
        
        // За кои потребители се отнася събитието. Празно => за всички
        $this->FLD('users', 'keylist(mvc=core_Users,title=nick)', 'caption=Потребители');

        // Заглавие на събитието
        $this->FLD('title', 'varchar', 'caption=Заглавие');

        // Приоритет 1=Нисък, 2=Нормале, 3=Висок, 4=Критичен, 0=Никакъв (приключена задача)
        $this->FLD('priority', 'int', 'caption=Приоритет,notNull,value=1');

        // Локално URL към обект, даващ повече информация за събитието
        $this->FLD('url',  'varchar', 'caption=Url,column=none');
        
        // Дали събитието се отнася за целия ден
        $this->FLD('allDay', 'enum(yes=Да,no=Не)', 'caption=Цял ден?');
        
        // Индекси
         $this->setDbUnique('key');
    }


    /**
     * Обновява събитията в календара
     *
     * @param $events   array   Масив със събития
     * @param $fromDate date    Начало на периода за който се отнасят събитията
     * @param $fromDate date    Край на периода за който се отнасят събитията
     * @param $prefix   string  Префикс на ключовете за събитията от този източник
     * 
     * @return $status array Статус на операцията, който съдържа:
     *      о ['updated'] броя на обновените събития
     * 
     */
    static function updateEvents($events, $fromDate, $toDate, $prefix)
    {
        $query    = self::getQuery();
        $fromTime = $fromDate . ' 00:00:00';
        $toTime   = $toDate   . ' 23:59:59';

        $query->where("#time >= '{$fromTime}' AND #time <= '{$toTime}' AND #key LIKE '{$prefix}%'");
        
        // Извличаме съществуващите събития за този префикс
        $exEvents = array();
        while($rec = $query->fetch()) {
            $exEvents[$rec->key] = $rec;
        }
 
        // Инициализираме резултатния масив
        $res = array(
            'new' => 0,
            'updated' => 0,
            'deleted' => 0
            );

        // Обновяваме информацията за новопостъпилите събития
        if(count($events)) {
            foreach($events as $e) {
                if(($e->id = $exEvents[$e->key]->id) ||
                   ($e->id = self::fetchField("#key = '{$e->key}'", 'id')) ) {
                    unset($exEvents[$e->key]);
                    $res['updated']++;
                } else {
                    $res['new']++;
                }

                self::save($e);
            }
        }

        // Изтриваме старите записи, които не са обновени
        foreach($exEvents as $e) {
            self::delete("#key = '{$e->key}'");
            $res['deleted']++;
        }
        
        return $res;
    }
        
    
    /**
     * Прилага филтъра, така че да се показват записите след посочената дата
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	$currentId = core_Users::getCurrent();
    	
        $data->query->orderBy("#time=ASC,#priority=DESC");
        
        if($from = $data->listFilter->rec->from) {
        	
            $data->query->where("#time >= date('$from')");
            $data->query->where("#users = '' OR #users LIKE '|{$currentID}|'");
        }
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('from', 'date', 'caption=От,input,silent');
        $data->listFilter->setdefault('from', date('Y-m-d'));
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'from';
        
        $data->listFilter->input('from', 'silent');
    }
    
    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    static function recToVerbal(&$rec)
    {
    	
    	$row = parent::recToVerbal_($rec);

    	$lowerType = strtolower($rec->type);
        $url = getRetUrl($rec->url);
        $attr['class'] = 'linkWithIcon';
        $attr['style'] = 'background-image:url(' . sbf("img/16/{$lowerType}.png") . ');';
        if($rec->priority <= 0) {
            $attr['style'] .= 'color:#aaa;text-decoration:line-through;';
        }
        $row->event = ht::createLink($row->title, $url, NULL, $attr);
     
        $today     = date('Y-m-d');
        $tommorow  = date('Y-m-d', time() + 24 * 60 * 60);
        $dayAT = date('Y-m-d', time() + 48 * 60 * 60);
        $yesterday = date('Y-m-d', time() - 24 * 60 * 60);
      
        list($rec->date,) = explode(' ', $rec->time);

        $row->date = dt::mysql2verbal($rec->time, 'd-m-Y');        

        if($rec->date == $today) {
            $row->ROW_ATTR['style'] .= 'background-color:#ffc;';
        } elseif($rec->date == $tommorow) {
            $row->ROW_ATTR['style'] .= 'background-color:#efc;';
        } elseif($rec->date == $dayAT) {
            $row->ROW_ATTR['style'] .= 'background-color:#dfc;';
        } elseif($rec->date == $yesterday) {
            $row->ROW_ATTR['style'] .= 'background-color:#eee;';
        } elseif($rec->date > $today) {
            $row->ROW_ATTR['style'] .= 'background-color:#cfc;';
        } elseif($rec->date < $yesterday) {
            $row->ROW_ATTR['style'] .= 'background-color:#ddd;';
        }

        
        return $row;
    }


    /**
     * Рендира календар за посочения месец
     *
     * @param int  $year Година
     * @param int  $month Месец
     * @param array $data  Масив с данни за дните в месеца
     *     о  $data[...]->isHoliday - дали е празник
     *     о  $data[...]->url - URL, където трябва да сочи посочения ден
     *     о  $data[...]->html - съдържание на клетката, осен датата
     * @param string $header - заглавие на календара
     *
     * @return string
     */
    static function renderCalendar($year, $month, $data = array(), $header = NULL)
    {   
        // Таймстамп на първия ден на месеца
        $firstDayTms = mktime(0, 0, 0, $month, 1, $year);

        // Броя на дните в месеца (= на последната дата в месеца);
        $lastDay = date('t', $firstDayTms);
        
        // Днес
        $today = date('j-n-Y');

        for($i = 1; $i <= $lastDay; $i++) {
            $t = mktime(0, 0, 0, $month, $i, $year);
            $monthArr[date('W', $t)][date('N', $t)] = $i;
        }

        $html = "<table class='mc-calendar'>";        

        $html .= "<tr><td colspan='8' style='padding:0px;'>{$header}</td><tr>";

        // Добавяне на втория хедър
        $html .= "<tr><td>" . tr('Сд') . "</td>";
        foreach(dt::$weekDays as $wdName) {
            $wdName = tr($wdName);
            $html .= "<td class='mc-wd-name'>{$wdName}</td>";
        }
        $html .= '<tr>';

        foreach($monthArr as $weekNum => $weekArr) {
            $html .= "<tr>";
            $html .= "<td class='mc-week-nb'>$weekNum</td>";
            for($wd = 1; $wd <= 7; $wd++) {
                if($d = $weekArr[$wd]) { 
                    if($data[$d]->type == 'holiday') {  
                        $class = 'mc-holiday';
                    } elseif(($wd == 6 || ($data[$d]->type == 'non-working' && $wd >= 4) ) && ($data[$d]->type != 'workday')) {
                        $class = 'mc-saturday';
                    } elseif(($wd == 7 || ($data[$d]->type == 'non-working' && $wd < 4) ) && ($data[$d]->type != 'workday')) {
                        $class = 'mc-sunday';
                    } else {
                        $class = '';
                    }

                    if($today == "{$d}-{$month}-{$year}") {
                        $class .= ' mc-today';
                    }
                    
                    // URL към което сочи деня
                    $url = $data[$d]->url;

                    // Съдържание на клетката, освен датата
                    $content = $data[$d]->html;

                    $html .= "<td class='{$class} mc-day' onclick='document.location=\"{$url}\"'>{$content}$d</td>";
                } else {
                    $html .= "<td class='mc-empty'>&nbsp;</td>";
                }
            }
            $html .= "</tr>";
        }

        $html .= "</table>";
        
        return $html;
    }




    /**
     * Рендира блока за портала на текущия потребител
     */
    static function renderPortal()
    {
        $month = Request::get('cal_month', 'int');
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        $year  = Request::get('cal_year', 'int');

        if(!$month || $month < 1 || $month > 12 || !$year || $year < 1970 || $year > 2038) {
            $year = date('Y');
            $month = date('n');
        }

        // Добавяне на първия хедър
        $currentMonth = tr(dt::$months[$month-1]) . " " . $year;

        $pm = $month-1;
        if($pm == 0) {
            $pm = 12;
            $py = $year-1;
        } else {
            $py = $year;
        }
        $prevMonth = tr(dt::$months[$pm-1]) . " " .$py;

        $nm = $month+1;
        if($nm == 13) {
            $nm = 1;
            $ny = $year+1;
        } else {
            $ny = $year;
        }
        $nextMonth = tr(dt::$months[$nm-1]) . " " .$ny;
        
        $link = $_SERVER['REQUEST_URI'];
        $nextLink = Url::addParams($link, array('cal_month' => $nm, 'cal_year' => $ny));
        $prevtLink = Url::addParams($link, array('cal_month' => $pm, 'cal_year' => $py));

        $header = "<table class='mc-header' width='100%' cellpadding='0'>
                <tr>
                    <td align='left'><a href='{$prevtLink}'>{$prevMonth}</a></td>
                    <td align='center'><b>{$currentMonth}</b></td>
                    <td align='right'><a href='{$nextLink}'>{$nextMonth}</a></td>
                </tr>
            </table>";
        
        
        // Съдържание на клетките на календара 
	       
        //От началото на месеца
        $from = "{$year}-{$month}-01 00:00:00";
        
        // До последния ден за месеца
        $lastDay = date('d', mktime(12, 59, 59, $month + 1, 0, $year));
        $to = "{$year}-{$month}-{$lastDay} 23:59:59";
       
        // Подготвяме заглавието на таблицата
        //$state->title = tr("Календар");

        $state = new stdClass();
        $state->query = self::getQuery();
        $state->query->where("#time >= '{$from}' AND #time <= '{$to}'");

        $Calendar = cls::get('cal_Calendar');
        $Calendar->prepareListFields($state);
        $Calendar->prepareListRecs($state);
        $Calendar->prepareListRows($state);
        
        // Подготвяме лентата с инструменти
        $Calendar->prepareListToolbar($state);

        if (is_array($state->recs)) {
            foreach($state->recs as $id => $rec) {
                if($rec->type == 'holiday' || $rec->type == 'non-working' || $rec->type == 'workday') {
                    $time = dt::mysql2timestamp($rec->time);
                    $i = (int) date('j', $time);
                    if(!isset($data[$i])) {
                        $data[$i] = new stdClass();
                    }
                    $data[$i]->type = $rec->type;
                } elseif($rec->type == 'workday') {
                }
                
            }    
        }
        
        for($i = 1; $i <= 31; $i++) {
            if(!isset($data[$i])) {
                $data[$i] = new stdClass();
            }
            $data[$i]->url = toUrl(array('cal_Calendar', 'list', 'from' => "{$i}-{$month}-{$year}"));;
        }

        $tpl = new ET("[#MONTH_CALENDAR#] <br> [#AGENDA#]");

        $tpl->replace(static::renderCalendar($year, $month, $data, $header), 'MONTH_CALENDAR');


        // Съдържание на списъка със събития

        // От вчера 
        $previousDayTms = mktime(0, 0, 0, date('m'), date('j')-1, date('Y'));
        $from = dt::timestamp2mysql($previousDayTms);

        // До вдругиден
        $afterTwoDays = mktime(0, 0, -1, date('m'), date('j')+3, date('Y'));
        $to = dt::timestamp2mysql($afterTwoDays);
       
        $state = new stdClass();
        $state->query = self::getQuery();
        $state->query->where("#time >= '{$from}' AND #time <= '{$to}'");

        $Calendar->prepareListFields($state);
        $Calendar->prepareListRecs($state);
        $Calendar->prepareListRows($state);

        $tpl->replace($Calendar->renderListTable($state), 'AGENDA');

        return $tpl;
    }

    
    /**
     * Функция извеждаща броя на работните, неработните и празничните дни в един месец
     */
    function calculateDays($month, $year)
    {
    
    	// Ако е въведен несъществуващ месец или година, взима текущите данни
        if(!$month || $month < 1 || $month > 12 || !$year || $year < 1970 || $year > 2038) {
            $year = date('Y');
            $month = date('n');
            
        }
        
        // Таймстамп на първия ден в месеца
        $timestamp = strtotime("$year-$month-01");
        
        // Броя на дните в месеца (= на последната дата в месеца);
        $lastDay = date('t', $timestamp);
    
       for($i = 1; $i <= $lastDay; $i++) {
            $t = mktime(0, 0, 0, $month, $i, $year);
            $monthArr[date('W', $t)][date('N', $t)] = $i;
            
        }
        
        // Начална дата
        $from = "{$year}-{$month}-01 00:00:00";
        
        // Крайна дата
        $to = "{$year}-{$month}-{$lastDay} 00:00:00";

        $monthEvent = array();
      
    	$query = self::getQuery();

    	$holiday = $nonWorking = $workday = 0;
    	
        while($rec = $query->fetch("#time >= '{$from}' AND #time <= '{$to}'")) {
            
	        if($rec->type == "holiday"){
	        		$holiday++;
	        	} elseif ($rec->type == "non-working"){
	        		$nonWorking++;
	        	} elseif($rec->type == "workday"){
	        		$workday++;
	        		
	        	}
	    }
	  
               $satSun = 0;
               
        	   foreach ($monthArr as $dayWeek){
		        	foreach($dayWeek as $k=>$day){
		        		if($k == 6 || $k == 7){
		        			$satSun++;
		        		}
		        	}
               }
      
        $allHolidays = $satSun - $workday + $nonWorking + $holiday;
        $allWoking = $lastDay - $allHolidays;
           
        $statusArr = array();
        $statusArr['working'] = $allWoking;
        $statusArr['nonWorking'] = $allHolidays;
        $statusArr['holiday'] = $holiday;
        
        return $statusArr;
           
    }

    function act_Test()
    {
    	$m = 9;
    	$y = 2012;
    
    	$days = self::calculateDays($m, $y);
    	
    	expect ($days['working'] == 18 && $days['nonWorking'] == 12 && $days['holiday'] == 2, 'Greshka');
    }
    
    /**
     * Функция показваща събитията за даден ден
     */
    function act_Day()
    {
    	
    	$from = Request::get('from');
    	$currentDate = dt::mysql2Verbal($from, 'd F Y, l');
    	
    	// Масив с часовете, който искаме да показваме
    	$hour = array(  "00:00" => " ",
				    	"01:00" => " ",
				    	"02:00" => " ",
				    	"03:00" => " ",
				    	"04:00" => " ",
				    	"05:00" => " ",
				    	"06:00" => " ",
				    	"07:00" => " ",
				    	"08:00" => " ",
				    	"09:00" => " ",
				    	"10:00" => " ",
				    	"11:00" => " ",
				    	"12:00" => " ",
				    	"13:00" => " ",
				    	"14:00" => " ",
				    	"15:00" => " ",
				    	"16:00" => " ",
				    	"17:00" => " ",
				    	"18:00" => " ",
				    	"19:00" => " ",
				    	"20:00" => " ",
				    	"21:00" => " ",
				    	"22:00" => " ",
				    	"23:00" => " ");
    	
    	// Масив с цветове за събитията
    	$color = array( "Crimson", 
				    	"OrangeRed",
				    	"Gold",
				    	"Olive", 
				    	"SteelBlue",
				    	"Brown", 
				    	"RosyBrown",
				    	"LightPink",
				    	"DarkSeaGreen",
				    	"Aqua",
				    	"DimGray",
				    	"DarkBlue",
				    	"Purple",
				    	"BlueViolet",
				    	"Khaki",
				    	"LightSalmon", 
				    	"Crimson");
    	
    	$state = new stdClass();
        $state->query = cal_Tasks::getQuery();
       	
    	while ($rec =  $state->query->fetch()){
    		 
    		$timeStarts = dt::mysql2verbal($rec->timeStart, 'd-m-Y');
    		
    		// Начален час: минути на събитието 
    		$timeHour = dt::mysql2verbal($rec->timeStart, 'H:i');
    		
    		
    		$taskEnd = ((strstr($timeHour, ":", TRUE) * 3600) + (substr(strstr($timeHour, ":"),1) * 60) + $rec->timeDuration) / 3600;
    		
    	    $taskEndH = floor($taskEnd);
    		$taskEndM =  ($taskEnd - $taskEndH) * 60;
	    		if(substr($taskEndM,1) === FALSE){
	    			$taskEndM = $taskEndM . '0';
	    		}

	        // Краен час: минути на събитието 
    		$taskHour = $taskEndH . ":" . $taskEndM;
    		
    		if(trim($timeStarts) == trim($from)){
	          
    			$hour[$timeHour] = $rec->title;
    			$hour[$taskHour] = "Kрай на задача: ". $rec->title;
    			$hour[] = ksort($hour);
    			$tasks[] = $rec;
    			$event[$timeHour] = $taskHour;
    			
    		}
    	}
    	unset ($hour[0]);
    	unset ($hour[1]);
    	unset ($hour[2]);
    	unset ($hour[3]);
    	unset ($hour[4]);
    	
    	$tpl = new ET(getFileContent('cal/tpl/SingleLayoutDays.shtml'));
    	
    	$Calendar = cls::get('cal_Calendar');
    	$Calendar->prepareListFilter($state);
        
        $tpl->replace($Calendar->renderListFilter($state), 'from');
    	
    	$tpl->replace('Събития за изпълнение', 'title');
    	$tpl->replace($currentDate, 'date');
    	
    	
    	foreach($hour as $h => $t){
    		
    		
    		if($t == " " || strpos($t, "K") === 0){
    	
    		
    	   /* if($t !== " "){
    			    		
    		        $cTpl->replace($colors, 'color');
    		        
    		      
    	    }*/
    		
    	
    	
    		        $cTpl = $tpl->getBlock("COMMENT_LI");
    		       
    		        $cTpl->replace($t, 'tasktitle');
		    		$cTpl->replace($h, 'time');
				    $cTpl->append2master();
    		 
    		}
    		if(is_array($tasks)){
    		
	         	foreach($tasks as $task){
	        //bp($task);
	            //$colors = array_pop($color);
		         	if(dt::mysql2verbal($task->timeStart, 'H:i') == $h){
		         	
		         	$url = toUrl(array('cal_Tasks', 'single', $task->id), 'relative');
		
				    	$cTpl = $tpl->getBlock("COMMENT_LI");
						
				    	if($task->allDay == 'no'){
				    		$cTpl->replace('Няма задачи с продължителност през целия ден', 'allday');
				    	} else {
				    		$cTpl->replace($task->allDay, 'allday');
				    	}
				    	$colors = array_pop($color);
    		            $cTpl->replace($colors, 'color');
    		            $cTpl->replace(ht::createLink($t, $url), 'tasktitle');
				    	$cTpl->replace($h, 'time');
				    	$cTpl->replace($task->description, 'description');
				    	
				    	$cTpl->append2master();
			         	}
	         	}
    		}
    	}
    	
    	return  $this->renderWrapping($tpl);
 
    }


    /**
     *
     */
    function act_Week()
    {
        $res = '1';

        return $this->renderWrapping($res);
    }


    /**
     *
     */
    function act_Month()
    {
        $res = '1';

        return $this->renderWrapping($res);

    }


    /**
     *
     */
    function act_Year()
    {
        $res = '1';

        return $this->renderWrapping($res);
    }

}
