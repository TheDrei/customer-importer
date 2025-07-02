<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\CustomerImporter;
use Illuminate\Support\Facades\Http;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

class CustomerImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        $em = $this->app->make(EntityManagerInterface::class);
        $tool = new SchemaTool($em);
        $classes = $em->getMetadataFactory()->getAllMetadata();

        $tool->dropSchema($classes);
        $tool->createSchema($classes);
    }

    public function test_it_imports_customers_from_mocked_api(): void
    {
            Http::fake([
                'https://randomuser.me/api*' => Http::response([
                    'results' => [
                        [
                            'gender' => 'male',
                            'name' => ['first' => 'John', 'last' => 'Doe'],
                            'email' => 'john.doe@example.com',
                            'login' => [
                                'username' => 'johndoe',
                                'password' => 'secret123'
                            ],
                            'location' => [
                                'country' => 'Australia',
                                'city' => 'Sydney',
                            ],
                            'phone' => '0400 000 000'
                        ]
                    ]
                ], 200),
            ]);

            $importer = $this->app->make(CustomerImporter::class);
            $count = $importer->import(1);

            $this->assertEquals(1, $count);
    }
    

    public function test_it_updates_existing_customer_by_email(): void
    {
        // Prepare two fake API responses in sequence
        Http::fakeSequence()
            ->push([
                'results' => [
                    [
                        'gender' => 'male',
                        'name' => ['first' => 'John', 'last' => 'Doe'],
                        'email' => 'john.doe@example.com',
                        'login' => [
                            'username' => 'johndoe',
                            'password' => 'secret123',
                        ],
                        'location' => [
                            'country' => 'Australia',
                            'city' => 'Sydney',
                        ],
                        'phone' => '0400 000 000',
                    ]
                ]
            ], 200)
            ->push([
                'results' => [
                    [
                        'gender' => 'male',
                        'name' => ['first' => 'Jonathan', 'last' => 'Doe'],
                        'email' => 'john.doe@example.com',
                        'login' => [
                            'username' => 'jonnydoe',
                            'password' => 'newpass123',
                        ],
                        'location' => [
                            'country' => 'Australia',
                            'city' => 'Melbourne',
                        ],
                        'phone' => '0411 111 111',
                    ]
                ]
            ], 200);

        // First import
        $importer = $this->app->make(CustomerImporter::class);
        $importer->import(1);

        $em = $this->app->make(EntityManagerInterface::class);
        $repo = $em->getRepository(\App\Entities\Customer::class);
        $original = $repo->findOneBy(['email' => 'john.doe@example.com']);
        $this->assertNotNull($original);
        $this->assertEquals('John', $original->getFirstName());
        $this->assertEquals('Sydney', $original->getCity());

        // Clear to avoid stale object caching
        $em->clear();

        // Second import (should update)
        $importer = $this->app->make(CustomerImporter::class);
        $importer->import(1);

        $em->clear(); // extra safety
        $repo = $em->getRepository(\App\Entities\Customer::class);
        $updated = $repo->findOneBy(['email' => 'john.doe@example.com']);

        $this->assertNotNull($updated);
        $this->assertEquals('Jonathan', $updated->getFirstName());   // ✅ updated
        $this->assertEquals('Melbourne', $updated->getCity());       // ✅ updated
        $this->assertEquals('jonnydoe', $updated->getUsername());    // ✅ updated

        // Ensure no duplicates
        $this->assertCount(1, $repo->findAll());
    }


    public function test_it_inserts_multiple_new_customers_with_unique_emails(): void
    {
        Http::fake([
            'https://randomuser.me/api*' => Http::response([
                'results' => [
                    [
                        'gender' => 'female',
                        'name' => ['first' => 'Alice', 'last' => 'Smith'],
                        'email' => 'alice@example.com',
                        'login' => [
                            'username' => 'alicesmith',
                            'password' => 'password1',
                        ],
                        'location' => [
                            'country' => 'Australia',
                            'city' => 'Brisbane',
                        ],
                        'phone' => '0400 111 111',
                    ],
                    [
                        'gender' => 'male',
                        'name' => ['first' => 'Bob', 'last' => 'Jones'],
                        'email' => 'bob@example.com',
                        'login' => [
                            'username' => 'bobbyj',
                            'password' => 'password2',
                        ],
                        'location' => [
                            'country' => 'Australia',
                            'city' => 'Perth',
                        ],
                        'phone' => '0400 222 222',
                    ]
                ]
            ], 200)
        ]);

        $importer = $this->app->make(CustomerImporter::class);
        $importedCount = $importer->import(2);

        $em = $this->app->make(EntityManagerInterface::class);
        $repo = $em->getRepository(\App\Entities\Customer::class);
        $all = $repo->findAll();

        $this->assertEquals(2, $importedCount);
        $this->assertCount(2, $all);

        $emails = array_map(fn($c) => $c->getEmail(), $all);
        $this->assertContains('alice@example.com', $emails);
        $this->assertContains('bob@example.com', $emails);
    }

    public function test_it_skips_customer_with_missing_email(): void
    {
        Http::fake([
            'https://randomuser.me/api*' => Http::response([
                'results' => [
                    [
                        'gender' => 'male',
                        'name' => ['first' => 'Ghost', 'last' => 'User'],
                        // Missing 'email'
                        'login' => [
                            'username' => 'ghostuser',
                            'password' => 'boo123',
                        ],
                        'location' => [
                            'country' => 'Nowhere',
                            'city' => 'Nulltown',
                        ],
                        'phone' => '0000 000 000',
                    ]
                ]
            ], 200)
        ]);

        $importer = $this->app->make(CustomerImporter::class);
        $imported = $importer->import(1);

        $this->assertEquals(0, $imported); // Should not import
    }

    public function test_it_handles_api_error_response(): void
    {
        Http::fake([
            'https://randomuser.me/api*' => Http::response(null, 500)
        ]);

        $importer = $this->app->make(CustomerImporter::class);
        $imported = $importer->import(1);

        $this->assertEquals(0, $imported); // Nothing imported on error
    }

    public function test_it_defaults_missing_optional_fields(): void
    {
        Http::fake([
            'https://randomuser.me/api*' => Http::response([
                'results' => [
                    [
                        'email' => 'partial@example.com',
                        // missing name, location, etc.
                        'login' => ['username' => 'partialuser', 'password' => '123'],
                    ]
                ]
            ])
        ]);

        $importer = $this->app->make(CustomerImporter::class);
        $imported = $importer->import(1);

        $this->assertEquals(1, $imported);

        $em = $this->app->make(EntityManagerInterface::class);
        $repo = $em->getRepository(\App\Entities\Customer::class);
        $c = $repo->findOneBy(['email' => 'partial@example.com']);

        $this->assertNotNull($c);
        $this->assertEquals('', $c->getFirstName()); // default
        $this->assertEquals('partialuser', $c->getUsername());
    }

    public function test_it_handles_empty_results_array(): void
    {
        Http::fake([
            'https://randomuser.me/api*' => Http::response(['results' => []], 200)
        ]);

        $importer = $this->app->make(CustomerImporter::class);
        $imported = $importer->import(1);

        $this->assertEquals(0, $imported);
    }

    public function test_it_handles_missing_results_key(): void
    {
        Http::fake([
            'https://randomuser.me/api*' => Http::response(['foo' => 'bar'], 200)
        ]);

        $importer = $this->app->make(CustomerImporter::class);
        $imported = $importer->import(1);

        $this->assertEquals(0, $imported);
    }

    public function test_it_handles_very_long_field_values(): void
    {
        $longName = str_repeat('A', 255);
        $longCity = str_repeat('Z', 255);

        Http::fake([
            'https://randomuser.me/api*' => Http::response([
                'results' => [
                    [
                        'gender' => 'female',
                        'name' => ['first' => $longName, 'last' => 'Smith'],
                        'email' => 'long@example.com',
                        'login' => [
                            'username' => $longName,
                            'password' => 'password123'
                        ],
                        'location' => [
                            'country' => 'Australia',
                            'city' => $longCity
                        ],
                        'phone' => '0999 999 999'
                    ]
                ]
            ], 200)
        ]);

        $importer = $this->app->make(\App\Services\CustomerImporter::class);
        $imported = $importer->import(1);

        $this->assertEquals(1, $imported);

        $em = $this->app->make(\Doctrine\ORM\EntityManagerInterface::class);
        $repo = $em->getRepository(\App\Entities\Customer::class);
        $c = $repo->findOneBy(['email' => 'long@example.com']);

        $this->assertNotNull($c);
        $this->assertEquals($longName, $c->getFirstName());
        $this->assertEquals($longCity, $c->getCity());
    }


    public function test_it_handles_empty_strings_in_fields(): void
    {
        Http::fake([
            'https://randomuser.me/api*' => Http::response([
                'results' => [
                    [
                        'gender' => '',
                        'name' => ['first' => '', 'last' => ''],
                        'email' => 'empty@example.com',
                        'login' => [
                            'username' => '',
                            'password' => ''
                        ],
                        'location' => [
                            'country' => '',
                            'city' => ''
                        ],
                        'phone' => ''
                    ]
                ]
            ])
        ]);

        $importer = $this->app->make(\App\Services\CustomerImporter::class);
        $imported = $importer->import(1);

        $this->assertEquals(1, $imported); // still valid structure

        $em = $this->app->make(\Doctrine\ORM\EntityManagerInterface::class);
        $repo = $em->getRepository(\App\Entities\Customer::class);
        $c = $repo->findOneBy(['email' => 'empty@example.com']);

        $this->assertNotNull($c);
        $this->assertEquals('', $c->getFirstName());
        $this->assertEquals('', $c->getCity());
    }

    public function test_it_imports_with_minimal_required_data(): void
    {
        Http::fake([
            'https://randomuser.me/api*' => Http::response([
                'results' => [
                    [
                        'email' => 'minimal@example.com',
                        'login' => ['username' => 'minuser', 'password' => '123']
                    ]
                ]
            ])
        ]);

        $importer = $this->app->make(\App\Services\CustomerImporter::class);
        $imported = $importer->import(1);

        $this->assertEquals(1, $imported);

        $em = $this->app->make(\Doctrine\ORM\EntityManagerInterface::class);
        $repo = $em->getRepository(\App\Entities\Customer::class);
        $c = $repo->findOneBy(['email' => 'minimal@example.com']);

        $this->assertNotNull($c);
        $this->assertEquals('minuser', $c->getUsername());
        $this->assertEquals('', $c->getFirstName());
    }

}
