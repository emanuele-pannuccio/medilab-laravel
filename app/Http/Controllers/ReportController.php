<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReportResource;
use App\MedicalCaseStatus;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Ramsey\Uuid\Uuid;

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
        $file = $request->file('document');
        $filename = $file->getClientOriginalName();

        // Contenuto del file
        $content = file_get_contents($file->getRealPath());
        $jobId = Uuid::uuid4()->toString();

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

        return  response()->json(
            ["status" => 201, "response" => [
                "job" => $jobId
            ]]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validation = $request->validate([
            'hospitalization_date' => "required|date|date_format:Y-m-d H:i:s",
            'past_illness_history' => "required|string",
            'present_illness_history' => "string",
            'clinical_evolution' => "string",
            'discharge_date' => "date|date_format:Y-m-d H:i:s",
            'discharge_description' => "string",
            'patient' => "required|exists:patients,id",
        ]);
        $validation["hospitalization_date"] = Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $request->hospitalization_date
        )->toDateTimeString();

        $report = Report::make($validation);
        $report->status = MedicalCaseStatus::aperto->value;
        $report->doctorId = auth('sanctum')->user()->id;
        $report->patientId = $request->patient;
        $report->save();
        return response()->json(["status" => 201, "response" => ["ok" => 1]], 201);
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
            'hospitalization_date' => "sometimes|date|date_format:Y-m-d H:i:s",
            'present_illness_history' => "sometimes|string",
            'past_illness_history' => "sometimes|string",
            'clinical_evolution' => "sometimes|string",
            'discharge_date' => "sometimes|date|date_format:Y-m-d H:i:s",
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
