<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait; // Renamed to avoid conflict with class alias
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
// Alias for the base User class
use Tymon\JWTAuth\Contracts\JWTSubject;

class BaseAuthenticateModel extends BaseMasterModel implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract, JWTSubject
{
    use AuthenticatableTrait, Authorizable, CanResetPassword, MustVerifyEmail;

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
