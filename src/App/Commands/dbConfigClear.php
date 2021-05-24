<?php

namespace hiddenCorporation\dbConfig\App\Commands;

use stdClass;
use Illuminate\Console\Command;
use hiddenCorporation\dbConfig\dbConfig;


class dbConfigClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbconfig:clear {--which=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to clear dbConfig accept a which argument with db,cache,all (redis cache will match a specific pattern, all is just flush)';

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
        $operation = $this->option('which');
        $this->info('Command : '.$operation);
        $tmp = dbConfig::clearConfig($operation);
        $this->info('dbConfig Clear');
    }
}
