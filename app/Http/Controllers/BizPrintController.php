<?php

namespace App\Http\Controllers;

use App\Models\Biz\Contract;
use App\Models\Biz\Customer;
use App\Models\Biz\Invoice;
use App\Models\Biz\JournalEntry;
use App\Models\Biz\Receipt;
use App\Services\Biz\Installments;
use App\Support\Qr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/** Printouts for a business. Every lookup is limited to the signed-in business by the models' scope. */
class BizPrintController extends Controller
{
    private function data(array $extra): array
    {
        $school = Auth::user()->school;
        abort_unless($school?->isBusiness(), 403);

        return ['school' => $school, 'settings' => Installments::settings($school)] + $extra;
    }

    public function receipt(int $receipt)
    {
        $receipt = Receipt::with(['contract.customer', 'contract.schedule', 'receiver'])->findOrFail($receipt);

        return view('print.biz.receipt', $this->data([
            'receipt' => $receipt,
            'contract' => $receipt->contract,
            'qr' => Qr::svg($receipt->number.' | '.$receipt->contract->number.' | Rs '.$receipt->total().' | '.$receipt->date->format('Y-m-d')),
        ]));
    }

    public function statement(int $contract)
    {
        $contract = Contract::with(['customer', 'schedule', 'receipts' => fn ($q) => $q->whereNull('voided_at'), 'guarantors'])->findOrFail($contract);

        return view('print.biz.statement', $this->data(['c' => $contract]));
    }

    public function agreement(int $contract)
    {
        $contract = Contract::with(['customer', 'schedule', 'guarantors'])->findOrFail($contract);

        return view('print.biz.agreement', $this->data(['c' => $contract]));
    }

    public function voucher(int $entry)
    {
        $entry = JournalEntry::with(['lines.account', 'lines.customer', 'creator'])->findOrFail($entry);

        return view('print.biz.voucher', $this->data(['e' => $entry]));
    }

    public function invoice(int $invoice)
    {
        $invoice = Invoice::with(['lines.product', 'customer'])->findOrFail($invoice);

        return view('print.biz.invoice', $this->data(['inv' => $invoice]));
    }

    public function customerPhoto(int $customer)
    {
        $customer = Customer::findOrFail($customer);
        abort_unless($customer->photo_path && Storage::disk('local')->exists($customer->photo_path), 404);

        return Storage::disk('local')->response($customer->photo_path, null, ['Cache-Control' => 'private, max-age=604800']);
    }
}
