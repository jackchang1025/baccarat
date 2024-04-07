<?php

namespace App\Baccarat\Service\Output;

use Carbon\Carbon;
use Hyperf\Command\Concerns\InteractsWithIO;
use Hyperf\Coroutine\Coroutine;
use Symfony\Component\Console\Style\SymfonyStyle;

trait TraitOutput
{
    use InteractsWithIO {
        InteractsWithIO::line as interactsWithLine;
    }

    public function line($string, $style = null, $verbosity = null): void
    {
        $str = Carbon::now();

        if (Coroutine::inCoroutine()){
            $str .= " Coroutine Id:".Coroutine::id()." ";
        }
        $str .= $string;

        $this->interactsWithLine($str,$style,$verbosity);
    }
}