<?php
// app/Http/Controllers/Api/CustomerController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Entities\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function __construct(protected EntityManagerInterface $em) {}

    public function index(): JsonResponse
    {
        $repo = $this->em->getRepository(Customer::class);
        $customers = $repo->findAll();

        $data = array_map(function (Customer $customer) {
            return [
                'id' => $customer->getId(),
                'full_name' => $customer->getFirstName() . ' ' . $customer->getLastName(),
                'email' => $customer->getEmail(),
                'country' => $customer->getCountry(),
            ];
        }, $customers);

        return response()->json($data);
    }

    public function show(int $id): JsonResponse
    {
        $customer = $this->em->getRepository(Customer::class)->find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        return response()->json([
            'id' => $customer->getId(),
            'full_name' => $customer->getFirstName() . ' ' . $customer->getLastName(),
            'email' => $customer->getEmail(),
            'username' => $customer->getUsername(),
            'gender' => $customer->getGender(),
            'country' => $customer->getCountry(),
            'city' => $customer->getCity(),
            'phone' => $customer->getPhone(),
        ]);
    }
}
