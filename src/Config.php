<?php

namespace Apolinux;

/**
 * Manage configuration read from array dictionary contained in file
 *
 * manage developer and production options
 *
 * @author Carlos Arce <apolinux@gmail.com>
 */
class Config {
    /**
     * is a cache that contains a list of configurations,
     * where each key is the alias config name
     * is used to read or write from memory
     * @var array
     */
    private static $file_list = [];

    /**
     * directory where config files are placed
     * @var string
     */
    private static $basedir;

    /**
     * define base dir for config files
     * @param string $basedir
     */
    public static function init($basedir){
        self::$basedir = $basedir ;
    }

    /**
     * clear cache of config files uploaded to memory
     */
    public static function clearCacheFile(){
        self::$file_list = [];
    }

    /**
     * get the item from file
     * The format of item is  "filealias.item1.item2..."
     * filealias is the name of file in "config" directory without .php extension
     *
     * @param string $item item like this "filealias.item1.item2..."
     * @param mixed $default default value used if item not exist
     * @return mixed
     * @throws \Exception
     */
    public static function get($item,$default=null)
    {
        if(empty($item)){
            throw new \Exception('The item is empty') ;
        }

        $fields = explode('.',$item) ;
        if(count($fields) < 2){
            throw new \Exception('The item "'. $item .'" must have at last two fields separated by dot(.)') ;
        }
        $config_name = array_shift($fields) ;

		$filename = self::$basedir . '/'. $config_name . '.php' ;

		if(! isset(self::$file_list[$filename])){
			if(file_exists($filename) and is_readable($filename)){
				$config = require($filename);
			}else{
				throw new FileNotFoundException('The configuration file "' . $filename .'" for item "'. $item .'" does not exists') ;
			}
            self::$file_list[$filename] = $config ;
		}else{
			$config = self::$file_list[$filename];
		}
    try{
        $return = self::getAnidated($config,$fields,$item,$filename);
    } catch (InvalidKeyException $ex) {
        if(! is_null($default)){
            return $default;
        }else{
            throw $ex ;
        }
    }

    return $return ;
    }

    /**
     * read item from array defined from config string as "item1.item2.item3..."
     * this is read from an array defined like this:
     * $item = [ ... 'item1' => [ ... 'item2' => [ ... 'item3' => 'value' ... ] ... ] ... ]
     * @param array $config_array
     * @param array $item_list list of items required to read
     * @param string $item items in string separated by dot format
     * @param string $filename
     * @return mixed
     * @throws InvalidKeyException
     */
    private static function getAnidated($config_array,$item_list,$item,$filename)
    {
        $value = $config_array ;
        foreach($item_list as $item1){
            if(! isset($value[$item1])){
                throw new InvalidKeyException('The key "' . $item1 . '" not exist in array, item:'.$item.', file: '.$filename) ;
            }
            $value = $value[$item1] ;
        }

        return $value ;
    }

    /**
     * validate that item exists in config file
     * @param string $item
     * @return boolean
     * @throws \Config\InvalidKeyException
     */
    public static function itemExist($item){
        try{
            $val = self::get($item);
        }catch(\Exception $e){
            if(is_a($e, InvalidKeyException::class)){
                return false ;
            }
            throw $e ;
        }
        return true ;
    }

    /**
     * like get method, but replaces wildards like %item% with value obtained in config:
     * 'config.item' inside value. example:
     * Config::get('config.testwc') returns '%init%/some'
     * then getReplaced search for Config::get('config.init') and replaces in '%init%'
     * @param string $item
     * @param mixed $default
     * @return mixed
     */
    public static function getReplaced($item,$default=null){
        $value = self::get($item,$default);
        return preg_replace_callback('/%(\w+)%/',
                                     function ($matches) {
                                        return self::get('config.'. $matches[1]) ;
                                     },$value);
    }

