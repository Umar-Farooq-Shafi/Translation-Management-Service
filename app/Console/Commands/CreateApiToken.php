<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateApiToken extends Command
{
    protected $signature = 'token:create {name=default}';
    protected $description = 'Create a new API bearer token';

    public function handle(): int
    {
        $name = (string) $this->argument('name');
        $plain = Str::random(64);
        $hash  = hash('sha256', $plain);

        ApiToken::create([
            'name' => $name,
            'token_hash' => $hash,
            'abilities' => [],
        ]);

        $this->info('Token Name: ' . $name);
        $this->info('Bearer Token (store securely): ' . $plain);

        return 0;
    }
}
