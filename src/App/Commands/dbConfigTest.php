<?php

namespace hiddenCorporation\dbConfig\App\Commands;

use stdClass;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use hiddenCorporation\dbConfig\dbConfig;
use hiddenCorporation\dbConfig\Configuration;

class dbConfigTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbconfig:test {--operation=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command made to test dbConfig class use a parameter operation : info, testCache, testCrypt, testGeneral';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $operation = $this->option('operation');
        $this->info('Command : '.$operation);
        switch($operation)
        {
            case 'info':
                $this->info('** info **');
                $this->info('- cache type in laravel        : '.config('cache.default'));
                $this->info('- cache activated for dbConfig : '.(config('dbConfig.useCache')?'Activated':'Desactivated'));
                $nb_entry = Configuration::count();
                $this->info('- entry count in db            : '.$nb_entry);
                if(config('cache.default') == 'redis' && config('dbConfig.useCache'))
                {
                    $redis = Cache::getRedis();
                    $keys = $redis->keys("*");
                    $nbCache = 0;
                    foreach ($keys as $key) {
                            $nbCache++;
                    }


                    $this->info('- entry count in redis         : '.$nbCache);
                }
                else
                {
                    $this->info('- Redis cache desactivated');
                }


            break;
            case 'testCache':
                $this->info('--- test unset cache     : '.($this->testUnsetCache()?'ok':'ko'));

            break;
            case 'testCrypt':
                $this->info('- test Crypt');
                $this->info('--- Crypt string        : '.($this->testCryptString()?'ok':'ko'));
                $this->info('--- Crypt array         : '.($this->testCryptArray()?'ok':'ko'));
                $this->info('--- Crypt Object        : '.($this->testCryptObject()?'ok':'ko'));
            break;
            case 'testGeneral':

                $this->info('- test String');
                $this->info('--- String Injection    : '.($this->testStringInjection()?'ok':'ko'));
                $this->info('--- String Get          : '.($this->testStringGet()?'ok':'ko'));
                $this->info('--- String Get full     : '.($this->testStringGetFull()?'ok':'ko'));
                $this->info('--- String Modification : '.($this->testStringModification()?'ok':'ko'));
                $this->info('--- String Deletion     : '.($this->testStringDeletion()?'ok':'ko'));

                $this->info('- test Array');
                $this->info('--- Array Injection     : '.($this->testArrayInjection()?'ok':'ko'));
                $this->info('--- Array Get           : '.($this->testArrayGet()?'ok':'ko'));
                $this->info('--- Array Get full      : '.($this->testArrayGetFull()?'ok':'ko'));
                $this->info('--- Array Modification  : '.($this->testArrayModification()?'ok':'ko'));
                $this->info('--- Array Deletion      : '.($this->testArrayDeletion()?'ok':'ko'));

                $this->info('- test Object');
                $this->info('--- Object Injection    : '.($this->testObjectInjection()?'ok':'ko'));
                $this->info('--- Object Get          : '.($this->testObjectGet()?'ok':'ko'));
                $this->info('--- Object Get full     : '.($this->testObjectGetFull()?'ok':'ko'));
                $this->info('--- Object Modification : '.($this->testObjectModification()?'ok':'ko'));
                $this->info('--- Object Deletion     : '.($this->testObjectDeletion()?'ok':'ko'));

                $this->info('- test Collection ');
                $this->info('--- get Collection Db       : '.($this->testgetCollectionDb()?'ok':'ko'));
                $this->info('--- get Collection Cache    : '.($this->testgetCollectionCache()?'ok':'ko'));
                $this->info('--- get Collection Cache/Db : '.($this->testgetCollectionCacheDb()?'ok':'ko'));

                break;
            default:
                $this->info('Command : '.$operation.' not found');


            break;
        }
    }

    protected function testgetCollectionDb()
    {

        $tmp = dbConfig::set('string-test','test');
        $tmp = dbConfig::set('array-test',[1,2,3,4,5,6]);
        $obj = new stdClass();
        $obj->element1=1;
        $obj->element2=2;
        $obj->element3=3;
        $tmp = dbConfig::set('object-test',$obj);

        $listToAsk=['string-test','array-test','object-test'];

        $list=dbConfig::getMultiple($listToAsk,Null,true);
        $test=[];
        $test[] = (count($list) == 3)?true:false;
        $test[] = (array_key_exists('string-test',$list) && array_key_exists('array-test',$list) && (array_key_exists('object-test',$list))  )?true:false;
        $test[] = (isset($list['string-test']->cache) && $list['string-test']->cache == 'noCache')?true:false;
        $test[] = dbConfig::unset(['string-test','array-test','object-test'])==true?true:false;

        if(in_array(false, $test, true) === false){
            return true;
        }
        return false;

    }

    protected function testgetCollectionCache()
    {
        $tmp = dbConfig::set('string-test','test',['cache_duration'=>3600]);
        $tmp = dbConfig::set('array-test',[1,2,3,4,5,6],['cache_duration'=>8000]);
        $test=[];
        $listToAsk=[];
        if(config('dbConfig.useCache')){
            $id=intval(dbConfig::getConfigKeyByCache('array-test'));
            if($id){
                $listToAsk[]=$id;
                $test[]=true;
            }
            else{
                $test[]=false;
            }
        }

        if(!count($listToAsk)){
            $listToAsk[]='array-test';
        }

        $listToAsk[]='string-test';

        $list=dbConfig::getMultiple($listToAsk,Null,true);
        $test[] = count($list)==2?true:false;
        $test[] = (array_key_exists('string-test',$list) && array_key_exists('array-test',$list))?true:false;
        $test[] = (isset($list['string-test']->cache) && $list['string-test']->cache == 'cached')?true:(config('dbConfig.useCache')?false:true);
        $test[] = dbConfig::unset(['string-test','array-test'])==true?true:false;

        if(in_array(false, $test, true) === false){
            return true;
        }
        return false;
    }

    protected function testgetCollectionCacheDb()
    {
        $tmp = dbConfig::set('string-test','test');
        $tmp = dbConfig::set('array-test',[1,2,3,4,5,6],['cache_duration'=>8000]);

        $test=[];
        $listToAsk=[];
        if(config('dbConfig.useCache')){
            $id=intval(dbConfig::getConfigKeyByCache('array-test'));
            if($id){
                $listToAsk[]=$id;
                $test[]=true;
            }
            else{
                $test[]=false;
            }
        }

        if(!count($listToAsk)){
            $listToAsk[]='array-test';
        }

        $listToAsk[]='string-test';

        $list=dbConfig::getMultiple($listToAsk,Null,true);
        $test[] = count($list)==2?true:false;
        $test[] = (array_key_exists('string-test',$list) && array_key_exists('array-test',$list))?true:false;
        $test[] = (isset($list['string-test']->cache) && $list['string-test']->cache == 'noCache')?true:false;
        $test[] = (isset($list['array-test']->cache) && $list['array-test']->cache == 'cached')?true:false;
        $test[] = dbConfig::unset(['string-test','array-test'])==true?true:false;

        if(in_array(false, $test, true) === false){
            return true;
        }
        return false;

    }


    protected function testStringInjection()
    {
        $tmp = dbConfig::set('string_test','test');
        $test = $tmp['success'];
        return $test;
    }

    protected function testStringGet()
    {
        $tmp = dbConfig::get('string_test');
        $tmp = ($tmp == 'test')?true:false;
        return $tmp;
    }

    protected function testStringGetFull()
    {
        $tmp = dbConfig::get('string_test',Null,true);
        $test=(isset($tmp->type) && $tmp->type == 'string' && isset($tmp->full_value) && (!empty($tmp->full_value)) && $tmp->full_value == 'test')?true:false;
        return $tmp;
    }

    protected function testStringModification()
    {

        $tmp = dbConfig::update('string_test','test2');
        $tmp = dbConfig::get('string_test');
        return ( ($tmp == 'test2' )?true:false );
    }

    protected function testStringDeletion()
    {
        $tmp = dbConfig::unset('string_test');
        $response = ($tmp == true && dbConfig::configExist('string_test') == false)?true:false;
        return $response;
    }

    protected function testArrayInjection()
    {
        $varTest=[1,2,3,4,5,6];
        $tmp = dbConfig::set('array_test',$varTest);
        $test = $tmp['success'];
        return $test;
    }

    protected function testArrayGet()
    {
        $tmp = dbConfig::get('array_test');
        return ((is_array($tmp) && count($tmp))?True:false);
    }

    protected function testArrayGetFull()
    {
        $tmp = dbConfig::get('array_test',Null,true);
        $test=(isset($tmp->type) && $tmp->type == 'array' && isset($tmp->full_value) && (!empty($tmp->full_value)) && is_array($tmp->full_value) && count($tmp->full_value) == 6)?true:false;
        return $test;
    }

    protected function testArrayModification()
    {
        $tmp = dbConfig::get('array_test');
        $tmp[]=7;
        $tmp = dbConfig::update('array_test',$tmp);
        $tmp = dbConfig::get('array_test');
        return (count($tmp) == 7?true:false);
    }

    protected function testArrayDeletion()
    {
        $tmp = dbConfig::unset('array_test');
        $response = ($tmp == true && dbConfig::configExist('array_test') == false)?true:false;
        return $response;
    }

    protected function testObjectInjection()
    {
        $obj = new stdClass();
        $obj->element1=1;
        $obj->element2=2;
        $obj->element3=3;
        $tmp = dbConfig::set('object_test',$obj);
        $test = $tmp['success'];
        return $test;
    }

    protected function testObjectGet()
    {
        $tmp = dbConfig::get('object_test');
        $tmp = (is_object($tmp) && isset($tmp->element2) && $tmp->element2 == 2 && count( (array)$tmp ) == 3 )?true:false;
        return $tmp;
    }

    protected function testObjectGetFull()
    {
        $tmp = dbConfig::get('object_test',Null,true);
        $test=(isset($tmp->type) && $tmp->type == 'object' && isset($tmp->full_value) && (!empty($tmp->full_value)) && is_object($tmp->full_value) && count((array)$tmp->full_value) == 3)?true:false;
        return $test;
    }

    protected function testObjectModification()
    {
        $tmp = dbConfig::get('object_test');
        $tmp->element4=['test','test1'];
        $tmp = dbConfig::update('object_test',$tmp);
        $tmp = dbConfig::get('object_test');
        return ((count( (array)$tmp ) == 4 && is_array($tmp->element4) && count($tmp->element4) == 2 )?true:false);
    }

    protected function testObjectDeletion()
    {
        $tmp = dbConfig::unset('object_test');
        $response = ($tmp == true && dbConfig::configExist('object_test') == false)?true:false;
        return $response;
    }

    protected function testCryptString()
    {
        $tmp = dbConfig::set('string_test_crypt','test crypt',['crypted'=>true]);
        $tmp = dbConfig::get('string_test_crypt',Null,true);
        $test=false;
        if(
            $tmp->crypted &&
            $tmp->value != 'test crypt' &&
            $tmp->full_value == 'test crypt'
        ){
            $test=true;
        }

        $tmp = dbConfig::unset('string_test_crypt');

        return $test;
    }

    protected function testCryptArray()
    {
        $value = [1,2,3,4,5,6];
        $tmp = dbConfig::set('array_test_crypt',$value,['crypted'=>true]);
        $tmp = dbConfig::get('array_test_crypt',Null,true);
        $test=false;
        if(
            $tmp->crypted
        ){
            if(
                is_array($tmp->full_value) && count($tmp->full_value) == 6
            ){
                $test=true;
            }
        }

        $tmp = dbConfig::unset('array_test_crypt');

        return $test;
    }

    protected function testCryptObject()
    {

        $obj = new stdClass();
        $obj->element1=1;
        $obj->element2=2;
        $obj->element3=3;

        $tmp = dbConfig::set('object_test_crypt',$obj,['crypted'=>true]);
        $tmp = dbConfig::get('object_test_crypt',Null,true);
        $test=false;
        if(
            $tmp->crypted
        ){
            $test=(isset($tmp->type) && $tmp->type == 'object' && isset($tmp->full_value) && (!empty($tmp->full_value)) && is_object($tmp->full_value) && count((array)$tmp->full_value) == 3)?true:false;
        }

        $tmp = dbConfig::unset('object_test_crypt');

        return $test;
    }

    public function testUnsetCache()
    {
        $tmp = dbConfig::set('string_test','test',['cache_duration'=>3600]);
        $tmp = dbConfig::get('string_test',Null,true);
        $test=false;
        if(
            $tmp->value == 'test' &&
            $tmp->full_value == 'test' &&
            (
                (config('dbConfig.useCache') == true && $tmp->cache_duration > 0 && $tmp->cache == 'cached') ||
                (config('dbConfig.useCache') == false && $tmp->cache_duration == NUll)
            )
        ){
            $test=true;
        }

        $tmp = dbConfig::unset('string_test');

        if(
            config('dbConfig.useCache') == true && dbConfig::configExist('string_test',true) == 'cache'
        )
        {
            $test=false;
        }

        return $test;
    }



}
