<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CleanerSchedule;
use App\Models\User;
use Carbon\Carbon;

class CreateCleanerSchedules extends Command
{
    /**
     * The name and signature of the console command.
     * Përdorimi: php artisan cleaners:create-weekly-schedules {cleanerId} {startDate} {shiftStart} {shiftEnd}
     *
     * @var string
     */
    protected $signature = 'cleaners:create-weekly-schedules {cleanerId} {startDate} {shiftStart} {shiftEnd}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Krijon orare javore për një pastrues specifik.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cleanerId = $this->argument('cleanerId');
        $startDate = $this->argument('startDate');
        $shiftStart = $this->argument('shiftStart');
        $shiftEnd = $this->argument('shiftEnd');

        // Validim bazik për ID-në
        if (!filter_var($cleanerId, FILTER_VALIDATE_INT)) {
            $this->error("ID-ja e pastruesit duhet të jetë numër. Ju lutemi jepni një ID pastruesi të vlefshme.");
            return Command::FAILURE;
        }

        // Validim për formatin e datës
        try {
            $startDate = Carbon::parse($startDate);
        } catch (\Exception $e) {
            $this->error("Formati i datës së fillimit është i pavlefshëm. Përdorni formatin 'YYYY-MM-DD'.");
            return Command::FAILURE;
        }

        // Kontrollo nëse pastruesi ekziston dhe ka rolin e duhur
        $cleaner = User::where('id', $cleanerId)->where('role', 'cleaner')->first();
        if (!$cleaner) {
            $this->error("Pastruesi me ID: {$cleanerId} nuk u gjet ose nuk ka rolin 'cleaner'.");
            return Command::FAILURE;
        }

        $this->info("Duke krijuar orare javore për pastruesin: " . $cleaner->name . " (ID: " . $cleaner->id . ")");
        $this->info("Duke filluar nga data: " . $startDate->toDateString() . " me turn nga " . $shiftStart . " deri " . $shiftEnd);

        $createdCount = 0;
        // Krijojmë orare për 7 ditë nga data e fillimit
        for ($i = 0; $i < 7; $i++) {
            $workDate = $startDate->copy()->addDays($i);

            // Kontrollo nëse ekziston tashmë një orar për këtë datë dhe pastrues
            $existingSchedule = CleanerSchedule::where('cleaner_id', $cleanerId)
                                               ->where('work_date', $workDate->toDateString())
                                               ->first();

            if ($existingSchedule) {
                $this->warn("    - Orari për " . $workDate->toDateString() . " ekziston tashmë. U kalua.");
                continue;
            }

            try {
                CleanerSchedule::create([
                    'cleaner_id' => $cleanerId,
                    'work_date' => $workDate->toDateString(),
                    'shift_start' => $shiftStart,
                    'shift_end' => $shiftEnd,
                    'status' => 'Planned', // Statusi fillestar
                ]);
                $this->line("    + Orar i krijuar për: " . $workDate->toDateString());
                $createdCount++;
            } catch (\Exception $e) {
                $this->error("Gabim gjatë krijimit të orarit për " . $workDate->toDateString() . ": " . $e->getMessage());
            }
        }

        if ($createdCount > 0) {
            $this->info("Gjithsej " . $createdCount . " orare javore u krijuan me sukses.");
            return Command::SUCCESS;
        } else {
            $this->warn("Nuk u krijua asnjë orar i ri. Të gjitha oraret mund të kenë ekzistuar tashmë.");
            return Command::SUCCESS;
        }
    }
}