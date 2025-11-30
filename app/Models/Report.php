<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;
    protected $fillable = [
        "hospitalization_date",
        "present_illness_history",
        "past_illness_history",
        "clinical_evolution",
        "discharge_date",
        "discharge_description",
        "status",
        "document",
        "documentHash"
    ];
    protected $casts = [
        'hospitalization_date' => 'datetime:d-m-Y',
        'discharge_date' => 'datetime:d-m-Y',
    ];
    /**
     * Get the doctor that owns the MedicalCase
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctorId', 'id');
    }

    /**
     * Get the patient that owns the MedicalCase
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patientId', 'id');
    }
}
