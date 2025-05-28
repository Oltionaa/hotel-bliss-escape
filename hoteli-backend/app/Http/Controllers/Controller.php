<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 * version="1.0.0",
 * title="Hoteli API Dokumentacioni",
 * description="Dokumentacioni i API-së për Sistemin e Rezervimeve të Hotelit",
 * @OA\Contact(
 * email="support@hoteli.com"
 * ),
 * @OA\License(
 * name="Apache 2.0",
 * url="http://www.apache.org/licenses/LICENSE-2.0.html"
 * )
 * )
 *
 * @OA\Server(
 * url=L5_SWAGGER_CONST_HOST,
 * description="Hotel Management API Server"
 * )
 *
 * @OA\SecurityScheme(
 * securityScheme="bearerAuth",
 * in="header",
 * name="Authorization",
 * type="http",
 * scheme="Bearer",
 * bearerFormat="JWT",
 * )
 *
 * @OA\Components(
 * schemas={
 *
 * @OA\Schema(
 * schema="ValidationErrorResponse",
 * title="Gabime Validimi",
 * description="Përgjigje standarde për gabimet e validimit",
 * @OA\Property(
 * property="message",
 * type="string",
 * example="The given data was invalid."
 * ),
 * @OA\Property(
 * property="errors",
 * type="object",
 * additionalProperties=@OA\AdditionalProperties(
 * type="array",
 * @OA\Items(type="string")
 * )
 * )
 * ),
 *
 * @OA\Schema(
 * schema="ErrorResponse",
 * title="Error Response",
 * description="Përgjigje standarde për gabimet (përdoret kur nuk ka gabime validimi)",
 * @OA\Property(property="message", type="string", example="Një gabim ndodhi."),
 * @OA\Property(property="error", type="string", nullable=true, example="Detaje shtesë gabimi (opsionale).")
 * ),
 *
 * @OA\Schema(
 * schema="ReceptionistSchedule",
 * title="Receptionist Schedule",
 * description="Detajet e orarit të një recepsionisti",
 * @OA\Property(property="id", type="integer", readOnly="true", example=1, description="ID e orarit"),
 * @OA\Property(property="user_id", type="integer", description="ID e recepsionistit", example=5),
 * @OA\Property(property="shift_date", type="string", format="date", description="Data e turnit (YYYY-MM-DD)", example="2024-06-15"),
 * @OA\Property(property="start_time", type="string", format="time", description="Ora e fillimit të turnit (HH:MM:SS)", example="08:00:00"),
 * @OA\Property(property="end_time", type="string", format="time", description="Ora e mbarimit të turnit (HH:MM:SS)", example="16:00:00"),
 * @OA\Property(property="created_at", type="string", format="date-time", readOnly="true", description="Data e krijimit"),
 * @OA\Property(property="updated_at", type="string", format="date-time", readOnly="true", description="Data e fundit e përditësimit")
 * ),
 *
 * @OA\Schema(
 * schema="Room",
 * title="Room",
 * description="Modeli i dhomës së hotelit",
 * @OA\Property(property="id", type="integer", readOnly="true", example=1),
 * @OA\Property(property="room_number", type="string", example="101"),
 * @OA\Property(property="type", type="string", example="Double"),
 * @OA\Property(property="capacity", type="integer", example=2),
 * @OA\Property(property="price", type="number", format="float", example=100.00),
 * @OA\Property(property="status", type="string", example="available", enum={"available", "occupied", "dirty", "maintenance"}),
 * @OA\Property(property="is_reserved", type="boolean", example=false),
 * @OA\Property(property="created_at", type="string", format="date-time", readOnly="true"),
 * @OA\Property(property="updated_at", type="string", format="date-time", readOnly="true")
 * ),
 *
 * @OA\Schema(
 * schema="Reservation",
 * title="Reservation",
 * description="Modeli i rezervimit",
 * @OA\Property(property="id", type="integer", readOnly="true", example=1),
 * @OA\Property(property="room_id", type="integer", example=1),
 * @OA\Property(property="user_id", type="integer", example=1),
 * @OA\Property(property="customer_name", type="string", example="Jon Doe"),
 * @OA\Property(property="check_in", type="string", format="date", example="2024-09-10"),
 * @OA\Property(property="check_out", type="string", format="date", example="2024-09-15"),
 * @OA\Property(property="status", type="string", example="confirmed", enum={"pending", "confirmed", "cancelled"}),
 * @OA\Property(property="created_at", type="string", format="date-time", readOnly="true"),
 * @OA\Property(property="updated_at", type="string", format="date-time", readOnly="true")
 * ),
 *
 * @OA\Schema(
 * schema="Payment",
 * title="Payment",
 * description="Modeli i pagesës",
 * @OA\Property(property="id", type="integer", readOnly="true", example=1),
 * @OA\Property(property="reservation_id", type="integer", example=1),
 * @OA\Property(property="cardholder", type="string", example="JOHN DOE"),
 * @OA\Property(property="bank_name", type="string", example="MyBank"),
 * @OA\Property(property="card_number", type="string", example="XXXXXXXXXXXX1234", description="Numri i kartës i fshehur"),
 * @OA\Property(property="card_type", type="string", example="Visa", enum={"Visa", "MasterCard"}),
 * @OA\Property(property="amount", type="number", format="float", example=500.00),
 * @OA\Property(property="paid_at", type="string", format="date-time", example="2024-09-08 10:30:00"),
 * @OA\Property(property="created_at", type="string", format="date-time", readOnly="true"),
 * @OA\Property(property="updated_at", type="string", format="date-time", readOnly="true")
 * ),
 *
 * @OA\Schema(
 * schema="UserResponse",
 * title="User Response",
 * description="Modeli i përgjigjes së përdoruesit",
 * @OA\Property(property="id", type="integer", readOnly="true", example=1),
 * @OA\Property(property="name", type="string", example="John Doe"),
 * @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 * @OA\Property(property="role", type="string", example="user", enum={"admin", "receptionist", "cleaner", "user"}),
 * @OA\Property(property="status", type="string", example="active", enum={"active", "inactive"}),
 * @OA\Property(property="created_at", type="string", format="date-time", readOnly="true", example="2024-01-01T12:00:00.000000Z"),
 * @OA\Property(property="updated_at", type="string", format="date-time", readOnly="true", example="2024-01-01T12:00:00.000000Z")
 * ),
 *
 * @OA\Schema(
 * schema="CleanerScheduleModel",
 * title="Cleaner Schedule Model",
 * description="Modeli i orarit të pastruesit",
 * @OA\Property(property="id", type="integer", readOnly="true", example=1, description="ID e orarit"),
 * @OA\Property(property="cleaner_id", type="integer", description="ID e pastruesit", example=5),
 * @OA\Property(property="work_date", type="string", format="date", description="Data e punës (YYYY-MM-DD)", example="2024-06-15"),
 * @OA\Property(property="shift_start", type="string", format="time", description="Ora e fillimit të turnit (HH:MM)", example="08:00"),
 * @OA\Property(property="shift_end", type="string", format="time", description="Ora e mbarimit të turnit (HH:MM)", example="16:00"),
 * @OA\Property(property="status", type="string", enum={"Planned", "Completed", "Canceled"}, example="Planned", description="Statusi i orarit"),
 * @OA\Property(property="created_at", type="string", format="date-time", readOnly="true", description="Data e krijimit"),
 * @OA\Property(property="updated_at", type="string", format="date-time", readOnly="true", description="Data e fundit e përditësimit"),
 * @OA\Property(property="cleaner", type="object", description="Detajet e pastruesit (nëse ngarkohen)",
 * @OA\Property(property="id", type="integer", example=5),
 * @OA\Property(property="name", type="string", example="Cleaner Name")
 * )
 * )
 * }
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}