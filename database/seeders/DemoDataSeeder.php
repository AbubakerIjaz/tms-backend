<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Client;
use App\Models\ClientMeasurement;
use App\Models\Design;
use App\Models\GalleryItem;
use App\Models\GarmentType;
use App\Models\Order;
use App\Models\Shop;
use App\Models\StitchingSize;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Pakistani tailor shop demo data.
 *
 * Login: demo@alhayattailors.pk / password
 */
class DemoDataSeeder extends Seeder
{
    private const DEMO_SLUG = 'al-hayat-tailors';

    /** @var list<string> */
    private const DEMO_EMAILS = [
        'demo@alhayattailors.pk',
        'staff@alhayattailors.pk',
    ];

    public function run(): void
    {
        $this->resetDemoData();

        $shop = Shop::create([
      'name' => 'Al-Hayat Tailors & Boutique',
      'slug' => self::DEMO_SLUG,
      'type' => 'both',
      'logo_path' => 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=200&h=200&fit=crop&q=80',
      'phone' => '+92 42 35761234',
      'email' => 'info@alhayattailors.pk',
      'address' => 'Shop 14, Main Boulevard, Gulberg III',
      'city' => 'Lahore',
      'currency' => 'PKR',
      'measurement_unit' => 'inch',
      'settings' => [
        'invoice_prefix' => 'AHT',
        'default_due_days' => 14,
        'module_clients' => true,
        'module_orders' => true,
        'module_designs' => true,
        'module_garment_types' => true,
        'module_gallery' => true,
        'module_categories' => true,
        'module_accounts' => true,
        'module_measurements' => true,
        'module_voice_measurements' => true,
        'urdu_labels_enabled' => false,
        'email_enabled' => false,
        'email_admin_address' => 'info@alhayattailors.pk',
        'email_on_order_created' => true,
        'email_on_order_updated' => true,
        'email_on_order_ready' => true,
        'email_on_payment_received' => true,
        'email_on_transaction' => true,
        'whatsapp_enabled' => true,
      ],
    ]);

    User::create([
      'shop_id' => $shop->id,
      'name' => 'Muhammad Imran',
      'email' => 'demo@alhayattailors.pk',
      'password' => Hash::make('password'),
      'phone' => '+92 300 1234567',
      'role' => 'admin',
    ]);

    User::create([
      'shop_id' => $shop->id,
      'name' => 'Usman Ali',
      'email' => 'staff@alhayattailors.pk',
      'password' => Hash::make('password'),
      'phone' => '+92 321 9876543',
      'role' => 'staff',
    ]);

    $garmentTypes = $this->seedGarmentTypes($shop);
    $categories = $this->seedCategories($shop);
    $designs = $this->seedDesigns($shop, $garmentTypes);
    $this->seedGallery($shop, $categories);
    $clients = $this->seedClients($shop);
    $this->seedStitchingSizes($shop, $clients);
    $this->seedClientMeasurements($shop, $clients, $garmentTypes);
    $orders = $this->seedOrders($shop, $clients, $designs, $garmentTypes);
    $this->seedTransactions($shop, $clients, $orders);
    }

    private function resetDemoData(): void
    {
        $shop = Shop::where('slug', self::DEMO_SLUG)->first();

        if ($shop) {
            User::where('shop_id', $shop->id)->delete();
            $shop->delete();
        }

        // Users survive shop delete (shop_id is nullOnDelete) — remove orphans from prior runs.
        User::whereIn('email', self::DEMO_EMAILS)->delete();
    }

