<?php

namespace App\Constants\Commons;

use App\Traits\ConstTrait;

class CommonPermissionConst
{
    use ConstTrait;

    // Admin Management
    const ADMINS_VIEW = 'admins.view';

    const ADMINS_CREATE = 'admins.create';

    const ADMINS_UPDATE = 'admins.update';

    const ADMINS_DELETE = 'admins.delete';

    // Role Management
    const ROLES_VIEW = 'roles.view';

    const ROLES_CREATE = 'roles.create';

    const ROLES_UPDATE = 'roles.update';

    const ROLES_DELETE = 'roles.delete';

    // Customer Management
    const CUSTOMERS_VIEW = 'customers.view';

    const CUSTOMERS_UPDATE = 'customers.update';

    const CUSTOMERS_BLOCK = 'customers.block';

    // Worker Management
    const WORKERS_VIEW = 'workers.view';

    const WORKERS_UPDATE = 'workers.update';

    const WORKERS_APPROVE = 'workers.approve';

    const WORKERS_REJECT = 'workers.reject';

    const WORKERS_SUSPEND = 'workers.suspend';

    const WORKERS_ACTIVATE = 'workers.activate';

    // Job Management
    const JOBS_VIEW = 'jobs.view';

    const JOBS_RESOLVE = 'jobs.resolve';

    // Finance - Payments
    const FINANCE_PAYMENTS_VIEW = 'finance.payments.view';

    const FINANCE_PAYMENTS_REFUND = 'finance.payments.refund';

    // Finance - Withdrawals
    const FINANCE_WITHDRAWALS_VIEW = 'finance.withdrawals.view';

    const FINANCE_WITHDRAWALS_APPROVE = 'finance.withdrawals.approve';

    const FINANCE_WITHDRAWALS_REJECT = 'finance.withdrawals.reject';

    // Finance - Statistics
    const FINANCE_STATISTICS_VIEW = 'finance.statistics.view';

    // Configuration
    const CONFIGURATION_VIEW = 'configuration.view';

    const CONFIGURATION_UPDATE = 'configuration.update';

    // Configuration - Categories
    const CATEGORIES_VIEW = 'categories.view';

    const CATEGORIES_CREATE = 'categories.create';

    const CATEGORIES_UPDATE = 'categories.update';

    const CATEGORIES_DELETE = 'categories.delete';

    // Configuration - Fees
    const FEES_VIEW = 'fees.view';

    const FEES_UPDATE = 'fees.update';

    // Promotions - Discounts/Vouchers
    const PROMOTIONS_VIEW = 'promotions.view';

    const PROMOTIONS_CREATE = 'promotions.create';

    const PROMOTIONS_UPDATE = 'promotions.update';

    const PROMOTIONS_DELETE = 'promotions.delete';

    /**
     * Get permissions grouped by module for display purposes.
     */
    public static function getGrouped(): array
    {
        return [
            'admins' => [
                self::ADMINS_VIEW,
                self::ADMINS_CREATE,
                self::ADMINS_UPDATE,
                self::ADMINS_DELETE,
            ],
            'roles' => [
                self::ROLES_VIEW,
                self::ROLES_CREATE,
                self::ROLES_UPDATE,
                self::ROLES_DELETE,
            ],
            'customers' => [
                self::CUSTOMERS_VIEW,
                self::CUSTOMERS_UPDATE,
                self::CUSTOMERS_BLOCK,
            ],
            'workers' => [
                self::WORKERS_VIEW,
                self::WORKERS_UPDATE,
                self::WORKERS_APPROVE,
                self::WORKERS_REJECT,
                self::WORKERS_SUSPEND,
                self::WORKERS_ACTIVATE,
            ],
            'jobs' => [
                self::JOBS_VIEW,
                self::JOBS_RESOLVE,
            ],
            'finance.payments' => [
                self::FINANCE_PAYMENTS_VIEW,
                self::FINANCE_PAYMENTS_REFUND,
            ],
            'finance.withdrawals' => [
                self::FINANCE_WITHDRAWALS_VIEW,
                self::FINANCE_WITHDRAWALS_APPROVE,
                self::FINANCE_WITHDRAWALS_REJECT,
            ],
            'finance.statistics' => [
                self::FINANCE_STATISTICS_VIEW,
            ],
            'configuration' => [
                self::CONFIGURATION_VIEW,
                self::CONFIGURATION_UPDATE,
            ],
            'categories' => [
                self::CATEGORIES_VIEW,
                self::CATEGORIES_CREATE,
                self::CATEGORIES_UPDATE,
                self::CATEGORIES_DELETE,
            ],
            'fees' => [
                self::FEES_VIEW,
                self::FEES_UPDATE,
            ],
            'promotions' => [
                self::PROMOTIONS_VIEW,
                self::PROMOTIONS_CREATE,
                self::PROMOTIONS_UPDATE,
                self::PROMOTIONS_DELETE,
            ],
        ];
    }
}
