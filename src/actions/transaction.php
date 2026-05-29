<?php
require_once __DIR__.'/../../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// ═══════════════════════════════════════════════════════════
//  CATEGORY CONSTANTS
// ═══════════════════════════════════════════════════════════
const CATEGORIES = [
    'Makanan & Minuman','Transportasi','Belanja','Hiburan',
    'Tagihan','Kesehatan','Pendidikan','Kecantikan',
    'Investasi','Perjalanan','Sosial & Hadiah','Lainnya'
];

// ═══════════════════════════════════════════════════════════

// ═══════════════════════════════════════════════════════════
//  GEMINI API — primary categorizer
// ═══════════════════════════════════════════════════════════
function callGemini(string $description, string $apiKey): ?string {
    $catList = implode(', ', CATEGORIES);
    $prompt  = "Kamu adalah asisten keuangan Gen-Z Indonesia yang ahli mengkategorikan transaksi.\n" .
               "Tugasmu: Kategorikan deskripsi berikut ke dalam SATU kategori yang paling relevan.\n\n" .
               "Konteks/Bahasa Gaul:\n" .
               "- 'ciki', 'seblak', 'boba', 'mixue', 'kopi', 'gofood', 'jajan', 'makan' -> Makanan & Minuman\n" .
               "- 'gojek', 'grab', 'bensin', 'parkir', 'krl', 'mrt' -> Transportasi\n" .
               "- 'topup', 'gopay', 'dana', 'ovo', 'tf' -> Transfer/Lainnya (tergantung konteks)\n" .
               "- 'shopee', 'tokped', 'baju', 'skincare' -> Belanja\n" .
               "- 'netflix', 'spotify', 'nonton', 'bioskop' -> Hiburan\n\n" .
               "Daftar Kategori: {$catList}\n\n" .
               "Deskripsi Transaksi: \"{$description}\"\n\n" .
               "Jawab HANYA dengan SATU nama kategori persis seperti di atas. Tanpa tanda baca, penjelasan, atau kata tambahan.";

    $payload = json_encode([
        'contents'         => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['temperature' => 0, 'maxOutputTokens' => 30],
    ]);

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$resp) return null;

    $data = json_decode($resp, true);
    $text = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');

    if (in_array($text, CATEGORIES)) return $text;
    foreach (CATEGORIES as $cat) {
        if (stripos($text, $cat) !== false) return $cat;
    }
    return null;
}

//  OPENAI API — primary categorizer
// ═══════════════════════════════════════════════════════════
function callOpenAI(string $description, string $apiKey): ?string {
    $catList = implode(', ', CATEGORIES);
    $prompt  = "Kamu adalah asisten keuangan Gen-Z Indonesia yang ahli mengkategorikan transaksi.\n" .
               "Tugasmu: Kategorikan deskripsi berikut ke dalam SATU kategori yang paling relevan.\n\n" .
               "Konteks/Bahasa Gaul:\n" .
               "- 'ciki', 'seblak', 'boba', 'mixue', 'kopi', 'gofood', 'jajan', 'makan' -> Makanan & Minuman\n" .
               "- 'gojek', 'grab', 'bensin', 'parkir', 'krl', 'mrt' -> Transportasi\n" .
               "- 'topup', 'gopay', 'dana', 'ovo', 'tf' -> Transfer/Lainnya (tergantung konteks)\n" .
               "- 'shopee', 'tokped', 'baju', 'skincare' -> Belanja\n" .
               "- 'netflix', 'spotify', 'nonton', 'bioskop' -> Hiburan\n\n" .
               "Daftar Kategori: {$catList}\n\n" .
               "Deskripsi Transaksi: \"{$description}\"\n\n" .
               "Jawab HANYA dengan SATU nama kategori persis seperti di atas. Tanpa tanda baca, penjelasan, atau kata tambahan.";

    $payload = json_encode([
        'model'       => 'gpt-4o-mini',
        'messages'    => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0,
        'max_tokens'  => 30,
    ]);

    $url = 'https://api.openai.com/v1/chat/completions';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    $resp     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$resp) return null;

    $data = json_decode($resp, true);
    $text = trim($data['choices'][0]['message']['content'] ?? '');

    // Exact match
    if (in_array($text, CATEGORIES)) return $text;
    // Partial match
    foreach (CATEGORIES as $cat) {
        if (stripos($text, $cat) !== false) return $cat;
    }
    return null;
}

