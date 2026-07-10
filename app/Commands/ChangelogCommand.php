<?php

namespace ChangelogCLI\Commands;

use ChangelogCLI\Changelog;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ChangelogCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'create';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new changelog file';

    /**
     * Execute the console command.
     *
     * @param Changelog $changelog
     */
    public function handle(Changelog $changelog): void
    {
        $option = $this->openMenu($changelog);

        if (is_null($option)) {
            $this->components->warn('Changelog cancelled.');

            return;
        }

        $this->present($changelog, $option);
    }

    /**
     * Render output after a category option has been selected.
     */
    public function present(Changelog $changelog, int $option): void
    {
        $created = $changelog->execute($option);

        if ($created) {
            $this->notify('Changelog Cli', "#{$changelog->menuItems[$option]} changelog file successfully created.");
            $this->components->info(sprintf('Changelog [%s] created successfully.', $changelog->filePath()));

            if ($this->output->isVerbose()) {
                $this->components->twoColumnDetail('Category', $changelog->menuItems[$option]);
            }
        } else {
            $this->components->error('Failed to create changelog file.');
        }
    }

    /**
     * Open interactive category menu and return selected option key.
     */
    protected function openMenu(Changelog $changelog): ?int
    {
        return $this->menu($changelog->menuName, $changelog->menuItems)
            ->setForegroundColour('green')
            ->setBackgroundColour('black')
            ->open();
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
