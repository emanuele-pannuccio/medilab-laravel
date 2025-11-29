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
    public $tries = 10;

    /**
     * Create a new job instance.
     */
    public function __construct($jobId, $doctor, $total_chunks, $data)
    {
        //
        $this->jobId = $jobId;
        $this->doctor = $doctor;
        $this->total_chunks = $total_chunks;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(Client $elasticsearch): void
    {
        // Esempio di query base
        $response = $elasticsearch->count([
            'index' => 'elasticsearch', // Sostituisci con il nome del tuo indice
            'body'  => [
                'query' => [
                    'term' => [
                        'metadata.jobId.keyword' => $this->jobId // Assicurati che il campo su ES si chiami così
                        ]
                    ]
                ]
            ]
        );
                    
        $indexed_docs_count = $response->asArray()["count"];

        Storage::
        
        if ($indexed_docs_count >= $this->total_chunks) {
            Log::info("Tutti i chunk sono stati processati!");
            event(new \App\Events\DoctorChatMessage($this->doctor, [
                "status" => 200,
                "job" => $this->jobId,
                "file" => Storage::disk("s3")->temporaryUrl(
                    $this->data->document, now()->addMinutes(5)
                ),
                "message" => "Document chunked and stored in VectorDB."
            ]));
            return; 
        }
        
        Log::info("⏳ Job {$this->jobId}: {$this->total_chunks}. Riprovo tra 10s...");
        
        $delay = 5 + $this->attempts();

        $this->release($delay);
    }
}