    /** @return array<string, GarmentType> */
  private function seedGarmentTypes(Shop $shop): array
  {
    $types = [
      'kameez' => [
        'name' => 'Kameez Shalwar',
        'description' => 'Traditional Pakistani outfit — kameez with matching shalwar.',
        'measurement_fields' => [
          ['key' => 'lambai', 'label' => 'Lambai'],
          ['key' => 'chati', 'label' => 'Chati'],
          ['key' => 'kamar', 'label' => 'Kamar'],
          ['key' => 'bazoo', 'label' => 'Bazoo'],
          ['key' => 'collar', 'label' => 'Collar'],
        ],
      ],
      'sherwani' => [
        'name' => 'Sherwani',
        'description' => 'Formal wedding sherwani with embroidery options.',
        'measurement_fields' => [
          ['key' => 'length', 'label' => 'Length'],
          ['key' => 'chest', 'label' => 'Chest'],
          ['key' => 'waist', 'label' => 'Waist'],
          ['key' => 'shoulder', 'label' => 'Shoulder'],
          ['key' => 'sleeve', 'label' => 'Sleeve'],
        ],
      ],
      'kurta' => [
        'name' => 'Kurta',
        'description' => 'Casual and semi-formal kurta stitching.',
        'measurement_fields' => [
          ['key' => 'length', 'label' => 'Length'],
          ['key' => 'chest', 'label' => 'Chest'],
          ['key' => 'sleeve', 'label' => 'Sleeve'],
        ],
      ],
      'waistcoat' => [
        'name' => 'Waistcoat',
        'description' => 'Waistcoat for formal and wedding wear.',
        'measurement_fields' => [
          ['key' => 'chest', 'label' => 'Chest'],
          ['key' => 'length', 'label' => 'Length'],
          ['key' => 'shoulder', 'label' => 'Shoulder'],
        ],
      ],
      'bridal' => [
        'name' => 'Bridal Dress',
        'description' => 'Bridal lehnga, maxi, and formal bridal wear.',
        'measurement_fields' => [
          ['key' => 'bust', 'label' => 'Bust'],
          ['key' => 'waist', 'label' => 'Waist'],
          ['key' => 'hips', 'label' => 'Hips'],
          ['key' => 'length', 'label' => 'Length'],
        ],
      ],
      'shirt' => [
        'name' => 'Formal Shirt',
        'description' => 'Office and formal dress shirts.',
        'measurement_fields' => [
          ['key' => 'chest', 'label' => 'Chest'],
          ['key' => 'collar', 'label' => 'Collar'],
          ['key' => 'sleeve', 'label' => 'Sleeve'],
          ['key' => 'length', 'label' => 'Length'],
        ],
      ],
    ];

    $created = [];
    foreach ($types as $key => $data) {
      $created[$key] = $shop->garmentTypes()->create($data);
    }

    return $created;
  }

  /** @return array<string, Category> */
  private function seedCategories(Shop $shop): array
  {
    $names = [
      'wedding' => 'Wedding Collection',
      'eid' => 'Eid Collection',
      'formal' => 'Formal Wear',
      'casual' => 'Casual Kurta',
      'kids' => 'Kids Wear',
    ];

    $created = [];
    foreach ($names as $key => $name) {
      $created[$key] = $shop->categories()->create([
        'name' => $name,
        'slug' => Str::slug($name),
        'description' => "Al-Hayat {$name} — handcrafted in Lahore.",
      ]);
    }

    return $created;
  }

