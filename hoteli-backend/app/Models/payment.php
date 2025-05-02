<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        "reservation_id",
        "cardholder",
        "bank_name",
        "card_number",
        "card_type",
        "cvv",
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
