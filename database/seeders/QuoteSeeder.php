<?php

namespace Database\Seeders;

use App\Models\Quote;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class QuoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $eventBaseDate = CarbonImmutable::now('Pacific/Auckland')->startOfDay();

        $quotes = [
            [
                'name' => 'Aroha Thompson',
                'email' => 'aroha.thompson@example.test',
                'phone' => '021 456 102',
                'guest_count' => 20,
                'package_name' => 'Mini Party Sparkle',
                'services_requested' => ['Face Painting'],
                'travel_area' => 'Auckland Central',
                'venue_type' => 'outdoor',
                'heard_about' => 'Google search',
                'notes' => 'Birthday party for 5 to 7 year olds. Backyard setup with shaded area.',
                'terms_accepted' => true,
                'event_type' => 'Birthday Party',
                'event_date' => $eventBaseDate->addDays(7)->toDateString(),
                'address' => '25 Seaview Road, Mission Bay, Auckland',
                'start_time' => '10:00:00',
                'end_time' => '12:00:00',
            ],
            [
                'name' => 'Liam Ngata',
                'email' => 'liam.ngata@example.test',
                'phone' => '022 991 7788',
                'guest_count' => 60,
                'package_name' => 'Classic Birthday Magic',
                'services_requested' => ['Face Painting', 'Glitter Tattoos'],
                'travel_area' => 'North Shore',
                'venue_type' => 'mixed',
                'heard_about' => 'Facebook',
                'notes' => 'Community day with a steady line expected between 1 and 3pm.',
                'terms_accepted' => true,
                'event_type' => 'Community Event',
                'event_date' => $eventBaseDate->addDays(10)->toDateString(),
                'address' => '8 Lake Road, Takapuna, Auckland',
                'start_time' => '13:00:00',
                'end_time' => '16:00:00',
            ],
            [
                'name' => 'Mia Patel',
                'email' => 'mia.patel@example.test',
                'phone' => '027 331 5091',
                'guest_count' => 120,
                'package_name' => 'Festival Crowd Package',
                'services_requested' => ['Festival Bling', 'Waterproof Festival Designs'],
                'travel_area' => 'South Auckland',
                'venue_type' => 'outdoor',
                'heard_about' => 'Instagram',
                'notes' => 'Food festival activation with bright, fast designs for photos.',
                'terms_accepted' => true,
                'event_type' => 'Festival',
                'event_date' => $eventBaseDate->addDays(14)->toDateString(),
                'address' => '120 Great South Road, Papatoetoe, Auckland',
                'start_time' => '11:00:00',
                'end_time' => '15:00:00',
            ],
            [
                'name' => 'Noah Rangi',
                'email' => 'noah.rangi@example.test',
                'phone' => '021 780 442',
                'guest_count' => 35,
                'package_name' => 'Classic Birthday Magic',
                'services_requested' => ['Face Painting', 'Themed Character Looks'],
                'travel_area' => 'West Auckland',
                'venue_type' => 'indoor',
                'heard_about' => 'Referral from a friend',
                'notes' => 'Wedding reception kids corner. Theme is pastel colours.',
                'terms_accepted' => true,
                'event_type' => 'Wedding',
                'event_date' => $eventBaseDate->addDays(21)->toDateString(),
                'address' => '55 Lincoln Road, Henderson, Auckland',
                'start_time' => '15:30:00',
                'end_time' => '18:00:00',
            ],
            [
                'name' => 'Sophie Wilson',
                'email' => 'sophie.wilson@example.test',
                'phone' => '020 4040 1122',
                'guest_count' => 200,
                'package_name' => 'Festival Crowd Package',
                'services_requested' => ['Face Painting', 'Glitter Tattoos'],
                'travel_area' => 'East Auckland',
                'venue_type' => 'outdoor',
                'heard_about' => 'Saw us at an event',
                'notes' => 'School fair booking. Peak crowd expected from late morning onward.',
                'terms_accepted' => true,
                'event_type' => 'School Fair',
                'event_date' => $eventBaseDate->addDays(28)->toDateString(),
                'address' => '14 Chapel Road, Flat Bush, Auckland',
                'start_time' => '09:00:00',
                'end_time' => '14:00:00',
            ],
        ];

        foreach ($quotes as $quoteData) {
            Quote::query()->updateOrCreate(
                ['email' => $quoteData['email']],
                $quoteData
            );
        }
    }
}