  /**
   * @param  array<string, GarmentType>  $garmentTypes
   * @return array<string, Design>
   */
  private function seedDesigns(Shop $shop, array $garmentTypes): array
  {
    $items = [
      'sherwani_royal' => [
        'garment_type_id' => $garmentTypes['sherwani']->id,
        'name' => 'Royal Ivory Sherwani',
        'description' => 'Hand-embroidered ivory sherwani with gold thread work — popular for baraat.',
        'base_price' => 45000,
        'image_path' => 'https://images.unsplash.com/photo-1617137968427-85924c800a41?w=800&q=80',
      ],
      'kurta_linen' => [
        'garment_type_id' => $garmentTypes['kurta']->id,
        'name' => 'Linen Summer Kurta',
        'description' => 'Light linen kurta — ideal for Lahore summers and Eid.',
        'base_price' => 8500,
        'image_path' => 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=800&q=80',
      ],
      'kameez_formal' => [
        'garment_type_id' => $garmentTypes['kameez']->id,
        'name' => 'Embroidered Shalwar Kameez',
        'description' => 'Premium cotton with collar embroidery and contrast shalwar.',
        'base_price' => 12000,
        'image_path' => 'https://images.unsplash.com/photo-1558171813-4c088753af8f?w=800&q=80',
      ],
      'bridal_lehnga' => [
        'garment_type_id' => $garmentTypes['bridal']->id,
        'name' => 'Bridal Lehnga — Gulabi',
        'description' => 'Heavy dupatta lehnga with zardozi and stone work.',
        'base_price' => 185000,
        'image_path' => 'https://images.unsplash.com/photo-1583939003579-730e3918a45a?w=800&q=80',
      ],
      'shirt_double_cuff' => [
        'garment_type_id' => $garmentTypes['shirt']->id,
        'name' => 'Double Cuff Formal Shirt',
        'description' => 'French cuff shirt for office and corporate events.',
        'base_price' => 6500,
        'image_path' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=800&q=80',
      ],
      'waistcoat_velvet' => [
        'garment_type_id' => $garmentTypes['waistcoat']->id,
        'name' => 'Velvet Wedding Waistcoat',
        'description' => 'Maroon velvet waistcoat paired with sherwani or kurta.',
        'base_price' => 15000,
        'image_path' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&q=80',
      ],
      'kurta_eid' => [
        'garment_type_id' => $garmentTypes['kurta']->id,
        'name' => 'Eid Special Kurta',
        'description' => 'Festive kurta with subtle machine embroidery.',
        'base_price' => 9500,
        'image_path' => 'https://images.unsplash.com/photo-1490578474895-699cd4e2cf59?w=800&q=80',
      ],
      'sherwani_black' => [
        'garment_type_id' => $garmentTypes['sherwani']->id,
        'name' => 'Black Velvet Sherwani',
        'description' => 'Evening wedding sherwani with silver buttons.',
        'base_price' => 52000,
        'image_path' => 'https://images.unsplash.com/photo-1617127365659-c47fa864d8bc?w=800&q=80',
      ],
    ];

    $created = [];
    foreach ($items as $key => $data) {
      $created[$key] = $shop->designs()->create($data);
    }

    return $created;
  }

