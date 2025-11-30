<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Elastic\Elasticsearch\Client;

class SendJobNotification implements ShouldQueue
{
    use Queueable;

    private $jobId;
    private $total_chunks;
    private $doctor;
    private $report;
    public $tries = 10;

    /**
     * Create a new job instance.
     */
    public function __construct($jobId, $doctor, $total_chunks, $report)
    {
        //
        $this->jobId = $jobId;
        $this->doctor = $doctor;
        $this->total_chunks = $total_chunks;
        $this->report = $report;
        dump($report);
    }

    /**
     * Execute the job.
     */
    public function handle(Client $elasticsearch): void
    {
        dump($this->report);
        $response = $elasticsearch->count([
            'index' => 'elasticsearch',
            'body'  => [
                'query' => [
                    'term' => [
                        // 'metadata.jobId' => $this->jobId
                        'metadata.document_hash' => $this->report["document_hash"]
                    ]
                ]
            ]
        ]);
                    
        $indexed_docs_count = $response->asArray()["count"];

        if ($indexed_docs_count >= $this->total_chunks) {
            Log::info("Tutti i chunk sono stati processati!");
            event(new \App\Events\DoctorChatMessage($this->doctor, [
                "status" => 200,
                "job" => $this->jobId,
                // "file" => Storage::disk("s3")->temporaryUrl(
                //     $this->report->document, now()->addMinutes(5)
                // ),
                "message" => "Document chunked and stored in VectorDB."
            ]));
            return; 
        }
        
        Log::info("⏳ Job {$this->jobId}: {$indexed_docs_count}/{$this->total_chunks}. Riprovo tra 10s...");
        
        $delay = 5 + $this->attempts();

        $this->release($delay);
    }
}
