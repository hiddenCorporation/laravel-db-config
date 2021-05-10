<?php

namespace hiddenCorporation\dbConfig;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use hiddenCorporation\dbConfig\Traits\DbConfigTools as DbConfigTools;
use hiddenCorporation\dbConfig\Models\Configuration as Configuration;
use phpDocumentor\Reflection\Types\Mixed_;

class dbConfig
{

    use DbConfigTools;

    protected const CACHE_MANAGEMENT_MODE = ['fix', 'auto'];
    protected const CACHE_MANAGEMENT_AUTO_MODEL = ['cache' => [], 'db' => []];

    /**
     * set
     *
     * @param  string $name the name of the var will be slugify to forge a technical_name
     * @param  mixed $value can be all kind of var
     * @param  array $additionalAttribute description,crypted,cache_duration (in second), cache_mangement (fix or auto)
     * @return array  An array with operation status and the element if created, if not list of errors
     */
    public static function set(string $name, $value, array $additionalAttribute = array())
    {
        $toReturn = ['success' => false, 'msg' => false, 'errors' => []];

        if (empty($value)) {
            $toReturn['errors'][] = "empty value.";
            return $toReturn;
        }

        $technicalName = Str::slug($name);

        $type = DbConfigTools::findType($value);
        if (!$type) {
            $toReturn['errors'][] = "Impossible to find type.";
            return $toReturn;
        }

        if (dbConfig::configExist($technicalName)) {
            $toReturn['errors'][] = "there is already a configuration item with this name.";
            return $toReturn;
        }

        $encodedValue = DbConfigTools::encodeConfigValue($value, $type);
        $crypted = (array_key_exists('crypted', $additionalAttribute) && $additionalAttribute['crypted'] === TRUE) ? TRUE : FALSE;
        if ($crypted) {
            $encodedValue = DbConfigTools::cryptConfigValue($encodedValue);
        }

        $param = [
            'name' => $name,
            'type' => $type,
            'technical_name' => $technicalName,
            'crypted' => (bool)$crypted,
            'value' => $encodedValue
        ];

        if (isset($additionalAttribute['description'])) $param['description'] = $additionalAttribute['description'];

        $needUpdateCache = false;
        if (config('dbConfig.useCache')) {

            $toReturn['cached'] = false;
            $testCacheDuration = (array_key_exists('cache_duration', $additionalAttribute) && is_int($additionalAttribute['cache_duration']) && $additionalAttribute['cache_duration'] > 0) ? true : false;
            $testCacheManagement = (array_key_exists('cache_management', $additionalAttribute) && in_array($additionalAttribute['cache_management'], dbConfig::CACHE_MANAGEMENT_MODE)) ? $additionalAttribute['cache_management'] : false;
            $cacheAutoMethodAvailable = (config('dbConfig.cache_auto_get') || config('dbConfig.cache_auto_modify')) ? true : false;

            $param['cache_duration'] = NULL;

            if ($testCacheDuration && ($testCacheManagement == FALSE or $testCacheManagement == 'fix')) {
                $param['cache_duration'] = $additionalAttribute['cache_duration'];
                $param['cache_management'] = 'fix';
                $needUpdateCache = true;
            } elseif ($testCacheManagement && $additionalAttribute['cache_management'] == 'auto') {
                if ($cacheAutoMethodAvailable) {
                    if (
                        $testCacheDuration &&
                        $additionalAttribute['cache_duration'] <= config('dbConfig.cache_duration_max') &&
                        $additionalAttribute['cache_duration'] >= config('dbConfig.cache_duration_min')
                    ) {
                        $param['cache_duration'] = intval($additionalAttribute['cache_duration']);
                    } else {
                        $param['cache_duration'] = config('dbConfig.cache_duration_default');
                    }

                    $param['cache_management'] = 'auto';
                    $dts = Carbon::now()->timestamp;
                    $indexAuto = dbConfig::CACHE_MANAGEMENT_AUTO_MODEL;

                    if (config('dbConfig.cache_auto_modify')) {
                        Cache::set(config('dbConfig.prefix_cache_internal') . 'auto_cache:update:' . $technicalName, $indexAuto);
                    }

                    if (config('dbConfig.cache_auto_get')) {
                        $indexAuto['db'][] = $dts;
                        Cache::set(config('dbConfig.prefix_cache_internal') . 'auto_cache:get:' . $technicalName, $indexAuto);
                    }

                    $needUpdateCache = true;
                } elseif ($testCacheDuration) {
                    $param['cache_duration'] = $additionalAttribute['cache_duration'];
                    $param['cache_management'] = 'fix';
                    $needUpdateCache = true;
                }
            }
        }


        $operation = Configuration::create($param);
        if ($needUpdateCache) $toReturn['cached'] = Cache::put(config('dbConfig.prefix_cache') . $param['technical_name'], $operation, $param['cache_duration']);
        if (config('dbConfig.useCache')) dbConfig::updateConfigCacheIndex('set', $operation->technical_name, $operation->id);

        $toReturn['dbConfig'] = $operation;
        $toReturn['dbConfig']->full_value = DbConfigTools::decodeConfigValue(($operation->crypted ? DbConfigTools::decryptConfigValue($operation->value) : $operation->value), $operation->type);
        $toReturn['success'] = true;
        return $toReturn;
    }