  /** @param  array<string, Category>  $categories */
  private function seedGallery(Shop $shop, array $categories): void
  {
    $items = [
      ['title' => 'Baraat Sherwani Setup', 'category' => 'wedding', 'description' => 'Complete groom look — sherwani, waistcoat, and turban styling.', 'image' => 'https://images.unsplash.com/photo-1617137968427-85924c800a41?w=900&q=80'],
      ['title' => 'Bridal Lehnga Fitting', 'category' => 'wedding', 'description' => 'Final fitting session for bridal lehnga at our Gulberg studio.', 'image' => 'https://images.unsplash.com/photo-1583939003579-730e3918a45a?w=900&q=80'],
      ['title' => 'Eid Kurta Collection 2025', 'category' => 'eid', 'description' => 'Fresh pastel kurtas for Chand Raat and Eid day.', 'image' => 'https://images.unsplash.com/photo-1490578474895-699cd4e2cf59?w=900&q=80'],
      ['title' => 'Formal Office Shirts', 'category' => 'formal', 'description' => 'Crisp cotton shirts tailored to measure.', 'image' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=900&q=80'],
      ['title' => 'Traditional Shalwar Kameez', 'category' => 'casual', 'description' => 'Everyday cotton shalwar kameez — comfortable Lahori cut.', 'image' => 'https://images.unsplash.com/photo-1558171813-4c088753af8f?w=900&q=80'],
      ['title' => 'Kids Eid Outfits', 'category' => 'kids', 'description' => 'Matching kurta shalwar sets for boys aged 5–12.', 'image' => 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=900&q=80'],
      ['title' => 'Fabric Selection — Liberty', 'category' => 'formal', 'description' => 'Premium suiting and cotton from Liberty Market, Lahore.', 'image' => 'https://images.unsplash.com/photo-1566174053879-31528523f8ae?w=900&q=80'],
      ['title' => 'Mehndi Bridal Maxi', 'category' => 'wedding', 'description' => 'Yellow and green mehndi outfit with gota work.', 'image' => 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=900&q=80'],
      ['title' => 'Velvet Waistcoat Display', 'category' => 'wedding', 'description' => 'Wedding season waistcoats in maroon, navy, and emerald.', 'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=900&q=80'],
      ['title' => 'Boutique Window Display', 'category' => 'eid', 'description' => 'Our Gulberg III storefront — visit for free measurement.', 'image' => 'https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=900&q=80'],
    ];

    foreach ($items as $item) {
      $shop->galleryItems()->create([
        'category_id' => $categories[$item['category']]->id,
        'title' => $item['title'],
        'description' => $item['description'],
        'image_path' => $item['image'],
      ]);
    }
  }

  /** @return list<Client> */
  private function seedClients(Shop $shop): array
  {
    $clients = [
      ['name' => 'Ahmed Hassan', 'phone' => '+92 300 1122334', 'email' => 'ahmed.hassan@gmail.com', 'address' => 'DHA Phase 5, Karachi', 'gender' => 'male', 'notes' => 'Regular customer — prefers slim fit kameez.'],
      ['name' => 'Fatima Khan', 'phone' => '+92 321 4455667', 'email' => 'fatima.khan@outlook.com', 'address' => 'Model Town, Lahore', 'gender' => 'female', 'notes' => 'Bridal booking March 2025.'],
      ['name' => 'Muhammad Usman', 'phone' => '+92 333 7788990', 'email' => null, 'address' => 'Satiana Road, Faisalabad', 'gender' => 'male', 'notes' => 'Sherwani for cousin wedding.'],
      ['name' => 'Ayesha Malik', 'phone' => '+92 345 2233445', 'email' => 'ayesha.malik@yahoo.com', 'address' => 'Bahria Town, Islamabad', 'gender' => 'female', 'notes' => 'Eid orders every year.'],
      ['name' => 'Bilal Ahmed', 'phone' => '+92 302 5566778', 'email' => 'bilal.ahmed@gmail.com', 'address' => 'Gulberg III, Lahore', 'gender' => 'male', 'notes' => 'Office shirts — monthly orders.'],
      ['name' => 'Sana Tariq', 'phone' => '+92 311 9988776', 'email' => 'sana.tariq@gmail.com', 'address' => 'Clifton Block 8, Karachi', 'gender' => 'female', 'notes' => 'Bridal lehnga — deposit paid.'],
      ['name' => 'Hassan Raza', 'phone' => '+92 304 6677889', 'email' => null, 'address' => 'Cavalry Ground, Lahore', 'gender' => 'male', 'notes' => 'University formal events.'],
      ['name' => 'Hira Shah', 'phone' => '+92 320 3344556', 'email' => 'hira.shah@hotmail.com', 'address' => 'PWD Housing Society, Islamabad', 'gender' => 'female', 'notes' => 'Mehndi outfit pending delivery.'],
      ['name' => 'Faisal Mahmood', 'phone' => '+92 301 8899001', 'email' => 'faisal.m@gmail.com', 'address' => 'Jinnah Colony, Faisalabad', 'gender' => 'male', 'notes' => 'Whole family Eid stitching.'],
      ['name' => 'Rabia Nawaz', 'phone' => '+92 322 1122998', 'email' => 'rabia.nawaz@gmail.com', 'address' => 'Johar Town, Lahore', 'gender' => 'female', 'notes' => 'Referred by Fatima Khan.'],
      ['name' => 'Omar Sheikh', 'phone' => '+92 306 4455778', 'email' => null, 'address' => 'North Nazimabad, Karachi', 'gender' => 'male', 'notes' => 'Velvet sherwani — wedding 15 Dec.'],
      ['name' => 'Mahnoor Ali', 'phone' => '+92 318 7766554', 'email' => 'mahnoor.ali@gmail.com', 'address' => 'F-10 Markaz, Islamabad', 'gender' => 'female', 'notes' => 'Kids wear for two sons.'],
    ];

    return array_map(
      fn (array $data) => $shop->clients()->create($data),
      $clients
    );
  }

  /** @param  list<Client>  $clients */
  private function seedStitchingSizes(Shop $shop, array $clients): void
  {
    $templates = [
      ['label' => 'Eid special', 'size' => 'L', 'sections' => [
        ['name' => 'Kameez', 'measurements' => ['Lambai' => '43', 'Chati' => '24', 'Kamar' => '23', 'Bazoo' => '25', 'Collar' => '16']],
        ['name' => 'Shalwar', 'measurements' => ['Lambai' => '42', 'Paincha' => '8.5', 'Ghera' => '26']],
      ]],
      ['label' => 'Bridal fitting', 'size' => null, 'sections' => [
        ['name' => 'Lehnga', 'measurements' => ['Kamar' => '28', 'Hip' => '40', 'Lambai' => '42']],
        ['name' => 'Blouse', 'measurements' => ['Chati' => '36', 'Kamar' => '30', 'Bazoo' => '13']],
      ]],
      ['label' => 'Sherwani order', 'size' => 'XL', 'sections' => [
        ['name' => 'Sherwani', 'measurements' => ['Lambai' => '44', 'Chati' => '26', 'Kamar' => '26', 'Bazoo' => '25.5', 'Shoulder' => '20']],
        ['name' => 'Shalwar', 'measurements' => ['Lambai' => '43', 'Paincha' => '9', 'Ghera' => '28']],
      ]],
      ['label' => 'Summer kurta', 'size' => 'M', 'sections' => [
        ['name' => 'Kurta', 'measurements' => ['Lambai' => '40', 'Chati' => '22', 'Bazoo' => '23', 'Gala' => '14']],
      ]],
      ['label' => 'Office shirts', 'size' => 'M', 'sections' => [
        ['name' => 'Shirt', 'measurements' => ['Collar' => '15.5', 'Chati' => '23', 'Kamar' => '22', 'Bazoo' => '24.5', 'Lambai' => '30']],
      ]],
      ['label' => 'Wedding lehnga', 'size' => null, 'sections' => [
        ['name' => 'Lehnga', 'measurements' => ['Kamar' => '27', 'Hip' => '38', 'Lambai' => '44']],
        ['name' => 'Maxi', 'measurements' => ['Chati' => '34', 'Kamar' => '28', 'Lambai' => '56']],
      ]],
      ['label' => 'Formal suit', 'size' => 'L', 'sections' => [
        ['name' => 'Kameez', 'measurements' => ['Lambai' => '42', 'Chati' => '25', 'Kamar' => '24', 'Bazoo' => '24']],
        ['name' => 'Shalwar', 'measurements' => ['Lambai' => '41', 'Paincha' => '8', 'Ghera' => '25']],
      ]],
      ['label' => 'Family Eid', 'size' => 'L', 'sections' => [
        ['name' => 'Kameez', 'measurements' => ['Lambai' => '43', 'Chati' => '25', 'Kamar' => '24', 'Bazoo' => '25', 'Collar' => '16']],
        ['name' => 'Shalwar', 'measurements' => ['Lambai' => '42', 'Paincha' => '8.5', 'Ghera' => '27']],
      ]],
      ['label' => 'Wedding December', 'size' => 'XL', 'sections' => [
        ['name' => 'Sherwani', 'measurements' => ['Lambai' => '45', 'Chati' => '27', 'Kamar' => '27', 'Bazoo' => '26', 'Shoulder' => '20.5']],
      ]],
      ['label' => 'Kids party wear', 'size' => 'S', 'sections' => [
        ['name' => 'Kurta', 'measurements' => ['Lambai' => '36', 'Chati' => '20', 'Kamar' => '22']],
        ['name' => 'Shalwar', 'measurements' => ['Lambai' => '40', 'Paincha' => '7.5', 'Ghera' => '24']],
      ]],
      ['label' => 'Casual kurta', 'size' => 'M', 'sections' => [
        ['name' => 'Kurta', 'measurements' => ['Lambai' => '42', 'Chati' => '23', 'Bazoo' => '24', 'Gala' => '15']],
      ]],
      ['label' => 'Winter shirt', 'size' => 'L', 'sections' => [
        ['name' => 'Shirt', 'measurements' => ['Collar' => '16', 'Chati' => '25', 'Kamar' => '24', 'Bazoo' => '25', 'Lambai' => '31']],
      ]],
    ];

    $notes = [
      'Measured at shop — client confirmed fit.',
      'Home visit measurement. Customer happy with final sample.',
      'Updated after last fitting session.',
      'Urgent order for ceremony.',
      'Follow-up measurement, ready for stitching.',
    ];

    for ($i = 0; $i < 52; $i++) {
      $template = $templates[$i % count($templates)];
      $client = $clients[$i % count($clients)];
      $sizeOptions = ['S', 'M', 'L', 'XL', null];

      $shop->stitchingSizes()->create([
        'client_id' => $client->id,
        'label' => $template['label'] . ' #' . ($i + 1),
        'standard_size' => $sizeOptions[array_rand($sizeOptions)],
        'sections' => $template['sections'],
        'notes' => $notes[array_rand($notes)],
        'measured_at' => now()->subDays(rand(0, 90))->toDateString(),
      ]);
    }
  }

  /**
   * @param  list<Client>  $clients
   * @param  array<string, GarmentType>  $garmentTypes
   */
  private function seedClientMeasurements(Shop $shop, array $clients, array $garmentTypes): void
  {
    $records = [
      [0, 'kameez', 'Winter shalwar kameez', ['lambai' => 43, 'chati' => 24, 'kamar' => 23, 'bazoo' => 25, 'collar' => 16]],
      [1, 'bridal', 'Bridal trial', ['bust' => 36, 'waist' => 28, 'hips' => 40, 'length' => 56]],
      [2, 'sherwani', 'Cousin wedding', ['length' => 44, 'chest' => 26, 'waist' => 26, 'shoulder' => 20, 'sleeve' => 25.5]],
      [4, 'shirt', 'Monthly shirt batch', ['chest' => 23, 'collar' => 15.5, 'sleeve' => 24.5, 'length' => 30]],
      [7, 'bridal', 'Mehndi maxi', ['bust' => 34, 'waist' => 27, 'hips' => 38, 'length' => 54]],
    ];

    foreach ($records as [$clientIdx, $typeKey, $label, $measurements]) {
      ClientMeasurement::create([
        'client_id' => $clients[$clientIdx]->id,
        'garment_type_id' => $garmentTypes[$typeKey]->id,
        'label' => $label,
        'measurements' => $measurements,
        'measured_at' => now()->subDays(rand(5, 60))->toDateString(),
      ]);
    }
  }

  /**
   * @param  list<Client>  $clients
   * @param  array<string, Design>  $designs
   * @param  array<string, GarmentType>  $garmentTypes
   * @return list<Order>
   */
  private function seedOrders(Shop $shop, array $clients, array $designs, array $garmentTypes): array
  {
    $orderData = [
      [0, 'kameez_formal', 'kameez', 'pending', 12000, 6000, 'pending', 5, 12],
      [1, 'bridal_lehnga', 'bridal', 'in_progress', 185000, 100000, 'pending', 10, 45],
      [2, 'sherwani_royal', 'sherwani', 'in_progress', 45000, 45000, 'paid', 3, 20],
      [3, 'kurta_eid', 'kurta', 'ready', 9500, 9500, 'paid', 15, 2],
      [4, 'shirt_double_cuff', 'shirt', 'delivered', 6500, 6500, 'paid', 25, -5],
      [5, 'bridal_lehnga', 'bridal', 'in_progress', 195000, 80000, 'pending', 7, 60],
      [6, 'kameez_formal', 'kameez', 'pending', 11500, 0, 'pending', 2, 10],
      [7, 'bridal_lehnga', 'bridal', 'ready', 165000, 165000, 'paid', 20, 0],
      [8, 'kurta_linen', 'kurta', 'delivered', 8500, 8500, 'paid', 30, -10],
      [9, 'kameez_formal', 'kameez', 'pending', 13000, 5000, 'pending', 1, 14],
      [10, 'sherwani_black', 'sherwani', 'in_progress', 52000, 25000, 'pending', 8, 35],
      [11, 'kurta_eid', 'kurta', 'pending', 9000, 0, 'pending', 0, 7],
      [0, 'waistcoat_velvet', 'waistcoat', 'delivered', 15000, 15000, 'paid', 40, -15],
      [4, 'shirt_double_cuff', 'shirt', 'in_progress', 13000, 6500, 'pending', 4, 8],
      [2, 'kurta_linen', 'kurta', 'cancelled', 8500, 2000, 'pending', 50, null],
    ];

    $orders = [];
    foreach ($orderData as $i => [$clientIdx, $designKey, $typeKey, $status, $total, $paid, $paymentStatus, $daysAgo, $dueIn]) {
      $orderDate = now()->subDays($daysAgo);
      $orders[] = $shop->orders()->create([
        'client_id' => $clients[$clientIdx]->id,
        'design_id' => $designs[$designKey]->id,
        'garment_type_id' => $garmentTypes[$typeKey]->id,
        'order_number' => 'AHT-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
        'status' => $status,
        'total_amount' => $total,
        'paid_amount' => $paid,
        'payment_status' => $paymentStatus,
        'order_date' => $orderDate->toDateString(),
        'due_date' => $dueIn !== null ? $orderDate->copy()->addDays($dueIn)->toDateString() : null,
        'delivery_date' => $status === 'delivered' ? $orderDate->copy()->addDays(10)->toDateString() : null,
        'notes' => $status === 'cancelled' ? 'Client cancelled — partial refund issued.' : null,
      ]);
    }

    return $orders;
  }

  /**
   * @param  list<Client>  $clients
   * @param  list<Order>  $orders
   */
  private function seedTransactions(Shop $shop, array $clients, array $orders): void
  {
    $income = [
      ['desc' => 'Advance — bridal lehnga (Fatima Khan)', 'amount' => 50000, 'client' => 1, 'order' => 1, 'days' => 8],
      ['desc' => 'Full payment — sherwani (Usman)', 'amount' => 45000, 'client' => 2, 'order' => 2, 'days' => 2],
      ['desc' => 'Eid kurta payment (Ayesha)', 'amount' => 9500, 'client' => 3, 'order' => 3, 'days' => 14],
      ['desc' => 'Shirt order — Bilal Ahmed', 'amount' => 6500, 'client' => 4, 'order' => 4, 'days' => 24],
      ['desc' => 'Bridal advance — Sana Tariq', 'amount' => 80000, 'client' => 5, 'order' => 5, 'days' => 6],
      ['desc' => 'Mehndi outfit — Hira Shah', 'amount' => 165000, 'client' => 7, 'order' => 7, 'days' => 19],
      ['desc' => 'Kameez advance — Ahmed Hassan', 'amount' => 6000, 'client' => 0, 'order' => 0, 'days' => 4],
      ['desc' => 'Sherwani advance — Omar Sheikh', 'amount' => 25000, 'client' => 10, 'order' => 10, 'days' => 7],
      ['desc' => 'Waistcoat — Ahmed Hassan', 'amount' => 15000, 'client' => 0, 'order' => 12, 'days' => 39],
      ['desc' => 'Walk-in kurta stitching', 'amount' => 7500, 'client' => null, 'order' => null, 'days' => 1],
    ];

    foreach ($income as $row) {
      $shop->transactions()->create([
        'type' => 'income',
        'amount' => $row['amount'],
        'description' => $row['desc'],
        'category' => 'Stitching',
        'payment_method' => $row['amount'] > 20000 ? 'bank' : 'cash',
        'transaction_date' => now()->subDays($row['days'])->toDateString(),
        'client_id' => $row['client'] !== null ? $clients[$row['client']]->id : null,
        'order_id' => $row['order'] !== null ? $orders[$row['order']]->id : null,
      ]);
    }

    $expenses = [
      ['desc' => 'Liberty Market fabric purchase', 'amount' => 85000, 'category' => 'Fabric', 'days' => 3],
      ['desc' => 'Zari & embroidery thread stock', 'amount' => 12000, 'category' => 'Supplies', 'days' => 10],
      ['desc' => 'Shop rent — Gulberg III', 'amount' => 95000, 'category' => 'Rent', 'days' => 1],
      ['desc' => 'Electricity bill (K-Electric)', 'amount' => 18500, 'category' => 'Utilities', 'days' => 5],
      ['desc' => 'Master tailor monthly salary', 'amount' => 55000, 'category' => 'Salaries', 'days' => 2],
      ['desc' => 'Sewing machine maintenance', 'amount' => 4500, 'category' => 'Equipment', 'days' => 18],
      ['desc' => 'Packaging & garment bags', 'amount' => 3200, 'category' => 'Supplies', 'days' => 12],
    ];

    foreach ($expenses as $row) {
      $shop->transactions()->create([
        'type' => 'expense',
        'amount' => $row['amount'],
        'description' => $row['desc'],
        'category' => $row['category'],
        'payment_method' => $row['amount'] > 30000 ? 'bank' : 'cash',
        'transaction_date' => now()->subDays($row['days'])->toDateString(),
      ]);
    }
  }
}
