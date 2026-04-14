<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'name' => 'Wired mechanical keyboard',
                'description' => 'Clicky switches, aluminium case — solid for work and gaming.',
                'price' => 899.00,
                'image_url' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=600&q=80',
                'stock' => 24,
            ],
            [
                'name' => 'Wireless mouse',
                'description' => 'Quiet clicks, multi-device switching, months of battery life.',
                'price' => 249.50,
                'image_url' => 'https://images.unsplash.com/photo-1527814050087-3793815479db?w=600&q=80',
                'stock' => 60,
            ],
            [
                'name' => 'USB-C cable (2 m)',
                'description' => '100 W fast charge, braided jacket, strain-relief tested.',
                'price' => 79.00,
                'image_url' => 'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=600&q=80',
                'stock' => 120,
            ],
            [
                'name' => 'Bluetooth headphones',
                'description' => 'ANC, transparency mode, IPX4 sweat resistance.',
                'price' => 1299.00,
                'image_url' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&q=80',
                'stock' => 15,
            ],
            [
                'name' => 'Monitor arm',
                'description' => 'Fits 17–32 inch displays, gas spring height, built-in cable channel.',
                'price' => 459.00,
                'image_url' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=600&q=80',
                'stock' => 8,
            ],
            [
                'name' => 'A4 sketchbook (spiral)',
                'description' => '110 gsm acid-free paper, 80 sheets — pencils, ink, light markers.',
                'price' => 68.00,
                'image_url' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=600&q=80',
                'stock' => 45,
            ],
            [
                'name' => 'Fineliner pen set (0.3–0.8 mm)',
                'description' => 'Archival pigment ink, 6 tip sizes, smudge-resistant on most papers.',
                'price' => 118.00,
                'image_url' => 'https://images.unsplash.com/photo-1513542789411-b6a5d4f31634?w=600&q=80',
                'stock' => 70,
            ],
            [
                'name' => 'LED desk lamp (dimmable)',
                'description' => 'Warm to cool white, memory brightness, USB charging port on base.',
                'price' => 329.00,
                'image_url' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=600&q=80',
                'stock' => 22,
            ],
            [
                'name' => '1080p webcam',
                'description' => 'Autofocus, dual mics, privacy shutter — plug-and-play USB-A.',
                'price' => 399.00,
                'image_url' => 'https://images.unsplash.com/photo-1612817288484-6f916006741a?w=600&q=80',
                'stock' => 18,
            ],
            [
                'name' => 'Ring light (10 inch)',
                'description' => 'Adjustable tripod, phone holder, three colour temperatures.',
                'price' => 189.00,
                'image_url' => 'https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=600&q=80',
                'stock' => 30,
            ],
            [
                'name' => 'Acrylic paint starter kit',
                'description' => '12 × 22 ml tubes, high pigment, suitable for canvas and wood.',
                'price' => 156.00,
                'image_url' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=600&q=80',
                'stock' => 25,
            ],
            [
                'name' => 'Cutting mat (A2, self-healing)',
                'description' => '5-ply PVC, printed grid in cm — craft, model making, layouts.',
                'price' => 199.00,
                'image_url' => 'https://images.unsplash.com/photo-1615486511484-92e172cc4fe0?w=600&q=80',
                'stock' => 14,
            ],
            [
                'name' => 'Ergonomic mesh office chair',
                'description' => 'Lumbar support, tilt lock, height and arm adjustment.',
                'price' => 1899.00,
                'image_url' => 'https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=600&q=80',
                'stock' => 6,
            ],
            [
                'name' => 'Portable SSD (1 TB, USB-C)',
                'description' => 'Up to 1050 MB/s read, drop resistant, works with Mac and PC.',
                'price' => 799.00,
                'image_url' => 'https://images.unsplash.com/photo-1597872200969-2b65d56bd16b?w=600&q=80',
                'stock' => 33,
            ],
            [
                'name' => 'Graphics tablet (medium)',
                'description' => '8192 pressure levels, battery-free pen, 6 express keys.',
                'price' => 1099.00,
                'image_url' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=600&q=80',
                'stock' => 11,
            ],
            [
                'name' => 'Paper tape set (washi, 8 rolls)',
                'description' => 'Low tack, removable — journaling, masking, temporary labels.',
                'price' => 42.00,
                'image_url' => 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?w=600&q=80',
                'stock' => 88,
            ],
            [
                'name' => 'Desk organiser (bamboo)',
                'description' => 'Compartments for pens, phone slot, small drawer for clips.',
                'price' => 139.00,
                'image_url' => 'https://images.unsplash.com/photo-1484480974693-6ca0a78fb36b?w=600&q=80',
                'stock' => 19,
            ],
        ];

        foreach ($rows as $row) {
            Product::query()->updateOrCreate(
                ['name' => $row['name']],
                $row
            );
        }
    }
}
