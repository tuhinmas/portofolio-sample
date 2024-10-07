<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GitTag extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'git:tag';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for tagging version';

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
        exec('git fetch',$output);
        $this->info(implode(" ",$output));
        exec('git tag --sort=committerdate | tail -1',$output);
        $currentTag =implode(" ",$output);
        $this->info('Current Tag : '.$currentTag);
        $continueTag = explode('.', $currentTag);

        if ($this->confirm('Do you wish to create tag '.$continueTag[0].'.'.$continueTag[1].'.'.(intval($continueTag[2])+1).' ?', true)) 
        {
            $newTag = $continueTag[0].'.'.$continueTag[1].'.'.(intval($continueTag[2])+1);
        }else{
            $newTag = $this->ask('Create New Tag Version?');
        }

        if($this->confirm('Do you wish to continue ? tag : '.$newTag ,true)) 
        {
            exec('git pull origin master',$output);
            $this->info(implode(" ",$output));
            
            exec('git tag -a '.$newTag.' -m "release version '.$newTag.'"',$output);
            $this->info(implode(" ",$output));

            exec('git push origin '.$newTag,$output);
            $this->info(implode(" ",$output));

            $this->info('Tag Command Completed');
            $this->info('Uploaded Tag : '.$newTag);
            return 0;
        }
        $this->info('Tag Command Canceled');
        return 0;
    }
}
