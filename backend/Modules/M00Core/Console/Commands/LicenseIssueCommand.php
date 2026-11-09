<?php

declare(strict_types=1);

namespace Modules\M00Core\Console\Commands;

use Illuminate\Console\Command;
use Modules\M00Core\Services\LicenseService;

class LicenseIssueCommand extends Command
{
    protected $signature = 'license:issue 
                            {--client=RentFleet Algeria : Client name}
                            {--email=client@rentfleet.dz : Client email}
                            {--modules=M00Core,M01Fleet,M02Customers : Comma-separated module IDs}
                            {--days=365 : Validity in days}
                            {--max-vehicles=100 : Maximum vehicles}
                            {--max-branches=10 : Maximum branches}
                            {--activate : Immediately activate the issued license}';

    protected $description = 'Issue and optionally activate a RentFleet module license';

    public function handle(LicenseService $licenseService): int
    {
        $client = (string) $this->option('client');
        $email = (string) $this->option('email');
        $modules = array_filter(array_map('trim', explode(',', (string) $this->option('modules'))));
        $days = (int) $this->option('days');
        $maxVehicles = (int) $this->option('max-vehicles');
        $maxBranches = (int) $this->option('max-branches');

        $result = $licenseService->issueLicense($client, $email, $modules, $days, $maxVehicles, $maxBranches);

        $this->info('License issued successfully:');
        $this->line('License Key: ' . $result['license_key']);
        $this->line('Modules:     ' . implode(', ', $modules));
        $this->line('Expires at:  ' . $result['payload']['expires_at']);
        $this->newLine();
        $this->line('Token:');
        $this->comment($result['token']);

        if ($this->option('activate')) {
            $licenseService->activate($result['token']);
            $this->info('License activated immediately!');
        }

        return self::SUCCESS;
    }
}
