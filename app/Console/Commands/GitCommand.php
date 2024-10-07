<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'git:push';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'set push and pull command';

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
        $commit = $this->ask('Commit Message: ');
        $branch = $this->anticipate('which branch? (example: master )', ['master']);
        $this->info('branch : origin '.$branch);
        $this->info('commit message : '.$commit);

        if ($this->confirm('Do you wish to continue?', true)) {
            exec('git add .',$output);
            $this->info(implode(" ",$output));
            exec('git commit -m "'.$commit.'"',$output);
            $this->info(implode(" ",$output));
            exec('git pull origin '.$branch,$output);
            $this->info(implode(" ",$output));
            exec('git push origin '.$branch,$output);
            $this->info(implode(" ",$output));
            $this->info('command git completed');
            return 0;
        }
        

        $this->info('command git Canceled');
        return 0;
    }
}