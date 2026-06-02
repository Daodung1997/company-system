<?php

namespace App\Services\Admin\Configuration;

use App\Constants\Commons\ExceptionCode;
use App\Constants\Master\Models\PlatformFee\PlatformFeeStatusConst;
use App\Exceptions\BusinessException;
use App\Repositories\Criteria\Configuration\SortAndFilterPlatformFeeCriteria;
use App\Repositories\PlatformFee\PlatformFeeRepository;
use App\Services\AbstractService;
use Exception;

class PlatformFeeService extends AbstractService
{
    protected $repository;

    public function __construct(PlatformFeeRepository $repository)
    {
        $this->repository = $repository;
    }

    public function list(array $params)
    {
        $filters = $params['filters'] ?? [];
        $sorts = $params['sorts'] ?? [
            'code' => 'asc',
            'start_date' => 'desc',
        ];
        $search = $params['search'] ?? [];

        $this->repository->pushCriteria(new SortAndFilterPlatformFeeCriteria($filters, $sorts, $search));

        return $this->repository->paginate($params['limit'] ?? 20);
    }

    public function create(array $data)
    {
        $this->beginTransaction();
        try {
            $data['start_date'] = $data['start_date'] ?? now();
            $start = $data['start_date'];
            $end = $data['end_date'] ?? null;

            // BR-03: Handle overlapping configs
            $overlapping = $this->repository->getInstance()
                ->where('code', $data['code'])
                ->where('status', PlatformFeeStatusConst::ACTIVE)
                ->where(function ($q) use ($start, $end) {
                    $q->where(function ($q2) use ($start) {
                        $q2->whereNull('end_date')
                            ->orWhere('end_date', '>=', $start);
                    });

                    if ($end) {
                        $q->where('start_date', '<=', $end);
                    }
                })
                ->get();

            // Reject if any overlapping config already has an explicit end_date
            $hasExplicitEndDate = $overlapping->whereNotNull('end_date')->isNotEmpty();
            if ($hasExplicitEndDate) {
                throw new BusinessException(
                    ExceptionCode::INVALID_SCHEDULE_OVERLAP,
                    'Configurator overlaps with existing active fee.',
                    422
                );
            }

            // Auto-close open-ended configs (end_date = null)
            if ($overlapping->isNotEmpty()) {
                $closeDate = \Carbon\Carbon::parse($start)->subSecond();
                foreach ($overlapping as $old) {
                    $this->repository->update($old->id, ['end_date' => $closeDate]);
                }
            }

            $fee = $this->repository->create($data);
            $this->commitTransaction();

            return $fee;
        } catch (Exception $e) {
            $this->rollbackTransaction();
            $this->handleException($e);
        }
    }

    public function update(int $id, array $data)
    {
        $this->beginTransaction();
        try {
            $fee = $this->repository->find($id);
            if (! $fee) {
                throw new BusinessException(ExceptionCode::NOT_FOUND, __('common.not_found'), 404);
            }

            $this->repository->update($id, $data);
            $fee = $this->repository->find($id); // Refresh
            $this->commitTransaction();

            return $fee;
        } catch (Exception $e) {
            $this->rollbackTransaction();
            $this->handleException($e);
        }
    }

    protected function handleException(Exception $e)
    {
        if ($e instanceof BusinessException) {
            throw $e;
        }

        throw new BusinessException(
            ExceptionCode::UNKNOWN_ERROR,
            $e->getMessage(),
            500
        );
    }
}
