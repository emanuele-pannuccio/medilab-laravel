<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {$validation = $request->validate([
            'name' => 'nullable|string|max:255',
            'birthday' => 'nullable|date_format:Y-m-d', // Esempio: 'YYYY-MM-DD'
            'birthday_operator' => [
                'nullable',
                'string',
                Rule::in(['=', '!=', '<', '>', '<=', '>=']), // Operatori consentiti
            ],
            'city' => 'nullable|string|max:255',
            'order_key' => 'nullable|string', // La colonna per cui ordinare
            'order_direction' => 'nullable|string|in:ASC,DESC', // Direzione
        ]);

        // 2. Inizio della query
        $query = Patient::query();

        // 3. Applicazione dinamica dei filtri

        // Filtro per 'name' (usando 'LIKE' standard)
        if ($request->filled('name')) {
            $query->where('name', 'LIKE', '%' . $validation['name'] . '%');
        }

        // Filtro per 'city'
        if ($request->filled('city')) {
            $query->where('city', 'LIKE', '%' . $validation['city'] . '%');
        }

        // Filtro per 'birthday' con operatore
        if ($request->filled('birthday')) {
            // Se 'birthday_operator' non è fornito, usa '=' come default
            $operator = $validation['birthday_operator'] ?? '=';
            $query->where('birthday', $operator, $validation['birthday']);
        }

        if ($request->filled('order_key')) {
            $direction = $validation['order_direction'] ?? 'ASC';

            // Controlla che 'order_key' sia una colonna sicura
            $allowedSortKeys = ['name', 'birthday', 'city', 'created_at', 'id'];
            if (in_array($validation['order_key'], $allowedSortKeys)) {
                $query->orderBy($validation['order_key'], $direction);
            } else {
                $query->orderBy('id', 'DESC');
            }
        } else {
            $query->orderBy('id', 'DESC');
        }

        $response = $query->paginate();

        $response = $response->toResourceCollection();

        return response()->json(["status" => 200, "response" => $response], 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validation = $request->validate([
            'name' => 'required|string|max:255',
            'birthday' => 'required|date_format:Y-m-d',
            'city' => 'required|string|max:255',
        ]);

        $validation["name"] = "pti-".hash_hmac('sha256', hash_hmac('sha256', $validation["name"], config('app.key')).$validation["birthday"].$validation["city"], config('app.key'));

        $patient = Patient::firstOrCreate([ "name" => $validation["name"] ], $validation);

        return response()->json([
            "status" => 201,
            "response" => $patient
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Patient $patient)
    {
        return response()->json(array("status" => 200, "response" => $patient), 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Patient $patient)
    {
        $validation = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'birthday' => 'sometimes|nullable|date_format:Y-m-d',
            'city' => 'sometimes|nullable|string|max:255',
        ]);

        $validation["name"] = "pti-".hash_hmac('sha256', $validation["name"].$validation["birthday"].$validation["city"], config('app.key'));

        // 2. Aggiornamento del modello (il $patient è già stato trovato da Laravel)
        $patient->update($validation);
        return response()->json(array("status" => 200, "response" => $patient->fresh()), 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Patient $patient)
    {
        $patient->delete();
        return response()->json([ "status" => 200, "response" => [ "ok" => 1 ] ]);
    }
}