// ═══════════════════════════════════════════════════════════
//  KEYWORD FALLBACK — 11 category dictionary
// ═══════════════════════════════════════════════════════════
function keywordCategorize(string $desc): string {
    $rules = [
        "Makanan & Minuman" => [
            "gofood","grabfood","shopeefood","shopee food",
            "makan","makanan","nasi","ayam","ikan","daging","sate","soto","bakso","mie","mi",
            "indomie","bubur","gado","ketoprak","lontong","rendang","gulai","pecel","rawon",
            "pempek","siomay","batagor","cilok","cimol","tahu","tempe","gorengan",
            "martabak","lumpia","risol","pastel","cireng",
            "pizza","burger","hotdog","sandwich","kebab","shawarma","sushi","ramen","udon",
            "pasta","steak","grill","bbq","fried chicken","kfc","mcd","mcdonalds",
            "hokben","hoka hoka","yoshinoya","solaria","popeyes","wendy",
            "warung","warteg","kantin","resto","restoran","rumah makan","depot","rm ",
            "sarapan","brunch","lunch","dinner","makan siang","makan malam","makan pagi",
            "jajan","ngemil","nongki","nongkrong",
            "snack","camilan","keripik","oreo","biskuit","coklat","kue","donat",
            "waffle","pancake","pudding","es krim","ice cream","gelato","brownies",
            "cookies","roti","bakery","toast",
            "minum","minuman","kopi","ngopi","coffee","cafe","coffeeshop","starbucks",
            "kopi kenangan","janji jiwa","fore coffee","flash coffee","excelso","jco",
            "dunkin","teh","tea","boba","thai tea","taro","matcha","oat milk",
            "susu","jus","juice","smoothie","milkshake","es teh","aqua",
            "chatime","gong cha","tiger sugar","xing fu","mie gacoan","geprek",
            "lalapan","bebek","seafood","kepiting","cumi","pecel lele",
        ],
        "Transportasi" => [
            "bensin","solar","pertamax","pertalite","pertamina","shell","spbu","bbm",
            "ngisi bensin","isi bensin","isi solar","full tank",
            "gojek","grab","goride","grabride","gocar","grabcar","gopool",
            "maxim","indriver","ojek","ojol","ojek online",
            "parkir","parkiran","valet",
            "tol","e-toll","etoll",
            "kereta","krl","commuter","mrt","lrt","transjakarta","busway","bus",
            "angkot","angkutan","mikrolet","damri","taxi","taksi","bluebird","blue bird",
            "tiket","pesawat","penerbangan","flight","lion air","garuda","citilink",
            "batik air","airasia","sriwijaya","super air",
            "traveloka","tiket.com","booking pesawat",
            "servis","service","bengkel","ganti oli","oli","ban","aki",
        ],
        "Belanja" => [
            "shopee","tokopedia","lazada","tiktok shop","bukalapak","blibli","jd.id",
            "amazon","aliexpress","zalora","sociolla","berrybenka","orami",
            "checkout","marketplace","official store",
            "indomaret","alfamart","alfamidi","circle k","lawson","family mart",
            "supermarket","hypermart","carrefour","transmart","giant","hero",
            "lottemart","ranch market","papaya","food hall","grand lucky",
            "baju","kaos","celana","jeans","denim","jaket","sweater","hoodie","cardigan",
            "dress","rok","kemeja","jas","blazer","atasan","bawahan","outfit","pakaian",
            "fashion","clothing","apparel","uniqlo","zara","h&m","pull bear","cotton on",
            "nevada","levis","levi","nike","adidas","puma","reebok","new balance",
            "sepatu","sandal","sendal","slipper","sneaker","heels","boots","flat shoes",
            "tas","tote bag","backpack","ransel","dompet","belt","sabuk",
            "jam tangan","kacamata","sunglasses","aksesoris","accessories",
            "topi","gelang","kalung","cincin","anting",
            "hp","handphone","smartphone","iphone","samsung","oppo","xiaomi","vivo",
            "realme","poco","laptop","notebook","tablet","ipad","earphone","airpods",
            "headset","headphone","tws","speaker","charger","powerbank","power bank",
            "adapter","gadget","elektronik","monitor","keyboard","mouse",
            "perabot","furniture","kasur","bantal","selimut","handuk","piring","gelas",
            "sabun","shampo","deterjen","pewangi",
        ],
        "Hiburan" => [
            "netflix","disney+","disneyplus","hbo","max","vidio","viu",
            "amazon prime","youtube premium","wetv","mola","rcti",
            "spotify","joox","resso","tidal","apple music","deezer","soundcloud",
            "twitch","subscribe","langganan","streaming",
            "game","gaming","steam","epic games","playstation","xbox","nintendo",
            "mobile legend","mlbb","pubg","free fire","freefire","genshin",
            "valorant","roblox","minecraft","cod","codm","call of duty",
            "topup","top up","diamond","uc","coin","gem","skin","token game",
            "voucher game","garena","riot","moonton","tencent","codashop","unipin",
            "nonton","bioskop","cinema","cgv","cinepolis","xxi","21 cineplex","imax","4dx",
            "tiket bioskop","movie","film",
            "karaoke","bowling","arcade","warnet","gaming center",
            "hiking","mendaki","camping","kemping","outbound","rafting","paintball",
            "gym","fitness","olahraga","renang","badminton","futsal","basket","golf",
            "yoga","pilates","muay thai","boxing","taekwondo",
            "konser","concert","festival","live show","event","pertunjukan",
            "tiket konser","meet and greet","fanmeet",
            "manhwa","manga","webtoon","komik","novel","self reward",
        ],
        "Tagihan" => [
            "listrik","pln","token listrik","bayar listrik",
            "pdam","air bersih","bayar air",
            "internet","wifi","wi-fi","indihome","first media","biznet","myrepublic","transvision",
            "pulsa","kuota","data","paket data","isi pulsa","beli pulsa",
            "telkomsel","xl","axis","indosat","im3","tri","three","smartfren","byU","by.u",
            "kos","kost","kontrakan","indekos","apartemen","apartment","kamar kos",
            "sewa","ngekos","bayar kos","uang kos","bayar sewa","uang sewa","bulanan kos","ipl",
            "cicilan","kredit","angsuran","dp","uang muka","asuransi","premi",
            "kartu kredit","pinjaman","hutang","bayar hutang","jatuh tempo",
            "spaylater","shopee paylater","kredivo","akulaku","home credit","fif","baf",
            "tagihan","iuran","bulanan","tahunan","abonemen",
            "pajak","pbb","pkb","stnk","perpanjang stnk",
        ],
        "Kesehatan" => [
            "dokter","klinik","puskesmas","rumah sakit","igd","usg",
            "konsultasi dokter","cek kesehatan","medical check",
            "apotik","apotek","kimia farma","guardian","century",
            "obat","vitamin","suplemen","supplement","minyak kayu putih","antigen","pcr",
            "bpjs","asuransi kesehatan",
            "spa","massage","pijat","refleksi","akupuntur","lulur",
            "psikolog","psikiater","konseling","therapy","terapi",
        ],
        "Pendidikan" => [
            "spp","uang kuliah","biaya kuliah","uang semester","uang sekolah",
            "kursus","les","bimbel","privat","les privat","bimbingan belajar",
            "course","online course","udemy","coursera","dicoding","ruangguru",
            "zenius","quipper","cakap","skill academy","duolingo","skillshare",
            "buku","buku tulis","buku pelajaran","textbook",
            "alat tulis","pensil","bolpen","spidol","stabilo","kertas","hvs",
            "fotokopi","print","laminating","jilid",
        ],
        "Kecantikan" => [
            "salon","creambath","keriting","smoothing","rebonding","blow","cat rambut",
            "semir rambut","barbershop","potong rambut","cukur","hair treatment","hair mask",
            "skincare","skin care","facewash","toner","serum","moisturizer","sunscreen","spf",
            "exfoliant","scrub","masker wajah","clay mask","eye cream","retinol","essence",
            "foundation","bb cream","cushion","concealer","bedak","powder","blush",
            "bronzer","highlighter","setting spray","eyeliner","mascara","alis","lipstik",
            "lip cream","lip tint","lip balm","eyeshadow","contour","makeup","make up",
            "parfum","perfume","deodorant","body lotion","body wash","sabun mandi",
            "nail art","kutek","cat kuku","manicure","pedicure",
            "wardah","emina","ms glow","scarlett","somethinc","skintific","inez",
            "maybelline","loreal","nyx","pixy","implora","esqa",
        ],
        "Investasi" => [
            "saham","stock","nabung saham","reksadana","reksa dana","mutual fund",
            "obligasi","bonds","sbn","sukuk","ipo",
            "deposito","tabungan","nabung","menabung","celengan",
            "emas","logam mulia","antam","pegadaian",
            "bitcoin","btc","ethereum","eth","crypto","kripto","nft","defi","staking",
            "bibit","bareksa","ipot","ajaib","pluang","indopremier",
            "investasi","invest","portofolio","dividen",
        ],
        "Perjalanan" => [
            "hotel","penginapan","villa","airbnb","booking","check in","hostel","resort","glamping",
            "wisata","jalan jalan","liburan","healing","vacation","trip","tour","traveling",
            "backpacker","staycation","workcation",
            "tiket masuk","entrance fee","loket","retribusi",
            "oleh oleh","souvenir","pasport","visa","imigrasi","koper",
        ],
        "Sosial & Hadiah" => [
            "hadiah","kado","present","gift","hamper","parcel","bunga","bouquet",
            "sedekah","donasi","zakat","infak","amal","jariyah","sumbangan","wakaf","charity",
            "kondangan","pernikahan","wedding","akad","walimah","lamaran","mahar",
            "ultah","ulang tahun","anniversary","aqiqah","sunatan","khitanan",
            "traktir","treat","bayarin","iuran arisan","arisan","patungan",
        ],
    ];

    // Scoring: pick category with most keyword hits
    $scores = [];
    foreach ($rules as $cat => $keywords) {
        $score = 0;
        foreach ($keywords as $kw) {
            $pattern = '/(?<![a-z0-9])' . preg_quote($kw, '/') . '(?![a-z0-9])/u';
            if (preg_match($pattern, $desc)) $score++;
        }
        if ($score > 0) $scores[$cat] = $score;
    }

    if (!empty($scores)) {
        arsort($scores);
        return array_key_first($scores);
    }
    return 'Lainnya';
}

