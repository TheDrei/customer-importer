<?php
namespace App\Services\Contracts;

interface CustomerDataProviderInterface
{
    public function getCustomers(int $count = 100): array;
}