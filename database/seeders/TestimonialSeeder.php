<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TestimonialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testimonialsPath = database_path('seeders/data/testimonials.json');

        /** @var list<array{id:int,name:string,testimonial:string,created_at:string,urls:array<int,string>|null}> $testimonials */
        $testimonials = json_decode(File::get($testimonialsPath), true, 512, JSON_THROW_ON_ERROR);

        foreach ($testimonials as $testimonialData) {
            $createdAt = $testimonialData['created_at'];

            Testimonial::query()->updateOrCreate(
                [
                    'name' => $testimonialData['name'],
                    'testimonial' => $testimonialData['testimonial'],
                ],
                [
                    'urls' => $testimonialData['urls'],
                    'is_approved' => true,
                    'approved_at' => $createdAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );
        }
    }
}