// ═══════════════════════════════════════════════════════════
//  UPSERT CATEGORY IN DB
// ═══════════════════════════════════════════════════════════
function upsertCategory(string $name, PDO $pdo): string {
    $stmt = $pdo->prepare("SELECT id FROM Category WHERE name = ?");
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if ($row) return $row['id'];

    $newId = generateUUID();
    $pdo->prepare("INSERT INTO Category (id, name, keywords) VALUES (?, ?, '')")
        ->execute([$newId, $name]);
    return $newId;
}


// ═══════════════════════════════════════════════════════════
//  MAIN CATEGORIZE — Dynamic AI with keyword fallback
// ═══════════════════════════════════════════════════════════
function smartCategorize(string $description, PDO $pdo, string $userId): string {
    // Check AI Provider setting
    $s = $pdo->prepare("SELECT `key`, value FROM UserSetting WHERE userId = ? AND `key` IN ('ai_provider', 'gemini_api_key', 'openai_api_key')");
    $s->execute([$userId]);
    $settings = [];
    while ($r = $s->fetch()) $settings[$r['key']] = trim($r['value']);
    
    $provider = $settings['ai_provider'] ?? 'openai'; // default to openai

    // Also check .env for fallback
    $envPath = __DIR__ . '/../../.env';
    $env = file_exists($envPath) ? parse_ini_file($envPath) : [];
    
    $aiCat = null;

    if ($provider === 'gemini') {
        $geminiKey = $settings['gemini_api_key'] ?? ($env['GEMINI_API_KEY'] ?? null);
        if ($geminiKey) {
            $aiCat = callGemini($description, $geminiKey);
        }
    } else {
        // OpenAI
        $openAiKey = $settings['openai_api_key'] ?? ($env['OPENAI_API_KEY'] ?? null);
        if ($openAiKey) {
            $aiCat = callOpenAI($description, $openAiKey);
        }
    }

    if ($aiCat) {
        return upsertCategory($aiCat, $pdo);
    }

    // Keyword scoring fallback
    $desc = mb_strtolower(trim($description), 'UTF-8');
    $cat  = keywordCategorize($desc);
    return upsertCategory($cat, $pdo);
}

