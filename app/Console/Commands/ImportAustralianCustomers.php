<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CustomerImporter;
use Exception;

class ImportAustralianCustomers extends Command
{
    protected $signature = 'import:customers {--count=100}';
    protected $description = 'Import Australian customers from external API';

    public function __construct(protected CustomerImporter $importer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $count = (int)$this->option('count');
            $imported = $this->importer->import($count);

            $this->info("Imported or updated {$imported} customers.");
            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
