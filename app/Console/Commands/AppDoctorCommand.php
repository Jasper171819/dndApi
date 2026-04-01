<?php
// Developer context: Project-owned source file; keep its responsibility narrow and consistent with the rest of the app.
// Clear explanation: This command prints a friendly checklist so someone setting up the app can see what is ready and what still needs attention.

namespace App\Console\Commands;

use App\Support\SetupDoctor;
use Illuminate\Console\Command;

class AppDoctorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:doctor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run startup checks for local ZIP-based setup';

    public function __construct(protected SetupDoctor $doctor)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $report = $this->doctor->inspect();

        $this->line('Startup doctor');
        $this->line(str_repeat('-', 14));

        foreach ($report['context'] as $line) {
            $this->line($line);
        }

        $this->printSection('PASS', 'info', $report['passes']);
        $this->printSection('WARN', 'warn', $report['warnings']);
        $this->printSection('FAIL', 'error', $report['failures']);

        if ($report['failures'] !== []) {
            $this->newLine();
            $this->error('Fix the failed items above, then rerun composer run doctor or composer run setup-local.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Startup checks passed. If this is your first run, use composer run setup-local. Otherwise, use composer run start-local.');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $lines
     */
    protected function printSection(string $prefix, string $method, array $lines): void
    {
        if ($lines === []) {
            return;
        }

        $this->newLine();

        foreach ($lines as $line) {
            $this->{$method}("[{$prefix}] {$line}");
        }
    }
}
