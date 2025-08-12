<?php

namespace Fluxtor\Cli\Commands;

use Illuminate\Support\Facades\Process;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;

class InitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fluxtor:init {--dark : add dark mode setup.} {--phosphoricons : installing phosphor icons.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize the fluxtor package.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $installPhosphorIcons = $this->option('phosphoricons') ?? text(label: "Do You want to install `phosphoricons`?", placeholder: 'Y/N', required: false);

        Process::run('composer require wireui/heroicons');

        if ($installPhosphorIcons) {
            Process::run('composer require wireui/phosphoricons');
        }
    }
}
