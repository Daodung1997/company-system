<?php

namespace App\Services\Common;

use App\Constants\Commons\CommonStatusConst;
use App\Constants\Master\Models\Customer\CustomerColumn;
use App\Constants\Master\Resource\LocationResourceConst;
use App\Exceptions\InvalidAuthException;
use App\Http\Requests\Common\AuthCustomer\LoginCustomerRequest;
use App\Http\Requests\Customer\RegisterRequest;
use App\Mail\RegisterMail;
use App\Models\Customer;
use App\Repositories\Repository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class CustomerAuthService extends BaseAuthService
{
    private Repository $customerRepo;

    public function __construct(Customer $customer)
    {
        $this->model = $customer;
        $this->customerRepo = new Repository($customer);
        $this->guard = 'customer';
    }

    public function login(LoginCustomerRequest $request): ?array
    {
        $user = $this->customerRepo->findWhere([
            CustomerColumn::EMAIL => $request->{CustomerColumn::EMAIL},
            CustomerColumn::STATUS => CommonStatusConst::ACTIVE,
        ])->first();

        if (empty($user)) {
            return null;
        }

        $response = Http::asForm()
            ->when(app()->environment('local'), fn ($req) => $req->withoutVerifying())
            ->post(config('app.url').'/oauth/token', [
                'grant_type' => 'password',
                'client_id' => config('passport.personal_access_client.id'),
                'client_secret' => config('passport.personal_access_client.secret'),
                'username' => $request->{CustomerColumn::EMAIL},
                'password' => $request->{CustomerColumn::PASSWORD},
                'scope' => '',
            ]);

        if ($response->failed()) {
            throw new InvalidAuthException;
        }

        return $response->json();
    }

    public function refresh($request)
    {
        $response = Http::asForm()
            ->when(app()->environment('local'), fn ($req) => $req->withoutVerifying())
            ->post(config('app.url').'/oauth/token', [
                'grant_type' => 'refresh_token',
                'client_id' => config('passport.personal_access_client.id'),
                'client_secret' => config('passport.personal_access_client.secret'),
                'refresh_token' => $request->refresh_token,
                'scope' => '',
            ]);

        if ($response->failed()) {
            throw new InvalidAuthException;
        }

        return $response->json();
    }

    /**
     * @throws \Exception
     */
    public function register(RegisterRequest $request): ?Customer
    {
        $data = $request->validated();
        $password = $data[CustomerColumn::PASSWORD];
        $data[CustomerColumn::PASSWORD] = bcrypt($password);

        try {
            $this->beginTransaction();
            $customer = $this->model->create($data);
            $this->commitTransaction();

            Mail::to($customer->email)->queue(new RegisterMail([
                LocationResourceConst::NAME => $customer->first_name.' '.$customer->last_name,
                CustomerColumn::EMAIL => $customer->email,
                CustomerColumn::PASSWORD => $password,
            ], $customer->{CustomerColumn::LOCALE}));

            return $customer;
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }

    public function loginGoogle($request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($request->token);

            $customer = $this->customerRepo->findWhere([
                CustomerColumn::EMAIL => $googleUser->email,
            ])->first();

            if (! $customer) {
                $customer = $this->customerRepo->create([
                    CustomerColumn::EMAIL => $googleUser->email,
                    CustomerColumn::FIRST_NAME => $googleUser->user['given_name'] ?? 'User',
                    CustomerColumn::LAST_NAME => $googleUser->user['family_name'] ?? rand(1000, 9999),
                    CustomerColumn::PASSWORD => bcrypt(Str::random()),
                ]);
            }

            // update google id
            if (empty($customer->google_id)) {
                $customer->google_id = $googleUser->id;
                $customer->save();
            }

            $originalPassword = $customer->password;

            $tempPassword = Str::random();
            $customer->password = bcrypt($tempPassword);
            $customer->save();

            $response = Http::asForm()
                ->when(app()->environment('local'), fn ($req) => $req->withoutVerifying())
                ->post(config('app.url').'/oauth/token', [
                    'grant_type' => 'password',
                    'client_id' => config('passport.personal_access_client.id'),
                    'client_secret' => config('passport.personal_access_client.secret'),
                    'username' => $customer->email,
                    'password' => $tempPassword,
                    'scope' => '',
                ]);

            if ($response->failed()) {
                throw new InvalidAuthException;
            }

            // back old password
            $customer->password = $originalPassword;
            $customer->save();

            return $response->json();
        } catch (\Exception $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}
