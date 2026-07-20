<?php

namespace ChangelogCLI\Tests\Feature;

use ChangelogCLI\Changelog;
use ChangelogCLI\Commands\ChangelogCommand;

class ControllableChangelogCommand extends ChangelogCommand
{
    public function __construct(private readonly ?int $option)
    {
        parent::__construct();
    }

    protected function openMenu(Changelog $changelog): ?int
    {
        return $this->option;
    }
}
