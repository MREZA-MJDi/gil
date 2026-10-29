<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Models\CashMovement;
use App\Models\CashShift;
use App\Models\Category;
use App\Models\CustomerFeedback;
use App\Models\Expense;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\PrintLog;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Reservation;
use App\Models\RestaurantSetting;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\CashShiftService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RestaurantDemoSeeder extends Seeder
{
    private int $currencyMultiplier = 1;

    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('RestaurantDemoSeeder فقط در APP_ENV=local مجاز است؛ داده نمونه را در production وارد نکنید.');
        }

        $manager = User::query()->where('is_admin', true)->orderBy('id')->first();
        if (! $manager) {
            throw new RuntimeException('مدیر پیدا نشد. ابتدا ADMIN_EMAIL و ADMIN_PASSWORD را تنظیم و AdminUserSeeder را اجرا کنید.');
        }

        $settings = RestaurantSetting::current();
        $this->currencyMultiplier = $settings->currency_label === 'ریال' ? 10 : 1;

        $settings->forceFill([
            'name' => $settings->name ?: 'خانه گیلاسی',
            'phone' => $settings->phone ?: '021-12345678',
            'address' => $settings->address ?: 'تهران، خیابان نمونه، پلاک ۱۲',
            'currency_label' => $settings->currency_label === 'ریال' ? 'ریال' : 'تومان',
            'tax_basis_points' => 900,
            'service_basis_points' => 500,
            'receipt_footer' => 'از اینکه خانه گیلاسی را انتخاب کردید سپاسگزاریم.',
            'paper_width' => 80,
            'customer_menu_enabled' => true,
        ])->save();

        $categories = $this->seedCategories();
        $products = $this->seedProducts($categories);
        $ingredients = $this->seedIngredients();
        $this->seedRecipes($products, $ingredients);
        $tables = $this->seedTables();

        $this->seedOperationalData($manager, $products, $tables);
        $this->seedReservations($manager, $tables);
        $this->seedFeedback();
    }

    /** @return array<string, Category> */
    private function seedCategories(): array
    {
        $definitions = [
            'coffee' => ['name' => 'قهوه گرم', 'sort_order' => 1],
            'cold' => ['name' => 'نوشیدنی سرد', 'sort_order' => 2],
            'food' => ['name' => 'صبحانه و غذا', 'sort_order' => 3],
            'dessert' => ['name' => 'کیک و دسر', 'sort_order' => 4],
        ];
        $result = [];
        foreach ($definitions as $key => $definition) {
            $category = Category::query()->firstOrNew(['name' => $definition['name']]);
            $category->fill(['sort_order' => $definition['sort_order'], 'is_active' => true])->save();
            $result[$key] = $category;
        }
        return $result;
    }

    /** @param array<string, Category> $categories
     *  @return array<string, Product>
     */
    private function seedProducts(array $categories): array
    {
        $definitions = [
            ['key'=>'espresso','category'=>'coffee','name'=>'اسپرسو دابل','description'=>'دو شات اسپرسو با کرمای غلیظ و طعم متعادل.','ingredients'=>'دانه قهوه، آب تصفیه‌شده','allergens'=>[],'tags'=>['بدون لبنیات'],'calories'=>8,'minutes'=>4,'price'=>85000,'stock'=>100,'image'=>'photo-1510591509098-f4fdc6d0ff04'],
            ['key'=>'americano','category'=>'coffee','name'=>'آمریکانو','description'=>'اسپرسو و آب داغ؛ ساده، تمیز و خوش‌عطر.','ingredients'=>'دانه قهوه، آب تصفیه‌شده','allergens'=>[],'tags'=>['بدون لبنیات'],'calories'=>12,'minutes'=>5,'price'=>95000,'stock'=>100,'image'=>'photo-1497515114629-f71d768fd07c'],
            ['key'=>'latte','category'=>'coffee','name'=>'لاته کلاسیک','description'=>'اسپرسوی متعادل با شیر بخار داده‌شده و بافت مخملی.','ingredients'=>'دانه قهوه، شیر','allergens'=>['شیر'],'tags'=>['گیاه‌خواری'],'calories'=>160,'minutes'=>6,'price'=>145000,'stock'=>100,'image'=>'photo-1461023058943-07fcbe16d735'],
            ['key'=>'cappuccino','category'=>'coffee','name'=>'کاپوچینو','description'=>'اسپرسو، شیر و فوم نرم با نسبت کلاسیک.','ingredients'=>'دانه قهوه، شیر','allergens'=>['شیر'],'tags'=>['گیاه‌خواری'],'calories'=>130,'minutes'=>6,'price'=>135000,'stock'=>100,'image'=>'photo-1570968915860-54d5c301fa9f'],
            ['key'=>'mocha','category'=>'coffee','name'=>'موکا شکلاتی','description'=>'ترکیب اسپرسو، شیر و شکلات تلخ.','ingredients'=>'دانه قهوه، شیر، سس شکلات','allergens'=>['شیر'],'tags'=>['شکلاتی'],'calories'=>260,'minutes'=>7,'price'=>175000,'stock'=>100,'image'=>'photo-1578314675249-a6910f80cc4e'],
            ['key'=>'hot_chocolate','category'=>'coffee','name'=>'هات چاکلت','description'=>'شکلات داغ غلیظ برای یک مکث شیرین.','ingredients'=>'شیر، کاکائو، شکر','allergens'=>['شیر'],'tags'=>['بدون قهوه'],'calories'=>290,'minutes'=>7,'price'=>155000,'stock'=>100,'image'=>'photo-1542990253-0b8be4c8b7b4'],
            ['key'=>'iced_latte','category'=>'cold','name'=>'آیس لاته','description'=>'اسپرسو و شیر سرد روی یخ؛ خنک و متعادل.','ingredients'=>'دانه قهوه، شیر، یخ','allergens'=>['شیر'],'tags'=>['سرد'],'calories'=>150,'minutes'=>5,'price'=>165000,'stock'=>100,'image'=>'photo-1517701604599-bb29b565090c'],
            ['key'=>'cold_brew','category'=>'cold','name'=>'کلد برو','description'=>'قهوه دم‌سرد با طعم نرم و تلخی کمتر.','ingredients'=>'دانه قهوه، آب، یخ','allergens'=>[],'tags'=>['بدون لبنیات','سرد'],'calories'=>10,'minutes'=>3,'price'=>160000,'stock'=>100,'image'=>'photo-1517701550927-30cf4ba1dba5'],
            ['key'=>'lemonade','category'=>'cold','name'=>'لیموناد تازه','description'=>'لیمو، یخ و شیرینی کنترل‌شده؛ تازه و پرطراوت.','ingredients'=>'لیمو، آب، شکر','allergens'=>[],'tags'=>['بدون کافئین','سرد'],'calories'=>110,'minutes'=>4,'price'=>125000,'stock'=>100,'image'=>'photo-1513558161293-cdaf765edfd7'],
            ['key'=>'mojito','category'=>'cold','name'=>'موهیتو بلوبری','description'=>'نعناع تازه، لیمو و نوشیدنی خنک.','ingredients'=>'نعناع، لیمو، آب، شکر','allergens'=>[],'tags'=>['بدون کافئین','سرد'],'calories'=>135,'minutes'=>5,'price'=>155000,'stock'=>100,'image'=>'photo-1513558161293-cdaf765edfd7'],
            ['key'=>'croissant','category'=>'dessert','name'=>'کروسان شکلاتی','description'=>'کروسان لایه‌لایه با شکلات؛ گرم‌شده هنگام سفارش.','ingredients'=>'خمیر کروسان، کره، شکلات','allergens'=>['گلوتن','شیر'],'tags'=>['شیرینی'],'calories'=>320,'minutes'=>6,'price'=>135000,'stock'=>60,'image'=>'photo-1555507036-ab1f4038808a'],
            ['key'=>'cheesecake','category'=>'dessert','name'=>'چیزکیک توت‌فرنگی','description'=>'چیزکیک خامه‌ای با لایه توت‌فرنگی.','ingredients'=>'کیک پنیر، خامه، توت‌فرنگی','allergens'=>['شیر','گلوتن'],'tags'=>['دسر'],'calories'=>390,'minutes'=>2,'price'=>175000,'stock'=>60,'image'=>'photo-1533134242443-d4fd215305ad'],
            ['key'=>'brownie','category'=>'dessert','name'=>'براونی شکلاتی','description'=>'بافت مرطوب، شکلات غلیظ و سطح کمی ترد.','ingredients'=>'شکلات، آرد، کره','allergens'=>['گلوتن','شیر'],'tags'=>['شکلاتی'],'calories'=>340,'minutes'=>2,'price'=>125000,'stock'=>60,'image'=>'photo-1606313564200-e75d5e30476c'],
            ['key'=>'chocolate_cake','category'=>'dessert','name'=>'کیک شکلاتی','description'=>'یک برش کیک شکلاتی چندلایه.','ingredients'=>'کیک شکلاتی، سس شکلات','allergens'=>['گلوتن','شیر'],'tags'=>['دسر'],'calories'=>420,'minutes'=>2,'price'=>165000,'stock'=>60,'image'=>'photo-1578985545062-69928b1d9587'],
            ['key'=>'sandwich','category'=>'food','name'=>'ساندویچ مرغ','description'=>'مرغ گریل، سبزیجات تازه و سس خانگی.','ingredients'=>'نان، مرغ، پنیر، گوجه، کاهو، سس','allergens'=>['گلوتن','شیر'],'tags'=>['وعده سبک'],'calories'=>460,'minutes'=>12,'price'=>285000,'stock'=>80,'image'=>'photo-1528735602780-2552fd46c7af'],
            ['key'=>'burger','category'=>'food','name'=>'برگر کلاسیک','description'=>'برگر گوشت، پنیر و سبزیجات در نان برشته.','ingredients'=>'نان برگر، گوشت، پنیر، گوجه، کاهو','allergens'=>['گلوتن','شیر'],'tags'=>['وعده اصلی'],'calories'=>680,'minutes'=>16,'price'=>385000,'stock'=>80,'image'=>'photo-1568901346375-23c9450c58cd'],
            ['key'=>'pasta','category'=>'food','name'=>'پاستا آلفردو','description'=>'پاستا با سس خامه‌ای، مرغ و پارمزان.','ingredients'=>'پاستا، مرغ، سس آلفردو، پارمزان','allergens'=>['گلوتن','شیر'],'tags'=>['وعده اصلی'],'calories'=>620,'minutes'=>18,'price'=>345000,'stock'=>80,'image'=>'photo-1473093295043-cdd812d0e601'],
            ['key'=>'salad','category'=>'food','name'=>'سالاد سزار مرغ','description'=>'کاهو، مرغ گریل، پارمزان و سس سزار.','ingredients'=>'مرغ، کاهو، گوجه، خیار، سس سزار','allergens'=>['شیر','تخم‌مرغ'],'tags'=>['تازه'],'calories'=>390,'minutes'=>10,'price'=>295000,'stock'=>80,'image'=>'photo-1512621776951-a57141f2eefd'],
            ['key'=>'omelette','category'=>'food','name'=>'املت گوجه','description'=>'املت گوجه تازه با نان گرم.','ingredients'=>'تخم‌مرغ، گوجه، کره، نان','allergens'=>['تخم‌مرغ','گلوتن','شیر'],'tags'=>['صبحانه'],'calories'=>360,'minutes'=>10,'price'=>195000,'stock'=>80,'image'=>'photo-1525351484163-7529414344d8'],
            ['key'=>'fries','category'=>'food','name'=>'سیب‌زمینی سرخ‌شده','description'=>'سیب‌زمینی طلایی با دیپ کنار غذا.','ingredients'=>'سیب‌زمینی، روغن، دیپ','allergens'=>[],'tags'=>['مناسب اشتراک'],'calories'=>410,'minutes'=>9,'price'=>155000,'stock'=>80,'image'=>'photo-1573080496219-bb080dd4f877'],
        ];

        $result = [];
        foreach ($definitions as $definition) {
            $product = Product::query()->firstOrNew(['name' => $definition['name']]);
            $isNew = ! $product->exists;
            $product->fill([
                'category_id' => $categories[$definition['category']]->id,
                'description' => $definition['description'],
                'ingredients' => $definition['ingredients'],
                'allergens' => $definition['allergens'],
                'dietary_tags' => $definition['tags'],
                'calories' => $definition['calories'],
                'preparation_time_minutes' => $definition['minutes'],
                'image_path' => 'https://images.unsplash.com/'.$definition['image'].'?auto=format&fit=crop&w=1000&q=85',
                'base_price' => $definition['price'] * $this->currencyMultiplier,
                'unit' => 'عدد',
                'status' => 'active',
                'sort_order' => count($result) + 1,
                'stock_quantity' => $isNew ? $definition['stock'] : $product->stock_quantity,
                'low_stock_threshold' => $product->low_stock_threshold ?? max(5, (int) floor($definition['stock'] * .15)),
            ])->save();
            $result[$definition['key']] = $product;

            $optionDefinitions = match ($definition['key']) {
                'latte', 'cappuccino', 'americano', 'iced_latte' => [
                    ['name'=>'سایز بزرگ','type'=>'size','price'=>25000,'sort_order'=>1],
                    ['name'=>'شیر جو دوسر','type'=>'addon','price'=>35000,'sort_order'=>2],
                    ['name'=>'شات اضافه','type'=>'addon','price'=>45000,'sort_order'=>3],
                ],
                'mocha', 'hot_chocolate' => [
                    ['name'=>'خامه اضافه','type'=>'addon','price'=>25000,'sort_order'=>1],
                ],
                'burger', 'sandwich' => [
                    ['name'=>'پنیر اضافه','type'=>'addon','price'=>30000,'sort_order'=>1],
                    ['name'=>'سیب‌زمینی کنار غذا','type'=>'addon','price'=>85000,'sort_order'=>2],
                ],
                default => [],
            };
            foreach ($optionDefinitions as $optionData) {
                ProductOption::query()->updateOrCreate(
                    ['product_id' => $product->id, 'name' => $optionData['name']],
                    ['type'=>$optionData['type'],'price'=>$optionData['price'] * $this->currencyMultiplier,'is_active'=>true,'sort_order'=>$optionData['sort_order']],
                );
            }
        }
        return $result;
    }

    /** @return array<string, Ingredient> */
    private function seedIngredients(): array
    {
        $definitions = [
            'coffee_beans'=>['دانه قهوه','g',12000,400],
            'water'=>['آب تصفیه‌شده','ml',120000,2],
            'whole_milk'=>['شیر','ml',40000,100],
            'oat_milk'=>['شیر جو دوسر','ml',4000,210],
            'chocolate_sauce'=>['سس شکلات','ml',2500,300],
            'cocoa'=>['پودر کاکائو','g',1500,260],
            'sugar'=>['شکر','g',8000,65],
            'lemon'=>['لیمو','g',6000,140],
            'mint'=>['نعناع تازه','g',400,180],
            'ice'=>['یخ','g',10000,5],
            'cold_cup'=>['لیوان سرد','pcs',500,2500],
            'hot_cup'=>['لیوان گرم','pcs',500,1800],
            'cup_lid'=>['درب لیوان','pcs',700,700],
            'flour'=>['آرد','g',15000,55],
            'butter'=>['کره','g',4000,420],
            'croissant'=>['کروسان آماده','pcs',80,55000],
            'pastry_chocolate'=>['شکلات شیرینی','g',1000,300],
            'cake_slice'=>['برش کیک آماده','pcs',80,65000],
            'brownie'=>['براونی آماده','pcs',80,50000],
            'bread'=>['نان ساندویچی','pcs',400,4500],
            'chicken'=>['مرغ گریل‌شده','g',12000,320],
            'cheese'=>['پنیر','g',7000,300],
            'tomato'=>['گوجه‌فرنگی','g',8000,95],
            'lettuce'=>['کاهو','g',4000,110],
            'mayonnaise'=>['سس مایونز','g',2500,120],
            'burger_bun'=>['نان برگر','pcs',100,8000],
            'beef'=>['گوشت برگر','g',10000,650],
            'pasta'=>['پاستا خشک','g',8000,95],
            'alfredo_sauce'=>['سس آلفردو','g',3000,210],
            'egg'=>['تخم‌مرغ','pcs',100,6500],
            'potato'=>['سیب‌زمینی','g',20000,45],
            'frying_oil'=>['روغن سرخ‌کردنی','ml',5000,160],
            'parmesan'=>['پنیر پارمزان','g',2000,480],
            'cucumber'=>['خیار','g',4000,70],
            'salad_dressing'=>['سس سزار','g',3000,180],
        ];

        $result = [];
        foreach ($definitions as $key => [$name, $unit, $stock, $cost]) {
            $ingredient = Ingredient::query()->firstOrNew(['name' => $name]);
            $isNew = ! $ingredient->exists;
            $ingredient->fill([
                'unit'=>$unit,
                'stock_quantity'=>$isNew ? $stock : $ingredient->stock_quantity,
                'low_stock_threshold'=>$ingredient->low_stock_threshold ?? max(1, (int) floor($stock * .12)),
                'cost_per_unit' => $cost * $this->currencyMultiplier,
                'is_active'=>true,
            ])->save();
            $result[$key] = $ingredient;
        }
        return $result;
    }

    /** @param array<string, Product> $products
     *  @param array<string, Ingredient> $ingredients
     */
    private function seedRecipes(array $products, array $ingredients): void
    {
        $recipes = [
            'espresso'=>['coffee_beans'=>18,'water'=>40,'hot_cup'=>1],
            'americano'=>['coffee_beans'=>18,'water'=>180,'hot_cup'=>1],
            'latte'=>['coffee_beans'=>18,'whole_milk'=>220,'hot_cup'=>1],
            'cappuccino'=>['coffee_beans'=>18,'whole_milk'=>150,'hot_cup'=>1],
            'mocha'=>['coffee_beans'=>18,'whole_milk'=>180,'chocolate_sauce'=>20,'hot_cup'=>1],
            'hot_chocolate'=>['whole_milk'=>220,'cocoa'=>25,'sugar'=>10,'hot_cup'=>1],
            'iced_latte'=>['coffee_beans'=>18,'whole_milk'=>180,'ice'=>80,'cold_cup'=>1,'cup_lid'=>1],
            'cold_brew'=>['coffee_beans'=>30,'water'=>300,'ice'=>80,'cold_cup'=>1,'cup_lid'=>1],
            'lemonade'=>['lemon'=>60,'sugar'=>22,'water'=>220,'ice'=>60,'cold_cup'=>1,'cup_lid'=>1],
            'mojito'=>['lemon'=>35,'mint'=>8,'sugar'=>15,'water'=>190,'ice'=>70,'cold_cup'=>1,'cup_lid'=>1],
            'croissant'=>['croissant'=>1,'pastry_chocolate'=>18],
            'cheesecake'=>['cake_slice'=>1],
            'brownie'=>['brownie'=>1],
            'chocolate_cake'=>['cake_slice'=>1,'chocolate_sauce'=>12],
            'sandwich'=>['bread'=>2,'chicken'=>100,'cheese'=>30,'tomato'=>20,'lettuce'=>15,'mayonnaise'=>12],
            'burger'=>['burger_bun'=>1,'beef'=>150,'cheese'=>25,'tomato'=>25,'lettuce'=>15,'mayonnaise'=>20],
            'pasta'=>['pasta'=>110,'chicken'=>100,'alfredo_sauce'=>80,'parmesan'=>15],
            'salad'=>['chicken'=>70,'lettuce'=>100,'tomato'=>50,'cucumber'=>40,'salad_dressing'=>25,'parmesan'=>10],
            'omelette'=>['egg'=>2,'tomato'=>100,'butter'=>8,'bread'=>1],
            'fries'=>['potato'=>220,'frying_oil'=>18,'mayonnaise'=>20],
        ];

        foreach ($recipes as $productKey => $lines) {
            $sync = [];
            foreach ($lines as $ingredientKey => $quantity) {
                $sync[$ingredients[$ingredientKey]->id] = ['quantity' => number_format($quantity, 3, '.', '')];
            }
            $products[$productKey]->recipeIngredients()->sync($sync);
        }
    }

    /** @return array<int, RestaurantTable> */
    private function seedTables(): array
    {
        $tables = [];
        for ($number = 1; $number <= 10; $number++) {
            $name = 'میز '.$number;
            $table = RestaurantTable::query()->firstOrNew(['name' => $name]);
            $table->fill([
                'token' => $table->exists && $table->token ? $table->token : Str::random(64),
                'is_active' => true,
                'description' => 'میز نمونه برای تست QR و سفارش حضوری',
            ])->save();
            $tables[$number] = $table;
        }
        return $tables;
    }

    /** @param array<string, Product> $products
     *  @param array<int, RestaurantTable> $tables
     */
    private function seedOperationalData(User $manager, array $products, array $tables): void
    {
        $orderService = app(OrderService::class);
        $paymentService = app(PaymentService::class);
        $cashShiftService = app(CashShiftService::class);
        $shift = CashShift::query()->where('status', 'open')->where('open_lock', true)->first();
        if (! $shift) {
            $shift = $cashShiftService->open(3000000 * $this->currencyMultiplier, 'صندوق شروع شیفت نمونه', $manager->id);
        }

        $specs = [
            ['key'=>'01-qr-new','channel'=>'dine_in','source'=>'qr','table'=>1,'items'=>[['espresso',1,[]],['cheesecake',1,[]]],'status'=>'new','payment'=>null,'preference'=>'at_counter'],
            ['key'=>'02-qr-preparing','channel'=>'dine_in','source'=>'qr','table'=>2,'items'=>[['latte',2,['سایز بزرگ']],['brownie',1,[]]],'status'=>'preparing','payment'=>['card_terminal','partial'],'preference'=>'card_terminal'],
            ['key'=>'03-staff-ready','channel'=>'dine_in','source'=>'staff','table'=>3,'items'=>[['pasta',1,[]],['lemonade',1,[]]],'status'=>'ready','payment'=>['cash','full'],'preference'=>'cash'],
            ['key'=>'04-website-new','channel'=>'takeaway','source'=>'website','table'=>null,'items'=>[['sandwich',1,[]],['iced_latte',1,[]]],'status'=>'new','payment'=>null,'preference'=>'at_counter'],
            ['key'=>'05-snappfood-prep','channel'=>'online','source'=>'snappfood','table'=>null,'external_provider'=>'snappfood','external_order_id'=>'DEMO-SNAPP-001','items'=>[['burger',1,[]],['fries',1,[]]],'status'=>'preparing','payment'=>null,'preference'=>'at_counter'],
            ['key'=>'06-counter-delivered','channel'=>'counter','source'=>'staff','table'=>null,'items'=>[['americano',1,[]],['croissant',1,[]]],'status'=>'delivered','payment'=>['card_terminal','full'],'preference'=>'card_terminal'],
            ['key'=>'07-qr-delivered','channel'=>'dine_in','source'=>'qr','table'=>4,'items'=>[['mocha',2,[]],['chocolate_cake',1,[]]],'status'=>'delivered','payment'=>['cash','full'],'preference'=>'cash'],
            ['key'=>'08-website-refund','channel'=>'takeaway','source'=>'website','table'=>null,'items'=>[['latte',1,[]],['cheesecake',1,[]]],'status'=>'delivered','payment'=>['card_terminal','partial_refund'],'preference'=>'card_terminal'],
            ['key'=>'09-takeaway-unpaid','channel'=>'takeaway','source'=>'staff','table'=>null,'items'=>[['cold_brew',1,[]],['brownie',1,[]]],'status'=>'new','payment'=>null,'preference'=>'at_counter'],
            ['key'=>'10-cancelled','channel'=>'counter','source'=>'staff','table'=>null,'items'=>[['omelette',1,[]]],'status'=>'cancelled','payment'=>null,'preference'=>'cash'],
            ['key'=>'11-website-partial','channel'=>'takeaway','source'=>'website','table'=>null,'items'=>[['pasta',1,[]]],'status'=>'preparing','payment'=>['card_terminal','partial'],'preference'=>'card_terminal'],
            ['key'=>'12-partner-new','channel'=>'online','source'=>'partner','table'=>null,'external_provider'=>'partner','external_order_id'=>'DEMO-PARTNER-001','items'=>[['salad',1,[]]],'status'=>'new','payment'=>null,'preference'=>'at_counter'],
        ];

        foreach ($specs as $index => $spec) {
            $itemLines = [];
            foreach ($spec['items'] as [$productKey, $quantity, $optionNames]) {
                $optionInputs = [];
                foreach ($optionNames as $optionName) {
                    $option = $products[$productKey]->options()->where('name', $optionName)->where('is_active', true)->first();
                    if ($option) $optionInputs[] = ['id' => $option->id];
                }
                $itemLines[] = ['product_id'=>$products[$productKey]->id,'quantity'=>$quantity,'options'=>$optionInputs];
            }

            $input = [
                'channel'=>$spec['channel'],
                'source'=>$spec['source'],
                'restaurant_table_id'=>isset($spec['table']) ? $tables[$spec['table']]->id : null,
                'items'=>$itemLines,
                'discount'=>0,
                'payment_preference'=>$spec['preference'],
                'customer_name'=>['سارا محمدی','علی رضایی','مریم کریمی','امیر حسینی','مشتری اسنپ‌فود','نگار احمدی','سامان قادری','ندا شریفی','پارسا موسوی','مشتری صندوق','رها مرادی','سفارش همکار'][ $index ],
                'customer_phone'=>'0912'.str_pad((string)(2000000 + $index),7,'0',STR_PAD_LEFT),
                'customer_note'=>$spec['source']==='qr' ? 'ثبت نمونه از QR میز' : null,
                'internal_note'=>'داده نمونه برای تست جریان کاری؛ در فروش واقعی استفاده نشود.',
                'idempotency_key'=>'demo-order-'.$spec['key'],
            ];
            if (isset($spec['external_provider'])) {
                $input['external_provider'] = $spec['external_provider'];
                $input['external_order_id'] = $spec['external_order_id'];
            }

            $order = $orderService->create($input, $manager->id);
            if (! $order->wasRecentlyCreated) {
                $this->stampDemoOrderDate($order, $index);
                continue;
            }

            if ($spec['status'] === 'cancelled') {
                $orderService->cancel($order->id, 'لغو داده نمونه برای تست بازگشت موجودی', $manager->id);
                $this->stampDemoOrderDate($order, $index);
                continue;
            }

            $statusPath = ['confirmed','preparing','ready','delivered'];
            foreach ($statusPath as $status) {
                if ($status === $spec['status']) {
                    $orderService->transition($order->id, OrderStatus::from($status), 'ایجاد وضعیت نمونه برای تست داشبورد', $manager->id);
                    break;
                }
                if (array_search($status, $statusPath, true) < array_search($spec['status'], $statusPath, true)) {
                    if ($order->fresh()->status->value !== $status) {
                        $orderService->transition($order->id, OrderStatus::from($status), 'ایجاد وضعیت نمونه برای تست داشبورد', $manager->id);
                    }
                }
            }

            $order->refresh();
            if ($spec['payment'] !== null) {
                [$methodValue, $kind] = $spec['payment'];
                $method = PaymentMethod::from($methodValue);
                $amount = match ($kind) {
                    'full', 'partial_refund' => (int) $order->total,
                    default => max(1, intdiv((int) $order->total, 2)),
                };
                $payment = $paymentService->record(
                    $order->id, $amount, $method, 'demo-payment-'.$spec['key'],
                    'DEMO-'.strtoupper(Str::slug($spec['key'])),
                    'پرداخت نمونه جهت تست UI؛ پول واقعی دریافت نشده است.',
                    $manager->id,
                );

                if ($kind === 'partial_refund') {
                    $paymentService->refund(
                        $payment->id, max(1, intdiv((int) $payment->amount, 4)),
                        'بازپرداخت جزئی نمونه برای تست گزارش و صندوق',
                        'demo-refund-'.$spec['key'], $manager->id,
                    );
                }
            }

            foreach ([\App\Enums\PrintType::Kitchen] as $type) {
                PrintLog::query()->firstOrCreate(
                    ['document_number'=>'DEMO-KITCHEN-'.$order->id],
                    ['order_id'=>$order->id,'payment_id'=>null,'user_id'=>$manager->id,'type'=>$type,'is_reprint'=>false,'printed_at'=>now()],
                );
            }
            if ($index % 3 === 0) {
                PrintLog::query()->firstOrCreate(
                    ['document_number'=>'DEMO-INVOICE-'.$order->id],
                    ['order_id'=>$order->id,'payment_id'=>null,'user_id'=>$manager->id,'type'=>'invoice','is_reprint'=>false,'printed_at'=>now()],
                );
            }
            $this->stampDemoOrderDate($order, $index);
        }

        foreach ([
            ['title'=>'DEMO · خرید مواد اولیه هفتگی','category'=>'ingredients','amount'=>1850000 * $this->currencyMultiplier,'is_paid'=>true,'method'=>'cash','note'=>'هزینه نمونه برای تست گزارش'],
            ['title'=>'DEMO · بسته‌بندی بیرون‌بر','category'=>'packaging','amount'=>420000 * $this->currencyMultiplier,'is_paid'=>true,'method'=>'card_terminal','note'=>'هزینه نمونه بسته‌بندی'],
            ['title'=>'DEMO · سرویس دستگاه اسپرسو','category'=>'repairs','amount'=>950000 * $this->currencyMultiplier,'is_paid'=>false,'method'=>'other','note'=>'هزینه نمونه پرداخت‌نشده'],
            ['title'=>'DEMO · خرید سبزی و میوه','category'=>'ingredients','amount'=>680000 * $this->currencyMultiplier,'is_paid'=>true,'method'=>'cash','note'=>'خرید نمونه روز'],
        ] as $expense) {
            $expenseRecord = Expense::query()->firstOrCreate(
                ['title'=>$expense['title']],
                [
                    'cash_shift_id'=>$shift->id,'user_id'=>$manager->id,'category'=>$expense['category'],
                    'amount'=>$expense['amount'],'payment_method'=>$expense['method'],'is_paid'=>$expense['is_paid'],
                    'paid_at'=>$expense['is_paid'] ? now()->subHours(2) : null,'note'=>$expense['note'],
                ],
            );

            // Keep demo cash expenses consistent with the cash ledger, including if a previous
            // seeding run created the expense before this reconciliation was added.
            if ($expenseRecord->is_paid && $expenseRecord->payment_method === 'cash'
                && $expenseRecord->cash_shift_id
                && ! CashMovement::query()->where('expense_id', $expenseRecord->id)->exists()) {
                CashMovement::query()->create([
                    'cash_shift_id' => $expenseRecord->cash_shift_id,
                    'user_id' => $manager->id,
                    'expense_id' => $expenseRecord->id,
                    'direction' => 'out',
                    'type' => 'expense',
                    'amount' => $expenseRecord->amount,
                    'reason' => $expenseRecord->title,
                    'idempotency_key' => 'expense:'.$expenseRecord->id,
                    'occurred_at' => $expenseRecord->paid_at ?? now(),
                ]);
            }
        }
    }


    /**
     * Spread demo orders over the last two weeks so Jalali date filters, sales charts,
     * receipts and daily reports have meaningful test data. Only deterministic demo orders
     * from the specifications above are updated.
     */
    private function stampDemoOrderDate(Order $order, int $index): void
    {
        $daysAgo = ($index * 2) % 14;
        $demoTime = now()->subDays($daysAgo)->setTime(9 + ($index % 10), ($index * 7) % 60, 0);

        DB::table('orders')->where('id', $order->id)->update([
            'created_at' => $demoTime,
            'updated_at' => $demoTime,
        ]);
        DB::table('order_status_histories')->where('order_id', $order->id)->update([
            'created_at' => $demoTime,
            'updated_at' => $demoTime,
        ]);
        DB::table('payments')->where('order_id', $order->id)->update([
            'paid_at' => $demoTime,
            'created_at' => $demoTime,
            'updated_at' => $demoTime,
        ]);

        $paymentIds = DB::table('payments')->where('order_id', $order->id)->select('id');
        DB::table('refunds')->whereIn('payment_id', $paymentIds)->update([
            'refunded_at' => $demoTime,
            'created_at' => $demoTime,
            'updated_at' => $demoTime,
        ]);
        DB::table('print_logs')->where('order_id', $order->id)->update(['printed_at' => $demoTime]);
        DB::table('cash_movements')->where('order_id', $order->id)->update(['occurred_at' => $demoTime]);
    }

    /** @param array<int, RestaurantTable> $tables */
    private function seedReservations(User $manager, array $tables): void
    {
        $items = [
            ['reference'=>'DEMO-RSV-BDAY','name'=>'الناز نادری','phone'=>'09121234561','email'=>'birthday-demo@example.test','party'=>8,'days'=>4,'hour'=>18,'duration'=>150,'status'=>'pending','source'=>'website','purpose'=>'birthday','occasion'=>'تولد الناز','budget'=>8000000,'table'=>null,'notes'=>'کیک شکلاتی، تزیین ساده و میز نزدیک پنجره.'],
            ['reference'=>'DEMO-RSV-CONS','name'=>'مهدی حیدری','phone'=>'09121234562','email'=>'consult-demo@example.test','party'=>3,'days'=>2,'hour'=>11,'duration'=>60,'status'=>'confirmed','source'=>'website','purpose'=>'consultation','occasion'=>'جلسه هماهنگی مراسم','budget'=>2500000,'table'=>2,'notes'=>'یک فضای نسبتاً آرام برای مشاوره لازم است.'],
            ['reference'=>'DEMO-RSV-TABLE','name'=>'سحر یوسفی','phone'=>'09121234563','email'=>null,'party'=>4,'days'=>1,'hour'=>20,'duration'=>90,'status'=>'confirmed','source'=>'staff','purpose'=>'table','occasion'=>null,'budget'=>null,'table'=>5,'notes'=>'ترجیحاً میز کنار پنجره.'],
            ['reference'=>'DEMO-RSV-CATER','name'=>'شرکت آبان','phone'=>'02155550000','email'=>'events-demo@example.test','party'=>20,'days'=>7,'hour'=>15,'duration'=>180,'status'=>'pending','source'=>'website','purpose'=>'catering','occasion'=>'جلسه تیمی','budget'=>15000000,'table'=>null,'notes'=>'پیشنهاد پکیج نوشیدنی و خوراکی برای ۲۰ نفر.'],
            ['reference'=>'DEMO-RSV-DONE','name'=>'پارسا امینی','phone'=>'09121234564','email'=>null,'party'=>2,'days'=>-1,'hour'=>19,'duration'=>90,'status'=>'arrived','source'=>'staff','purpose'=>'table','occasion'=>null,'budget'=>null,'table'=>1,'notes'=>'رزرو نمونه انجام‌شده برای تست تاریخچه.'],
        ];

        foreach ($items as $index => $item) {
            $starts = now()->addDays($item['days'])->setTime($item['hour'], 0)->seconds(0);
            Reservation::query()->firstOrCreate(
                ['reference'=>$item['reference']],
                [
                    'restaurant_table_id'=>isset($item['table']) ? $tables[$item['table']]->id : null,
                    'customer_name'=>$item['name'],'phone'=>$item['phone'],'email'=>$item['email'],
                    'party_size'=>$item['party'],'starts_at'=>$starts,'duration_minutes'=>$item['duration'],
                    'ends_at'=>$starts->copy()->addMinutes($item['duration']),
                    'status'=>ReservationStatus::from($item['status']),'source'=>$item['source'],
                    'purpose'=>$item['purpose'],'occasion_name'=>$item['occasion'],
                    'estimated_budget' => ($item['budget'] === null ? null : $item['budget'] * $this->currencyMultiplier),'notes'=>$item['notes'],
                    'idempotency_key'=>'demo-reservation-'.$index.'-'.strtolower($item['reference']),
                    'idempotency_hash'=>hash('sha256',$item['reference']),
                    'created_by'=>$item['source']==='staff' ? $manager->id : null,
                ],
            );
        }
    }

    private function seedFeedback(): void
    {
        $feedback = [
            ['email'=>'feedback-demo-1@example.test','name'=>'نیلوفر احمدی','phone'=>'09121110001','kind'=>'praise','rating'=>5,'message'=>'لاته خیلی خوش‌طعم بود و سفارش QR بدون معطلی ثبت شد.','status'=>'resolved','response'=>'از اینکه تجربه‌ات را به اشتراک گذاشتی ممنونیم.'],
            ['email'=>'feedback-demo-2@example.test','name'=>'آرمان شجاعی','phone'=>'09121110002','kind'=>'suggestion','rating'=>4,'message'=>'اگر گزینه شیر گیاهی در منو پررنگ‌تر نمایش داده شود عالی می‌شود.','status'=>'in_progress','response'=>null],
            ['email'=>'feedback-demo-3@example.test','name'=>'مشتری نمونه','phone'=>'09121110003','kind'=>'complaint','rating'=>2,'message'=>'سفارش بیرون‌بر کمی دیر آماده شد؛ لطفاً زمان تقریبی را به‌روز کنید.','status'=>'new','response'=>null],
        ];
        foreach ($feedback as $item) {
            CustomerFeedback::query()->firstOrCreate(
                ['email'=>$item['email']],
                [
                    'customer_name'=>$item['name'],'phone'=>$item['phone'],'kind'=>$item['kind'],
                    'rating'=>$item['rating'],'message'=>$item['message'],'status'=>$item['status'],
                    'admin_response'=>$item['response'],
                ],
            );
        }
    }
}
