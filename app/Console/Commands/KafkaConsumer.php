<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Facades\Kafka;

use App\Models\Report;
use App\Models\Patient;

use App\Jobs\SendJobNotification;

class KafkaConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kafka:consume';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    public function createReport($job){
        $report = $job["record"];
        $patient = Patient::firstOrCreate([ "name" => $report["patient"] ], [
                "birthday" => $report["birthday"],
                "city" => $report["place_of_birth"]
        ]);
        
        $report_db = Report::firstOrNew(
            [
                'documentHash' => $report["document_hash"]
            ],
            [
                'hospitalization_date' => $report["hospitalization_date"],
                'past_illness_history' => $report["past_illness_history"],
                'present_illness_history' => $report["present_illness_history"],
                'clinical_evolution' => $report["clinical_evolution"],
                'discharge_date' => $report["discharge_date"],
                'discharge_description' => $report["discharge_description"],
                'documentHash' => $report["document_hash"],
                'document' => $report["bucket"]."/".$report["file"],
            ]
        );
        $report_db->patientId = $patient->id;
        $report_db->doctorId = $report["doctor"];
        $report_db->save();

        SendJobNotification::dispatch($report["jobId"], $report["doctor"], $job["chunks"], $report);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {

        // SendJobNotification::dispatch("5196345f-79ad-4543-a2ce-1b5d35b6dd4e", "1", 5);
        $consumer = Kafka::consumer(['reports'])
        ->withHandler(function (\Junges\Kafka\Contracts\ConsumerMessage $message, \Junges\Kafka\Contracts\MessageConsumer $consumer) {
            $job = $message->getBody();
            $this->createReport($job);
        })->build();
                        
        $consumer->consume();
    }
}
