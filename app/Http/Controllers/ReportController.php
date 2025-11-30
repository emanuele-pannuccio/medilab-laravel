<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReportResource;
use App\MedicalCaseStatus;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Ramsey\Uuid\Uuid;
use PhpOffice\PhpWord\Element\TextRun;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Report::with(['patient', 'doctor']);

        // 2. Applica i filtri in modo condizionale

        if ($request->filled('paziente')) {
            $query->where('patientId', $request->query('paziente'));
        }

        if ($request->filled('medico')) {
            $query->where('doctorId', $request->query('medico'));
        }

        // Filtro per Stato (colonna diretta sulla tabella 'reports')
        if ($request->filled('stato')) {
            $query->where('status', $request->query('stato'));
        }

        // Usiamo 'whereHas' per filtrare i Report basandoci su una proprietà del medico
        if ($request->filled('reparto')) {
            $query->whereHas('doctor', function ($doctorQuery) use ($request) {
                $doctorQuery->where('departmentId', $request->query('reparto'));
            });
        }

        $query->orderBy('hospitalization_date', 'desc');

        if ($request->filled('nome_paziente')) {
            $query->whereHas('patient', function ($patientQuery) use ($request) {
                $patientQuery->where('name', $request->query('nome_paziente'));
            });
        }

        $query->orderBy('hospitalization_date', 'desc');

        $reports = $query->paginate(15);

        return ReportResource::collection($reports);
    }

    public function get_documents(Request $request){
        return  Storage::disk('s3')->allFiles();
    }

    public function elaborate_document(Request $request){
        $doctorId = auth('sanctum')->user()->id;
        $files = $request->file('documents');
        $jobs = [];
        
        foreach ($files as $file) {
            # code...
            $filename = $file->getClientOriginalName();
            // Contenuto del file
            $content = file_get_contents($file->getRealPath());
            $jobId = Uuid::uuid4()->toString();
            array_push($jobs, $jobId);

            Storage::disk('s3')->put(
                $file->hashName(),
                $content,
                [
                    'Metadata' => [
                        'doctor' => (string) $doctorId,
                        'filename' => (string) $filename,
                        'job' => $jobId,
                        'hash' => hash("sha256", $content)
                    ],
                    'ContentType' => $file->getMimeType(),
                ]
            );
        }

        return  response()->json(
            ["status" => 201, "response" => [
                "jobs" => $jobs
            ]]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validation = $request->validate([
            'hospitalization_date' => "required|date|date_format:Y-m-d",
            'past_illness_history' => "required|string",
            'present_illness_history' => "string|required",
            'clinical_evolution' => "string|required",
            'discharge_date' => "date|date_format:Y-m-d|required",
            'discharge_description' => "string|required",
            'patient' => "required|exists:patients,id",
        ]);

        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor('/var/www/html/resources/templates/template.docx');

        $report = Report::make($validation);
        $report->status = MedicalCaseStatus::aperto->value;
        $report->doctorId = auth('sanctum')->user()->id;
        $report->patientId = $request->patient;
        $report->document = "";
        $report->documentHash = "";
        $report->save();

        $patient = \App\Models\Patient::find($request->patient);
        $validation["patient"] = $patient->name;
        $validation["birthday"] = $patient->birthday;
        $validation["city"] = $patient->city;
        
        $report->document = "";
        $report->documentHash = "";
        $keys = [];
        foreach ($validation as $key => $value) {
            $subs = new TextRun();
            if(in_array($key, ["discharge_date", "hospitalization_date", "birthday"])) $value = Carbon::createFromFormat('Y-m-d', $value)->format('d/m/Y');
            $keys[$key] = $value;
            $subs->addText($value);
            $templateProcessor->setComplexValue(strtoupper($key), $subs);
        }
        
        $jobId = Uuid::uuid4()->toString();
        $filename = '/var/www/html/resources/templates/'.$patient->name.'-'.$jobId.'.docx';
        $templateProcessor->saveAs($filename);

        $content = file_get_contents($filename);
        $doctorId = auth()->user()->id;
        Storage::disk('s3')->put(
            hash("sha256", $filename),
            $content,
            [
                'Metadata' => [
                    'doctor' => (string) $doctorId,
                    'filename' => (string) $filename,
                    'job' => $jobId,
                    'hash' => hash("sha256", $content)
                ],
                'ContentType' => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            ]
        );

        delete($filename)

        return response()->json(["status" => 201, "response" => ["ok" => 1, "test"=>$keys]], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Report $report)
    {
        $report->load(['doctor', 'patient']);

        return response()->json(array("status" => 200, "response" => $report), 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Report $report)
    {
        $validation = $request->validate([
            'hospitalization_date' => "sometimes|date|date_format:Y-m-d",
            'present_illness_history' => "sometimes|string",
            'past_illness_history' => "sometimes|string",
            'clinical_evolution' => "sometimes|string",
            'discharge_date' => "sometimes|date|date_format:Y-m-d",
            'discharge_description' => "sometimes|string",
            'patient' => "sometimes|exists:patients,id",
            'stato' => "sometimes|in:Aperto,Revisione,Chiuso,Analisi",
        ]);

        if ($request->filled('stato')) {
            $validation["status"] = $validation["stato"];
        }


        $report->update($validation);
        $report->save();

        return response()->json(["status" => 200, "response" => ["ok" => $report->fresh()]], status: 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Report $report)
    {
        if($report->delete())
            return response()->json(array("status" => 200, "response" => ["ok" => "Resource deleted"]), 200);
        return response()->json(array("status" => 400, "response" => ["error" => "Resource cannot be deleted"]), 400);
    }
}