    /**
     * get
     *
     * @param  mixed $entity a config id, the technical Name of a config element or a mixed array of both
     * @param  mixed $defaultReturnValue, the default return value you want
     * @param  bool $full if true will return all info about the configuration var
     * @return mixed can return null if data not found, or a single value, the value and information about the data if full is true
     */
    public static function get($entity, $defaultReturnValue = Null, bool $full = false)
    {
        if (!dbConfig::configExist($entity)) return $defaultReturnValue;

        $nameToFound = is_int($entity) ? dbConfig::getConfigKeyByCache($entity) : Str::slug($entity);

        if (config('dbConfig.useCache') && Cache::has(config('dbConfig.prefix_cache') . $nameToFound)) {

            $tmp = Cache::get(config('dbConfig.prefix_cache') . $nameToFound);
            $cache_duration = $tmp->cache_duration;
            $tmp = config('dbConfig.cache_auto_get') ? dbConfig::autoCacheUpdate($tmp, 'cache', 'get') : $tmp;

            if ($tmp->cache_duration != $cache_duration) {
                Cache::put(config('dbConfig.prefix_cache') . $tmp->technical_name, $tmp, $tmp->cache_duration);
            }

            return DbConfigTools::transformEntry($tmp, $full, 'cached');
        }

        $tmp = Configuration::where('technical_name', $nameToFound)->first();
        if ($tmp) {
            $cacheStatus = 'noCache';
            $tmp = config('dbConfig.cache_auto_get') ? dbConfig::autoCacheUpdate($tmp, 'db', 'get') : $tmp;

            if (config('dbConfig.useCache') && $tmp->cache_duration > 0) {
                Cache::put(config('dbConfig.prefix_cache') . $tmp->technical_name, $tmp, $tmp->cache_duration);
                $cacheStatus = 'insertion';
            }

            return DbConfigTools::transformEntry($tmp, $full, $cacheStatus);
        }
    }

