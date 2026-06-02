<?php

namespace App\Services\Admin;

use App\Repositories\Admin\Configuration\ConfigurationRepository;
use App\Services\AbstractService;
use Illuminate\Support\Facades\Cache;

class ConfigurationService extends AbstractService
{
    protected $configurationRepository;

    // Define keys for Job Assignment Config
    const KEY_JOB_ASSIGNMENT_CONFIG = 'job_assignment_config';

    public function __construct(ConfigurationRepository $configurationRepository)
    {
        $this->configurationRepository = $configurationRepository;
    }

    public function getJobAssignmentConfig()
    {
        return Cache::rememberForever('config.'.self::KEY_JOB_ASSIGNMENT_CONFIG, function () {
            $value = $this->configurationRepository->getValue(self::KEY_JOB_ASSIGNMENT_CONFIG);
            if ($value) {
                return json_decode($value, true);
            }

            // Default Config
            return [
                'scan_radius' => 10, // km
                'max_workers_per_job' => 5,
                'rating_weight' => 0.5,
                'distance_weight' => 0.3,
                'response_rate_weight' => 0.2,
            ];
        });
    }

    public function updateJobAssignmentConfig(array $data)
    {
        $this->beginTransaction();
        try {
            // Validate logic if needed (e.g. weights sum to 1.0)

            $value = json_encode($data);
            $this->configurationRepository->updateValue(self::KEY_JOB_ASSIGNMENT_CONFIG, $value);

            // Clear Cache
            Cache::forget('config.'.self::KEY_JOB_ASSIGNMENT_CONFIG);

            $this->commitTransaction();

            return $data;
        } catch (\Throwable $e) {
            $this->rollbackTransaction();
            throw $e;
        }
    }
}
