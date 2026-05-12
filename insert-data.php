<?php
// '../' ka matlab hai ek folder bahar nikalna
require_once __DIR__ . '/../vendor/autoload.php'; 

use MongoDB\Client;

try {
    $client = new Client("mongodb://localhost:27017");
    $collection = $client->renthub_db->products;

    // Pehle purana data clear karte hain taake duplicate na ho
    $collection->deleteMany([]);

    $collection->insertMany([
        [
            'name' => 'Sony Alpha a7 III (4K)',
            'category' => 'Cameras',
            'city' => 'Karachi',
            'price' => 2500,
            'image' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?q=80&w=1000',
            'status' => 'available'
        ],
        [
            'name' => 'Designer Bridal Red Lehanga',
            'category' => 'Dresses',
            'city' => 'Lahore',
            'price' => 12000,
            'image' => 'https://images.unsplash.com/photo-1594552072238-b8a33785b261?q=80&w=1000',
            'status' => 'available'
        ],
        [
            'name' => 'DJI Mavic 3 Pro Drone',
            'category' => 'Drones',
            'city' => 'Islamabad',
            'price' => 5500,
            'image' => 'https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=1000',
            'status' => 'available'
        ],
        [
            'name' => 'Luxury Wedding Stage Decor',
            'category' => 'Decor',
            'city' => 'Karachi',
            'price' => 45000,
            'image' => 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1000',
            'status' => 'available'
        ],
        [
            'name' => 'Epson 4K Home Theater Projector',
            'category' => 'Projectors',
            'city' => 'Lahore',
            'price' => 3500,
            'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRNYT5WKPEsEfWrIqxDqfd5pjiKFpU6ekZUCw&s',
            'status' => 'available'
        ],
        [
            'name' => 'JBL Professional Party Sound System',
            'category' => 'Sound Systems',
            'city' => 'Islamabad',
            'price' => 8000,
            'image' => 'https://images.unsplash.com/photo-1508700115892-45ecd05ae2ad?q=80&w=1000',
            'status' => 'available'
        ]
    ]);

    echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h1 style='color:#D4AF37;'>Mubarak Ho!</h1>
            <p style='color:#0A192F;'>Saari 6 Categories ka Data MongoDB mein insert ho gaya hai.</p>
            <a href='index.php' style='color:blue;'>Wapas Home Page par jayein</a>
          </div>";
          
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>