    /**
     * getMultiple
     *
     * @param  mixed $entities list of config id or technical Name of a config element
     * @param  mixed $defaultReturnValue the default return value you want
     * @param  mixed $full if true will return all info about the configuration var
     * @return mixed
     */
    public static function getMultiple(array $entities, $defaultReturnValue = Null, bool $full = false)
    {
        if (!count($entities)) return $defaultReturnValue;

        $getListDb = function ($list, $full = false) {
            $toReturn = Null;
            $req = Configuration::whereIn('technical_name', $list['technical_name'])
                ->orWhereIn('id', $list['id_list'])
                ->orderBy('technical_name', 'asc')->get();
            if ($req->count()) {
                $toReturn = [];
                foreach ($req as $config) {
                    $cacheStatus = 'noCache';
                    $toReturn[$config->technical_name] = $config;
                    if (config('dbConfig.useCache') && $config->cache_duration > 0) {
                        $tmpConfig = config('dbConfig.cache_auto_get') ? dbConfig::autoCacheUpdate($config, 'db', 'get') : $config;
                        Cache::put(config('dbConfig.prefix_cache') . $tmpConfig->technical_name, $tmpConfig, $tmpConfig->cache_duration);
                        $cacheStatus = 'insertion';
                    } else {
                        $toReturn[$config->technical_name] = DbConfigTools::transformEntry($toReturn[$config->technical_name], $full, $cacheStatus);
                    }
                }
            }
            return $toReturn;
        };

        if (!config('dbConfig.useCache')) {
            $tmpResult = $getListDb(DbConfigTools::parseListKey($entities), $full);
            return (is_array($tmpResult) && count($tmpResult)) ? $tmpResult : $defaultReturnValue;
        }

        $result = [];
        $list = $listDb = DbConfigTools::parseListKey($entities);
        foreach ($list as $keyType => $keyList) {
            if ($keyType == 'technical_name') {
                foreach ($keyList as $key) {
                    $tmp  = Cache::get(config('dbConfig.prefix_cache') . $key, Null);
                    if ($tmp) {
                        $cache_duration = $tmp->cache_duration;
                        $tmp = config('dbConfig.cache_auto_get') ? dbConfig::autoCacheUpdate($tmp, 'cache', 'get') : $tmp;
                        if ($tmp->cache_duration != $cache_duration) Cache::put(config('dbConfig.prefix_cache') . $tmp->technical_name, $tmp, $tmp->cache_duration);
                        $result[$key] = DbConfigTools::transformEntry($tmp, $full, 'cached');
                        $listDb['technical_name'] = array_diff($listDb['technical_name'], [$key]);
                    }
                }
            } else {
                foreach ($keyList as $key) {
                    $technical_name = DbConfig::getConfigKeyByCache($key);
                    $tmp  = $technical_name != Null ? Cache::get(config('dbConfig.prefix_cache') . $technical_name, Null) : Null;

                    if ($tmp) {
                        $cache_duration = $tmp->cache_duration;
                        $tmp = config('dbConfig.cache_auto_get') ? dbConfig::autoCacheUpdate($tmp, 'cache', 'get') : $tmp;
                        if ($tmp->cache_duration != $cache_duration) Cache::put(config('dbConfig.prefix_cache') . $tmp->technical_name, $tmp, $tmp->cache_duration);
                        $result[$technical_name] = DbConfigTools::transformEntry($tmp, $full, 'cached');
                        $listDb['id_list'] = array_diff($listDb['id_list'], [$key]);
                    }
                }
            }
        }

        if (
            count($listDb['id_list']) ||
            count($listDb['technical_name'])
        ) {
            $listDb = $getListDb($listDb, $full);
            if ($listDb) $result = array_merge($result, $listDb);
        }

        if (!is_array($result) && count($result)) {
            $result = $defaultReturnValue;
        } else {
            ksort($result);
        }

        return $result;
    }

