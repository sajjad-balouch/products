<?php

namespace App\Support\Biz;

/**
 * Help shown on every business entry form: how to fill it in, what saving it changes,
 * and the mistakes to avoid. The live "what will change" box under each form shows the
 * exact amounts; these texts explain the why.
 */
class Guides
{
    /** key => [title, how to fill in, what it changes, good to know] */
    public const ALL = [
        'sale' => [
            'How a sale works',
            [
                'Search for each product and add it. The saved retail or wholesale price fills in; change it if you agreed another price.',
                'For phones, bikes and other numbered pieces, scan or type the IMEI / engine no. of each piece sold. If you leave it empty, the oldest pieces in stock are sold first.',
                'Choose the customer, or just write a walk-in name. A saved customer is needed if any money is left to pay later.',
                'Write what the customer paid now and how (cash or bank). Anything less than the total is kept as credit (udhaar) on that customer.',
            ],
            [
                'Each piece leaves stock at the price you bought that very piece for, so a phone bought for 5,000 and one bought for 6,000 each show their own profit.',
                'Money received goes into Cash in hand or Bank. The unpaid part goes to Customer credit (udhaar).',
                'Sales income and the cost of the goods are recorded. The difference is your profit on this sale.',
            ],
            [
                'A saved sale is never edited. If it is wrong, cancel it (the pieces come back into stock) and make it again.',
                'Money paid later on a credit sale is taken from the sale page with "Receive payment".',
            ],
        ],
        'contract' => [
            'How an installment sale works',
            [
                'Choose the customer or add a new one with CNIC, phone and address. Add one or two guarantors.',
                'Choose the product and the exact piece (IMEI / engine no.) the customer takes home.',
                'Enter the cash price, the down payment, the markup and the number of installments. Check the schedule that appears.',
                'Save, then print the agreement for both sides to sign.',
            ],
            [
                'The piece leaves stock at what you paid for it.',
                'The down payment goes into Cash in hand. The rest of the price plus markup becomes Installment receivable: the customer owes you this.',
                'The markup is not counted as profit yet. It waits as "markup to earn later" and turns into profit bit by bit as each installment is paid, so your profit is never shown higher than it is.',
            ],
            [
                'A plan cannot be edited after saving. Cancel the down payment receipt and make a new plan if something is wrong.',
                'Rounding makes every installment a round number; the last one takes the small difference.',
            ],
        ],
        'collect' => [
            'How taking an installment works',
            [
                'Write the amount the customer paid. It can be more or less than one installment.',
                'Add a late fee if you charge one, or a discount if you forgive part of it.',
                'Choose how it was paid: cash, bank, JazzCash or Easypaisa.',
            ],
            [
                'The money pays the oldest unpaid installments first.',
                'Cash (or bank) goes up and what the customer owes goes down by the same amount.',
                'The markup inside this payment now becomes profit. A late fee is extra income; a discount is a cost.',
            ],
            [
                'Print or send the receipt so the customer has proof.',
                'A wrong receipt is cancelled, not deleted: the installments go back to unpaid and the books are reversed.',
            ],
        ],
        'void_receipt' => [
            'What cancelling a receipt does',
            ['Write why it is cancelled, for example "entered twice" or "cheque bounced".'],
            [
                'A mirror entry undoes the payment in the books. Nothing is deleted, so the history stays clear.',
                'The installments it paid become unpaid again and the customer owes that amount again.',
            ],
            [],
        ],
        'write_off' => [
            'What writing off a plan does',
            ['Use this only when the money will not be recovered. Write the reason.'],
            [
                'What the customer still owes is removed from receivables.',
                'The markup not yet earned is removed, and the rest is recorded as a loss (bad debt), so it lowers profit.',
                'The plan is closed. Receipts already taken stay as they are.',
            ],
            ['If the customer pays later, take it as a receipt voucher under "Other income".'],
        ],
        'sale_receive' => [
            'How receiving a credit payment works',
            ['Write the amount received, the date and how it was paid.'],
            ['Cash or bank goes up and the customer\'s credit (udhaar) goes down by the same amount. Profit does not change: it was counted on the sale.'],
            [],
        ],
        'sale_cancel' => [
            'What cancelling a sale does',
            ['Write why the sale is cancelled.'],
            [
                'The pieces go back into stock at their original cost.',
                'The sale, its cost and any payments are reversed in the books with mirror entries. Nothing is deleted.',
            ],
            ['If the customer was given money back, that is already part of the reversal.'],
        ],
        'stock' => [
            'How stock in works',
            [
                'Write how many pieces came and what one piece cost you.',
                'For phones and bikes, write each IMEI / engine no. on its own line. The number of lines must match the quantity.',
                'Choose how you paid: cash, bank, on credit if you still owe the supplier, or "Already in the shop" for stock you had before you started using this system.',
                'Paid only part? Choose cash or bank and write what you paid now in "Paid now". The rest goes on the vendor\'s account.',
            ],
            [
                'The pieces enter stock at this cost. Each piece remembers its own cost, so later sales show true profit.',
                'Cash or bank goes down by what you paid now, and what you did not pay is owed to that vendor (Accounts payable).',
                'There is no profit or loss yet. Profit is counted when each piece is sold.',
            ],
            ['The same IMEI / engine no. can never be in stock twice. The system stops a repeat.'],
        ],
        'product' => [
            'How adding a product works',
            [
                'Choose the product type. It decides what is written for each piece (IMEI, engine no., serial).',
                'Choose the brand and model, or add a new one. Add a variant such as "8/256 GB" or "2025" if you keep more than one.',
                'Write the retail price, and a wholesale price if you sell to shops.',
            ],
            [
                'Nothing changes in money or stock. The product is only saved, so you never type it again.',
                'It now appears when you search on a sale or an installment plan.',
                'Add pieces with "Stock in" when they arrive.',
            ],
            ['Changing a price later only changes new sales. Old sales keep the price they were made at.'],
        ],
        'customer' => [
            'How adding a customer works',
            [
                'Write the full name, father\'s / husband\'s name and a working mobile number.',
                'For installments, add the CNIC and home address. A photo helps when you collect.',
            ],
            [
                'No money changes. The customer can now be chosen on sales and installment plans.',
                'Their page will show every plan, payment and what they still owe.',
            ],
            ['Search first, so the same person is not added twice.'],
        ],
        'payment' => [
            'How a payment voucher works',
            [
                'Use it for money going out that is not stock: rent, salaries, bills, fuel, paying a supplier, or the owner taking money out.',
                'Choose what the money was for, the amount, and whether it came from cash or bank. Write a short description.',
            ],
            [
                'Cash or bank goes down.',
                'An expense (rent, salary, bills) goes up and lowers profit. Paying a supplier lowers what you owe instead, and the owner taking money out does not change profit.',
            ],
            ['A posted voucher is never edited. Reverse it and post it again if it is wrong.'],
        ],
        'receipt' => [
            'How a receipt voucher works',
            [
                'Use it for money coming in that is not a sale or installment: the owner putting in capital, a loan received, or other income.',
                'Choose where the money came from, the amount, and whether it went to cash or bank.',
            ],
            [
                'Cash or bank goes up.',
                'Capital or a loan goes up (not profit). Only "Other income" counts as profit.',
            ],
            ['Sales and installments have their own screens; do not enter them here.'],
        ],
        'journal' => [
            'How a journal voucher works',
            [
                'Use it for adjustments and opening balances, when no cash moves the usual way.',
                'For each account, choose if it goes up or down and by how much. You do not need to know debit and credit; the system works them out.',
                'Every entry has two sides that match. Example: cash goes up 50,000 and owner\x27s capital goes up 50,000.',
            ],
            ['Exactly the lines you write are posted. The box below shows what each account will do.'],
            ['Most entries, opening balances too, are easier from "New entry · Kya hua?", which needs no accounting at all.'],
        ],
        'reverse' => [
            'What reversing a voucher does',
            ['Write why it is reversed.'],
            ['A mirror entry with the debits and credits swapped is posted. Every account goes back to what it was before. Nothing is deleted.'],
            [],
        ],
        'type' => [
            'How a product type works',
            ['Name the type (for example "Smart watch") and list the details each piece needs, one per line. The first detail is the one searched, such as IMEI.'],
            ['No money or stock changes. Products of this type will ask for these details on stock in.'],
            [],
        ],
        'category' => [
            'How a category works',
            ['Give it a short name such as "Chargers and cables".'],
            ['No money or stock changes. Categories only group products for search and reports.'],
            [],
        ],
        'brand' => [
            'How a brand works',
            ['Write the brand, the product type it belongs to, and its models.'],
            ['No money or stock changes. Brands and models fill in quickly when you add products.'],
            [],
        ],
        'vendor' => [
            'How adding a vendor works',
            ['Write the vendor\'s name, shop or company and phone. These are the people you buy stock or services from.'],
            ['No money changes. The vendor can now be chosen on stock in and on payments, and gets their own account showing what you owe them.'],
            ['Stock bought on credit from this vendor adds to what you owe them; every payment takes it down.'],
        ],
        'vendor_pay' => [
            'How paying a vendor works',
            ['Write how much you paid and whether it was cash or bank.'],
            ['Cash or bank goes down, and what you owe this vendor goes down by the same amount. Profit does not change: the stock was already counted when it came in.'],
            ['The vendor\'s page shows the new balance straight away.'],
        ],
        'vendor_loan' => [
            'How cash borrowed from a vendor works',
            ['Write how much cash the vendor gave you and whether it went to cash or bank.'],
            ['Cash or bank goes up, and you owe this vendor that much more. It is not income, so profit does not change.'],
            ['Pay it back later with "Pay vendor", like any other amount you owe them.'],
        ],
        'vendor_bill' => [
            'How a vendor\'s bill works',
            [
                'Use it for a bill that is not stock: repair, transport, printing, rent paid to them.',
                'Write the bill total, what it was for, and how much you paid now. Leave "Paid now" at 0 if you will pay later.',
            ],
            [
                'The full bill is an expense and lowers profit straight away.',
                'Cash or bank goes down by what you paid now; the rest is owed to this vendor.',
            ],
            [],
        ],
        'vendor_opening' => [
            'How an old vendor balance works',
            ['Use this once, for money you already owed this vendor before you started using the system.'],
            ['What you owe this vendor starts at this amount. It is set against the owner\'s capital, so profit does not change.'],
            [],
        ],
        'employee' => [
            'How adding an employee works',
            [
                'Write the name, phone and job. Tick "Salesman" for anyone who sells; they can then be chosen on sales.',
                'Write the monthly salary, and the commission % if they earn one on their sales.',
            ],
            ['No money changes. The employee gets their own page with salary, advances and, for a salesman, sales and commission.'],
            ['Commission is worked out on the sale price of the bills and installment plans they made.'],
        ],
        'salary' => [
            'How paying a salary works',
            [
                'Check the salary amount and the month it is for.',
                'If the employee owes an advance, write how much to take back from this salary.',
                'Choose cash or bank.',
            ],
            [
                'The full salary is an expense and lowers profit.',
                'The advance taken back lowers what the employee owes you.',
                'Cash or bank goes down only by what you actually hand over.',
            ],
            [],
        ],
        'advance' => [
            'How an advance works',
            ['Write how much the employee took and whether it was cash or bank.'],
            ['Cash or bank goes down and the employee owes you this amount. It is not an expense, so profit does not change.'],
            ['Take it back later from a salary: the salary screen offers it.'],
        ],
        'commission' => [
            'How paying commission works',
            ['The amount starts at the commission earned and not yet paid. Change it if you agreed another amount.'],
            ['Cash or bank goes down and the commission is an expense, so it lowers profit.'],
            [],
        ],
    ];

