<?php

namespace App\Http\Controllers;

use App\Traits\ResponseHandlerV2;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Artisan;

class PM2Installer extends Controller
{
    use ResponseHandlerV2;

    public function __invoke()
    {
        try {
            $scriptPath = base_path('opt/elasticbeanstalk/hooks/appdeploy/pre/10_pm2_start.sh');

            // Run the shell script
            $output = shell_exec("sh {$scriptPath}");

            // Create a new Process instance
            $process = new Process(["sh", $scriptPath]);

            // Run the process
            $process->run();

            // Get the output
            $output = $process->getOutput();

            return $this->response("00", "succes", [
                "output" => $output,
                "pathc" => $scriptPath
            ]);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th);
        }

    }
}
