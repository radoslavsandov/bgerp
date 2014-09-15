<?php



/**
 * Клас 'core_Sbf'
 *
 *
 * @category  ef
 * @package   core
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Sbf extends core_Mvc
{

    static function convertUrlToPath($url)
    {
        list($first, $last) = explode('/' . EF_SBF . '/' . EF_APP_NAME . '/', $url);
        $path = EF_SBF_PATH . '/' . $last;
        
        return $path;
    }


    /**
     * Записва посоченото съдържание на указания път
     * Връща FALSE при грешка или пълния път до новозаписания файл
     */
    static function saveFile_($content, $path, $isFullPath = FALSE)
    { 
        if(!$isFullPath) {
            $path = EF_SBF_PATH . '/' . $path;
        }
        
        // Ако директорията не съществува
        if(!is_dir($dir = dirname($path))) {
                    
            // Създаваме директория
            if(!@mkdir($dir, 0777, TRUE)) {

                return FALSE;
            }
        }

        if(@file_put_contents($path, $content) !== FALSE) {

            return $path;
        }

        return FALSE;
    }


    /**
     * Връща съответстващия път в sbf на зададен вътрешен път
     */
    static function getSbfFilePath_($path)
    {  
        $pathArr = pathinfo($path);

        $timeSuffix = '';

        if($file = getFullPath($path)) {
            $time =  filemtime($file);
            $timeSuffix = "_" . date("mdHis", $time);
        } 
        
        // Новото име на файла, зависещо от времето на последната му модификация
        $sbfPath = EF_SBF_PATH . "/" . $pathArr['dirname'] . '/' . $pathArr['filename'] . $timeSuffix . '.' . $pathArr['extension'];

        return $sbfPath;
    }


    /**
     * Връща URL на Browser Resource File, по подразбиране, оградено с кавички
     *
     * @param string $rPath Релативен път до статичния файл
     * @param string $qt    Символ за ограждане на резултата
     * @param boolean $absolute Дали резултатното URL да е абсолютно или релативно
     *
     * @return string
     */
    public static function getUrl($rPath, $qt = '"', $absolute = FALSE)
    {
        // Ако файла съществува
        if (($sbfPath = self::getSbfFilePath($rPath)) && $rPath{0} != '_') {
            
            // Ако файла не съществува в SBF
            if(!file_exists($sbfPath)) {
                
                $content = getFileContent($rPath);

                if(core_Sbf::saveFile($content, $sbfPath, TRUE)) {
                    
                    // Записваме в лога, всеки път след като създадам файл в sbf
                    core_Logs::add(get_called_class(), NULL, "Генериране на файл в 'sbf' за '{$rPath}'", 5);
                    
                    // Пътя до файла
                    $sbfArr = pathinfo($sbfPath);
                    $rArr = pathinfo($rPath);
                    $rPath = $rArr['dirname'] . '/'. $sbfArr['basename'];
                 } else {
                    
                     // Записваме в лога
                    core_Logs::add(get_called_class(), NULL, "Файла не може да се запише в '{$sbfPath}'.");
                }   

            } 
                
        }
        
        $res = $qt . core_App::getBoot($absolute) . '/' . EF_SBF . '/' . EF_APP_NAME . '/' . $rPath . $qt;
         
        return $res;
    }


    /**
     * Функция, която проверява и ако се изисква, сервира
     * браузърно съдържание html, css, img ...
     *
     * @param string $name
     */
    public static function serveStaticFile($name)
    {
        $file = getFullPath($name);
 
        // Грешка. Файла липсва
        if (!$file || !($toSave = $content = @file_get_contents($file))) {
            
            if (isDebug()) {
                error_log("EF Error: Mising file: {$name}");
            }
            
            header('HTTP/1.1 404 Not Found');

        } else {
 
            // Файла съществува и трябва да бъде сервиран
            // Определяне на Content-Type на файла
            $fileExt = str::getFileExt($file);
            $mimeTypes = array(
                
                // Текстови
                'css'  => 'text/css',
                'htm'  => 'text/html',
                'svg'  => 'image/svg+xml',
                'html' => 'text/html',
                'xml'  => 'text/xml',
                'js'   => 'application/javascript',

                // Бинарни
                'swf'  => 'application/x-shockwave-flash',
                'jar'  => 'application/x-java-applet',
                'java' => 'application/x-java-applet',

                // Графични
                'png'  => 'image/png',
                'jpe'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'jpg'  => 'image/jpeg',
                'gif'  => 'image/gif',
                'ico'  => 'image/vnd.microsoft.icon'
            );

            $ctype = $mimeTypes[$fileExt];

            if (!$ctype) {
                if (isDebug()) {
                    error_log("Warning: Unsuported file extention: {$file}");
                }
                header('HTTP/1.1 404 Not Found');
            } else {        
                header("Content-Type: $ctype; charset: utf-8");

                // Хедъри за управлението на кеша в браузъра
                header("Expires: " . gmdate("D, d M Y H:i:s", time() + 3153600) . " GMT");
                header("Cache-Control: public, max-age=3153600");
                
                // Поддържа ли се gzip компресиране на съдържанието?
                $isGzipSupported = in_array('gzip', array_map('trim', explode(',', @$_SERVER['HTTP_ACCEPT_ENCODING'])));

                if ($isGzipSupported && (substr($ctype, 0, 5) == 'text/' || $ctype == 'application/javascript')) {
                    // Компресираме в движение и подаваме правилния хедър
                    $content = gzencode($content);
                    header("Content-Encoding: gzip");
                } 
                
                // Отпечатваме съдържанието и го изпращаме към браузъра
                header("Content-Length: " . strlen($content));
                echo $content;
                flush();
 
                // Копираме файла за директно сервиране от Apache следващия път
                // @todo: Да се минимализират .js и .css
                if(!isDebug()) {
                    self::saveFile_($toSave, $name);
                }
            }
        }

    }

    
}