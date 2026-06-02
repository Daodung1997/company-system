<?php

namespace App\Http\Controllers\Api\Admin\Configuration;

use App\Http\Controllers\Controller;
use App\Services\Admin\ConfigurationService;
use App\Supports\Facades\Response\Response;

class ConfigurationController extends Controller
{
    protected $configurationService;

    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    public function getJobAssignmentConfig()
    {
        $config = $this->configurationService->getJobAssignmentConfig();

        return Response::success($config);
    }

    public function updateJobAssignmentConfig(\App\Http\Requests\Admin\Configuration\UpdateJobAssignmentConfigRequest $request)
    {
        $data = $request->validated();
        $config = $this->configurationService->updateJobAssignmentConfig($data);

        return Response::success($config);
    }
}
