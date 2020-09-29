<?php
namespace hiddenCorporation\dbConfig\Traits;

use Illuminate\Support\Str;
use Illuminate\Encryption\Encrypter;

trait DbConfigTools
{
	/**
	 * findType
	 *
	 * @param  mixed $value
	 * @return mixed the type or false
	 */
	public static function findType($value=null)
    {

        if(!is_null($value))
        {
            if(is_int($value)){
                return 'int';
            }

            if(is_bool($value)){
                return 'boolean';
            }

            if(is_string($value)){
                return 'string';
            }

            if(is_float($value)){
                return 'float';
            }

            if(is_string($value) && is_array(json_decode($value, true))){
                return 'json';
            }

            if(is_array($value)){
                return 'array';
            }

            if(
                is_a($value, 'Illuminate\Database\Eloquent\Collection') ||
                is_a($value,'Illuminate\Support\Collection')
            ){
                return 'collection';
            }


            if(is_object($value)){
                return 'object';
            }

            if(!is_null($value)){
                return 'binary';
            }
        }
        return false;
    }

    /**
     * encodeConfigValue
     *
     * @param  mixed $value
     * @param  mixed $type
     * @return mixed the value or the transformed value
     */
    public static function encodeConfigValue($value,$type=NULL)
    {
        $type = is_null($type)?DbConfigTools::findType($value):$type;
        if(empty($value) || empty($type)){ return false; }
        switch($type){
          case 'boolean':
            return $value==true?'ok':'ko';
          break;
          case 'int':
          case 'float':
            return strval($value);
          break;
          case 'string':
            return $value;
          break;
          case 'array':
          case 'collection':
          case 'object':
            return serialize($value);
          break;
          case 'json':
            return serialize(json_decode($value));
          case 'binary':
          default:
            return $value;
          break;
        }
    }

    /**
     * decodeConfigValue
     *
     * @param  mixed $value
     * @param  mixed $type
     * @return mixed the value or the transformed value
     */
    public static function decodeConfigValue($value,$type)
    {
        if(empty($value) || empty($type)){ return false; }
        switch ($type) {
            case 'int':
                return intval($value);
            case 'boolean':
                return $value=='ok'?true:false;
            break;
            case 'string':
                return strval($value);
            break;
            case 'float':
                return floatval($value);
            break;
            case 'array':
                return (is_array($value)?$value:unserialize($value));
            break;
            case 'collection':
            case 'object':
                return (is_object($value)?$value:unserialize($value));
            break;
            case 'json':
                return json_encode(unserialize($value));
            case 'binary':
            default:
                return $value;
            break;
        }
    }

    /**
     * decryptConfigValue
     *
     * @param  mixed $value
     * @param  mixed $key
     * @return string the decrypted value
     */
    public static function decryptConfigValue($value, $key = false)
    {
        $key = $key ? $key : config('app.key');
        $encrypter = new Encrypter($key, config('app.cipher'));
        $decrypted = $encrypter->decrypt($value);
        return $decrypted;
    }

    /**
     * cryptConfigValue
     *
     * @param  mixed $value
     * @param  mixed $key
     * @return string the encrypted value
     */
    public static function cryptConfigValue($value, $key = false)
    {
        $key = $key ? $key : config('app.key');
        $encrypter = new Encrypter($key, config('app.cipher'));
        $encrypted = $encrypter->encrypt($value);
        return $encrypted;
    }

    /**
     * parseListKey
     *
     * @param  mixed $list
     * @return array  splitted list of the entry var
     */
    public static function parseListKey($list)
    {
        $technical_name_list=$id_list=[];
        foreach($list as $key){
            if(is_string($key) && (!empty($key)))
            {
                $technical_name_list[]=Str::slug($key);
            }
            elseif(is_int($key) && $key > 0)
            {
                $id_list[]=intval($key);
            }
        }
        return ['technical_name'=>$technical_name_list,'id_list'=>$id_list];
    }

    /**
     * transformEntry
     *
     * @param  mixed $entry
     * @param  mixed $full
     * @param  mixed $statusCache
     * @return mixed the full configuration object or the value
     */
    public static function transformEntry($entry,$full=false,$statusCache=false){
        $toReturn=$entry;
        $toReturn->full_value = DbConfigTools::decodeConfigValue( ($toReturn->crypted?DbConfigTools::decryptConfigValue($toReturn->value):$toReturn->value) , $toReturn->type );
        if($full){
            if($statusCache){
                $toReturn->cache=$statusCache;
            }
            return $toReturn;
        }
        return $toReturn->full_value;
    }


}
