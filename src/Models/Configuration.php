<?php

namespace hiddenCorporation\dbConfig\Models;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model{

    protected $table = 'configurations';

    protected $attributes = array(
        'crypted' => FALSE,
        'cache_duration'=>NULL,
        'cache_management'=>'fix'
    );

    protected $fillable=['name','technical_name','description','type','value','crypted','cache_duration','cache_management'];

}
