<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use ProcessMaker\Services\Api\Reports;

class ReportsDashboardEmailEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const MAX_RETRY = 3;
    const MAX_EXCEPTIONS = 3;
    const TIMEOUT = 3600;
    const RELEASE_AFTER = 5;
    const RETRY_AFTER = 10;
    private $payload;



     /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = self::MAX_RETRY;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = self::MAX_EXCEPTIONS;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = self::TIMEOUT;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $retryAfter = self::RETRY_AFTER;


    private $reports_logger = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    // *
    //  * Determine the time at which the job should timeout.
    //  *
    //  * @return \DateTime
     
    // public function retryUntil()
    // {
    //     return now()->addSeconds(21600);
    // }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Reports $reportsObj)
    {
        try {
            $this->reports_logger = $reportsObj->reports_logger;
            $this->reports_logger->clog($this->reports_logger, ' :: starting job processing for dashboard excel generation request :: '.$this->payload);

            $mail_host = env('MAILIO_HOST');
            if ($mail_host === null) {
                $this->reports_logger->clog($this->reports_logger, ' :: job failed due to Missing host for mail sending :: '.$this->payload);
                $this->fail(new \Exception('Missing host for mail sending'));
            }


            $job_payload = (array)json_decode($this->payload);


            $send_to_email = $job_payload['send_to_email'];
            $cc_to_email = $job_payload['cc_to_email'];
            $date_to = $job_payload['date_to'];
            $date_from = $job_payload['date_from'];
            $workspace_name = trim($job_payload['workspace_name']);
            unset($job_payload['send_to_email']);
            unset($job_payload['cc_to_email']);
            unset($job_payload['workspace_name']);

            
            $report_excel_link = $reportsObj->getProcessDashboardDetailsVersion2ExcelGenerate(
                $job_payload['start'],
                $job_payload['limit'],
                $job_payload['date_from'],
                $job_payload['date_to'],
                $job_payload['process'],
                $job_payload['search'],
                $job_payload['owner_id'],
                $job_payload['is_custom_attributes_honoured'],
                $job_payload['assignee_list']
            );

            // $report_excel_link = htmlentities("<a href='".$report_excel_link['report_excel_link']."' target='_blank'>Download</a>");
            if (!isset($report_excel_link['report_excel_link'])) {
                $this->fail(new Exception(json_encode($report_excel_link)));
            }

            $report_excel_link = $report_excel_link['report_excel_link'];

            $mail_text = "Hello,".PHP_EOL.PHP_EOL."Please click on the link below to download the process volume report for the ".$workspace_name." spot";
            if(!empty($date_to) && !empty($date_from))
            {
                $date_from = date("Y-m-d", strtotime($date_from));
                $date_to = date("Y-m-d", strtotime($date_to));

                // $mail_text .= " for date range : " . $date_from. " - " . $date_to;
            }

            $mail_text .= ".".PHP_EOL.PHP_EOL.$report_excel_link.PHP_EOL.PHP_EOL."Regards,".PHP_EOL."Team Groupe.io";

            $mail_payload = [
                'to' => $send_to_email,
                'cc' => $cc_to_email,
                'subject' => 'Groupe.io - Download the Process Volume report',
                'text' => $mail_text,
            ];

            $mail_response = $this->sendPostcurl($mail_host.'/send/email', json_encode($mail_payload));
            
            if ($mail_response['success'] != 1) {
                $this->release(self::RELEASE_AFTER);
            }
        } catch (Exception $exception) {
            $this->reports_logger->clog($this->reports_logger, 'Process Volume Excel Generation Failed due to : '.PHP_EOL.$exception->getMessage().PHP_EOL.'Payload : '.$this->payload);
            $this->fail($exception);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function failed(\Exception $exception)
    {
        // Send user notification of failure, etc...
        $mail_host = env('MAILIO_HOST');
        $notification_payload = [
            'to' => env('FAILURE_NOTIFICATION_GROUP_EMAIL'),
            'subject' => 'Process Dashboard Reports Excel Generation Failure',
            'text' => 'Process Dashboard Reports Excel Generation Failed due to : '.PHP_EOL.$exception->getMessage().PHP_EOL.PHP_EOL.PHP_EOL.'Payload : '.$this->payload
        ];
        $this->sendPostcurl($mail_host.'/send/email', json_encode($notification_payload));
    }

    private function sendPostcurl($url, $payload)
    {
        try {
            $curl_response = [
                'success' => true,
                'error'   => false,
            ];
            
            $headers = [
                'Content-Type: application/json'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

            $server_output = curl_exec($ch);
            $httpCode      = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($server_output === false) {
                $curl_response['success'] = false;
                $curl_response['error']   = "CURL Error: " . curl_error($ch);
                curl_close($ch);
                return $curl_response;
            }


            if ($httpCode != 200) {
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $header = substr($server_output, 0, $header_size);
                $body = substr($server_output, $header_size);

                $curl_response['success'] = false;
                $curl_response['error']   = json_encode(json_decode($body));
                curl_close($ch);
                return $curl_response;
            }

            if ($httpCode == 200) {
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $header = substr($server_output, 0, $header_size);
                $body = substr($server_output, $header_size);
                $body = (array)json_decode($body);
                
                if ($body['success'] != 1) {
                    $error = (array)$body['error'];
                    $curl_response['success'] = false;
                    $curl_response['error']   = json_encode($error);
                    curl_close($ch);
                    return $curl_response;
                }
                curl_close($ch);
                return $curl_response;
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
