<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function processCheckout(Request $request)
{
    \Log::info('Checkout request received', $request->all());

    try {
        $validated = $request->validate([
            'room_title' => 'required|string',
            'room_price' => 'required|numeric',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'people' => 'required|integer',
            'cardholder' => 'required|string',
            'bank_name' => 'required|string',
            'card_number' => 'required|digits:16',
            'card_type' => 'required|in:visa,mastercard',
            'cvv' => 'required|digits:3',
            'room_id' => 'required|integer|exists:rooms,id',
        ]);

        // Kontrollo nëse dhoma është e rezervuar për periudhën
        $roomReserved = Reservation::where('room_id', $validated['room_id'])
            ->where(function ($query) use ($validated) {
                $query->whereBetween('check_in', [$validated['check_in'], $validated['check_out']])
                      ->orWhereBetween('check_out', [$validated['check_in'], $validated['check_out']])
                      ->orWhere(function ($query) use ($validated) {
                          $query->where('check_in', '<=', $validated['check_in'])
                                ->where('check_out', '>=', $validated['check_out']);
                      });
            })->exists();

        if ($roomReserved) {
            return response()->json([
                'error' => 'Dhoma është e rezervuar tashmë për këto data'
            ], 409);
        }

        DB::beginTransaction();

        $reservation = Reservation::create([
            'customer_name' => $validated['cardholder'],
            'check_in' => $validated['check_in'],
            'check_out' => $validated['check_out'],
            'room_id' => $validated['room_id'],
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => $validated['room_price'],
            'paid_at' => now(),
        ]);

        DB::commit();

        return response()->json([
            'message' => 'Payment and reservation processed successfully!',
            'payment' => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'date' => $payment->paid_at->format('Y-m-d'),
                'status' => 'completed',
            ],
        ], 200);

    } catch (ValidationException $e) {
        \Log::error('Validation failed: ' . json_encode($e->errors()));
        return response()->json([
            'error' => 'Validation failed.',
            'messages' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Checkout error: ' . $e->getMessage());
        return response()->json([
            'error' => 'Failed to process payment.',
            'message' => $e->getMessage(),
        ], 500);
    }
}

}