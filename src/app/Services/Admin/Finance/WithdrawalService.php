<?php

namespace App\Services\Admin\Finance;

use App\Constants\Commons\ExceptionCode;
use App\Exceptions\BusinessException;
use App\Repositories\Criteria\Admin\Finance\SortAndFilterWithdrawalCriteria;
use App\Repositories\Wallet\WithdrawalRepository;
use App\Services\AbstractService;
use Illuminate\Http\Request;

class WithdrawalService extends AbstractService
{
    public function __construct(
        protected WithdrawalRepository $withdrawalRepository
    ) {}

    public function list(Request $request)
    {
        $limit = $request->query('limit', 10);
        $filters = $request->except(['limit', 'page', 'sorts', 'keyword']);
        $sorts = $request->query('sorts', []);
        $keyword = $request->query('keyword', []);

        if (is_string($keyword)) {
            $keyword = ['code' => $keyword, 'worker_name' => $keyword];
        }

        return $this->withdrawalRepository->pushCriteria(
            new SortAndFilterWithdrawalCriteria($filters, $sorts, $keyword ?: [])
        )->paginate($limit);
    }

    public function show($id)
    {
        $withdrawal = $this->withdrawalRepository->with(['worker', 'bankAccount', 'logs'])->find($id);

        if (! $withdrawal) {
            throw new BusinessException(ExceptionCode::NOT_FOUND, 'Withdrawal request not found', 404);
        }

        return $withdrawal;
    }
}
