<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use App\Services\Contracts\CustomerDataProviderInterface;
use App\Entities\Customer;
use Illuminate\Support\Arr;

class CustomerImporter
{
    public function __construct(
        protected CustomerDataProviderInterface $provider,
        protected EntityManagerInterface $em
    ) {}

    public function import(int $count = 100): int
    {
        try {
            $customers = $this->provider->getCustomers($count);
        } catch (\Throwable $e) {
            return 0; // Gracefully handle API error
        }

        if (!is_array($customers)) {
            return 0; // API returned invalid format
        }

        $repo = $this->em->getRepository(Customer::class);
        $imported = 0;

        foreach ($customers as $data) {
            $email = Arr::get($data, 'email');

            if (empty($email)) {
                continue; // Skip customers with no email
            }

            $existing = $repo->findOneBy(['email' => $email]);
            $isNew = $existing === null;
            $customer = $existing ?? new Customer();

            $customer->setFirstName(Arr::get($data, 'name.first', ''));
            $customer->setLastName(Arr::get($data, 'name.last', ''));
            $customer->setEmail($email);
            $customer->setUsername(Arr::get($data, 'login.username', ''));
            $customer->setGender(Arr::get($data, 'gender', ''));
            $customer->setCountry(Arr::get($data, 'location.country', ''));
            $customer->setCity(Arr::get($data, 'location.city', ''));
            $customer->setPhone(Arr::get($data, 'phone', ''));
            $customer->setPassword(md5(Arr::get($data, 'login.password', '')));

            if ($isNew) {
                $this->em->persist($customer);
                $imported++;
            }
        }

        $this->em->flush();
        return $imported;
    }
}