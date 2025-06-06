<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Payment;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function processCheckout(Request $request)
    {
        Log::info('Checkout request received', $request->all());

        try {
            // Validimi i të dhënave sipas strukturës së frontend-it
            $validated = $request->validate([
                'customer_name' => 'required|string|max:255',
                'check_in' => 'required|date|after_or_equal:today',
                'check_out' => 'required|date|after:check_in',
                'room_id' => 'required|integer|exists:rooms,id',
                'user_id' => 'nullable|integer',
                'status' => 'nullable|string|in:pending,confirmed,cancelled',
                'payment.cardholder' => 'required|string|max:255',
                'payment.bank_name' => 'required|string|max:255',
                'payment.card_number' => 'required|digits:16',
                'payment.card_type' => 'required|in:Visa,MasterCard,visa,mastercard',
                'payment.cvv' => 'required|digits:3',
            ]);

            // Kontrollo nëse dhoma është e rezervuar për periudhën
            $roomReserved = Reservation::where('room_id', $validated['room_id'])
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('check_in', [$validated['check_in'], $validated['check_out']])
                          ->orWhereBetween('check_out', [$validated['check_in'], $validated['check_out']])
                          ->orWhere(function ($query) use ($validated) {
                              $query->where('check_in', '<=', $validated['check_in'])
                                    ->where('check_out', '>=', $validated['check_out']);
                          });
                })->exists();

            if ($roomReserved) {
                Log::warning('Conflicting reservation found:', [
                    'room_id' => $validated['room_id'],
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out']
                ]);
                return response()->json([
                    'error' => 'Dhoma është e rezervuar tashmë për këto data'
                ], 409);
            }

            // Merr çmimin dhe dhomën
            $room = Room::findOrFail($validated['room_id']);
            $room_price = $room->price ?? 100; // Fallback nëse price mungon

            DB::beginTransaction();

            // Krijo rezervimin
            $reservation = Reservation::create([
                'customer_name' => $validated['customer_name'],
                'check_in' => $validated['check_in'],
                'check_out' => $validated['check_out'],
                'room_id' => $validated['room_id'],
                'user_id' => $validated['user_id'] ?? null,
                'status' => $validated['status'] ?? 'confirmed',
            ]);

            Log::info('Reservation created:', ['reservation_id' => $reservation->id]);

            // Përditëso statusin e dhomës
            $room->is_reserved = true;
            $room->status = 'dirty';
            $room->save();

            Log::info('Room updated:', [
                'room_id' => $room->id,
                'is_reserved' => $room->is_reserved,
                'status' => $room->status
            ]);

            // Krijo pagesën
            $payment = Payment::create([
                'reservation_id' => $reservation->id,
                'amount' => $room_price,
                'paid_at' => now(),
                'cardholder' => $validated['payment']['cardholder'],
                'bank_name' => $validated['payment']['bank_name'],
                'card_number' => $validated['payment']['card_number'],
                'card_type' => $validated['payment']['card_type'],
                'cvv' => $validated['payment']['cvv'],
            ]);

            Log::info('Payment created:', ['payment_id' => $payment->id]);

            DB::commit();

            return response()->json([
                'message' => 'Payment and reservation processed successfully!',
                'payment' => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'date' => $payment->paid_at ? $payment->paid_at->format('Y-m-d') : null,
                    'status' => 'completed',
                    'cardholder' => $payment->cardholder,
                    'bank_name' => $payment->bank_name,
                    'card_number' => $payment->card_number,
                    'card_type' => $payment->card_type,
                    'cvv' => $payment->cvv,
                ],
                'reservation' => [
                    'id' => $reservation->id,
                    'customer_name' => $reservation->customer_name,
                    'check_in' => $reservation->check_in,
                    'check_out' => $reservation->check_out,
                    'room_id' => $reservation->room_id,
                    'status' => $reservation->status,
                ],
            ], 200);

        } catch (ValidationException $e) {
            Log::error('Validation failed:', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);
            return response()->json([
                'error' => 'Validation failed.',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Checkout error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'error' => 'Failed to process payment.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}