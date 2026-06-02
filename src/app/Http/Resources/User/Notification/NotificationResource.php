<?php

namespace App\Http\Resources\User\Notification;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Constants\Master\Models\Notification\NotificationTypeConst;

class NotificationResource extends JsonResource
{
    public function toArray($request)
    {
        $type = $this->type;
        $title = $this->title;
        $body = $this->body;
        $data = $this->data;

        if (!is_array($data)) {
            $data = json_decode(json_encode($data), true) ?: [];
        }

        try {
            // Auto fill metadata & translation fallback for legacy database records
            if ($type === NotificationTypeConst::JOB_QUOTATION_ACCEPTED) {
                $jobId = $data['job_id'] ?? null;
                if ($jobId) {
                    $job = \App\Models\Job::with(['customer', 'serviceCategory'])->find($jobId);
                    if ($job) {
                        $data['job_code'] = $data['job_code'] ?? $job->code;
                        $data['customer_name'] = $data['customer_name'] ?? ($job->customer->full_name ?? 'Khách hàng');
                        $data['price'] = $data['price'] ?? (float) ($job->quotation_price ?? ($job->quotations()->where('worker_id', $this->user_id ?? ($data['worker_id'] ?? null))->first()?->price ?? 0));
                        $data['service_name'] = $data['service_name'] ?? ($job->serviceCategory->name ?? 'Dịch vụ');
                    }
                }
                $title = __('notification.job_quotation_accepted.title');
                $body = __('notification.job_quotation_accepted.body', ['job_code' => $data['job_code'] ?? '']);
            } elseif ($type === NotificationTypeConst::JOB_QUOTATION_RECEIVED) {
                $jobId = $data['job_id'] ?? null;
                if ($jobId) {
                    $job = \App\Models\Job::find($jobId);
                    if ($job) {
                        $data['job_code'] = $data['job_code'] ?? $job->code;
                        $data['quotation_id'] = $data['quotation_id'] ?? ($job->quotations()->first()?->id ?? null);
                    }
                }
                $title = __('notification.job_quotation_received.title');
                $body = __('notification.job_quotation_received.body', ['job_code' => $data['job_code'] ?? '']);
            } elseif ($type === NotificationTypeConst::JOB_QUOTATION_REJECTED) {
                $jobId = $data['job_id'] ?? null;
                if ($jobId) {
                    $job = \App\Models\Job::find($jobId);
                    if ($job) {
                        $data['job_code'] = $data['job_code'] ?? $job->code;
                    }
                }
                $title = __('notification.job_quotation_rejected.title');
                $body = __('notification.job_quotation_rejected.body', ['job_code' => $data['job_code'] ?? '']);
            } elseif ($type === NotificationTypeConst::JOB_STARTED) {
                $jobId = $data['job_id'] ?? null;
                if ($jobId) {
                    $job = \App\Models\Job::find($jobId);
                    if ($job) {
                        $data['job_code'] = $data['job_code'] ?? $job->code;
                    }
                }
                $title = __('notification.job_started.title');
                $body = __('notification.job_started.body', ['job_code' => $data['job_code'] ?? '']);
            } elseif ($type === NotificationTypeConst::JOB_COMPLETED) {
                $jobId = $data['job_id'] ?? null;
                if ($jobId) {
                    $job = \App\Models\Job::find($jobId);
                    if ($job) {
                        $data['job_code'] = $data['job_code'] ?? $job->code;
                    }
                }
                $title = __('notification.job_completed.title');
                $body = __('notification.job_completed.body', ['job_code' => $data['job_code'] ?? '']);
            } elseif ($type === NotificationTypeConst::JOB_COMPLAINT) {
                $jobId = $data['job_id'] ?? null;
                if ($jobId) {
                    $job = \App\Models\Job::find($jobId);
                    if ($job) {
                        $data['job_code'] = $data['job_code'] ?? $job->code;
                    }
                }
                $title = __('notification.job_complaint.title');
                $body = __('notification.job_complaint.body', ['job_code' => $data['job_code'] ?? '']);
            } elseif ($type === NotificationTypeConst::NEW_JOB_NEARBY) {
                $title = __('notification.new_job_nearby.title');
                if (str_contains($body, 'New job') || str_contains($body, 'cách bạn')) {
                    $serviceName = $data['title'] ?? 'Dịch vụ';
                    $distance = $data['distance'] ?? null;
                    $body = __('notification.new_job_nearby.body', [
                        'service_name' => $serviceName,
                        'distance' => $distance ?? 'gần'
                    ]);
                }
            } elseif ($type === NotificationTypeConst::WITHDRAWAL_SUCCESS) {
                $title = __('notification.withdrawal_success.title');
                if (str_contains($body, 'withdrawal request') || str_contains($body, 'Your withdrawal') || str_contains($body, 'Yêu cầu rút tiền')) {
                    $body = __('notification.withdrawal_success.body', [
                        'withdrawal_code' => $data['withdrawal_code'] ?? '',
                        'amount' => number_format($data['amount'] ?? 0)
                    ]);
                }
            } elseif ($type === NotificationTypeConst::WITHDRAWAL_FAILED) {
                $title = __('notification.withdrawal_failed.title');
                if (str_contains($body, 'withdrawal request') || str_contains($body, 'Your withdrawal') || str_contains($body, 'Yêu cầu rút tiền')) {
                    $body = __('notification.withdrawal_failed.body', [
                        'withdrawal_code' => $data['withdrawal_code'] ?? '',
                        'reason' => $data['reason'] ?? ''
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Silently fallback to original data on exception
            \Illuminate\Support\Facades\Log::error('NotificationResource formatting error: ' . $e->getMessage());
        }

        return [
            'id' => $this->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => empty($data) ? new \ArrayObject() : $data,
            'is_read' => ! is_null($this->read_at),
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