    /**
     * update
     *
     * @param  mixed $entity $entity id or technical_name
     * @param  mixed $value the new value
     * @param  mixed $additionalAttribute the additional attribute
     * @return array an array with the detail of the operation and the configuration element if found.
     */
    public static function update($entity, $value = Null, $additionalAttribute = array())
    {
        $toReturn = ['success' => false, 'msg' => false, 'errors' => [], 'cached' => false];
        if (!dbConfig::configExist($entity)) {
            $toReturn['errors'][] = 'Config not found';
            return $toReturn;
        }
        $nameToFind = (is_int($entity)) ? dbConfig::getConfigKeyByCache($entity) : Str::slug($entity);
        if (!$nameToFind) {
            $toReturn['errors'][] = 'unable to resolve config name';
            return $toReturn;
        }

        // $valueM  : true/false there is a value to modify
        // $valueSb : model the value to use
        // $oldConfiguration : the configuration found
        // $newConfiguration : the configuration found updated progressively

        $oldConfiguration = $newConfiguration = Configuration::where('technical_name', $nameToFind)->first();

        $valueM = $valueSb = false;
        $toUpdate = 0;
        if ($value) {
            $type = DbConfigTools::findType($value);
            if (!$type) {
                $toReturn['errors'][] = 'impossible to resolve value type';
                return $toReturn;
            }
            $valueM = true;
            $newConfiguration->type = $type;
            $newConfiguration->value = DbConfigTools::encodeConfigValue($value, $type);
            $valueSb = $value;
            $toUpdate += 1;
        } else {
            $valueSb = DbConfigTools::decodeConfigValue($oldConfiguration->crypted ? dbConfigTools::uncryptConfigValue($oldConfiguration->value) : $oldConfiguration->value, $oldConfiguration->type);
        }


        if (isset($additionalAttribute['description'])) {
            $toUpdate += 1;
            $newConfiguration->description = $additionalAttribute['description'];
        }

        if (isset($additionalAttribute['crypted'])) {
            $toUpdate += 1;
            $newConfiguration->crypted = boolval($additionalAttribute['crypted']);
        }

        if ($valueM && $newConfiguration->crypted) {
            // if we need to crypt the new value
            $toUpdate += 1;
            $newConfiguration->value = DbConfigTools::cryptConfigValue($newConfiguration->value);
        }

        if ((!$valueM) && $newConfiguration->crypted != $oldConfiguration->crypted) {
            $toUpdate += 1;
            // no new value but now we
            if ($newConfiguration->crypted) {
                $newConfiguration->value =   DbConfigTools::cryptConfigValue(DbConfigTools::encodeConfigValue($valueSb, $newConfiguration->type));
            } else {
                $newConfiguration->value = DbConfigTools::encodeConfigValue($valueSb);
            }
        }

        if (config('dbConfig.useCache')) {
            $cacheDurationExist       = array_key_exists('cache_duration', $additionalAttribute) ? true : false;
            $cacheDurationDisabled    = ($cacheDurationExist && $additionalAttribute['cache_duration'] === False) ? true : false;
            $cacheDurationValidValue  = ($cacheDurationExist && is_int($additionalAttribute['cache_duration']) && $additionalAttribute['cache_duration'] > 0) ? $additionalAttribute['cache_duration'] : false;
            $cacheManagementExist     = array_key_exists('cache_management', $additionalAttribute) && in_array($additionalAttribute['cache_management'], dbConfig::CACHE_MANAGEMENT_MODE) ? $additionalAttribute['cache_management'] : false;
            $cacheAutoMethodAvailable = (config('dbConfig.cache_auto_get') || config('dbConfig.cache_auto_modify')) ? true : false;

            if ($cacheDurationExist && $cacheDurationDisabled) {
                //cache desactivation
                $toUpdate += 1;
                $newConfiguration->cache_duration = Null;
                $newConfiguration->cache_management = 'fix';
            } elseif ($cacheDurationExist && $cacheDurationValidValue && $cacheManagementExist == 'fix') {
                // full fix description
                $toUpdate += 1;
                $newConfiguration->cache_management = 'fix';
                $newConfiguration->cache_duration = $additionalAttribute['cache_duration'];
            } elseif ($cacheDurationExist && $cacheDurationValidValue && (!$cacheManagementExist) && $oldConfiguration->cache_management == 'fix') {
                // change fix value  cache
                $toUpdate += 1;
                $newConfiguration->cache_duration = $additionalAttribute['cache_duration'];
            } elseif ($cacheManagementExist == 'auto') {
                // change for auto value
                if (!$cacheAutoMethodAvailable) {
                    if ($cacheDurationExist && $cacheDurationValidValue) {
                        $newConfiguration->cache_duration = $cacheDurationValidValue;
                    } else {
                        $newConfiguration->cache_duration = false;
                    }
                    $newConfiguration->cache_management = 'fix';
                    $toUpdate += 1;
                } else {
                    $newConfiguration->cache_management = 'auto';
                    if (
                        $cacheDurationExist && $cacheDurationValidValue &&
                        $additionalAttribute['cache_duration'] <= config('dbConfig.cache_duration_max') &&
                        $additionalAttribute['cache_duration'] >= config('dbConfig.cache_duration_min')
                    ) {
                        $newConfiguration->cache_duration = $cacheDurationValidValue;
                        $toUpdate += 1;
                    } else {
                        if (!$oldConfiguration->cache_duration) {
                            $newConfiguration->cache_duration = config('dbConfig.cache_duration_default');
                            $toUpdate += 1;
                        }
                    }
                }
            }
        } elseif ($newConfiguration->cache_duration) {
            $toUpdate += 1;
            $newConfiguration->cache_duration = null;
            $newConfiguration->cache_management = 'fix';
        }


        if (!$toUpdate) {
            $toReturn['errors'][] = 'nothing to update';
            return $toReturn;
        }

        if (config('dbConfig.useCache')) {
            if (
                ($oldConfiguration->cache_management == 'auto' && $newConfiguration->cache_management == 'fix') ||
                ($oldConfiguration->cache_management == 'auto' && $newConfiguration->cache_management == 'auto' && $oldConfiguration->cache_duration != $newConfiguration->cache_duration)
            ) {
                dbConfig::clearAutoCache($newConfiguration->technical_name);
            }

            if ($newConfiguration->cache_management == 'auto') {
                $newConfiguration = dbConfig::autoCacheUpdate($newConfiguration, Cache::has(config('dbConfig.prefix_cache') . $newConfiguration->technical_name) ? 'cache' : 'db', 'update');
            }
        }

        $resultInjectionBd = $newConfiguration->save();

        if (!$resultInjectionBd) {
            $toReturn['errors'][] = 'Unable to Update the bd';
            return $toReturn;
        }

        $toReturn['dbConfig'] = $newConfiguration;

        if (config('dbConfig.useCache')) {
            $toReturn['cached'] = false;
            Cache::forget(config('dbConfig.prefix_cache') . $newConfiguration->technical_name);
            if ($newConfiguration->cache_duration > 0) {
                $toReturn['cached'] = Cache::put(config('dbConfig.prefix_cache') . $newConfiguration->technical_name, $newConfiguration, intval($newConfiguration->cache_duration));
            }
        }

        $toReturn['success'] = true;
        return $toReturn;
    }

