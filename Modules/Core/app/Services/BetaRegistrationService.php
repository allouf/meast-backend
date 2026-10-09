<?php

namespace Modules\Core\Services;

use Modules\Core\Models\User;
use Modules\Core\Repositories\Interfaces\UserRepositoryInterface;
use App\Mail\WelcomeBetaUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BetaRegistrationService
{
    protected $userRepository;
    protected $excelFilePath = 'beta-registrations.xlsx';
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $isBusinessUser = !empty($data['registering_as']);
            $password = $this->generatePassword();
            $hashedPassword = Hash::make($password);
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $hashedPassword,
                'is_beta_user' => true,
                'is_active' => true,
                'verified_at' => now(),
                'is_profile_complete' => true,
            ];
            $user = $this->userRepository->create($userData);
            if ($isBusinessUser) {
                $user->assignRole('company');
            } else {
                $user->assignRole('consumer');
            }
            $this->appendToExcel($user, $isBusinessUser, $data);
            Mail::to($data['email'])->send(new WelcomeBetaUser($user, $password));
            return $user;
        });
    }
    protected function generatePassword(): string
    {
        return Str::random(12);
    }
    protected function appendToExcel(User $user, bool $isBusinessUser, array $data): void
    {
        $filePath = storage_path('app/' . $this->excelFilePath);
        $fileExists = file_exists($filePath);
        $sheetName = $isBusinessUser ? 'Business Users' : 'Normal Users';
        if ($fileExists) {
            $spreadsheet = IOFactory::load($filePath);
        } else {
            $spreadsheet = new Spreadsheet();
            // Remove default sheet if we're creating new sheets
            if ($spreadsheet->getSheetCount() > 0) {
                $spreadsheet->removeSheetByIndex(0);
            }
        }
        if ($spreadsheet->sheetNameExists($sheetName)) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $nextRow = $sheet->getHighestRow() + 1;
        } else {
            $sheet = new Worksheet($spreadsheet, $sheetName);
            $spreadsheet->addSheet($sheet);
            if ($isBusinessUser) {
                $headers = [
                    'Name',
                    'Email',
                    'Phone',
                    'Registering As',
                    'Country',
                    'Business Type',
                    'Industry Categories',
                    'Selling Online',
                    'Interested In',
                    'Structured Feedback',
                    'Created At'
                ];
            } else {
                $headers = [
                    'Name',
                    'Email',
                    'Phone',
                    'Country',
                    'Interested In',
                    'Structured Feedback',
                    'Created At'
                ];
            }
            $column = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($column . '1', $header);
                $column++;
            }
            $nextRow = 2;
        }
        if ($isBusinessUser) {
            $rowData = [
                $data['name'],
                $data['email'],
                $data['phone'] ?? '',
                $data['registering_as'] ?? '',
                $data['country'] ?? '',
                $data['business_type'] ?? '',
                is_array($data['industry_categories'] ?? null)
                    ? implode(', ', $data['industry_categories'])
                    : ($data['industry_categories'] ?? ''),
                $data['selling_online'] ?? '',
                is_array($data['interested_in'] ?? null)
                    ? implode(', ', $data['interested_in'])
                    : ($data['interested_in'] ?? ''),
                $data['structured_feedback'] ?? '',
                $user->created_at->format('Y-m-d H:i:s')
            ];
        } else {
            $rowData = [
                $data['name'],
                $data['email'],
                $data['phone'] ?? '',
                $data['country'] ?? '',
                is_array($data['interested_in'] ?? null)
                    ? implode(', ', $data['interested_in'])
                    : ($data['interested_in'] ?? ''),
                $data['structured_feedback'] ?? '',
                $user->created_at->format('Y-m-d H:i:s')
            ];
        }
        $column = 'A';
        foreach ($rowData as $value) {
            $sheet->setCellValue($column . $nextRow, $value);
            $column++;
        }
        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
    }
}
