<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReceptionistSchedule; // Ose 'Schedule' nëse keni ndryshuar emrin e modelit
use App\Models\User;
use Carbon\Carbon;

class CreateReceptionistSchedules extends Command
{
    protected $signature = 'schedules:create-weekly'; // Ndryshova emrin e komandës
    protected $description = 'Create schedules automatically for receptionists for the upcoming week';

    public function handle()
    {
        // Merr të gjithë recepsionistët
        $receptionists = User::where('role', 'receptionist')->get();

        // Dy turne të mundshme
        $shifts = [
            ['shift_start' => '08:00:00', 'shift_end' => '14:00:00'],
            ['shift_start' => '14:00:00', 'shift_end' => '20:00:00'],
        ];

        // Fillojmë nga dita e nesërme
        $startDay = Carbon::tomorrow();

        // Krijojmë orare për 7 ditët e ardhshme
        for ($i = 0; $i < 7; $i++) {
            $currentDate = $startDay->copy()->addDays($i)->toDateString(); // Data për ditën aktuale

            foreach ($receptionists as $index => $receptionist) {
                // Kontrollo nëse orari ekziston tashmë për këtë recepsionist dhe këtë datë
                $exists = ReceptionistSchedule::where('receptionist_id', $receptionist->id)
                    ->where('work_date', $currentDate)
                    ->exists();

                if (!$exists) {
                    // Caktuam turnin bazuar në indeksin e recepsionistit për t'i shpërndarë turnet
                    $shift = $shifts[$index % count($shifts)];

                    ReceptionistSchedule::create([
                        'receptionist_id' => $receptionist->id,
                        'work_date' => $currentDate,
                        'shift_start' => $shift['shift_start'],
                        'shift_end' => $shift['shift_end'],
                        'status' => 'Planned', // Statusi default
                    ]);
                    $this->info("Orari u krijua për recepsionistin {$receptionist->name} në datën {$currentDate}.");
                } else {
                    $this->info("Orari ekziston tashmë për recepsionistin {$receptionist->name} në datën {$currentDate}.");
                }
            }
        }

        $this->info('Oraret javore u kontrolluan dhe u krijuan sipas nevojës.');
    }
}