    /**
     * unset
     *
     * @param  mixed $entity id or technical_name or array of both
     * @return mixed  null, true or false
     */
    public static function unset($entity)
    {

        if (is_array($entity)) {

            $result = [];
            foreach ($entity as $key) {
                $tmp = dbConfig::unset($key);
                $result[] = $tmp ? true : false;
            }

            if (!(is_array($result) && count($result))) return Null;
            if (in_array(false, $result, true) === false) return true;
            return false;
        }

        if (is_int($entity) && $entity > 0) {

            $tmp = Configuration::where('id', $entity)->first();
            if ($tmp) {
                $technicalName = $tmp->technical_name;
                $cache_duration = $tmp->cache_duration;
                $tmp->delete();
                if (config('dbConfig.useCache')) {
                    if ($cache_duration > 0) {
                        Cache::forget(config('dbConfig.prefix_cache') . $technicalName);
                        if ($tmp->cache_management == 'auto') {
                            Cache::forget(config('dbConfig.prefix_cache_internal') . 'auto_cache:get:' . $technicalName);
                            Cache::forget(config('dbConfig.prefix_cache_internal') . 'auto_cache:update:' . $technicalName);
                        }
                    }
                    dbConfig::updateConfigCacheIndex('unset', $technicalName, $entity);
                }

                return true;
            }
        }

        if (is_string($entity) && (!empty($entity))) {
            $technicalName = Str::slug($entity);
            $tmp = Configuration::where('technical_name', $technicalName)->first();
            if ($tmp) {
                $idToDelete = $tmp->id;
                $cache_duration = $tmp->cache_duration;
                $tmp->delete();
                if (config('dbConfig.useCache')) {
                    if ($cache_duration > 0) {
                        Cache::forget(config('dbConfig.prefix_cache') . $technicalName);
                        if ($tmp->cache_management == 'auto') {
                            Cache::forget(config('dbConfig.prefix_cache_internal') . 'auto_cache:get:' . $technicalName);
                            Cache::forget(config('dbConfig.prefix_cache_internal') . 'auto_cache:update:' . $technicalName);
                        }
                    }
                    dbConfig::updateConfigCacheIndex('unset', $technicalName, $idToDelete);
                }

                return true;
            }

            return false;
        }

        return null;
    }


