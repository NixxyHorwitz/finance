<?php
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// ─────────────────────────────────────────────────────────────
//  SMART CATEGORIZE — Gen-Z ID dictionary (11 categories)
//  Scoring: category with most keyword hits wins.
// ─────────────────────────────────────────────────────────────
function smartCategorize($description, $pdo) {
    $desc = mb_strtolower(trim($description), 'UTF-8');

    $rules = [

        // 🍔 Makanan & Minuman
        "Makanan & Minuman" => [
            // delivery
            "gofood","grabfood","shopeefood","shopee food",
            // generic
            "makan","makanan","nasi","ayam","ikan","daging","sate","soto","bakso","mie","mi",
            "indomie","bubur","gado","ketoprak","lontong","rendang","gulai","pecel","rawon",
            "pempek","siomay","batagor","cilok","cimol","tahu","tempe","gorengan",
            "martabak","lumpia","risol","pastel","cireng",
            // fast food & resto
            "pizza","burger","hotdog","sandwich","kebab","shawarma","sushi","ramen","udon",
            "pasta","steak","grill","bbq","fried chicken","kfc","mcd","mcdonalds",
            "hokben","hoka hoka","yoshinoya","solaria","popeyes","wendy",
            "warung","warteg","kantin","resto","restoran","rumah makan","depot","rm ",
            // meals
            "sarapan","brunch","lunch","dinner","makan siang","makan malam","makan pagi",
            "jajan","ngemil","nongki","nongkrong",
            // snacks & sweets
            "snack","camilan","keripik","oreo","biskuit","coklat","kue","donat",
            "waffle","pancake","pudding","es krim","ice cream","gelato","brownies",
            "cookies","roti","bakery","toast",
            // drinks
            "minum","minuman","kopi","ngopi","coffee","cafe","coffeshop","starbucks",
            "kopi kenangan","janji jiwa","fore coffee","flash coffee","excelso","jco",
            "dunkin","teh","tea","boba","thai tea","taro","matcha","oat milk",
            "susu","jus","juice","smoothie","milkshake","es teh","aqua","miniso drink",
            // specific spots / brands
            "chatime","gong cha","tiger sugar","xing fu","mie gacoan","geprek",
            "lalapan","bebek","seafood","kepiting","cumi","pecel lele",
        ],

        // 🚗 Transportasi
        "Transportasi" => [
            // fuel
            "bensin","solar","pertamax","pertalite","pertamina","shell","spbu","bbm",
            "ngisi bensin","isi bensin","isi solar","full tank",
            // ride hailing
            "gojek","grab","goride","grabride","gocar","grabcar","gopool",
            "maxim","indriver","ojek","ojol","ojek online",
            // parking
            "parkir","parkiran","valet",
            // toll
            "tol","e-toll","etoll","toll",
            // public transport
            "kereta","krl","commuter","mrt","lrt","transjakarta","busway","bus",
            "angkot","angkutan","mikrolet","damri","taxi","taksi","bluebird","blue bird",
            // flights
            "tiket","pesawat","penerbangan","flight","lion air","garuda","citilink",
            "batik air","airasia","sriwijaya","super air",
            "traveloka","tiket.com","booking pesawat",
            // maintenance
            "servis","service","bengkel","ganti oli","oli","ban","aki",
        ],

        // 🛍️ Belanja
        "Belanja" => [
            // e-commerce
            "shopee","tokopedia","lazada","tiktok shop","bukalapak","blibli","jd.id",
            "amazon","aliexpress","zalora","sociolla","berrybenka","orami",
            "checkout","marketplace","official store",
            // minimarket / supermarket
            "indomaret","alfamart","alfamidi","circle k","lawson","family mart",
            "supermarket","hypermart","carrefour","transmart","giant","hero",
            "lottemart","ranch market","papaya","food hall","grand lucky",
            // fashion
            "baju","kaos","celana","jeans","denim","jaket","sweater","hoodie","cardigan",
            "dress","rok","kemeja","jas","blazer","atasan","bawahan","outfit","pakaian",
            "fashion","clothing","apparel","uniqlo","zara","h&m","pull bear","cotton on",
            "nevada","levis","levi","nike","adidas","puma","reebok","new balance",
            "sepatu","sandal","sendal","slipper","sneaker","heels","boots","flat shoes",
            "tas","tote bag","backpack","ransel","dompet","belt","sabuk",
            "jam tangan","kacamata","sunglasses","aksesoris","accessories",
            "topi","gelang","kalung","cincin","anting",
            // electronics
            "hp","handphone","smartphone","iphone","samsung","oppo","xiaomi","vivo",
            "realme","poco","laptop","notebook","tablet","ipad","earphone","airpods",
            "headset","headphone","tws","speaker","charger","powerbank","power bank",
            "adapter","gadget","elektronik","monitor","keyboard","mouse",
            // household
            "perabot","furniture","kasur","bantal","selimut","handuk","piring","gelas",
            "sabun","shampo","deterjen","pewangi",
        ],

        // 🎮 Hiburan
        "Hiburan" => [
            // streaming
            "netflix","disney+","disneyplus","hbo","max","vidio","viu",
            "amazon prime","youtube premium","wetv","mola","rcti",
            "spotify","joox","resso","tidal","apple music","deezer","soundcloud",
            "twitch","subscribe","langganan","streaming",
            // gaming
            "game","gaming","steam","epic games","playstation","xbox","nintendo",
            "mobile legend","mlbb","pubg","free fire","freefire","genshin",
            "valorant","roblox","minecraft","cod","codm","call of duty",
            "topup","top up","diamond","uc","coin","gem","skin","token game",
            "voucher game","garena","riot","moonton","tencent","codashop","unipin",
            // cinema
            "nonton","bioskop","cinema","cgv","cinepolis","xxi","21 cineplex","imax","4dx",
            "tiket bioskop","movie","film",
            // activities
            "karaoke","bowling","arcade","warnet","gaming center",
            "hiking","mendaki","camping","kemping","outbound","rafting","paintball",
            "gym","fitness","olahraga","renang","badminton","futsal","basket","golf",
            "yoga","pilates","muay thai","boxing","taekwondo",
            // events
            "konser","concert","festival","live show","event","pertunjukan",
            "tiket konser","meet and greet","fanmeet",
            // hobby
            "manhwa","manga","webtoon","komik","novel","self reward",
        ],

        // 📄 Tagihan
        "Tagihan" => [
            // electricity
            "listrik","pln","token listrik","bayar listrik",
            // water
            "pdam","air bersih","bayar air",
            // internet & phone
            "internet","wifi","wi-fi","indihome","first media","biznet","myrepublic","transvision",
            "pulsa","kuota","data","paket data","isi pulsa","beli pulsa",
            "telkomsel","xl","axis","indosat","im3","tri","three","smartfren","byU","by.u",
            // housing
            "kos","kost","kontrakan","indekos","apartemen","apartment","kamar kos",
            "sewa","ngekos","bayar kos","uang kos","bayar sewa","uang sewa","bulanan kos","ipl",
            // installment
            "cicilan","kredit","angsuran","dp","uang muka","asuransi","premi",
            "kartu kredit","pinjaman","hutang","bayar hutang","jatuh tempo",
            "spaylater","shopee paylater","kredivo","akulaku","home credit","fif","baf",
            // general
            "tagihan","iuran","bulanan","tahunan","abonemen",
            "pajak","pbb","pkb","stnk","perpanjang stnk",
        ],

        // 💊 Kesehatan
        "Kesehatan" => [
            "dokter","klinik","puskesmas","rumah sakit","igd","usg",
            "konsultasi dokter","cek kesehatan","medical check",
            "apotik","apotek","kimia farma","guardian","century",
            "obat","vitamin","suplemen","supplement","minyak kayu putih","antigen","pcr",
            "bpjs","asuransi kesehatan",
            "spa","massage","pijat","refleksi","akupuntur","lulur",
            "psikolog","psikiater","konseling","therapy","terapi",
        ],

        // 📚 Pendidikan
        "Pendidikan" => [
            "spp","uang kuliah","biaya kuliah","uang semester","uang sekolah",
            "kursus","les","bimbel","privat","les privat","bimbingan belajar",
            "course","online course","udemy","coursera","dicoding","ruangguru",
            "zenius","quipper","cakap","skill academy","duolingo","skillshare",
            "buku","buku tulis","buku pelajaran","textbook",
            "alat tulis","pensil","bolpen","spidol","stabilo","kertas","hvs",
            "fotokopi","print","laminating","jilid",
        ],

        // 💄 Kecantikan
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

        // 📈 Investasi
        "Investasi" => [
            "saham","stock","nabung saham","reksadana","reksa dana","mutual fund",
            "obligasi","bonds","sbn","sukuk","ipo",
            "deposito","tabungan","nabung","menabung","celengan",
            "emas","logam mulia","antam","pegadaian",
            "bitcoin","btc","ethereum","eth","crypto","kripto","nft","defi","staking",
            "bibit","bareksa","ipot","ajaib","pluang","indopremier",
            "investasi","invest","portofolio","dividen",
        ],

        // ✈️ Perjalanan
        "Perjalanan" => [
            "hotel","penginapan","villa","airbnb","booking","check in","hostel","resort","glamping",
            "wisata","jalan jalan","liburan","healing","vacation","trip","tour","traveling",
            "backpacker","staycation","workcation",
            "tiket masuk","entrance fee","loket","retribusi",
            "oleh oleh","souvenir","pasport","visa","imigrasi","koper",
        ],

        // 🎁 Sosial & Hadiah
        "Sosial & Hadiah" => [
            "hadiah","kado","present","gift","hamper","parcel","bunga","bouquet",
            "sedekah","donasi","zakat","infak","amal","jariyah","sumbangan","wakaf","charity",
            "kondangan","pernikahan","wedding","akad","walimah","lamaran","mahar",
            "ultah","ulang tahun","anniversary","aqiqah","sunatan","khitanan",
            "traktir","treat","bayarin","iuran arisan","arisan","patungan",
        ],
    ];

    // Scoring: count keyword hits per category, pick highest
    $scores = [];
    foreach ($rules as $cat => $keywords) {
        $score = 0;
        foreach ($keywords as $kw) {
            // word-boundary-aware: keyword must not be surrounded by other alphanumeric chars
            $pattern = '/(?<![a-z0-9])' . preg_quote($kw, '/') . '(?![a-z0-9])/u';
            if (preg_match($pattern, $desc)) {
                $score++;
            }
        }
        if ($score > 0) $scores[$cat] = $score;
    }

    $matchedCategory = "Lainnya";
    if (!empty($scores)) {
        arsort($scores);
        $matchedCategory = array_key_first($scores);
    }

    // Upsert category into DB
    $stmt = $pdo->prepare("SELECT id FROM Category WHERE name = ?");
    $stmt->execute([$matchedCategory]);
    $catRow = $stmt->fetch();

    if ($catRow) {
        return $catRow['id'];
    } else {
        $newId = generateUUID();
        $pdo->prepare("INSERT INTO Category (id, name, keywords) VALUES (?, ?, '')")
            ->execute([$newId, $matchedCategory]);
        return $newId;
    }
}

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
                $categoryId = smartCategorize($description, $pdo);
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
