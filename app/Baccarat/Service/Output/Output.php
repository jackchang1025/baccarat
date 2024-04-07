<?php

namespace App\Baccarat\Service\Output;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Output
{
    use TraitOutput;

    public function __construct(
        protected ConsoleOutput $consoleOutput,
        protected ArgvInput $argvInput,
    )
    {
        $this->output = new SymfonyStyle($this->argvInput, $this->consoleOutput);
    }
}