    /**
     * configExist
     *
     * @param  mixed $entity $entity id or technical_name
     * @param  mixed $infoSup boolean to get more intel about where the config has been found.
     * @return mixed 'cache','db or true or false depends if infoSup is true
     */
    public static function configExist($entity, $infoSup = false)
    {
        if (is_int($entity) && $entity > 0) {
            if (config('dbConfig.useCache')) {
                $indexIdName = Cache::get(config('dbConfig.prefix_cache_internal') . 'index_id_name', array());

                if ($infoSup) {
                    $test1 = array_key_exists(strval($entity), $indexIdName) ? true : false;
                    $indexNameId = Cache::get(config('dbConfig.prefix_cache_internal') . 'index_name_id', array());
                    $test2 = ($test1 && array_key_exists($indexIdName[strval($entity)], $indexNameId)) ? true : false;
                    $test3 = ($test2 && Cache::has(config('dbConfig.prefix_cache') . $indexNameId[$indexIdName[strval($entity)]])) ? true : false;
                    return ($test1 && $test2 && $test3) ? 'cache' : false;
                } else {
                    return array_key_exists(strval($entity), $indexIdName) ? true : false;
                }
            }

            $tmp = Configuration::where('id', $entity)->count();
            return $tmp ? ($infoSup ? 'db' : true) : false;
        }

        if (is_string($entity) && (!empty($entity))) {
            $technicalName = Str::slug($entity);
            if (config('dbConfig.useCache')) {
                $indexNameId = Cache::get(config('dbConfig.prefix_cache_internal') . 'index_name_id', array());
                if ($infoSup) {
                    $test1 = array_key_exists($technicalName, $indexNameId) ? true : false;
                    $indexIdName = Cache::get(config('dbConfig.prefix_cache_internal') . 'index_id_name', array());
                    $test2 = ($test1 && array_key_exists($indexNameId[$technicalName], $indexIdName)) ? true : false;
                    $test3 = ($test2 && Cache::has(config('dbConfig.prefix_cache') . $technicalName)) ? true : false;
                    return ($test1 && $test2 && $test3) ? 'cache' : false;
                } else {
                    return isset($indexNameId[$technicalName]) ? true : false;
                }
            }

            $tmp = Configuration::where('technical_name', $technicalName)->count();

            return $tmp ? ($infoSup ? 'db' : true) : false;
        }

        return null;
    }

