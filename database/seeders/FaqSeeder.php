<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'Can I arrange pickup from ferry terminal or resort?',
                'answer' => 'Yes, pickup options can be arranged depending on package, meeting point, and route availability.',
                'sort_order' => 10,
            ],
            [
                'question' => 'How do I confirm a booking?',
                'answer' => 'Choose a package and contact us through WhatsApp to confirm date, guests, pickup, and availability.',
                'sort_order' => 20,
            ],
            [
                'question' => 'Can packages be customized?',
                'answer' => 'Many tours and transfers can be adjusted for timing, route, or pickup location.',
                'sort_order' => 30,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                $faq + ['is_active' => true]
            );
        }
    }
}
