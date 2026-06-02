<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Repositories\Repository;
use App\Services\Common\CustomerAuthService;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    private Repository $customerRepo;

    public function __construct(protected CustomerAuthService $authService,
        Customer $customer)
    {
        $this->customerRepo = new Repository($customer);
    }

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback() {}
}
