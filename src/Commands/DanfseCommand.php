<?php

namespace Danfse\Danfse\Commands;

use Illuminate\Console\Command;

class DanfseCommand extends Command
{
    public $signature = 'danfse-php';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
