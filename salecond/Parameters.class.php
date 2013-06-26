<?php



/**
 * Клас 'salecond_Parameters' - Търговски параметри
 *
 *
 * @category  bgerp
 * @package   salecond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class salecond_Parameters extends core_Manager
{
    
    
	/**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'salecond_Others';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, salecond_Wrapper';
    
    
    /**
     * Заглавие
     */
    var $title = 'Търговски параметри';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Търговски параметри";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('type', 'enum(double=Число, int=Цяло число, varchar=Текст, color=Цвят, date=Дата,enum=Изброим)', 'caption=Тип');
        $this->FLD('options', 'varchar(128)', 'caption=Стойности');
        $this->FLD('default', 'varchar(64)', 'caption=Дефолт');
        $this->FLD('sysId', 'varchar(32)', 'caption=Sys Id');
        
        $this->setDbUnique('name');
        //$this->setDbUnique("sysId");
    }
    
    
    /**
     * Начални данни за инициализация
     */
    public static function setup()
    {
    	$csvFile = __DIR__ . "/csv/Parameters.csv";
        $created = $updated = 0;
        if(($handle = @fopen($csvFile, "r")) !== FALSE) {
          while (($csvRow = fgetcsv($handle, 2000, ",", '"', '\\')) !== FALSE) {
              $rec = new stdClass();
              $rec->name = $csvRow[0];
              $rec->type = $csvRow[1];
              $rec->sysId = $csvRow[2];
              $rec->default = $csvRow[3];
              if($rec->id = static::fetchField("#sysId = '{$rec->sysId}'", 'id')){
              	$updated++;
           } else {
                $created++;
           }
         
           static::save($rec);
		}
            
        fclose($handle);
           $res .= "<li style='color:green;'>Създадени са записи за {$created} търговски параметри. Обновени {$updated}</li>";
        } else {
           $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
    
    
	/**
     * Връща стойността на дадено търговско условие за клиента
     * @param int $id - ид на контрагента
     * @param string $conditionSysId - sysId на параметър (@see salecond_Others)
     * @return string $value - стойността на параметъра
     * Намира се в следния ред:
     * 	  1. Директен запис в salecond_ConditionsToCustomers
     * 	  2. Дефолт метод "get{$conditionSysId}" дефиниран в модела
     *    3. Супер дефолта на параметъра дефиниран в salecond_Others
     *    4. NULL ако нищо не е намерено
     */
    public static function getParameter($cClass, $cId, $conditionSysId, $mvc = NULL)
    {
    	expect($Class = cls::get($cClass));
    	expect($Class::fetch($cId));
    	expect($condId = static::fetchField("#sysId = '{$conditionSysId}'", 'id'));
    	
    	if($mvc){
    		if(is_string($mvc)){
    			expect($mvc = cls::get($mvc));
    		}
    	}
    	
    	//Връщаме стойността ако има директен запис за условието
    	if($value = salecond_ConditionsToCustomers::fetchByCustomer($cClass, $cId, $condId)){
    		return $value;
    	}
    	
    	// Търси се метод дефиниран за връщане на стойността на условието
    	$method = "get{$conditionSysId}";
    	if(method_exists($Class, $method)){
    		return $Class::$method($cId);
    	}
    	
    	// Връща се супер дефолта на параметъра;
    	$default = static::fetchField($condId, 'default');
    	if(isset($default)) return $default;
    	
    	return NULL;
    }
}