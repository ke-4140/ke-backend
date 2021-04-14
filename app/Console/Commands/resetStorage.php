<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class resetStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 's3:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete everything from the s3 bucket';

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
     * @return int
     */
    public function handle()
    {
        $this->info('The command will delete everything from the s3 bucket.');
        if ($this->confirm('Do you wish to continue?', false)) {
            
            $this->info('Gathering information...');
            $allFiles = Storage::disk('s3')->allFiles();
            $count = count($allFiles);
            $bar = $this->output->createProgressBar($count);
            $this->info('Deleting files...');
            
            $bar->start();
            foreach ($allFiles as $file) {
                Storage::disk('s3')->delete($file);
                $bar->advance();
            }

            $bar->finish();
            $this->info('');
            $this->info('Done.');
        } else {
            $this->info('Nothing is done. Exiting.');
        }

        
        return 0;
    }
}