    /**
     * clearConfig
     *
     * @param  mixed $type db,cache,all
     * @return boolean boolean for confirmation
     */
    public static function clearConfig($type = 'all')
    {
        $result = false;
        switch ($type) {
            case 'db':
                $result = Configuration::truncate();
                break;
            case 'cache':
                if (config('cache.default') == 'redis') {
                    $list = Configuration::where('cache_duration', '>', 0)->get();
                    foreach ($list as $configuration) {
                        Cache::forget(config('dbConfig.prefix_cache') . $configuration['technical_name']);
                        if ($configuration->cache_management == 'auto') {
                            Cache::forget(config('dbConfig.prefix_cache_internal') . 'auto_cache:get:' . $configuration->technical_name);
                            Cache::forget(config('dbConfig.prefix_cache_internal') . 'auto_cache:update:' . $configuration->technical_name);
                        }
                    }
                    $indexIdName = Cache::forget(config('dbConfig.prefix_cache_internal') . 'index_id_name');
                    $indexNameId = Cache::forget(config('dbConfig.prefix_cache_internal') . 'index_name_id');
                    $result = true;
                } else {
                    $result = Cache::flush();
                }

                break;
            case 'all':
                $tmp = dbConfig::clearConfig('cache');
                $tmp2 = dbConfig::clearConfig('db');
                $result = ($tmp && $tmp2) ? true : false;
                break;
            default:
                break;
        }
        return $result;
    }

    /**
     * getConfigKeyByCache
     *
     * @param  mixed $key can be string or int use the internal cache index
     * @return mixed null if not found or string or key in opposition with $key
     */
    public static function getConfigKeyByCache($key)
    {

        if (config('dbConfig.useCache')) {
            if (is_int($key)) {
                $indexIdName = Cache::get(config('dbConfig.prefix_cache_internal') . 'index_id_name', array());
                if (count($indexIdName) && isset($indexIdName[strval($key)])) {
                    return $indexIdName[strval($key)];
                }
            } elseif (is_string($key)) {
                $technical_name = Str::slug($key);
                $indexNameId = Cache::get(config('dbConfig.prefix_cache_internal') . 'index_name_id', array());
                if (count($indexNameId) && isset($indexNameId[$technical_name])) {
                    return $indexNameId[$technical_name];
                }
            }
        }
        return null;
    }


    /**
     * updateConfigCacheIndex
     *
     * @param  mixed $operation can be 'set' or 'unset'
     * @param  mixed $name the technical_name of the configuration var
     * @param  mixed $id the id of the configuration var
     * @return boolean
     */
    protected static function updateConfigCacheIndex($operation = 'set', $name, $id)
    {

        if (config('dbConfig.useCache')) {
            if (is_string($name) && is_int($id)) {
                $indexIdName = Cache::get(config('dbConfig.prefix_cache_internal') . 'index_id_name', array());
                $indexNameId = Cache::get(config('dbConfig.prefix_cache_internal') . 'index_name_id', array());
                $operationDone = false;

                switch ($operation) {
                    case 'set':
                        $indexIdName[strval($id)] = $name;
                        $indexNameId[$name] = $id;
                        $operationDone = true;
                        break;
                    case 'unset':
                        if (isset($indexIdName[strval($id)])) {
                            unset($indexIdName[strval($id)]);
                        }

                        if (isset($indexNameId[$name])) {
                            unset($indexNameId[$name]);
                        }
                        $operationDone = true;
                        break;
                    default:
                        break;
                }

                if ($operationDone) {
                    $indexIdName = Cache::set(config('dbConfig.prefix_cache_internal') . 'index_id_name', $indexIdName);
                    $indexNameId = Cache::set(config('dbConfig.prefix_cache_internal') . 'index_name_id', $indexNameId);

                    return ($indexIdName && $indexNameId) ? true : false;
                } else {
                    return false;
                }
            }
            return false;
        }
        return false;
    }



    /**
     * clearAutoCache
     *
     * @param  mixed $technicalName string
     * @return boolean
     */
    protected static function clearAutoCache($technicalName)
    {
        $tmp1 =  Cache::forget(config('dbConfig.prefix_cache_internal') . 'auto_cache:get:' . $technicalName);
        $tmp2 =  Cache::forget(config('dbConfig.prefix_cache_internal') . 'auto_cache:update:' . $technicalName);
        return ($tmp1 && $tmp2) ? true : false;
    }

