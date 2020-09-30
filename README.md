# laravel-db-config

> A package to manage configuration items and their properties in Laravel.
> php laravel configuration cache db

## What it is

Another library to handle configuration data in laravel, much more an exercice to handle gitHub/Packagist sync.
This library.

In addition to allowing you to simply save variables in database, this package allows you to:

- Add a description to these variables.

- Standardize the caching of those variables (setting up indexes, updating the cache time, updating the cached config var).

- On-the-fly encryption / decryption of values.

- Automatically retrieving multiple configuration values.

## Installation

### Using GitHub

git clone `https://github.com/hiddenCorporation/laravel-db-config.git`

### Using Laravel Packager

Using Laravel Packager `https://github.com/Jeroen-G/laravel-packager`. You can use artisan to integrate it in your laravel app.

`https://github.com/Jeroen-G/laravel-packager`


### Using Composer

```bash
$ composer require hiddencorporation/laravel-db-config 
```

## Configuration

After having install the package, you need to :

**Publish configuration var for the package**

```bash
$ php artisan vendor:publish --tag=dbConfig.config
```

**Migrate database layout**

```bash
$ php artisan migrate
```

## Configuration file & env

** (optional) change behaviour of package **

To set in your .env :

- **DBCONFIG_USECACHE** 
use cache or not.

- **DBCONFIG_PREFIXCACHE** & **DBCONFIG_PREFIXCACHE_INTERNAL**
mostly use to avoid collision using a prefix to define perimeter.

- **auto cache management _experimental_** - 

If cache enabled, you can add cache_management option when setting a config.
cache_management can be fix or auto. (fix is the default value). 

Auto mode is still in evaluation.

- fix : use only cache_duration
- auto  :

If cache_duration is filled, system will register in cache your config with this time.
If not this field will take value of cache_duration_default.

- **DBCONFIG_CACHE_AUTO_GET** 
Activate get process to improve cache_duration.

- **DBCONFIG_CACHE_AUTO_MODIFY** 
Activate modify process to improve cache_duration.

- **DBCONFIG_CACHE_DURATION_MIN**
In seconds, the minimum amount of time for auto Cached var.

- **DBCONFIG_CACHE_DURATION_MAX**
In seconds, the maximum amount of time for auto Cached var.

- **DBCONFIG_CACHE_DURATION_DEFAULT**
In seconds, the amount of time by default for auto Cached var.

- **DBCONFIG_CACHE_SSM_FLOOR** 
Between 1 and 0 think of it as a ratio :
I want 20% of the modification for the same var happening when not cached, my ratio will be 0.8.

- **DBCONFIG_CACHE_TSM_FLOOR**
Number of Modification used to recalculate cache_duration think of it like a dataset of ssm.

- **DBCONFIG_CACHE_SSG_FLOOR**
Between 1 and 0 think of it as a ratio :
I want 20% of the get for the same var happening when not cached, my ratio will be 0.8.

- **DBCONFIG_CACHE_TSG_FLOOR**
Number of get request used to recalculate cache_duration think of it like a dataset of ssg. 

cache_ssg_floor and cache_tsg_floor are used to decrease cache_duration of a var.

- **DBCONFIG_CACHE_CALCULUS_PRECISION**
Between 0 and 1. To avoid an eternal fight between modification and get we can fix an additionnal ratio :
For example for a ccp of 0.02 :

with the ssm of 0.8 the score calculus of a modification has to be between 0.078 and 0.082 to be considered good
same for the get Operation

## Methods

- **dbConfig::set($name,$value,$additionalAttribute=array())**

1. **$name** the name of the var will be slugify to forge a *technical_name*.
2. **$value** can be all kind of var
3. **$additionalAttribute**
    _ **description** (string)
    _ **cache_duration** (int), Seconds use for fix cache or to set a duration to begin when in auto.
    _ **cache_management** fix or auto by default fixed.
    _ **crypted** (bool)

return  An array with operation status and the element if created, if not list of errors.

- **dbConfig::get($entity=false,$defaultReturnValue = Null,$full=false)**

1. **$entity** the config id, the technical Name of a config element or a mixed array of both.
2. **$defaultReturnValue** (defaut = Null) The default return value you want.
3. **$full** if true will return all info about the configuration var

It can return null if data not found, an array of var if entity is an array,a single value or the value with all info if full = true.

- **dbConfig::update($entity=false,$value=Null,$additionalAttribute=array())**

1. **$entity** $entity_id or technical_name
2. **$value** (default Null), the new value or nothing if you just want to manage additional attributes.
3. **$additionalAttribute**  *see dbConfig::set*

Return An array with the detail of the operation and the configuration element if found.

- **dbConfig::unset($entity)**

1. **$entity** entity id or technical_name
2. **$infoSup** (default false) To get more intel about where the config has been found.

Return *cache*, *db* or *true/false* depends if $infoSup is true

Return Null, true or false

- **dbConfig::configExist($entity,$infoSup=false)**


## Commands

```bash
$ php artisan dbConfig:clear --operation=[db,cache,all] 
```

*The clear cache specifically target elements used by dbConfig leaving other cached things intacts*

```bash
$ php artisan dbConfig:test --which=[info, testCache, testCrypt, testGeneral] 
```

*The test has been done for basic use, auto cache management has not been tested at the moment.*


## Unit test

```bash
$ vendor/bin/phpunit packages/Hephaistos/dbConfig
```

*The testUnits replicate test of dbConfig:test*


## Roadmap

1. test auto mode
2. create unit test for benchmarking the package for the auto Cache Management
