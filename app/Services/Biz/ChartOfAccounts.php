<?php

namespace App\Services\Biz;

use App\Models\Biz\Account;
use App\Models\School;

/** The starting chart of accounts for a new business. Accounts with a key are used by sales and collections. */
class ChartOfAccounts
{
    /** [code, name, type, system key] */
    public const DEFAULT = [
        ['1000', 'Cash in hand', 'asset', 'cash'],
        ['1010', 'Bank account', 'asset', 'bank'],
        ['1100', 'Installment receivable', 'asset', 'receivable'],
        ['1150', 'Customer credit (udhaar)', 'asset', 'trade_receivable'],
        ['1200', 'Stock (inventory)', 'asset', 'inventory'],
        ['1300', 'Advances and deposits', 'asset', null],
        ['1350', 'Staff advances', 'asset', 'staff_advance'],
        ['1500', 'Furniture and equipment', 'asset', null],
        ['2000', 'Accounts payable (suppliers)', 'liability', 'payable'],
        ['2100', 'Unearned markup', 'liability', 'deferred_markup'],
        ['2200', 'Loans', 'liability', null],
        ['3000', "Owner's capital", 'equity', 'capital'],
        ['3100', "Owner's drawings", 'equity', 'drawings'],
        ['3900', 'Retained earnings', 'equity', 'retained'],
        ['4000', 'Sales', 'income', 'sales'],
        ['4100', 'Markup income', 'income', 'markup_income'],
        ['4200', 'Late fee income', 'income', 'late_fee_income'],
        ['4900', 'Other income', 'income', null],
        ['5000', 'Cost of goods sold', 'expense', 'cogs'],
        ['5100', 'Discount allowed', 'expense', 'discount'],
        ['5200', 'Salaries and wages', 'expense', 'salaries'],
        ['5250', 'Sales commission', 'expense', 'commission'],
        ['5300', 'Rent', 'expense', null],
        ['5400', 'Electricity, gas and phone', 'expense', null],
        ['5500', 'Fuel and transport', 'expense', null],
        ['5600', 'Bad debts written off', 'expense', 'bad_debt'],
        ['5900', 'Other expenses', 'expense', null],
    ];

    public static function seed(School $school): void
    {
        foreach (self::DEFAULT as [$code, $name, $type, $key]) {
            Account::withoutGlobalScopes()->firstOrCreate(
                ['school_id' => $school->id, 'code' => $code],
                ['name' => $name, 'type' => $type, 'system_key' => $key],
            );
        }
    }
}