    /**
     * autoCacheUpdate
     *
     * @param  mixed $configuration the configuration element from db or Cache
     * @param  mixed $from source db or cache
     * @param  mixed $method get or update
     * @return object the configuration data
     */
    protected static function autoCacheUpdate($configuration, $from, $method)
    {
        if ($configuration->cache_management == 'auto' && in_array($from, ['db', 'cache']) && in_array($method, ['get', 'update'])) {

            if (
                (config('dbConfig.cache_auto_get') && $from == 'get') ||
                (config('dbConfig.cache_auto_update') && $from == 'update')
            ) {
                $regenerate_cmi = true;
                $cacheDurationChanged = false;
                $dts = Carbon::now()->timestamp;
                $cache_management_info = Cache::get(config('dbConfig.prefix_cache_internal') . 'auto_cache:' . $method . ':' . $configuration->technical_name, Null);
                if ($cache_management_info) {
                    if ((count($cache_management_info['cache']) + count($cache_management_info['db'])) == config('dbConfig.cache_' . ($from == 'get' ? 'tsg' : 'tsm') . '_floor')) {
                        $ratioGetCache = count($cache_management_info['cache']) / config('dbConfig.cache_' . ($from == 'get' ? 'tsg' : 'tsm') . '_floor');
                        $range = ['min' => (config('dbConfig.cache_' . ($from == 'get' ? 'ssg' : 'ssm') . '_floor') - config('dbConfig.cache_calculus_precision')), 'max' => (config('dbConfig.cache_' . ($from == 'get' ? 'ssg' : 'ssm') . '_floor') + config('dbConfig.cache_calculus_precision'))];
                        if (!($ratioGetCache >= $range['min'] && $ratioGetCache <= $range['max'])) {

                            $new_cache_duration = abs(($configuration->cache_duration * config('dbConfig.cache_' . ($from == 'get' ? 'ssg' : 'ssm') . '_floor')) / $ratioGetCache);
                            $new_cache_duration =  ($new_cache_duration > config('dbConfig.cache_duration_max') ? config('dbConfig.cache_duration_max') : $new_cache_duration);
                            $new_cache_duration =  ($new_cache_duration < config('dbConfig.cache_duration_min') ? config('dbConfig.cache_duration_min') : $new_cache_duration);

                            if ($new_cache_duration != $configuration->cache_duration) {
                                $cacheDurationChanged = true;
                                $configuration->cache_duration = $new_cache_duration;
                                $configuration->save();
                            }
                        }
                    } else {
                        $cache_management_info[$from][] = Carbon::now()->timestamp;
                        Cache::set(config('dbConfig.prefix_cache_internal') . 'auto_cache:' . $method . ':' . $configuration->technical_name, $cache_management_info);
                        $regenerate_cmi = false;
                    }
                }


                if ($regenerate_cmi) {
                    if ($method == 'get') {
                        $cacheGet = dbConfig::CACHE_MANAGEMENT_AUTO_MODEL;
                        if (!$cacheDurationChanged) {
                            $cacheGet[$from][] = $dts;
                        }
                        Cache::set(config('dbConfig.prefix_cache_internal') . 'auto_cache:get:' . $configuration->technical_name, $cacheGet);
                    }

                    if (config('dbConfig.cache_auto_modify')) {
                        $cacheUpdate = dbConfig::CACHE_MANAGEMENT_AUTO_MODEL;
                        if ($cacheDurationChanged) {
                            $cacheUpdate['db'] = $dts;
                        }
                        Cache::set(config('dbConfig.prefix_cache_internal') . 'auto_cache:update:' . $configuration->technical_name, $cacheUpdate);
                    }
                }
            }
        }
        return $configuration;
    }
}
