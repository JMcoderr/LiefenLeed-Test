<?php

namespace App\Imports;

use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MemberImport implements ToCollection, WithStartRow //ToModel, WithUpserts
{
    protected array $seenIds = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            if (count($row) != 8)
                continue;

            $id = $row[0];

            $this->seenIds[] = $id;

            try {
                $values = [
                    'email' => strtolower(trim($row[7])),
                    'full_name' => mb_convert_encoding($row[1], 'UTF-8'),
                    'name' => $row[2],
                    'dob' => $this->parseDate($row[5]),
                    'years_of_service' => $this->parseDate($row[6]),
                ];
            } catch (\Exception $e) {
                break;
            }

            $member = Member::withTrashed()->updateOrCreate(['id' => $id], $values);
            if ($member->trashed())
                $member->restore();
        }
        $this->softDeleteMissingMembers();
    }

    protected function softDeleteMissingMembers(): void
    {
        Member::query()
            ->whereNull('deleted_at')
            ->whereNotIn('id', $this->seenIds)
            ->get()
            ->each(fn ($member) => $member->delete());
    }

    protected function parseDate($date): ?Carbon
    {
        try {
            return Carbon::instance(Date::excelToDateTimeObject($date));
        } catch (\Exception $e) {
            return null;
        }
    }
//    /**
//     * @param array $row
//     *
//     * @return Model|Member|null
//     */
//    public function model(array $row): Model|Member|null
//    {
////        echo '<pre><p>';
////        print_r($row);
////        echo '</p></pre>';
//        if (count($row) < 6)
//            return null;
//
//        return new Member([
//            // Future should include EMAIL in Excel document.
//            'id' => (int) $row[0],
//            'email' => strtolower(trim($row[7])),
//            'full_name' => mb_convert_encoding($row[1], 'UTF-8'),
//            'name' => $row[2],
////            'dob' => Carbon::parse($row[5])->format('Y-m-d'),
//            'dob' => Carbon::instance(Date::excelToDateTimeObject($row[5])), //->format('Y-m-d'),
////            'years_of_service' => Carbon::parse($row[6])->format('Y-m-d'),
//            'years_of_service' => Carbon::instance(Date::excelToDateTimeObject($row[6])), //->format('Y-m-d'),
//        ]);
//    }
//
//    /**
//     * @return string|array
//     */
//    public function uniqueBy(): array|string
//    {
//        return 'id'; // ['id', 'email']
//    }
//
//    /**
//     * @return array
//     */
//    public function upsertColumns(): array
//    {
//        return ['full_name', 'name', 'dob', 'years_of_service'];
//    }

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 5;
    }
}