// ═══════════════════════════════════════════════════════════
//  ACTIONS
// ═══════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add_transaction') {
        $type            = $_POST['type'] ?? '';
        $amount          = (float)($_POST['amount'] ?? 0);
        $description     = $_POST['description'] ?? '';
        $walletId        = $_POST['walletId'] ?? '';
        $relatedWalletId = $_POST['relatedWalletId'] ?? null;

        if (!$type || $amount <= 0 || !$description || !$walletId) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields or invalid amount']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id FROM Wallet WHERE id = ? AND userId = ?");
            $stmt->execute([$walletId, $userId]);
            if (!$stmt->fetch()) throw new Exception("Unauthorized wallet");

            $categoryId = null;
            if ($type !== 'TRANSFER') {
                $categoryId = smartCategorize($description, $pdo, $userId);
            }

            $txId = generateUUID();
            $pdo->prepare("INSERT INTO `Transaction` (id, userId, walletId, type, amount, description, date, categoryId, relatedWalletId) VALUES (?, ?, ?, ?, ?, ?, NOW(3), ?, ?)")
                ->execute([$txId, $userId, $walletId, $type, $amount, $description, $categoryId, $relatedWalletId]);

            if ($type === 'INCOME') {
                $pdo->prepare("UPDATE Wallet SET balance = balance + ? WHERE id = ?")->execute([$amount, $walletId]);
            } elseif ($type === 'EXPENSE') {
                $pdo->prepare("UPDATE Wallet SET balance = balance - ? WHERE id = ?")->execute([$amount, $walletId]);
            } elseif ($type === 'TRANSFER') {
                $pdo->prepare("UPDATE Wallet SET balance = balance - ? WHERE id = ?")->execute([$amount, $walletId]);
                $pdo->prepare("UPDATE Wallet SET balance = balance + ? WHERE id = ?")->execute([$amount, $relatedWalletId]);
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

    } elseif ($action === 'set_balance') {
        $walletId = $_POST['walletId'] ?? '';
        $balance  = (float)($_POST['balance'] ?? 0);
        try {
            $pdo->prepare("UPDATE Wallet SET balance = ? WHERE id = ? AND userId = ?")
                ->execute([$balance, $walletId, $userId]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if ($action === 'delete_transaction') {
        parse_str(file_get_contents("php://input"), $delVars);
        $txId = $delVars['id'] ?? '';

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM `Transaction` WHERE id = ? AND userId = ?");
            $stmt->execute([$txId, $userId]);
            $tx = $stmt->fetch();

            if (!$tx) throw new Exception("Transaction not found");

            $amt = (float)$tx['amount'];
            if ($tx['type'] === 'INCOME') {
                $pdo->prepare("UPDATE Wallet SET balance = balance - ? WHERE id = ?")->execute([$amt, $tx['walletId']]);
            } elseif ($tx['type'] === 'EXPENSE') {
                $pdo->prepare("UPDATE Wallet SET balance = balance + ? WHERE id = ?")->execute([$amt, $tx['walletId']]);
            } elseif ($tx['type'] === 'TRANSFER') {
                $pdo->prepare("UPDATE Wallet SET balance = balance + ? WHERE id = ?")->execute([$amt, $tx['walletId']]);
                $pdo->prepare("UPDATE Wallet SET balance = balance - ? WHERE id = ?")->execute([$amt, $tx['relatedWalletId']]);
            }

            $pdo->prepare("DELETE FROM `Transaction` WHERE id = ?")->execute([$txId]);
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?>
