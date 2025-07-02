<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Services\Contracts\CustomerDataProviderInterface;
use Exception;

class RandomUserDataProvider implements CustomerDataProviderInterface
{
    public function getCustomers(int $count = 100): array
    {
        $url = config('customer_importer.api_url');
        logger()->info('API URL from config:', ['url' => $url]);

        // 🔍 Quick fail-safe for debugging
        if (!$url) {
            throw new Exception('API URL is null. Check RANDOM_USER_API in .env and config.');
        }

        $response = Http::get($url, [
            'results' => $count,
            'nat' => 'AU'
        ]);

        if ($response->failed()) {
            throw new Exception('Failed to fetch data from provider.');
        }

        return $response->json('results');
    }

}
