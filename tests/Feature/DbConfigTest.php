<?php

namespace hiddenCorporation\dbConfig\Tests\Feature;

use stdClass;
use Tests\TestCase;
use hiddenCorporation\dbConfig\dbConfig;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class DbConfigTest extends TestCase
{

    use DatabaseMigrations;
    /**
     * Injection string
     *
     * @return void
     */
    public function testStringInjection()
    {
        $tmp = dbConfig::set('string_test','test');
        $test = $tmp['success'];
        $this->assertTrue($test);
    }

    /**
     * Get string
     *
     * @return void
     */
    public function testStringGet()
    {
        $tmp = dbConfig::set('string_test','test');
        $tmp = dbConfig::get('string_test');
        $test = ($tmp == 'test')?true:false;
        $this->assertTrue($test);
    }

    /**
     * Get stringFull
     *
     * @return void
     */
    public function testStringGetFull()
    {
        $tmp = dbConfig::set('string_test','test');
        $tmp = dbConfig::get('string_test',Null,true);
        $test=(isset($tmp->type) && $tmp->type == 'string' && isset($tmp->full_value) && (!empty($tmp->full_value)) && $tmp->full_value == 'test')?true:false;
        $this->assertTrue($test);
    }

    /**
     * update string
     *
     * @return void
     */
    public function testUpdateString()
    {
        $tmp = dbConfig::set('string_test','test');
        $tmp = dbConfig::update('string_test','test2');
        $tmp = dbConfig::get('string_test',Null,true);
        $test=(isset($tmp->type) && $tmp->type == 'string' && isset($tmp->full_value) && (!empty($tmp->full_value)) && $tmp->full_value == 'test2')?true:false;
        $this->assertTrue($test);
    }

     /**
     * deletion string
     *
     * @return void
     */
    public function testDeletionString()
    {
        $tmp = dbConfig::set('string_test','test');
        $tmp = dbConfig::unset('string_test');
        $test = ($tmp == true && dbConfig::configExist('string_test') == false)?true:false;
        $this->assertTrue($test);
    }

     /**
     * insertion array
     *
     * @return void
     */
    public function testArrayInjection()
    {
        $varTest=[1,2,3,4,5,6];
        $tmp = dbConfig::set('array_test',$varTest);
        $test = $tmp['success'];
        $this->assertTrue($test);
    }

     /**
     * get array
     *
     * @return void
     */
    public function testArrayGet()
    {
        $varTest=[1,2,3,4,5,6];
        $tmp = dbConfig::set('array_test',$varTest);
        $tmp = dbConfig::get('array_test');
        $test = ((is_array($tmp) && count($tmp))?True:false);
        $this->assertTrue($test);
    }

     /**
     * get full array
     *
     * @return void
     */
    public function testArrayGetFull()
    {
        $varTest=[1,2,3,4,5,6];
        $tmp = dbConfig::set('array_test',$varTest);
        $tmp = dbConfig::get('array_test',Null,true);
        $test=(isset($tmp->type) && $tmp->type == 'array' && isset($tmp->full_value) && (!empty($tmp->full_value)) && is_array($tmp->full_value) && count($tmp->full_value) == 6)?true:false;
        $this->assertTrue($test);
    }

     /**
     * modification array
     *
     * @return void
     */
    public function testArrayModification()
    {
        $varTest=[1,2,3,4,5,6];
        $tmp = dbConfig::set('array_test',$varTest);
        $tmp = dbConfig::get('array_test');
        $tmp[]=7;
        $tmp = dbConfig::update('array_test',$tmp);
        $tmp = dbConfig::get('array_test');
        $test=(count($tmp) == 7?true:false);
        $this->assertTrue($test);
    }

     /**
     * deletion array
     *
     * @return void
     */
    public function testArrayDeletion()
    {
        $varTest=[1,2,3,4,5,6];
        $tmp = dbConfig::set('array_test',$varTest);
        $tmp = dbConfig::unset('array_test');
        $test = ($tmp == true && dbConfig::configExist('array_test') == false)?true:false;
        $this->assertTrue($test);
    }


     /**
     * insertion object
     *
     * @return void
     */
    public function testObjectInjection()
    {
        $obj = new stdClass();
        $obj->element1=1;
        $obj->element2=2;
        $obj->element3=3;
        $tmp = dbConfig::set('object_test',$obj);
        $test = $tmp['success'];
        $this->assertTrue($test);
    }

     /**
     * get object
     *
     * @return void
     */
    public function testObjectGet()
    {
        $obj = new stdClass();
        $obj->element1=1;
        $obj->element2=2;
        $obj->element3=3;
        $tmp = dbConfig::set('object_test',$obj);
        $tmp = dbConfig::get('object_test');
        $test = (is_object($tmp) && isset($tmp->element2) && $tmp->element2 == 2 && count( (array)$tmp ) == 3 )?true:false;
        $this->assertTrue($test);
    }

     /**
     * get full object
     *
     * @return void
     */
    public function testObjectGetFull()
    {
        $obj = new stdClass();
        $obj->element1=1;
        $obj->element2=2;
        $obj->element3=3;
        $tmp = dbConfig::set('object_test',$obj);
        $tmp = dbConfig::get('object_test',Null,true);
        $test=(isset($tmp->type) && $tmp->type == 'object' && isset($tmp->full_value) && (!empty($tmp->full_value)) && is_object($tmp->full_value) && count((array)$tmp->full_value) == 3)?true:false;
        $this->assertTrue($test);
    }

     /**
     * modification object
     *
     * @return void
     */
    public function testObjectModification()
    {
        $obj = new stdClass();
        $obj->element1=1;
        $obj->element2=2;
        $obj->element3=3;
        $tmp = dbConfig::set('object_test',$obj);
        $tmp = dbConfig::get('object_test');
        $tmp->element4=['test','test1'];
        $tmp = dbConfig::update('object_test',$tmp);
        $tmp = dbConfig::get('object_test');
        $test = ((count( (array)$tmp ) == 4 && is_array($tmp->element4) && count($tmp->element4) == 2 )?true:false);
        $this->assertTrue($test);
    }

     /**
     * deletion object
     *
     * @return void
     */
    public function testObjectDeletion()
    {
        $obj = new stdClass();
        $obj->element1=1;
        $obj->element2=2;
        $obj->element3=3;
        $tmp = dbConfig::set('object_test',$obj);
        $tmp = dbConfig::unset('object_test');
        $test = ($tmp == true && dbConfig::configExist('object_test') == false)?true:false;
        $this->assertTrue($test);
    }

     /**
     * test crypt string
     *
     * @return void
     */
    public function testCryptString()
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

        $this->assertTrue($test);
    }

     /**
     * test crypt array
     *
     * @return void
     */
    public function testCryptArray()
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

        $this->assertTrue($test);
    }

     /**
     * test crypt object
     *
     * @return void
     */
    public function testCryptObject()
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

        $this->assertTrue($test);
    }

     /**
     * test unset cache
     *
     * @return void
     */
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

        $this->assertTrue($test);
    }

     /**
     * test collection DB
     *
     * @return void
     */
    public function testgetCollectionDb()
    {

        $tmp = dbConfig::set('string-test','test');
        $tmp = dbConfig::set('array-test',[1,2,3,4,5,6]);
        $obj = new stdClass();
        $obj->element1=1;
        $obj->element2=2;
        $obj->element3=3;
        $tmp = dbConfig::set('object-test',$obj);

        $listToAsk=['string-test','array-test'];
        if(config('dbConfig.useCache')){
            $id=intval(dbConfig::getConfigKeyByCache('object-test'));
            if($id){
                $listToAsk[]=$id;
            }
            else{
                $listToAsk[]='object-test';
            }
        }

        $list=dbConfig::get($listToAsk,Null,true);
        $test=[];
        $test[] = (count($list)==3)?true:false;
        $test[] = (array_key_exists('string-test',$list) && array_key_exists('array-test',$list) && array_key_exists('object-test',$list))?true:false;
        $test[] = (isset($list['object-test']->full_value) && isset($list['object-test']->full_value->element1) && $list['object-test']->full_value->element1 == 1)?true:false;
        $test[] = (isset($list['string-test']->cache) && $list['string-test']->cache == 'noCache')?true:false;
        $list=dbConfig::get($listToAsk);
        $test[] = count($list)==3?true:false;
        $test[] = (array_key_exists('string-test',$list) && array_key_exists('array-test',$list) && array_key_exists('object-test',$list))?true:false;
        $test[] = $list['string-test']=='test'?true:false;
        $test[] = dbConfig::unset(['string-test','array-test','object-test'])==true?true:false;
        if(in_array(false, $test, true) === false){
            $test = true;
        }else{
            $test = false;
        }

        $this->assertTrue($test);
    }

     /**
     * test collection Cache
     *
     * @return void
     */
    public function testgetCollectionCache()
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

        $list=dbConfig::get($listToAsk,Null,true);
        $test[] = count($list)==2?true:false;
        $test[] = (array_key_exists('string-test',$list) && array_key_exists('array-test',$list))?true:false;
        $test[] = (isset($list['string-test']->cache) && $list['string-test']->cache == 'cached')?true:(config('dbConfig.useCache')?false:true);
        $test[] = dbConfig::unset(['string-test','array-test'])==true?true:false;

        if(in_array(false, $test, true) === false){
            $test =  true;
        }
        else{
            $test = false;
        }
        $this->assertTrue($test);

    }

     /**
     * test collection cache/DB
     *
     * @return void
     */
    public function testgetCollectionCacheDb()
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

        $list=dbConfig::get($listToAsk,Null,true);
        $test[] = count($list)==2?true:false;
        $test[] = (array_key_exists('string-test',$list) && array_key_exists('array-test',$list))?true:false;
        $test[] = (isset($list['string-test']->cache) && $list['string-test']->cache == 'noCache')?true:false;
        $test[] = (isset($list['array-test']->cache) && $list['array-test']->cache == 'cached')?true:false;
        $test[] = dbConfig::unset(['string-test','array-test'])==true?true:false;

        if(in_array(false, $test, true) === false){
            $test=true;
        }
        else
        {
            $test=false;
        }
        $this->assertTrue($test);

    }


}
