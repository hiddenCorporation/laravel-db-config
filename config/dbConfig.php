<?php

return [
    /*
    |--------------------------------------------------------------------------
    | UseCache
    |--------------------------------------------------------------------------
    |
    | use the configured cache if true, the expiration time has to be set directly into the configuration item.
    |
    */

    'useCache' => env('DBCONFIG_USECACHE', false),

    /*
    |--------------------------------------------------------------------------
    | prefix_cache
    |--------------------------------------------------------------------------
    |
    | mostly use to avoid collision using a prefix to define perimeter.
    |
    */

    'prefix_cache'          => env('DBCONFIG_PREFIXCACHE', 'dbConfig:'),
    'prefix_cache_internal' => env('DBCONFIG_PREFIXCACHE_INTERNAL', 'dbConfig_internal:'),


    /*
    |--------------------------------------------------------------------------
    | auto cache management
    |--------------------------------------------------------------------------
    |
    | if cache enabled, you can add cache_management option when setting a config.
    | cache_management can be fixed or auto.
    |
    | auto mode is still in evaluation
    |
    | fix : use only cache_duration
    | auto  :
    |
    | if cache_duration is filled, system will register in cache your config with this time.
    | if not this field will take value of cache_duration_default.
    |
    | cache_duration_min : in seconds, the amount of time minimum for auto Cached var
    | cache_duration_max : in seconds, the amount of time maximum for auto Cached var
    | cache_duration_default : in seconds, the amount of time by default for auto Cached var
    |
    | cache_auto_get : activate get process to improve cache_duration
    | cache_auto_modify : activate modification process to improve cache_duration
    |
    |  cache_ssm_floor : between 1 and 0 think of it as a ratio :
    |                     I want 20% of the modification for the same var happening when not cached, my ratio will be 0.8.
    |
    |  cache_tsm_floor : Number of Modification used to recalculate cache_duration think of it like a dataset of ssm.
    |
    |  cache_ssg_floor : between 1 and 0 think of it as a ratio :
    |                     I want 20% of the get for the same var happening when not cached, my ratio will be 0.8.
    |
    |
    |  cache_tsg_floor : Number of get request used to recalculate cache_duration think of it like a dataset of ssg.
    |
    |  cache_ssg_floor and cache_tsg_floor are used to decrease cache_duration of a var
    |
    |  cache_calculus_precision :  between 0 and 1. To avoid an eternal fight between modification and get we can fix an additionnal ratio :
    |    For example for a ccp of 0.02 :
    |    with the ssm of 0.8 the score calculus of a modification has to be between 0.078 and 0.082 to be considered good
    |    same for the get Operation
    |
    */

    'cache_duration_min'        => env('DBCONFIG_CACHE_DURATION_MIN', 600), // 10 minutes
    'cache_duration_max'        => env('DBCONFIG_CACHE_DURATION_MAX', 86400), // one day
    'cache_duration_default'    => env('DBCONFIG_CACHE_DURATION_DEFAULT', 7200), // two hours

    'cache_auto_get'            => env('DBCONFIG_CACHE_AUTO_GET',  TRUE),
    'cache_tsg_floor'           => env('DBCONFIG_CACHE_TSG_FLOOR', 30),
    'cache_ssg_floor'           => env('DBCONFIG_CACHE_SSG_FLOOR',0.8),

    'cache_auto_modify'         => env('DBCONFIG_CACHE_AUTO_MODIFY',  TRUE),
    'cache_ssm_floor'           => env('DBCONFIG_CACHE_SSM_FLOOR', 0.8),
    'cache_tsm_floor'           => env('DBCONFIG_CACHE_TSM_FLOOR',  10),


    'cache_calculus_precision'  => env('DBCONFIG_CACHE_CALCULUS_PRECISION',0.05),

];
