<?php

use App\Models\AcademicRecord;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Major;
use App\Models\IdCard;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure official ID Cards table supports expired status and seed official verified cards.
     */
    public function up(): void
    {
        // 1. Modify status column to varchar(30) to support all statuses without strict enum constraints
        if (Schema::hasTable('id_cards')) {
            try {
                Schema::table('id_cards', function (Blueprint $table) {
                    $table->string('status', 30)->default('active')->change();
                });
            } catch (\Throwable $e) {
                // SQLite or MySQL raw fallback
                try {
                    DB::statement("ALTER TABLE id_cards MODIFY status VARCHAR(30) DEFAULT 'active'");
                } catch (\Throwable $ex) {}
            }
        }

        // 2. Ensure Official Faculty, Department, and Major exist for Saint Paul Institute
        $faculty = Faculty::firstOrCreate(
            ['code' => 'FST'],
            [
                'name'        => 'Faculty of Science & Technology',
                'name_kh'     => 'មហាវិទ្យាល័យវិទ្យាសាស្ត្រ និងបច្ចេកវិទ្យា',
                'dean'        => 'H.E. Bishop Olivier Schmitthaeusler',
                'email'       => 'fst@spilms.tech',
                'est_year'    => 2009,
                'description' => 'Faculty of Science and Information Technology at Saint Paul Institute',
                'is_active'   => true,
            ]
        );

        $department = Department::firstOrCreate(
            ['code' => 'DIT'],
            [
                'faculty_id'  => $faculty->id,
                'name'        => 'Department of Information Technology',
                'name_kh'     => 'ដេប៉ាតឺម៉ង់បច្ចេកវិទ្យាព័ត៌មាន',
                'head'        => 'Ph.D. IT Department Head',
                'email'       => 'dit@spilms.tech',
                'description' => 'Department of IT & Software Engineering',
                'is_active'   => true,
            ]
        );

        $major = Major::firstOrCreate(
            ['code' => 'IT-BSC'],
            [
                'department_id' => $department->id,
                'name'          => 'Information Technology',
                'name_kh'       => 'បច្ចេកវិទ្យាព័ត៌មាន (IT)',
                'duration'      => '4 Years',
                'degree_level'  => 'Bachelor of Science (B.Sc.)',
                'credits'       => 120,
                'language'      => 'Khmer / English',
                'is_active'     => true,
            ]
        );

        // 3. Ensure primary user (id: 1 or admin) has active student profile and the requested official token
        $primaryUser = User::where('email', 'kosalsensok065@gmail.com')->first() ?: User::first();
        if ($primaryUser) {
            $primaryUser->update([
                'name'         => $primaryUser->name ?: 'kosal sensok',
                'name_kh'      => $primaryUser->name_kh ?: 'កុសល សែនសុខ',
                'major_id'     => $major->id,
                'status'       => 'active',
                'is_active'    => true,
                'student_code' => $primaryUser->student_code ?: 'SPI-2026-001582',
            ]);

            // Ensure official Academic Record exists
            AcademicRecord::firstOrCreate(
                ['user_id' => $primaryUser->id],
                [
                    'faculty_id'    => $faculty->id,
                    'department_id' => $department->id,
                    'major_id'      => $major->id,
                    'degree_level'  => 'Bachelor of Science (B.Sc.)',
                    'study_type'    => 'Full-time',
                    'gpa'           => 3.85,
                    'total_credits' => 120,
                ]
            );

            // Ensure official ID card exists with token 0b7a68d6a1a3c4de139b5fa8750c2f9960b3264c
            $targetToken = '0b7a68d6a1a3c4de139b5fa8750c2f9960b3264c';
            $idCard = IdCard::where('user_id', $primaryUser->id)->first();

            if ($idCard) {
                $idCard->update([
                    'card_no'     => 'SPI-2026-001582',
                    'token'       => $targetToken,
                    'issued_at'   => Carbon::create(2026, 11, 14, 0, 0, 0),
                    'expires_at'  => Carbon::create(2030, 11, 14, 23, 59, 59),
                    'status'      => 'active',
                    'approved_by' => $primaryUser->id,
                    'approved_at' => Carbon::create(2026, 11, 14, 0, 0, 0),
                ]);
            } else {
                IdCard::create([
                    'user_id'     => $primaryUser->id,
                    'card_no'     => 'SPI-2026-001582',
                    'token'       => $targetToken,
                    'issued_at'   => Carbon::create(2026, 11, 14, 0, 0, 0),
                    'expires_at'  => Carbon::create(2030, 11, 14, 23, 59, 59),
                    'status'      => 'active',
                    'approved_by' => $primaryUser->id,
                    'approved_at' => Carbon::create(2026, 11, 14, 0, 0, 0),
                ]);
            }
        }

        // 4. Ensure all other existing users have an official valid ID card
        $allUsers = User::all();
        foreach ($allUsers as $u) {
            if ($primaryUser && $u->id === $primaryUser->id) {
                continue;
            }

            IdCard::firstOrCreate(
                ['user_id' => $u->id],
                [
                    'card_no'     => IdCard::generateCardNo($u),
                    'token'       => IdCard::generateUniqueToken(),
                    'issued_at'   => Carbon::create(2026, 11, 14, 0, 0, 0),
                    'expires_at'  => Carbon::create(2030, 11, 14, 23, 59, 59),
                    'status'      => 'active',
                    'approved_by' => $primaryUser?->id ?: $u->id,
                    'approved_at' => Carbon::create(2026, 11, 14, 0, 0, 0),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe to keep records
    }
};