    /** The same guides in Roman Urdu, line for line. */
    public const UR = [
        'sale' => [
            'Sale kaise hoti hai',
            [
                'Har product search karke add karein. Save ki hui retail ya wholesale qeemat khud aa jati hai; agar koi aur qeemat tay hui hai to badal dein.',
                'Mobile, bike aur numbered pieces ke liye har bikne wale piece ka IMEI / engine no. scan karein ya likhein. Khaali chhorein to stock ke sab se purane pieces pehle bikenge.',
                'Customer chunein, ya sirf walk-in ka naam likh dein. Agar koi raqam baad mein leni hai to save kiya hua customer zaroori hai.',
                'Customer ne abhi jitne paise diye wo likhein aur kaise diye (cash ya bank). Total se jo kam hai wo us customer ke udhaar mein chala jata hai.',
            ],
            [
                'Har piece usi qeemat par stock se nikalta hai jis par wo khaas piece khareeda gaya tha. 5,000 aur 6,000 wale phone ka munafa alag alag sahi nikalta hai.',
                'Mile hue paise Cash in hand ya Bank mein jate hain. Baqi raqam Customer credit (udhaar) mein jati hai.',
                'Sale ki aamdani aur maal ki laagat dono darj hoti hain. Dono ka farq is sale ka munafa hai.',
            ],
            [
                'Save hui sale edit nahi hoti. Ghalat ho to cancel karein (pieces stock mein wapas aa jate hain) aur dobara banayein.',
                'Udhaar sale ke baad mein milne wale paise sale ke page par "Receive payment" se lein.',
            ],
        ],
        'contract' => [
            'Qiston par sale kaise hoti hai',
            [
                'Customer chunein, ya naya customer CNIC, phone aur pata ke saath add karein. Aik ya do zamin (guarantor) bhi likhein.',
                'Product aur wo khaas piece (IMEI / engine no.) chunein jo customer le ja raha hai.',
                'Cash qeemat, advance (down payment), markup aur qiston ki tadaad likhein. Jo schedule bane wo check kar lein.',
                'Save karein, phir agreement print karke dono taraf se dastakhat karwa lein.',
            ],
            [
                'Piece usi qeemat par stock se nikalta hai jis par aap ne khareeda tha.',
                'Advance Cash in hand mein jata hai. Baqi qeemat aur markup Installment receivable ban jata hai, yani customer ne aap ko itna dena hai.',
                'Markup abhi munafa nahi ginta. Wo "baad mein kamane wala markup" ban kar rukta hai aur har qist ke saath thora thora munafa banta hai, taa\'ke munafa kabhi asal se zyada na dikhe.',
            ],
            [
                'Save hone ke baad plan edit nahi hota. Kuch ghalat ho to advance ki receipt cancel karein aur naya plan banayein.',
                'Rounding har qist ko gol raqam bana deti hai; aakhri qist chhota sa farq le leti hai.',
            ],
        ],
        'collect' => [
            'Qist kaise wasool hoti hai',
            [
                'Customer ne jitni raqam di wo likhein. Ye aik qist se kam ya zyada ho sakti hai.',
                'Late fee leni ho to likhein, ya kuch maaf karna ho to discount likhein.',
                'Kaise di gayi: cash, bank, JazzCash ya Easypaisa.',
            ],
            [
                'Raqam sab se purani baqi qiston mein pehle lagti hai.',
                'Cash (ya bank) barhta hai aur customer ka baqaya utna hi kam ho jata hai.',
                'Is raqam mein jo markup hai wo ab munafa ban jata hai. Late fee alag aamdani hai; discount kharcha hai.',
            ],
            [
                'Receipt print karein ya bhej dein taa\'ke customer ke paas saboot ho.',
                'Ghalat receipt delete nahi hoti, cancel hoti hai: qistein dobara baqi ho jati hain aur hisaab ulta ho jata hai.',
            ],
        ],
        'void_receipt' => [
            'Receipt cancel karne se kya hota hai',
            ['Wajah likhein, misal "do dafa enter hui" ya "cheque wapas aa gaya".'],
            [
                'Aik ulti entry hisaab mein payment ko khatam kar deti hai. Kuch delete nahi hota, is liye record saaf rehta hai.',
                'Jo qistein is se ada hui thin wo dobara baqi ho jati hain aur customer ko wo raqam dobara deni hoti hai.',
            ],
            [],
        ],
        'write_off' => [
            'Plan write-off karne se kya hota hai',
            ['Ye sirf tab karein jab raqam wapas milne ki umeed na ho. Wajah likhein.'],
            [
                'Customer ka baqaya receivable se hat jata hai.',
                'Jo markup abhi kamaya nahi gaya wo hat jata hai, aur baqi raqam nuqsan (bad debt) ban kar munafa kam karti hai.',
                'Plan band ho jata hai. Pehle li hui receipts waisi hi rehti hain.',
            ],
            ['Agar customer baad mein paise de de to receipt voucher se "Other income" mein lein.'],
        ],
        'sale_receive' => [
            'Udhaar ki wasooli kaise hoti hai',
            ['Mili hui raqam, tareekh aur kaise mili, likhein.'],
            ['Cash ya bank barhta hai aur customer ka udhaar utna hi kam hota hai. Munafa nahi badalta: wo sale ke waqt gin liya gaya tha.'],
            [],
        ],
        'sale_cancel' => [
            'Sale cancel karne se kya hota hai',
            ['Sale cancel karne ki wajah likhein.'],
            [
                'Pieces apni asal laagat par stock mein wapas aa jate hain.',
                'Sale, us ki laagat aur payments hisaab mein ulti entries se khatam ho jati hain. Kuch delete nahi hota.',
            ],
            ['Agar customer ko paise wapas diye gaye the to wo bhi isi mein shamil hai.'],
        ],
        'stock' => [
            'Stock in kaise hota hai',
            [
                'Kitne pieces aaye aur aik piece aap ko kitne ka pada, likhein.',
                'Mobile aur bike ke liye har IMEI / engine no. alag line mein likhein. Lines ki ginti pieces ke barabar honi chahiye.',
                'Kaise paise diye: cash, bank, udhaar par agar supplier ko abhi dene hain, ya "Already in the shop" agar maal is system se pehle ka dukaan mein para hai.',
                'Kuch abhi diye, kuch baad mein? Cash ya bank chunein aur "Paid now" mein abhi di gayi raqam likhein. Baqi vendor ke khate mein chali jati hai.',
            ],
            [
                'Pieces isi laagat par stock mein aate hain. Har piece apni laagat yaad rakhta hai, is liye baad mein munafa sahi nikalta hai.',
                'Cash ya bank utna kam hota hai jitna abhi diya, aur jo nahi diya wo us vendor ka qarz (Accounts payable) ban jata hai.',
                'Abhi koi munafa ya nuqsan nahi. Munafa tab ginta hai jab har piece bikta hai.',
            ],
            ['Aik hi IMEI / engine no. stock mein do dafa nahi ho sakta. System dobara aane se rok deta hai.'],
        ],
        'product' => [
            'Product kaise add hota hai',
            [
                'Product type chunein. Is se tay hota hai ke har piece ke liye kya likha jayega (IMEI, engine no., serial).',
                'Brand aur model chunein, ya naya add karein. Agar aik se zyada qism rakhte hain to variant likhein, jaise "8/256 GB" ya "2025".',
                'Retail qeemat likhein, aur dukandaron ko bechte hain to wholesale qeemat bhi.',
            ],
            [
                'Paise ya stock mein kuch nahi badalta. Product sirf save hota hai taa\'ke dobara likhna na pade.',
                'Ab ye sale aur qiston ke plan par search karne se nazar aayega.',
                'Jab maal aaye to "Stock in" se pieces add karein.',
            ],
            ['Baad mein qeemat badalne se sirf nayi sales badalti hain. Purani sales apni qeemat par rehti hain.'],
        ],
        'customer' => [
            'Customer kaise add hota hai',
            [
                'Poora naam, walid / shohar ka naam aur chalta hua mobile number likhein.',
                'Qiston ke liye CNIC aur ghar ka pata bhi likhein. Tasveer wasooli mein madad karti hai.',
            ],
            [
                'Paise mein kuch nahi badalta. Ab ye customer sale aur qiston ke plan par chuna ja sakta hai.',
                'Us ke page par har plan, har payment aur baqaya nazar aayega.',
            ],
            ['Pehle search kar lein, taa\'ke aik hi banda do dafa add na ho.'],
        ],
        'payment' => [
            'Payment voucher kaise kaam karta hai',
            [
                'Un paison ke liye jo bahar ja rahe hain aur stock nahi hain: kiraya, tankhwah, bill, petrol, supplier ko adaigi, ya malik ka paise nikalna.',
                'Chunein ke paise kis cheez ke liye gaye, raqam likhein, aur cash se gaye ya bank se. Chhoti si tafseel likhein.',
            ],
            [
                'Cash ya bank kam hota hai.',
                'Kharcha (kiraya, tankhwah, bill) barhta hai aur munafa kam karta hai. Supplier ko adaigi se qarz kam hota hai, aur malik ke paise nikalne se munafa nahi badalta.',
            ],
            ['Post hua voucher edit nahi hota. Ghalat ho to reverse karein aur dobara post karein.'],
        ],
        'receipt' => [
            'Receipt voucher kaise kaam karta hai',
            [
                'Un paison ke liye jo aa rahe hain magar sale ya qist nahi: malik ka capital dalna, qarz milna, ya koi aur aamdani.',
                'Chunein ke paise kahan se aaye, raqam likhein, aur cash mein aaye ya bank mein.',
            ],
            [
                'Cash ya bank barhta hai.',
                'Capital ya qarz barhta hai (ye munafa nahi). Sirf "Other income" munafa ginti hai.',
            ],
            ['Sales aur qiston ki apni screens hain; unhein yahan enter na karein.'],
        ],
        'journal' => [
            'Journal voucher kaise kaam karta hai',
            [
                'Adjustment aur opening balance ke liye, jab paise aam tareeqe se nahi hilte.',
                'Har account ke liye chunein ke wo barhega ya ghatega, aur kitna. Debit credit jaanne ki zaroorat nahi; system khud lagata hai.',
                'Har entry ki do taraf hoti hain jo barabar hon. Misal: cash 50,000 barha aur maalik ka capital 50,000 barha.',
            ],
            ['Bilkul wahi lines post hoti hain jo aap likhte hain. Neeche wala box batata hai ke har account par kya asar hoga.'],
            ['Zyada tar entries, opening balance bhi, "New entry · Kya hua?" se aasan hain, jahan accounting ki zaroorat hi nahi.'],
        ],
        'reverse' => [
            'Voucher reverse karne se kya hota hai',
            ['Reverse karne ki wajah likhein.'],
            ['Debit aur credit ulte kar ke aik nayi entry post hoti hai. Har account wapas pehle jaisa ho jata hai. Kuch delete nahi hota.'],
            [],
        ],
        'type' => [
            'Product type kaise kaam karta hai',
            ['Type ka naam likhein (misal "Smart watch") aur har piece ki zaroori tafseel aik aik line mein. Pehli tafseel wo hai jis se search hoti hai, jaise IMEI.'],
            ['Paise ya stock nahi badalta. Is type ke products stock in par yehi tafseel poochenge.'],
            [],
        ],
        'category' => [
            'Category kaise kaam karti hai',
            ['Chhota sa naam rakhein, jaise "Chargers and cables".'],
            ['Paise ya stock nahi badalta. Category sirf products ko search aur reports ke liye ikatha karti hai.'],
            [],
        ],
        'brand' => [
            'Brand kaise kaam karta hai',
            ['Brand ka naam, kis product type ka hai, aur us ke models likhein.'],
            ['Paise ya stock nahi badalta. Products add karte waqt brand aur model jaldi bhar jate hain.'],
            [],
        ],
        'vendor' => [
            'Vendor kaise add hota hai',
            ['Vendor ka naam, dukaan ya company aur phone likhein. Ye wo log hain jin se aap maal ya service lete hain.'],
            ['Paise nahi badalte. Ab ye vendor stock in aur payment par chuna ja sakta hai, aur is ka apna khata banta hai jis mein likha hota hai ke aap ne isay kitna dena hai.'],
            ['Is vendor se udhaar par liya maal aap ka qarz barhata hai; har payment usay kam karti hai.'],
        ],
        'vendor_pay' => [
            'Vendor ko payment kaise hoti hai',
            ['Kitne paise diye aur cash se ya bank se, likhein.'],
            ['Cash ya bank kam hota hai, aur is vendor ka qarz bhi utna hi kam hota hai. Munafa nahi badalta: maal aate waqt gin liya gaya tha.'],
            ['Vendor ke page par naya baqaya foran nazar aata hai.'],
        ],
        'vendor_loan' => [
            'Vendor se cash udhaar kaise likhein',
            ['Vendor ne kitna cash diya aur wo cash mein aaya ya bank mein, likhein.'],
            ['Cash ya bank barhta hai, aur is vendor ka qarz utna hi barhta hai. Ye aamdani nahi, is liye munafa nahi badalta.'],
            ['Baad mein "Pay vendor" se wapas karein, baqi qarz ki tarah.'],
        ],
        'vendor_bill' => [
            'Vendor ka bill kaise likhein',
            [
                'Us bill ke liye jo maal nahi: repair, transport, printing, ya unhein diya kiraya.',
                'Bill ki kul raqam, kis cheez ka tha, aur abhi kitna diya, likhein. Baad mein dena ho to "Paid now" 0 rehne dein.',
            ],
            [
                'Poora bill kharcha hai aur munafa foran kam karta hai.',
                'Cash ya bank utna kam hota hai jitna abhi diya; baqi is vendor ka qarz ban jata hai.',
            ],
            [],
        ],
        'vendor_opening' => [
            'Vendor ka purana baqaya kaise likhein',
            ['Ye sirf aik dafa, us qarz ke liye jo system shuru karne se pehle is vendor ka dena tha.'],
            ['Is vendor ka qarz isi raqam se shuru hota hai. Ye maalik ke capital se kam hota hai, is liye munafa nahi badalta.'],
            [],
        ],
        'employee' => [
            'Employee kaise add hota hai',
            [
                'Naam, phone aur kaam likhein. Jo bechta hai us ke liye "Salesman" par tick karein; phir wo sale par chuna ja sakta hai.',
                'Mahana tankhwah likhein, aur agar sale par commission milta hai to commission %.',
            ],
            ['Paise nahi badalte. Employee ka apna page banta hai jis mein tankhwah, advance aur salesman ke liye sales aur commission hota hai.'],
            ['Commission us ke banaye bills aur qiston ke plans ki sale qeemat par ginta hai.'],
        ],
        'salary' => [
            'Tankhwah kaise di jati hai',
            [
                'Tankhwah ki raqam aur kis mahine ki hai, check karein.',
                'Agar employee ne advance liya hua hai to likhein ke is tankhwah se kitna wapas lena hai.',
                'Cash ya bank chunein.',
            ],
            [
                'Poori tankhwah kharcha hai aur munafa kam karti hai.',
                'Wapas liya advance employee ka qarz kam karta hai.',
                'Cash ya bank sirf utna kam hota hai jitna aap asal mein hath mein dete hain.',
            ],
            [],
        ],
        'advance' => [
            'Advance kaise diya jata hai',
            ['Employee ne kitne liye aur cash se ya bank se, likhein.'],
            ['Cash ya bank kam hota hai aur employee ne ye raqam aap ko wapas deni hai. Ye kharcha nahi, is liye munafa nahi badalta.'],
            ['Baad mein tankhwah se wapas lein: tankhwah wali screen khud poochti hai.'],
        ],
        'commission' => [
            'Commission kaise diya jata hai',
            ['Raqam pehle se wo hoti hai jo commission bana aur abhi diya nahi gaya. Koi aur raqam tay hui ho to badal dein.'],
            ['Cash ya bank kam hota hai aur commission kharcha hai, is liye munafa kam karta hai.'],
            [],
        ],
    ];

    /** @return array{title: string, how: array<int, string>, impact: array<int, string>, tips: array<int, string>}|null */
    public static function get(string $key, string $lang = 'en'): ?array
    {
        $all = $lang === 'ur' ? self::UR : self::ALL;
        if (! isset($all[$key])) {
            return null;
        }
        [$title, $how, $impact, $tips] = $all[$key];

        return compact('title', 'how', 'impact', 'tips');
    }
}