    /**
     * convert an indexed array like 'a' => 'b' obtained from config
     * to form 'a:b'.
     *
     * used for curl user password configuration options
     * @param string $item
     * @param int $index
     * @return string
     */
    public static function getToUserPwd($item, $index=0){
        $value = self::get($item);
        $cont = 0;
        $result = null ;
        foreach($value as $index => $value2){
            if($cont++ == $index){
                $result = "$index:$value2";
                break ;
            }
        }

        return $result ;
    }

    /**
     * Set a value on a item on specified config file. This works only in memory, not is file persistent.
     * @param string $item
     * @param mixed $value
     * @return type
     * @throws \Exception
     */
    public static function set($item, $value)
    {
      if(empty($item)){
          throw new \Exception('The item is empty') ;
      }

      $fields = explode('.',$item) ;
      if(count($fields) < 2){
          throw new \Exception('The item "'. $item .'" must have at last two fields separated by dot(.)') ;
      }

      $config_name = array_shift($fields) ;

  		$filename = self::$basedir . '/'. $config_name . '.php' ;

  		if(! isset(self::$file_list[$filename])){
  			if(file_exists($filename) and is_readable($filename)){
  				$config = require($filename);
  			}else{
  				throw new FileNotFoundException('The configuration file "' . $filename .'" for item "'. $item .'" does not exists') ;
  			}
  		}else{
  			$config = self::$file_list[$filename];
  		}

      $result = self::setAnidated($config,$fields,$item,$value,$filename);
      // save in memory
      self::$file_list[$filename] = $config ;
      return $result ;
    }

    /**
     * set a value on array config
     *
     * @param array $config_array
     * @param array $item_list
     * @param string $item
     * @param mixed $value
     * @param string $filename
     */
    private static function setAnidated(&$config_array,$item_list,$item, $value,$filename)
    {
        if(count($item_list)>1){
            $first_item = array_shift($item_list) ;
            self::setAnidated($config_array[$first_item], $item_list, $item, $value, $filename) ;
        }else{
            // set the value
            $first_item = array_shift($item_list) ;
            $config_array[$first_item] = $value ;
        }
    }

    /**
     * sweep all config and returned as a flat array of form:
     *   item1.item2 => value1
     *   item3.item4.item5 => value2
     *  ...
     * @param string $config_name
     * @return array
     * @throws FileNotFoundException
     */
    public static function sweep($config_name='config') {//$config_array,$item_list,$item,$filename){
        $filename = self::$basedir . '/'. $config_name . '.php' ;

		if(! isset(self::$file_list[$filename])){
			if(file_exists($filename) and is_readable($filename)){
				$config = require($filename);
			}else{
				throw new FileNotFoundException('The configuration file "' . $filename .' does not exists') ;
			}
            self::$file_list[$filename] = $config ;
		}else{
			$config = self::$file_list[$filename];
		}

        //$value = $config ;

        $value = self::sweepRecursive($config);

        return $value ;
    }

    /**
     * sweep recursely config array
     * @param string $config
     * @param string $parent
     * @return array
     */
    private static function sweepRecursive($config,$parent=null){
        $out = [] ;
        foreach($config as $item => $value){
            $header = is_null($parent)?  '' : "$parent." ;
            if(is_array($value)){
                $out += self::sweepRecursive($value, $header.$item);
            }elseif(is_scalar ($item)){

                $out[$header . $item] = $value ;
            }else{
                $out[$header. $item] = json_encode($value) ;
            }
        }

        return $out ;
    }

    public static function getAll($item){
        if(empty($item)){
            throw new \Exception('The item is empty') ;
        }

        $filename = self::$basedir . "/$item.php" ;

		if(! isset(self::$file_list[$filename])){
			if(file_exists($filename) and is_readable($filename)){
				$config = require($filename);
			}else{
				throw new FileNotFoundException(
                        'The configuration file "' . $filename .
                        '" for item "'. $item .'" does not exists') ;
			}
            self::$file_list[$filename] = $config ;
		}else{
			$config = self::$file_list[$filename];
		}

        return $config ;
    }
}
