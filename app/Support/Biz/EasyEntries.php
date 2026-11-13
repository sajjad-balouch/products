<?php

namespace App\Support\Biz;

/**
 * Everyday money events in plain words, so nobody has to know debit and credit. The user says
 * what happened ("the owner put money in", "paid the rent"); each event knows which account
 * goes up and which goes down and posts the right books entry itself.
 *
 * Accounts are named by system key (cash, capital...), by code ("2200"), or by these words:
 *   money: the cash or bank account the user chose     pick: the account the user chose from a list
 */
class EasyEntries
{
    public const GROUPS = [
        'in' => ['Money came in', 'Paise aaye'],
        'out' => ['Money went out', 'Paise gaye'],
        'move' => ['Moved money', 'Paise idhar udhar kiye'],
        'start' => ['Starting the books', 'Shuru ka hisaab (opening)'],
    ];

    /**
     * key => [group, voucher kind, debit account, credit account, money side (in, out or null), pick list (null or "expense"),
     *         [English title, example], [Urdu title, example], [what happens in English, in Urdu]]
     * pick: "expense" chooses the account itself; "vendor" and "employee" choose whose account the entry is on.
     */
    public const ALL = [
        'capital_in' => ['in', 'receipt', 'money', 'capital', 'in', null,
            ['The owner put money into the business', 'You added Rs 200,000 of your own money'],
            ['Maalik ne business mein paise dale', 'Aap ne apne 2 lakh business mein dale'],
            ['Cash or bank goes up and so does Owner\'s capital. It is the owner\'s money, not profit.', 'Cash ya bank barhta hai aur Owner\'s capital bhi. Ye maalik ka paisa hai, munafa nahi.']],
        'loan_in' => ['in', 'receipt', 'money', '2200', 'in', null,
            ['Took a loan', 'Borrowed from a person or a bank'],
            ['Qarz liya', 'Kisi bande ya bank se udhaar liya'],
            ['Cash or bank goes up, and what the business owes (Loans) goes up by the same amount. Not profit.', 'Cash ya bank barhta hai, aur business ka qarz (Loans) bhi utna hi barhta hai. Ye munafa nahi.']],
        'vendor_loan' => ['in', 'receipt', 'money', 'payable', 'in', 'vendor',
            ['Borrowed cash from a vendor', 'A supplier lent you money'],
            ['Vendor se cash udhaar liya', 'Supplier ne paise udhaar diye'],
            ['Cash or bank goes up and you owe that vendor more; it shows on their account. Not profit.', 'Cash ya bank barhta hai aur us vendor ka qarz barhta hai; ye us ke khate mein nazar aata hai. Munafa nahi.']],
        'other_income' => ['in', 'receipt', 'money', '4900', 'in', null,
            ['Other income', 'Repair charges, commission, scrap sold'],
            ['Koi aur aamdani', 'Repair charges, commission, kabaad becha'],
            ['Cash or bank goes up and it counts as profit.', 'Cash ya bank barhta hai aur ye munafa mein ginta hai.']],

        'expense' => ['out', 'payment', 'pick', 'money', 'out', 'expense',
            ['Paid an expense', 'Rent, salary, electricity bill, fuel, tea'],
            ['Kharcha kiya', 'Kiraya, tankhwah, bijli ka bill, petrol, chaye'],
            ['Cash or bank goes down and the expense lowers your profit.', 'Cash ya bank kam hota hai aur kharcha munafa kam karta hai.']],
        'pay_supplier' => ['out', 'payment', 'payable', 'money', 'out', 'vendor',
            ['Paid a supplier we owed', 'For stock bought on credit earlier'],
            ['Supplier ko purana udhaar diya', 'Pehle udhaar par liye maal ke paise'],
            ['Cash or bank goes down and what you owe suppliers goes down. Profit does not change.', 'Cash ya bank kam hota hai aur supplier ka qarz bhi kam hota hai. Munafa nahi badalta.']],
        'loan_repay' => ['out', 'payment', '2200', 'money', 'out', null,
            ['Paid back a loan', 'Returned borrowed money'],
            ['Qarz wapas kiya', 'Liya hua udhaar lautaya'],
            ['Cash or bank goes down and the loan goes down. Profit does not change.', 'Cash ya bank kam hota hai aur qarz bhi kam hota hai. Munafa nahi badalta.']],
        'salary' => ['out', 'payment', 'salaries', 'money', 'out', 'employee',
            ['Paid a salary', 'Monthly pay to an employee or salesman'],
            ['Tankhwah di', 'Employee ya salesman ki mahana tankhwah'],
            ['Cash or bank goes down and the salary is an expense, so profit goes down. It shows on the employee\x27s page.', 'Cash ya bank kam hota hai aur tankhwah kharcha hai, is liye munafa kam hota hai. Ye employee ke page par nazar aati hai.']],
        'staff_advance' => ['out', 'payment', 'staff_advance', 'money', 'out', 'employee',
            ['Gave an employee an advance', 'Money they will pay back or have cut from salary'],
            ['Staff ko advance diya', 'Jo wo wapas karega ya tankhwah se katega'],
            ['Cash or bank goes down and the employee owes you this amount. Not an expense, so profit does not change.', 'Cash ya bank kam hota hai aur employee ne ye raqam wapas deni hai. Kharcha nahi, is liye munafa nahi badalta.']],
        'owner_takes' => ['out', 'payment', 'drawings', 'money', 'out', null,
            ['The owner took money out', 'For home or personal use'],
            ['Maalik ne paise nikale', 'Ghar ya apne kharche ke liye'],
            ['Cash or bank goes down. It is not an expense, so profit does not change; the owner\'s share goes down.', 'Cash ya bank kam hota hai. Ye kharcha nahi, is liye munafa nahi badalta; maalik ka hissa kam hota hai.']],
        'buy_equipment' => ['out', 'payment', '1500', 'money', 'out', null,
            ['Bought something for the shop to keep', 'Counter, AC, computer, furniture (not for sale)'],
            ['Dukaan ke liye cheez khareedi', 'Counter, AC, computer, furniture (bechne ke liye nahi)'],
            ['Cash or bank goes down and the shop\'s equipment goes up. Not an expense, so profit does not change.', 'Cash ya bank kam hota hai aur dukaan ka saman barhta hai. Kharcha nahi, is liye munafa nahi badalta.']],
        'advance_given' => ['out', 'payment', '1300', 'money', 'out', null,
            ['Gave an advance or deposit', 'Shop security deposit, advance to a supplier'],
            ['Advance ya security di', 'Dukaan ki security, supplier ko advance'],
            ['Cash or bank goes down, and money others hold for you goes up. Profit does not change.', 'Cash ya bank kam hota hai, aur jo paise doosron ke paas aap ke hain wo barhte hain. Munafa nahi badalta.']],

        'cash_to_bank' => ['move', 'journal', 'bank', 'cash', null, null,
            ['Put cash into the bank', 'Deposited at the bank'],
            ['Cash bank mein jama karwaya', 'Bank mein deposit kiya'],
            ['Cash in hand goes down and the bank goes up by the same amount. Nothing else changes.', 'Hath ka cash kam hota hai aur bank utna hi barhta hai. Aur kuch nahi badalta.']],
        'bank_to_cash' => ['move', 'journal', 'cash', 'bank', null, null,
            ['Took cash out of the bank', 'ATM or cheque withdrawal'],
            ['Bank se cash nikala', 'ATM ya cheque se'],
            ['The bank goes down and cash in hand goes up by the same amount. Nothing else changes.', 'Bank kam hota hai aur hath ka cash utna hi barhta hai. Aur kuch nahi badalta.']],

        'opening_money' => ['start', 'journal', 'money', 'capital', 'in', null,
            ['Cash or bank we had when we started', 'Money already in the drawer or bank on day one'],
            ['Shuru mein jo cash ya bank tha', 'Pehle din jo paise galle ya bank mein the'],
            ['Cash or bank starts at this amount, and it is counted as the owner\'s capital.', 'Cash ya bank isi raqam se shuru hota hai, aur ye maalik ka capital gina jata hai.']],
        'opening_payable' => ['start', 'journal', 'capital', 'payable', null, 'vendor',
            ['Money we already owed suppliers', 'Old supplier dues from before'],
            ['Suppliers ka purana udhaar jo dena tha', 'Pehle ka supplier ka baqaya'],
            ['What you owe suppliers starts at this amount; the owner\'s capital is lower by the same.', 'Supplier ka qarz isi raqam se shuru hota hai; maalik ka capital utna kam gina jata hai.']],
        'opening_loan' => ['start', 'journal', 'capital', '2200', null, null,
            ['A loan we already had', 'Borrowed before you started using this system'],
            ['Pehle se liya hua qarz', 'Is system se pehle ka udhaar'],
            ['The loan starts at this amount; the owner\'s capital is lower by the same.', 'Qarz isi raqam se shuru hota hai; maalik ka capital utna kam gina jata hai.']],
    ];

    /** @return array{key: string, group: string, kind: string, debit: string, credit: string, money: ?string, pick: ?string, en: array, ur: array, explain: array}|null */
    public static function get(?string $key): ?array
    {
        if (! $key || ! isset(self::ALL[$key])) {
            return null;
        }
        [$group, $kind, $debit, $credit, $money, $pick, $en, $ur, $explain] = self::ALL[$key];

        return compact('key', 'group', 'kind', 'debit', 'credit', 'money', 'pick', 'en', 'ur', 'explain');
    }
}
