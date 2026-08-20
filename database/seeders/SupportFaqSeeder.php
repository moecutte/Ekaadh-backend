<?php

namespace Database\Seeders;

use App\Models\SupportFaq;
use Illuminate\Database\Seeder;

class SupportFaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'locale' => 'en',
                'sort_order' => 1,
                'question' => 'How do I buy a ticket?',
                'answer' => 'Browse events on the home page, open an event, choose your ticket type, and complete checkout with Zaad, eDahab, Sahal, or EVC Plus.',
            ],
            [
                'locale' => 'en',
                'sort_order' => 2,
                'question' => 'Where are my tickets and QR code?',
                'answer' => 'Go to Booked Events or My Tickets. Each ticket has a QR code to show at the venue entrance.',
            ],
            [
                'locale' => 'en',
                'sort_order' => 3,
                'question' => 'How do I create a private invitation?',
                'answer' => 'Open Create Ticket, fill in your event details, pick an invitation design, add your text, and pay to publish.',
            ],
            [
                'locale' => 'en',
                'sort_order' => 4,
                'question' => 'How do I send invitations to guests?',
                'answer' => 'After creating your private event, open Manage Invitations, add guest phone numbers, and send. Each guest receives their link and tickets.',
            ],
            [
                'locale' => 'en',
                'sort_order' => 5,
                'question' => 'Payment succeeded but I have no ticket',
                'answer' => 'Check Booked Events with the phone number used at checkout. If it still missing, message us here with your order number.',
            ],
            [
                'locale' => 'en',
                'sort_order' => 6,
                'question' => 'How does QR check-in work?',
                'answer' => 'Staff scan your ticket QR at the door. Each code is valid once. Keep your phone brightness up for a clear scan.',
            ],
            [
                'locale' => 'so',
                'sort_order' => 1,
                'question' => 'Sidee baan tigidh u iibsadaa?',
                'answer' => 'Ka baadh dhacdooyinka bogga hore, fur dhacdada, dooro nooca tigidhka, oo dhammaystir lacag bixinta Zaad, eDahab, Sahal, ama EVC Plus.',
            ],
            [
                'locale' => 'so',
                'sort_order' => 2,
                'question' => 'Halkee ayaan ka helaa tigidhkeyga iyo QR?',
                'answer' => 'Tag Dhacdooyinka Ballansan ama Tigidhada. Tigidh kasta wuxuu leeyahay QR aad albaabka ku tusato.',
            ],
            [
                'locale' => 'so',
                'sort_order' => 3,
                'question' => 'Sidee baan u abuuraa martiqaad gaar ah?',
                'answer' => 'Fur Samee Tigidh, buuxi faahfaahinta dhacdada, dooro naqshadda martiqaadka, ku dar qoraalkaaga, oo bixi si aad u daabacdo.',
            ],
            [
                'locale' => 'so',
                'sort_order' => 4,
                'question' => 'Sidee baan u diraa martiqaadyada martida?',
                'answer' => 'Ka dib abuurista dhacdada gaarka ah, fur Maamul Martiqaadyada, ku dar lambarrada martida, oo dir. Marti kasta wuxuu helaa xiriiriyaha iyo tigidhkiisa.',
            ],
            [
                'locale' => 'so',
                'sort_order' => 5,
                'question' => 'Lacag bixintu way guulaysatay laakiin tigidh ma hayo',
                'answer' => 'Hubi Dhacdooyinka Ballansan lambarka aad checkout ku isticmaashay. Haddii weli ma jirto, noo soo qor lambarka amarkaaga halkan.',
            ],
            [
                'locale' => 'so',
                'sort_order' => 6,
                'question' => 'Sidee u shaqeeyaa gelitaanka QR?',
                'answer' => 'Shaqaaluhu waxay ka baaraan QR-ka tigidhkaaga albaabka. Koodh kasta hal mar ayuu shaqeeyaa. Iftiiminta telefoonka kor u qaad si scan u fududaado.',
            ],
        ];

        foreach ($faqs as $faq) {
            SupportFaq::query()->updateOrCreate(
                ['locale' => $faq['locale'], 'question' => $faq['question']],
                [
                    'answer' => $faq['answer'],
                    'sort_order' => $faq['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
