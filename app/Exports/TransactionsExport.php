<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        protected int $userId,
        protected int $year,
        protected int $month
    ) {}

    public function collection()
    {
        return Transaction::with(['wallet', 'category', 'targetWallet'])
            ->where('user_id', $this->userId)
            ->whereYear('transaction_date', $this->year)
            ->whereMonth('transaction_date', $this->month)
            ->orderBy('transaction_date')
            ->get();
    }

    public function headings(): array
    {
        return ['Tanggal', 'Deskripsi', 'Jenis', 'Jumlah', 'Wallet', 'Wallet Tujuan', 'Kategori', 'Merchant', 'Sumber', 'Catatan'];
    }

    public function map($t): array
    {
        return [
            $t->transaction_date->format('d/m/Y H:i'),
            $t->description,
            ucfirst($t->type),
            $t->amount,
            $t->wallet->name,
            $t->targetWallet?->name ?? '',
            $t->category?->name ?? '',
            $t->merchant ?? '',
            $t->source,
            $t->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
