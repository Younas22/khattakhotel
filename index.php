<?php
/**
 * Khattak Hotel — Menu rendering engine
 * Reads menu-data.json and renders category tabs, search-ready item cards,
 * a curated "Chef's Picks" set, and the official menu image gallery.
 */
$jsonData = file_get_contents('menu-data.json');
$menuData = json_decode($jsonData, true);
$categories = $menuData['categories'] ?? [];
$popularCategories = array_values(array_filter($categories, fn($c) => !empty($c['popular'])));
$serviceCharge = $menuData['serviceCharge'] ?? '';
$menuGallery = $menuData['menuGallery'] ?? [];

// Build a flat lookup of every item by its printed menu number, tagged with its category title/id
$itemsByNo = [];
foreach ($categories as $cat) {
    foreach ($cat['sections'] as $section) {
        foreach (($section['items'] ?? []) as $item) {
            if (isset($item['no'])) {
                $itemsByNo[$item['no']] = ['item' => $item, 'catTitle' => $cat['title'], 'catId' => $cat['id']];
            }
        }
    }
}
$chefsPicks = [];
foreach (($menuData['chefsPicks'] ?? []) as $no) {
    if (isset($itemsByNo[$no])) $chefsPicks[] = $itemsByNo[$no];
}

function searchAttr($name, $urdu, $catTitle) {
    return htmlspecialchars(mb_strtolower(trim($name . ' ' . $urdu . ' ' . $catTitle)), ENT_QUOTES);
}

// Renders one item as a text-forward menu card (no per-item photography — see project notes)
function renderItemCard($item, $catTitle, $showCatTag = false) {
    $name = htmlspecialchars($item['name']);
    $urdu = htmlspecialchars($item['urdu_name'] ?? '');
    $no = isset($item['no']) ? (int)$item['no'] : null;
    $desc = isset($item['description']) ? htmlspecialchars(trim($item['description'])) : '';
    $search = searchAttr($item['name'], $item['urdu_name'] ?? '', $catTitle);

    $badge = $no ? ('<span class="item-no">' . $no . '</span>') : '<i class="fas fa-utensils item-no-icon"></i>';
    $catTag = $showCatTag ? '<span class="cat-tag">' . htmlspecialchars($catTitle) . '</span>' : '';
    $descHtml = $desc ? '<p class="item-desc" dir="rtl">' . $desc . '</p>' : '';

    if (isset($item['sizes'])) {
        $chips = '';
        foreach ($item['sizes'] as $s) {
            $sizeLabel = htmlspecialchars($s['size']);
            $price = htmlspecialchars($s['price']);
            $waText = str_replace("'", "\\'", $name . ' (' . $sizeLabel . ') - ' . $price);
            $chips .= '<button onclick="orderWhatsapp(\'' . $waText . '\')" class="size-chip">' . $sizeLabel . ' <b>' . $price . '</b></button>';
        }
        $footer = '<div class="size-chip-row">' . $chips . '</div>';
    } else {
        $price = htmlspecialchars($item['price'] ?? '');
        $waText = str_replace("'", "\\'", $name . ' - ' . $price);
        $footer = '<div class="card-footer">
            <span class="card-price">' . $price . '</span>
            <button onclick="orderWhatsapp(\'' . $waText . '\')" class="card-btn"><i class="fab fa-whatsapp"></i> Order</button>
        </div>';
    }

    return '
    <div class="menu-card" data-search="' . $search . '">
        <div class="menu-card-top">
            <span class="item-badge">' . $badge . '</span>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <h4 class="card-title">' . $name . '</h4>
                    ' . $catTag . '
                </div>
                ' . ($urdu ? '<p class="urdu-text" dir="rtl">' . $urdu . '</p>' : '') . '
            </div>
        </div>
        ' . $descHtml . '
        ' . $footer . '
    </div>';
}

// Renders one category's full content: heading + its section(s), wrapped for search-filtering
function renderCategory($cat) {
    $icon = htmlspecialchars($cat['icon']);
    $title = htmlspecialchars($cat['title']);
    $html = '<h2 class="menu-cat-heading"><i class="' . $icon . '"></i> ' . $title . '</h2>';

    foreach ($cat['sections'] as $section) {
        $html .= '<div class="cat-block">';
        if (!empty($section['title'])) {
            $html .= '<h3 class="menu-subcat-heading">' . htmlspecialchars($section['title']) . '</h3>';
        }
        $html .= '<div class="menu-grid">';
        foreach (($section['items'] ?? []) as $item) {
            $html .= renderItemCard($item, $cat['title']);
        }
        $html .= '</div></div>';
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khattak Hotel Kacha Khuh | Best Restaurant in Khanewal</title>

    <!-- Primary SEO Meta Tags -->
    <meta name="description" content="Visit Khattak Hotel in Kacha Khuh, Khanewal for Pakistani, Afghan-inspired, BBQ, Karahi, Biryani, Chinese, Continental and more. View our menu or call now.">
    <meta name="keywords" content="Khattak Hotel, Khattak Hotel Kacha Khuh, restaurant in Kacha Khuh, best restaurant in Kacha Khuh, restaurant in Khanewal, Pakistani food in Kacha Khuh, BBQ in Kacha Khuh, Karahi in Kacha Khuh, family restaurant in Kacha Khuh, food in Kacha Khuh, Chinese food in Kacha Khuh, seafood in Kacha Khuh, steak in Kacha Khuh, pizza in Kacha Khuh, biryani in Kacha Khuh, Khattak Hotel Khanewal, 0328-5370000">
    <meta name="author" content="Khattak Hotel">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow">
    <meta name="geo.region" content="PK-PB">
    <meta name="geo.placename" content="Kacha Khuh, Khanewal, Punjab, Pakistan">
    <meta name="geo.position" content="29.8333;71.3333">
    <link rel="canonical" href="https://khattakhotel.com/">

    <!-- Open Graph / Facebook Meta Tags -->
    <meta property="og:type" content="restaurant">
    <meta property="og:site_name" content="Khattak Hotel">
    <meta property="og:title" content="Khattak Hotel Kacha Khuh | Best Restaurant in Khanewal">
    <meta property="og:description" content="Khattak Hotel in Kacha Khuh, Khanewal serves Pakistani, Afghan-inspired, BBQ, Karahi, Biryani, Chinese, Continental, seafood, steak, pizza and more. View the menu or call now.">
    <meta property="og:image" content="https://khattakhotel.com/icons/khattak-hotel-logo.png">
    <meta property="og:image:width" content="1254">
    <meta property="og:image:height" content="1254">
    <meta property="og:url" content="https://khattakhotel.com/">
    <meta property="og:locale" content="en_PK">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Khattak Hotel Kacha Khuh | Best Restaurant in Khanewal">
    <meta name="twitter:description" content="Khattak Hotel in Kacha Khuh, Khanewal serves Pakistani, Afghan-inspired, BBQ, Karahi, Biryani, Chinese, Continental, seafood, steak, pizza and more.">
    <meta name="twitter:image" content="https://khattakhotel.com/icons/khattak-hotel-logo.png">

    <!-- Contact Information -->
    <meta name="contact" content="0328-5370000">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#D60040">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Khattak Hotel">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="Khattak Hotel">
    <meta name="msapplication-TileColor" content="#D60040">
    <meta name="msapplication-tap-highlight" content="no">
    <meta name="format-detection" content="telephone=yes">

    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">

    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="152x152" href="icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="167x167" href="icons/icon-192x192.png">
    <link rel="apple-touch-icon-precomposed" href="icons/icon-192x192.png">

    <!-- Favicon -->
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="icons/icon-72x72.png">
    <link rel="icon" type="image/png" sizes="16x16" href="icons/icon-72x72.png">

    <!-- Google Fonts: Plus Jakarta Sans (English) + Noto Nastaliq Urdu (Urdu) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Noto+Nastaliq+Urdu:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-7D85607QXP"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-7D85607QXP');
    </script>

    <!-- Schema.org Structured Data - Restaurant -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Restaurant",
      "name": "Khattak Hotel",
      "alternateName": "Haji Tufail Khattak Hotel",
      "description": "Khattak Hotel is a restaurant in Kacha Khuh, Khanewal serving Pakistani, Afghan-inspired, BBQ, Karahi, Biryani, Dum Pukht, Chinese, Continental, seafood, steak, pizza, burgers and tandoor dishes for families, groups and travelers.",
      "url": "https://khattakhotel.com/",
      "logo": "https://khattakhotel.com/icons/khattak-hotel-logo.png",
      "image": [
        "https://khattakhotel.com/icons/khattak-hotel-logo.png",
        "https://khattakhotel.com/gallery/hotel-front.webp"
      ],
      "telephone": "0328-5370000",
      "priceRange": "PKR",
      "servesCuisine": [
        "Pakistani",
        "Afghan-inspired",
        "BBQ",
        "Karahi",
        "Chinese",
        "Continental",
        "Turkish",
        "Seafood",
        "Steak",
        "Pizza",
        "Burgers"
      ],
      "menu": "https://khattakhotel.com/#menu",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "GT Road Kacha Khuh By Pass, Near Adda Muhsin Wal, 135/15-L Phatak",
        "addressLocality": "Kacha Khuh",
        "addressRegion": "Punjab",
        "addressCountry": "PK"
      },
      "areaServed": "Kacha Khuh, Khanewal, Punjab, Pakistan",
      "potentialAction": {
        "@type": "OrderAction",
        "target": {
          "@type": "EntryPoint",
          "urlTemplate": "https://wa.me/923285370000?text=Hello%20Khattak%20Hotel%2C%20I%20would%20like%20to%20order",
          "actionPlatform": [
            "https://schema.org/DesktopWebPlatform",
            "https://schema.org/MobileWebPlatform"
          ]
        },
        "deliveryMethod": "http://purl.org/goodrelations/v1#DeliveryModeOwnFleet"
      }
    }
    </script>

    <!-- PWA Styles -->
    <link rel="stylesheet" href="pwa-styles.css">

    <!-- Tailwind CSS -->
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'khattak-burgundy': '#D60040',
                    'khattak-burgundy-deep': '#9F0030',
                    'khattak-sand': '#AEA38A',
                    'khattak-sand-light': '#F4EFE6',
                    'khattak-cream': '#FFF9F0',
                    'khattak-brown': '#241C18',
                    'khattak-brown-soft': '#66594D',
                    'khattak-gold': '#C7B78F',
                },
                fontFamily: {
                    'jakarta': ['Plus Jakarta Sans', 'sans-serif'],
                    'nastaliq': ['Noto Nastaliq Urdu', 'serif'],
                }
            }
        }
    }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #FFF9F0; }
        html { scroll-behavior: smooth; }

        .urdu-text, [dir="rtl"] { font-family: 'Noto Nastaliq Urdu', serif; line-height: 2.1; }
        .urdu-text { color: #66594D; font-size: 0.95rem; }

        /* Subtle Pashtoon-inspired geometric divider */
        .pattern-divider {
            height: 6px;
            background-image: repeating-linear-gradient(135deg, #D60040 0 6px, transparent 6px 12px, #AEA38A 12px 18px, transparent 18px 24px);
            opacity: 0.55;
            border-radius: 4px;
        }
        .pattern-frame {
            position: relative;
            border: 1px solid #C7B78F;
        }
        .pattern-frame::before {
            content: '';
            position: absolute; inset: 6px;
            border: 1px dashed #C7B78F;
            pointer-events: none;
            border-radius: inherit;
        }

        /* Menu cards */
        .menu-card {
            background: #ffffff;
            border: 1px solid rgba(199,183,143,0.45);
            border-radius: 0.9rem;
            padding: 0.9rem;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        }
        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(214,0,64,0.12);
            border-color: #D60040;
        }
        .menu-card-top { display: flex; align-items: flex-start; gap: 0.6rem; }
        .item-badge { flex-shrink: 0; }
        .item-no {
            display: inline-flex; align-items: center; justify-content: center;
            width: 1.75rem; height: 1.75rem; border-radius: 9999px;
            background: #F4EFE6; color: #9F0030; font-size: 0.65rem; font-weight: 800;
        }
        .item-no-icon { color: #AEA38A; font-size: 0.9rem; }
        .card-title { font-weight: 700; color: #241C18; font-size: 0.9rem; line-height: 1.3; }
        .cat-tag { font-size: 0.6rem; font-weight: 700; color: #9F0030; background: #F4EFE6; padding: 1px 7px; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.04em; }
        .item-desc { font-size: 0.75rem; color: #66594D; margin-top: 0.35rem; line-height: 1.9; }
        .card-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 0.65rem; padding-top: 0.6rem; border-top: 1px solid #F4EFE6; }
        .card-price { font-weight: 800; color: #D60040; font-size: 0.95rem; }
        .card-btn {
            font-size: 0.7rem; font-weight: 700; color: #fff; background: #D60040;
            padding: 0.35rem 0.7rem; border-radius: 0.5rem; display: inline-flex; align-items: center; gap: 0.3rem;
            transition: background 0.2s ease;
        }
        .card-btn:hover { background: #9F0030; }
        .size-chip-row { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.65rem; }
        .size-chip {
            font-size: 0.68rem; font-weight: 700; color: #9F0030; background: #F4EFE6;
            border: 1px solid #C7B78F; padding: 0.3rem 0.55rem; border-radius: 0.5rem;
            transition: background 0.2s ease, color 0.2s ease;
        }
        .size-chip:hover { background: #D60040; color: #fff; border-color: #D60040; }
        .size-chip b { font-weight: 800; }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 0.9rem;
        }
        @media (max-width: 640px) {
            .menu-grid { grid-template-columns: repeat(1, 1fr); gap: 0.7rem; }
        }

        .menu-cat-heading {
            font-size: 1.4rem; font-weight: 800; color: #241C18;
            display: flex; align-items: center; gap: 0.6rem;
            margin: 2.5rem 0 1rem; padding-bottom: 0.6rem; border-bottom: 2px solid #C7B78F;
        }
        .menu-cat-heading:first-child { margin-top: 0; }
        .menu-cat-heading i { color: #D60040; }
        .menu-subcat-heading {
            font-size: 1.05rem; font-weight: 700; color: #9F0030;
            margin: 1.25rem 0 0.75rem;
        }
        .menu-section.hidden { display: none; }
        .cat-block { margin-bottom: 0.5rem; }

        /* Tab pills */
        .tabs-container { overflow-x: auto; -webkit-overflow-scrolling: touch; cursor: grab; }
        .tabs-container.dragging { cursor: grabbing; user-select: none; }
        .tabs-container::-webkit-scrollbar { height: 3px; }
        .tabs-container::-webkit-scrollbar-thumb { background: #D60040; border-radius: 2px; }
        .tab-pill { transition: all 0.2s ease; padding: 6px 14px; font-size: 12px; border-radius: 9999px; border: 1.5px solid #C7B78F; white-space: nowrap; font-weight: 600; }
        .tab-pill.active { background-color: #D60040; color: #fff; border-color: #D60040; }
        .tab-pill:not(.active) { background-color: #fff; color: #66594D; }
        .tab-pill:not(.active):hover { background-color: #F4EFE6; }

        /* Popular category chips */
        .cat-chip {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 0.4rem; min-width: 92px; padding: 1rem 0.5rem;
            background: #fff; border: 1px solid rgba(199,183,143,0.5); border-radius: 1rem;
            transition: all 0.2s ease; flex-shrink: 0;
        }
        .cat-chip:hover { border-color: #D60040; transform: translateY(-2px); box-shadow: 0 8px 18px rgba(214,0,64,0.1); }
        .cat-chip i { color: #D60040; font-size: 1.35rem; }
        .cat-chip span { font-size: 0.7rem; font-weight: 700; color: #241C18; text-align: center; }

        /* Section divider */
        .section-divider { height: 2px; background: linear-gradient(90deg, transparent, #D60040, transparent); }

        /* Nav active indicator */
        .nav-btn.active { position: relative; }

        body { padding-bottom: 74px; }

        @media (max-width: 640px) { .tab-pill { padding: 5px 11px; font-size: 11px; } }
    </style>
</head>
<body class="bg-[#FFF9F0] text-[#241C18] font-jakarta">

    <!-- ═══════════════════════════════════════ -->
    <!-- 1. HEADER / NAVIGATION                 -->
    <!-- ═══════════════════════════════════════ -->
    <header class="sticky top-0 z-50 bg-[#FFF9F0]/95 backdrop-blur-md border-b border-[#C7B78F]/50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-2.5 flex items-center justify-between gap-3">

            <!-- Logo + Brand -->
            <a href="#" class="flex items-center gap-2.5 flex-shrink-0" aria-label="Khattak Hotel home">
                <img src="icons/khattak-hotel-logo.png" alt="Khattak Hotel Logo" class="h-14 w-14 object-contain">
                <div class="hidden sm:block leading-none">
                    <div class="text-lg md:text-xl font-black leading-none text-[#241C18]">Khattak <span class="text-[#D60040]">Hotel</span></div>
                    <p class="text-[10px] text-[#66594D] font-semibold tracking-wide mt-0.5">Traditional Hospitality. Exceptional Taste.</p>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden lg:flex items-center gap-7 text-sm font-semibold text-[#241C18]">
                <a href="#" class="nav-btn active hover:text-[#D60040] transition">Home</a>
                <a href="#menu" class="nav-btn hover:text-[#D60040] transition">Menu</a>
                <a href="#specials" class="nav-btn hover:text-[#D60040] transition">Specials</a>
                <a href="#gallery" class="nav-btn hover:text-[#D60040] transition">Gallery</a>
                <a href="#about-us" class="nav-btn hover:text-[#D60040] transition">About</a>
                <a href="#location" class="nav-btn hover:text-[#D60040] transition">Location</a>
            </nav>

            <!-- Actions -->
            <div class="flex items-center gap-2">
                <a href="tel:03285370000" class="inline-flex items-center gap-1.5 text-[#241C18] text-sm sm:text-base md:text-lg font-bold">
                    <i class="fas fa-phone-alt text-[#D60040] text-xs sm:text-sm"></i> 0328-5370000
                </a>
                <a href="tel:03285370000" class="inline-flex items-center gap-2 bg-[#D60040] hover:bg-[#9F0030] text-white font-bold text-xs md:text-sm px-3 md:px-5 py-2 md:py-2.5 rounded-full transition">
                    <i class="fas fa-phone-alt"></i> <span class="hidden sm:inline">Call Now</span>
                </a>
            </div>
        </div>
    </header>

    <!-- ═══════════════════════════════════════ -->
    <!-- 2. HERO SECTION                        -->
    <!-- ═══════════════════════════════════════ -->
    <section class="relative overflow-hidden bg-gradient-to-b from-[#F4EFE6] to-[#FFF9F0] pt-5 md:pt-8 pb-12 md:pb-20 px-4">
        <div class="max-w-6xl mx-auto relative z-10">
            <div class="flex flex-col lg:flex-row items-center gap-10 lg:gap-14">

                <!-- Left: Text Content -->
                <div class="flex-1 text-center lg:text-left">
                    <p class="text-[#9F0030] font-bold text-xs md:text-sm uppercase tracking-[0.2em] mb-4">Kacha Khuh · GT Road</p>
                    <h1 class="font-black leading-tight text-2xl md:text-4xl text-[#241C18] mb-4">
                        Khattak <span class="text-[#D60040]">Hotel</span> – Best Restaurant in Kacha Khuh, Khanewal
                    </h1>

                    <!-- Google Rating -->
                    <div class="inline-flex items-center gap-2 bg-white pattern-frame rounded-full pl-3 pr-4 py-1.5 mb-5">
                        <span class="flex items-center gap-0.5 text-[#D60040] text-xs">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-stroke"></i>
                        </span>
                        <span class="text-[#241C18] font-bold text-sm">4.2</span>
                        <span class="text-[#66594D] text-xs">· 496 Google reviews</span>
                    </div>

                    <div class="pattern-divider w-32 mx-auto lg:mx-0 mb-5"></div>
                    <p class="text-lg md:text-2xl font-bold text-[#241C18] mb-3">Traditional Hospitality. Exceptional Taste.</p>
                    <p class="text-[#66594D] text-sm md:text-base max-w-xl mx-auto lg:mx-0 mb-8 leading-relaxed">
                        Khattak Hotel Kacha Khuh serves Pakistani, Afghan-inspired, BBQ, Karahi, Biryani, dum pukht, seafood, steaks, Chinese, Continental, pizza, burgers and tandoor favorites for families, groups and travelers on GT Road.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start items-center">
                        <a href="#menu" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-[#D60040] hover:bg-[#9F0030] text-white font-bold text-base px-8 py-3.5 rounded-xl transition-all duration-200 shadow-lg shadow-[#D60040]/25 hover:scale-105">
                            <i class="fas fa-utensils"></i> Explore Menu
                        </a>
                        <a href="tel:03285370000" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 border-2 border-[#241C18] text-[#241C18] hover:bg-[#241C18] hover:text-white font-bold text-base px-8 py-3.5 rounded-xl transition-all duration-200">
                            <i class="fas fa-phone-alt"></i> Call Now
                        </a>
                        <a href="https://share.google/Apt9D90llaUUqtncT" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 text-[#66594D] hover:text-[#D60040] font-semibold text-sm px-4 py-3.5 transition-all duration-200">
                            <i class="fas fa-location-arrow"></i> Get Directions
                        </a>
                    </div>
                </div>

                <!-- Right: Hotel Front View -->
                <div class="flex-shrink-0 w-full max-w-sm lg:max-w-md">
                    <div class="pattern-frame rounded-2xl overflow-hidden shadow-2xl shadow-[#241C18]/15 bg-white">
                        <img src="gallery/hotel-front.webp" alt="Khattak Hotel — front view of the building" loading="eager"
                             class="w-full h-auto object-cover">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 3. QUICK ACTION CARDS                  -->
    <!-- ═══════════════════════════════════════ -->
    <section class="px-4 -mt-6 md:-mt-8 relative z-10">
        <div class="max-w-4xl mx-auto grid grid-cols-3 gap-2 md:gap-4">
            <a href="tel:03285370000" class="bg-white pattern-frame rounded-xl p-3 md:p-5 flex flex-col items-center text-center gap-1.5 shadow-sm hover:shadow-md transition">
                <i class="fas fa-phone-alt text-[#D60040] text-lg md:text-2xl"></i>
                <span class="text-[11px] md:text-sm font-bold text-[#241C18]">Call Us</span>
                <span class="hidden md:inline text-[11px] text-[#66594D]">0328-5370000</span>
            </a>
            <a href="https://share.google/Apt9D90llaUUqtncT" target="_blank" rel="noopener noreferrer" class="bg-white pattern-frame rounded-xl p-3 md:p-5 flex flex-col items-center text-center gap-1.5 shadow-sm hover:shadow-md transition">
                <i class="fas fa-location-dot text-[#D60040] text-lg md:text-2xl"></i>
                <span class="text-[11px] md:text-sm font-bold text-[#241C18]">Directions</span>
                <span class="hidden md:inline text-[11px] text-[#66594D]">Find Us</span>
            </a>
            <a href="#menu" class="bg-white pattern-frame rounded-xl p-3 md:p-5 flex flex-col items-center text-center gap-1.5 shadow-sm hover:shadow-md transition">
                <i class="fas fa-utensils text-[#D60040] text-lg md:text-2xl"></i>
                <span class="text-[11px] md:text-sm font-bold text-[#241C18]">View Menu</span>
                <span class="hidden md:inline text-[11px] text-[#66594D]">Explore Food</span>
            </a>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 4. ABOUT KHATTAK HOTEL                 -->
    <!-- ═══════════════════════════════════════ -->
    <section id="about-us" class="py-14 md:py-20 px-4">
        <div class="max-w-3xl mx-auto text-center">
            <p class="text-[#D60040] font-bold text-xs uppercase tracking-[0.2em] mb-2">Our Story</p>
            <h2 class="text-2xl md:text-3xl font-black text-[#241C18] mb-4">Welcome to Khattak Hotel in Kacha Khuh</h2>
            <div class="pattern-divider w-20 mx-auto mb-6"></div>
            <p class="text-[#66594D] text-sm md:text-base leading-relaxed mb-4">
                Rooted in Pashtoon hospitality, Khattak Hotel brings traditional Pakistani and Afghan-inspired flavors to GT Road Kacha Khuh By Pass. From hearty Karahi and BBQ to Chinese, Continental, Seafood, Steak, Pizza, Burgers and fresh-from-the-tandoor specialties, every dish is prepared with care for families, groups and travelers visiting Kacha Khuh.
            </p>
            <p class="text-[#66594D] text-sm md:text-base leading-relaxed">
                Conveniently located and easy to find, Khattak Hotel offers a warm, family-friendly dining experience for guests looking for authentic food in Kacha Khuh and a comfortable stop on the road through Khanewal.
            </p>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 5. POPULAR CATEGORIES                  -->
    <!-- ═══════════════════════════════════════ -->
    <section class="py-10 px-4 bg-[#F4EFE6]/50">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-6">
                <h2 class="text-2xl md:text-3xl font-black text-[#241C18]">Popular <span class="text-[#D60040]">Categories</span></h2>
            </div>
            <div class="tabs-container">
                <div class="flex gap-3 pb-2 px-1">
                    <?php foreach ($popularCategories as $cat): ?>
                    <button onclick="jumpToCategory('<?php echo htmlspecialchars($cat['id'], ENT_QUOTES); ?>')" class="cat-chip">
                        <i class="<?php echo htmlspecialchars($cat['icon']); ?>"></i>
                        <span><?php echo htmlspecialchars($cat['title']); ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 6. FEATURED MENU / CHEF'S PICKS        -->
    <!-- ═══════════════════════════════════════ -->
    <section class="py-14 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-8">
                <p class="text-[#D60040] font-bold text-xs uppercase tracking-[0.2em] mb-2">Chef's Picks</p>
                <h2 class="text-2xl md:text-3xl font-black text-[#241C18]">Popular at <span class="text-[#D60040]">Khattak Hotel</span></h2>
            </div>
            <div class="menu-grid max-w-5xl mx-auto">
                <?php foreach ($chefsPicks as $pick): ?>
                    <?php echo renderItemCard($pick['item'], $pick['catTitle'], true); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 7. EXPLORE OUR MENU                    -->
    <!-- ═══════════════════════════════════════ -->
    <section id="menu" class="bg-[#241C18] py-14 px-4">
        <div class="max-w-7xl mx-auto">

            <div class="text-center mb-6">
                <p class="text-[#C7B78F] font-bold text-xs uppercase tracking-[0.2em] mb-2">Full Menu</p>
                <h2 class="text-3xl md:text-4xl font-black text-white">Khattak Hotel Menu – Pakistani, BBQ, Karahi &amp; More</h2>
            </div>

            <!-- Search -->
            <div class="max-w-xl mx-auto mb-6">
                <div class="relative">
                    <i class="fas fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-[#66594D]"></i>
                    <input id="menuSearch" type="text" placeholder="Search menu... (English or Urdu)"
                           oninput="filterMenu(this.value)"
                           class="w-full bg-white rounded-full py-3 pl-11 pr-4 text-sm text-[#241C18] placeholder-[#66594D]/60 focus:outline-none focus:ring-2 focus:ring-[#D60040]">
                </div>
            </div>

            <!-- Category Tabs -->
            <div class="tabs-container mb-2">
                <div class="flex flex-nowrap gap-2 pb-2 px-1 justify-start">
                    <button data-cat="all" onclick="showCategory('all', this)" class="tab-pill">
                        <i class="fas fa-border-all mr-1"></i> All
                    </button>
                    <button data-cat="popular" onclick="showCategory('popular', this)" class="tab-pill">
                        <i class="fas fa-star mr-1"></i> Popular
                    </button>
                    <?php foreach ($categories as $cat): ?>
                    <button data-cat="<?php echo htmlspecialchars($cat['id'], ENT_QUOTES); ?>" onclick="showCategory('<?php echo htmlspecialchars($cat['id'], ENT_QUOTES); ?>', this)" class="tab-pill<?php echo $cat['id'] === 'karahi' ? ' active' : ''; ?>">
                        <i class="<?php echo htmlspecialchars($cat['icon']); ?> mr-1"></i> <?php echo htmlspecialchars($cat['title']); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($serviceCharge): ?>
            <p class="text-center text-[#C7B78F] text-xs mb-8"><i class="fas fa-circle-info mr-1"></i> <?php echo htmlspecialchars($serviceCharge); ?></p>
            <?php endif; ?>

            <!-- Menu Content -->
            <main class="bg-[#FFF9F0] rounded-2xl p-4 md:p-8">
                <section id="cat-popular" class="menu-section hidden" data-cat="popular">
                    <h2 class="menu-cat-heading"><i class="fas fa-star"></i> Popular / Chef's Picks</h2>
                    <div class="cat-block">
                        <div class="menu-grid">
                            <?php foreach ($chefsPicks as $pick): ?>
                                <?php echo renderItemCard($pick['item'], $pick['catTitle'], true); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <?php foreach ($categories as $cat): ?>
                <section id="cat-<?php echo htmlspecialchars($cat['id'], ENT_QUOTES); ?>" class="menu-section<?php echo $cat['id'] !== 'karahi' ? ' hidden' : ''; ?>" data-cat="<?php echo htmlspecialchars($cat['id'], ENT_QUOTES); ?>">
                    <?php echo renderCategory($cat); ?>
                </section>
                <?php endforeach; ?>
                <p id="noResultsMsg" class="hidden text-center text-[#66594D] py-10"><i class="fas fa-magnifying-glass mb-2 block text-2xl"></i> No items match your search.</p>
            </main>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 8. MENU IMAGE GALLERY (Official Menu)  -->
    <!-- ═══════════════════════════════════════ -->
    <section class="py-14 px-4">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-8">
                <p class="text-[#D60040] font-bold text-xs uppercase tracking-[0.2em] mb-2">As Printed</p>
                <h2 class="text-2xl md:text-3xl font-black text-[#241C18]">Full Menu Card</h2>
                <p class="text-[#66594D] text-sm mt-2">Browse the official Khattak Hotel menu, page by page.</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
                <?php foreach ($menuGallery as $i => $page): ?>
                <button onclick="openMenuGalleryModal(<?php echo $i; ?>)" class="pattern-frame rounded-xl overflow-hidden bg-white group">
                    <img src="<?php echo htmlspecialchars($page['file']); ?>" alt="<?php echo htmlspecialchars($page['label']); ?>" loading="lazy"
                         class="w-full h-auto object-contain group-hover:scale-[1.03] transition-transform duration-300">
                </button>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-8">
                <button onclick="openMenuGalleryModal(0)" class="inline-flex items-center gap-2 bg-[#D60040] hover:bg-[#9F0030] text-white font-bold px-8 py-3 rounded-xl transition">
                    <i class="fas fa-book-open"></i> View Full Menu
                </button>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 9. SPECIAL OFFERS                      -->
    <!-- ═══════════════════════════════════════ -->
    <section id="specials" class="bg-[#F4EFE6]/60 py-14 px-4">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-8">
                <p class="text-[#D60040] font-bold text-xs uppercase tracking-[0.2em] mb-2">Specials</p>
                <h2 class="text-2xl md:text-3xl font-black text-[#241C18]">Feasts &amp; Family Specials</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-4xl mx-auto">
                <div class="bg-white pattern-frame rounded-2xl p-6">
                    <i class="fas fa-people-group text-[#D60040] text-2xl mb-3"></i>
                    <h3 class="font-bold text-lg text-[#241C18] mb-1">Turkish Special Platter — For 8 Persons</h3>
                    <p class="text-[#66594D] text-sm mb-3">Chicken &amp; beef kababs, fish, naan &amp; more — a full Turkish feast.</p>
                    <div class="flex items-center justify-between">
                        <span class="text-[#D60040] font-black text-xl">Rs. 9350</span>
                        <button onclick="orderWhatsapp('Turkish Special Platter For 8 Persons - Rs. 9350')" class="card-btn"><i class="fab fa-whatsapp"></i> Order</button>
                    </div>
                </div>
                <div class="bg-white pattern-frame rounded-2xl p-6">
                    <i class="fas fa-people-group text-[#D60040] text-2xl mb-3"></i>
                    <h3 class="font-bold text-lg text-[#241C18] mb-1">Turkish Special Platter — For 6 Persons</h3>
                    <p class="text-[#66594D] text-sm mb-3">Malai boti, lamb chops, mutton raan, naan &amp; more.</p>
                    <div class="flex items-center justify-between">
                        <span class="text-[#D60040] font-black text-xl">Rs. 4975</span>
                        <button onclick="orderWhatsapp('Turkish Special Platter For 6 Persons - Rs. 4975')" class="card-btn"><i class="fab fa-whatsapp"></i> Order</button>
                    </div>
                </div>
                <div class="bg-white pattern-frame rounded-2xl p-6">
                    <i class="fas fa-drumstick-bite text-[#D60040] text-2xl mb-3"></i>
                    <h3 class="font-bold text-lg text-[#241C18] mb-1">Continental Mains</h3>
                    <p class="text-[#66594D] text-sm mb-3">Chicken Cordon Bleu, Mozzarella Stuffed Chicken &amp; more — served with rice &amp; vegetables.</p>
                    <div class="flex items-center justify-between">
                        <span class="text-[#D60040] font-black text-xl">Rs. 1695</span>
                        <button onclick="jumpToCategory('continental')" class="card-btn"><i class="fas fa-arrow-right"></i> View</button>
                    </div>
                </div>
                <div class="bg-white pattern-frame rounded-2xl p-6">
                    <i class="fas fa-drumstick-bite text-[#D60040] text-2xl mb-3"></i>
                    <h3 class="font-bold text-lg text-[#241C18] mb-1">Desi Special (Per Kg)</h3>
                    <p class="text-[#66594D] text-sm mb-3">Dum Pukht Saleem Bakra &amp; Mutton Khinda Sajji — traditional feast dishes.</p>
                    <div class="flex items-center justify-between">
                        <span class="text-[#D60040] font-black text-xl">From Rs. 4000/kg</span>
                        <button onclick="jumpToCategory('desi-special')" class="card-btn"><i class="fas fa-arrow-right"></i> View</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 10. WHY KHATTAK HOTEL                  -->
    <!-- ═══════════════════════════════════════ -->
    <section class="py-14 px-4">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-10">
                <h2 class="text-2xl md:text-3xl font-black text-[#241C18]">Why <span class="text-[#D60040]">Khattak Hotel</span></h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-[#F4EFE6] flex items-center justify-center"><i class="fas fa-mortar-pestle text-[#D60040] text-xl"></i></div>
                    <h3 class="font-bold text-sm text-[#241C18] mb-1">Traditional Flavors</h3>
                    <p class="text-xs text-[#66594D]">Authentic Pakistani &amp; Afghan-inspired taste</p>
                </div>
                <div class="text-center">
                    <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-[#F4EFE6] flex items-center justify-center"><i class="fas fa-people-roof text-[#D60040] text-xl"></i></div>
                    <h3 class="font-bold text-sm text-[#241C18] mb-1">Family Friendly</h3>
                    <p class="text-xs text-[#66594D]">Comfortable dining for families and groups</p>
                </div>
                <div class="text-center">
                    <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-[#F4EFE6] flex items-center justify-center"><i class="fas fa-kitchen-set text-[#D60040] text-xl"></i></div>
                    <h3 class="font-bold text-sm text-[#241C18] mb-1">Freshly Prepared</h3>
                    <p class="text-xs text-[#66594D]">Quality food prepared with care</p>
                </div>
                <div class="text-center">
                    <div class="w-14 h-14 mx-auto mb-3 rounded-full bg-[#F4EFE6] flex items-center justify-center"><i class="fas fa-road text-[#D60040] text-xl"></i></div>
                    <h3 class="font-bold text-sm text-[#241C18] mb-1">Easy Access</h3>
                    <p class="text-xs text-[#66594D]">Convenient location on GT Road</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 11. GALLERY                            -->
    <!-- ═══════════════════════════════════════ -->
    <?php
    $galleryPhotos = [
        ['src' => 'gallery/hotel-front.webp',     'label' => 'Front View',         'cat' => 'exterior'],
        ['src' => 'gallery/hotel-full-view.webp', 'label' => 'Full View',          'cat' => 'exterior'],
        ['src' => 'gallery/hotel-side.webp',      'label' => 'Entrance',           'cat' => 'exterior'],
        ['src' => 'gallery/dining-hall.webp',     'label' => 'Dining Hall',        'cat' => 'dining'],
        ['src' => 'gallery/gents-hall.webp',      'label' => 'Gents Hall',         'cat' => 'dining'],
        ['src' => 'gallery/family-hall.webp',     'label' => 'Family Hall',        'cat' => 'dining'],
        ['src' => 'gallery/lawn-1.webp',          'label' => 'Lawn & Outdoor Seating', 'cat' => 'lawn'],
        ['src' => 'gallery/lawn-2.webp',          'label' => 'Lawn & Outdoor Seating', 'cat' => 'lawn'],
        ['src' => 'gallery/lawn-3.webp',          'label' => 'Lawn & Outdoor Seating', 'cat' => 'lawn'],
        ['src' => 'gallery/lawn-4.webp',          'label' => 'Lawn & Outdoor Seating', 'cat' => 'lawn'],
        ['src' => 'gallery/masjid.webp',          'label' => 'Masjid',             'cat' => 'amenities'],
        ['src' => 'gallery/tuck-shop.webp',       'label' => 'Tuck Shop',          'cat' => 'amenities'],
        ['src' => 'gallery/bbq-counter.webp',     'label' => 'BBQ Counter',        'cat' => 'amenities'],
    ];
    $galleryCats = [
        'all' => 'All', 'exterior' => 'Exterior', 'dining' => 'Dining Halls',
        'lawn' => 'Lawn & Outdoor', 'amenities' => 'Amenities',
    ];
    ?>
    <section id="gallery" class="py-14 px-4 bg-[#F4EFE6]/50">
        <div class="max-w-6xl mx-auto">
            <div class="text-center mb-8">
                <p class="text-[#D60040] font-bold text-xs uppercase tracking-[0.2em] mb-2">Take a Look Inside</p>
                <h2 class="text-2xl md:text-3xl font-black text-[#241C18]">Experience Khattak Hotel</h2>
            </div>

            <div class="tabs-container mb-6">
                <div class="flex flex-nowrap gap-2 pb-2 px-1 justify-start">
                    <?php foreach ($galleryCats as $catId => $catLabel): ?>
                    <button data-gcat="<?php echo htmlspecialchars($catId, ENT_QUOTES); ?>" onclick="filterGallery('<?php echo htmlspecialchars($catId, ENT_QUOTES); ?>', this)" class="tab-pill<?php echo $catId === 'all' ? ' active' : ''; ?>">
                        <?php echo htmlspecialchars($catLabel); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-4">
                <?php foreach ($galleryPhotos as $i => $photo): ?>
                <button onclick="openGalleryLightbox(<?php echo $i; ?>)" data-gcat="<?php echo htmlspecialchars($photo['cat'], ENT_QUOTES); ?>" class="gallery-thumb pattern-frame rounded-xl overflow-hidden bg-white group">
                    <img src="<?php echo htmlspecialchars($photo['src']); ?>" alt="Khattak Hotel — <?php echo htmlspecialchars($photo['label']); ?>" loading="lazy"
                         class="w-full h-40 md:h-52 object-cover group-hover:scale-105 transition-transform duration-300">
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Gallery Lightbox -->
    <div id="galleryLightbox" class="fixed inset-0 bg-black/95 z-[65]" style="display:none;">
        <div class="relative w-full h-full flex flex-col">
            <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
                <h3 class="text-white text-lg font-bold" id="galleryLightboxTitle"></h3>
                <button onclick="closeGalleryLightbox()" class="text-white text-xl bg-[#D60040] w-10 h-10 rounded-full hover:bg-[#9F0030] transition flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-hidden flex items-center justify-center relative px-2">
                <button onclick="galleryLightboxPrev()" class="absolute left-2 md:left-6 text-white bg-white/10 hover:bg-[#D60040] w-10 h-10 rounded-full flex items-center justify-center transition z-10">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <img id="galleryLightboxImg" src="" alt="" class="max-h-[80vh] max-w-full object-contain rounded-lg">
                <button onclick="galleryLightboxNext()" class="absolute right-2 md:right-6 text-white bg-white/10 hover:bg-[#D60040] w-10 h-10 rounded-full flex items-center justify-center transition z-10">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="text-center text-white/60 text-xs py-3" id="galleryLightboxCounter"></div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- 12. LOCATION / MAP                     -->
    <!-- ═══════════════════════════════════════ -->
    <section id="location" class="py-14 px-4">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-8">
                <p class="text-[#D60040] font-bold text-xs uppercase tracking-[0.2em] mb-2">Find Us</p>
                <h2 class="text-2xl md:text-3xl font-black text-[#241C18]">Visit Khattak Hotel</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <div>
                    <p class="text-[#66594D] text-sm leading-relaxed mb-5">
                        <i class="fas fa-map-marker-alt text-[#D60040] mr-2"></i>
                        GT Road Kacha Khuh By Pass, Near Adda Muhsin Wal, 135/15-L Phatak
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="https://share.google/Apt9D90llaUUqtncT" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center justify-center gap-2 bg-[#D60040] hover:bg-[#9F0030] text-white font-bold px-6 py-3 rounded-xl transition">
                            <i class="fas fa-diamond-turn-right"></i> Get Directions
                        </a>
                        <a href="tel:03285370000"
                           class="inline-flex items-center justify-center gap-2 border-2 border-[#241C18] text-[#241C18] hover:bg-[#241C18] hover:text-white font-bold px-6 py-3 rounded-xl transition">
                            <i class="fas fa-phone-alt"></i> Call Now
                        </a>
                    </div>
                </div>
                <div class="rounded-2xl overflow-hidden pattern-frame h-72 md:h-80">
                    <iframe
                        src="https://www.google.com/maps?q=<?php echo urlencode('Khattak Hotel, GT Road Kacha Khuh By Pass, Near Adda Muhsin Wal, 135/15-L Phatak, Pakistan'); ?>&output=embed"
                        class="w-full h-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Khattak Hotel location map"></iframe>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 13. CONTACT CTA                        -->
    <!-- ═══════════════════════════════════════ -->
    <section class="py-14 px-4 bg-[#241C18]">
        <div class="max-w-3xl mx-auto text-center">
            <h2 class="text-2xl md:text-3xl font-black text-white mb-3">Ready for a Great Meal?</h2>
            <p class="text-[#C7B78F] text-sm md:text-base mb-8">Visit Khattak Hotel or call us for your next order.</p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="tel:03285370000" class="inline-flex items-center justify-center gap-2 bg-[#D60040] hover:bg-[#9F0030] text-white font-bold px-7 py-3.5 rounded-xl transition">
                    <i class="fas fa-phone-alt"></i> Call Now
                </a>
                <a href="https://wa.me/923285370000?text=Hello%20Khattak%20Hotel%2C%20I%20would%20like%20to%20order" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center justify-center gap-2 bg-white hover:bg-[#F4EFE6] text-[#241C18] font-bold px-7 py-3.5 rounded-xl transition">
                    <i class="fab fa-whatsapp text-green-600"></i> WhatsApp
                </a>
                <a href="https://share.google/Apt9D90llaUUqtncT" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center justify-center gap-2 border-2 border-[#C7B78F] text-white hover:bg-white/10 font-bold px-7 py-3.5 rounded-xl transition">
                    <i class="fas fa-diamond-turn-right"></i> Get Directions
                </a>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 14. FAQ                                -->
    <!-- ═══════════════════════════════════════ -->
    <section class="py-14 px-4 bg-[#F4EFE6]/40">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-8">
                <p class="text-[#D60040] font-bold text-xs uppercase tracking-[0.2em] mb-2">FAQ</p>
                <h2 class="text-2xl md:text-3xl font-black text-[#241C18]">Frequently Asked Questions</h2>
            </div>
            <div class="space-y-4">
                <div class="bg-white pattern-frame rounded-2xl p-5">
                    <h3 class="font-bold text-[#241C18] text-lg mb-2">Where is Khattak Hotel located?</h3>
                    <p class="text-[#66594D] text-sm leading-relaxed">Khattak Hotel is located on GT Road Kacha Khuh Bypass, near Adda Muhsin Wal, 135/15-L Phatak.</p>
                </div>
                <div class="bg-white pattern-frame rounded-2xl p-5">
                    <h3 class="font-bold text-[#241C18] text-lg mb-2">What food does Khattak Hotel serve?</h3>
                    <p class="text-[#66594D] text-sm leading-relaxed">Khattak Hotel serves Pakistani, Afghan-inspired, BBQ, Karahi, Chinese, Continental, Turkish, seafood, steak, pizza, burgers, tandoor items and more.</p>
                </div>
                <div class="bg-white pattern-frame rounded-2xl p-5">
                    <h3 class="font-bold text-[#241C18] text-lg mb-2">Does Khattak Hotel serve Karahi?</h3>
                    <p class="text-[#66594D] text-sm leading-relaxed">Yes. The menu includes Chicken, Mutton, Beef, Peshawari, Shinwari, Afghani and White Karahi varieties.</p>
                </div>
                <div class="bg-white pattern-frame rounded-2xl p-5">
                    <h3 class="font-bold text-[#241C18] text-lg mb-2">Does Khattak Hotel offer family dining?</h3>
                    <p class="text-[#66594D] text-sm leading-relaxed">Yes, the restaurant provides a family-friendly dining environment and group dining options.</p>
                </div>
                <div class="bg-white pattern-frame rounded-2xl p-5">
                    <h3 class="font-bold text-[#241C18] text-lg mb-2">How can I contact Khattak Hotel?</h3>
                    <p class="text-[#66594D] text-sm leading-relaxed">Customers can call 0328-5370000 or contact the hotel through WhatsApp.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════ -->
    <!-- 15. FOOTER                             -->
    <!-- ═══════════════════════════════════════ -->
    <footer class="bg-[#241C18] border-t-2 border-[#D60040] pt-12 pb-4 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">

                <!-- Brand Column -->
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <img src="icons/khattak-hotel-logo.png" alt="Khattak Hotel" class="h-16 w-16 object-contain">
                        <div class="leading-none">
                            <div class="font-black text-white text-lg">Khattak Hotel</div>
                            <div class="text-[#C7B78F] text-[10px] font-semibold uppercase tracking-widest mt-0.5">Traditional Hospitality. Exceptional Taste.</div>
                        </div>
                    </div>
                    <p class="text-white/50 text-sm leading-relaxed">
                        GT Road Kacha Khuh By Pass,<br>
                        Near Adda Muhsin Wal, 135/15-L Phatak
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-white font-bold text-base mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="text-white/50 hover:text-[#D60040] transition">Home</a></li>
                        <li><a href="#menu" class="text-white/50 hover:text-[#D60040] transition">Menu</a></li>
                        <li><a href="#specials" class="text-white/50 hover:text-[#D60040] transition">Offers</a></li>
                        <li><a href="#gallery" class="text-white/50 hover:text-[#D60040] transition">Gallery</a></li>
                        <li><a href="#about-us" class="text-white/50 hover:text-[#D60040] transition">About</a></li>
                        <li><a href="#location" class="text-white/50 hover:text-[#D60040] transition">Location</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-white font-bold text-base mb-4">Contact</h4>
                    <div class="space-y-3 text-sm">
                        <a href="tel:03285370000" class="text-white/50 hover:text-[#D60040] transition flex items-center gap-2">
                            <i class="fas fa-phone text-[#D60040]"></i> 0328-5370000
                        </a>
                        <a href="https://wa.me/923285370000?text=Hello%20Khattak%20Hotel%2C%20I%20would%20like%20to%20order" target="_blank"
                           class="text-white/50 hover:text-[#D60040] transition flex items-center gap-2">
                            <i class="fab fa-whatsapp text-green-500"></i> Chat on WhatsApp
                        </a>
                        <p class="text-white/50 flex items-start gap-2">
                            <i class="fas fa-map-marker-alt text-[#D60040] mt-0.5"></i>
                            <span>GT Road Kacha Khuh By Pass, Near Adda Muhsin Wal, 135/15-L Phatak</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-white/10 pt-6 text-center">
                <p class="text-white/40 text-sm">
                    &copy; <?php echo date('Y'); ?> <span class="text-[#D60040] font-bold">Khattak Hotel</span>. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    <!-- ═══════════════════════════════════════ -->
    <!-- BOTTOM NAVIGATION (Mobile)             -->
    <!-- ═══════════════════════════════════════ -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-[#C7B78F]/50 z-50" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex justify-around items-center py-2 max-w-lg mx-auto">
            <a href="#" class="flex flex-col items-center text-[#D60040] transition px-3 py-1">
                <i class="fas fa-home text-xl"></i>
                <span class="text-[10px] mt-1 font-semibold">Home</span>
            </a>
            <a href="#menu" class="flex flex-col items-center text-[#66594D] hover:text-[#D60040] transition px-3 py-1">
                <i class="fas fa-utensils text-xl"></i>
                <span class="text-[10px] mt-1 font-semibold">Menu</span>
            </a>
            <a href="#specials" class="flex flex-col items-center text-[#66594D] hover:text-[#D60040] transition px-3 py-1">
                <i class="fas fa-percent text-xl"></i>
                <span class="text-[10px] mt-1 font-semibold">Offers</span>
            </a>
            <a href="#location" class="flex flex-col items-center text-[#66594D] hover:text-[#D60040] transition px-3 py-1">
                <i class="fas fa-map-marker-alt text-xl"></i>
                <span class="text-[10px] mt-1 font-semibold">Location</span>
            </a>
            <a href="tel:03285370000" class="flex flex-col items-center text-[#66594D] hover:text-[#D60040] transition px-3 py-1">
                <i class="fas fa-phone text-xl"></i>
                <span class="text-[10px] mt-1 font-semibold">Call</span>
            </a>
        </div>
    </nav>

    <!-- ═══════════════════════════════════════ -->
    <!-- FLOATING LOCATION BUTTON (Mobile)      -->
    <!-- ═══════════════════════════════════════ -->
    <div id="float-location-wrap" class="md:hidden fixed z-[55]" style="left:0; top:45%; transform:translateY(-50%); user-select:none; touch-action:none;">
        <button onclick="hideFloatLocation()"
                style="position:absolute; top:-8px; right:-8px; width:18px; height:18px; border-radius:50%; background:#fff; border:1.5px solid #D60040; color:#9F0030; font-size:8px; display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:1;">
            <i class="fas fa-times"></i>
        </button>
        <a id="float-location-btn" href="https://share.google/Apt9D90llaUUqtncT" target="_blank" rel="noopener noreferrer"
           style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:5px; background:linear-gradient(180deg,#D60040,#9F0030); color:#fff; font-weight:700; font-size:9px; padding:12px 7px; border-radius:0 10px 10px 0; box-shadow:3px 0 16px rgba(214,0,64,0.4); text-decoration:none; letter-spacing:1px;">
            <i class="fas fa-map-marker-alt" style="font-size:14px;"></i>
            <span style="writing-mode:vertical-rl; transform:rotate(180deg);">Find Us on Map</span>
        </a>
    </div>

    <script>
        (function(){
            const wrap = document.getElementById('float-location-wrap');
            const btn  = document.getElementById('float-location-btn');
            let dragging = false, startY = 0, startTop = 0, moved = false;

            function getTop() { return wrap.getBoundingClientRect().top; }
            function onStart(clientY) {
                dragging = true; moved = false; startY = clientY; startTop = getTop();
                wrap.style.transform = 'none'; wrap.style.top = startTop + 'px';
            }
            function onMove(clientY) {
                if (!dragging) return;
                const diff = clientY - startY;
                if (Math.abs(diff) > 4) moved = true;
                let newTop = startTop + diff;
                const maxTop = window.innerHeight - wrap.offsetHeight - 8;
                newTop = Math.max(8, Math.min(newTop, maxTop));
                wrap.style.top = newTop + 'px';
            }
            function onEnd() { dragging = false; if (moved) btn.addEventListener('click', stopLink, { once: true }); }
            function stopLink(e) { e.preventDefault(); }

            wrap.addEventListener('mousedown',  e => { if(e.target.closest('button')) return; onStart(e.clientY); });
            document.addEventListener('mousemove', e => onMove(e.clientY));
            document.addEventListener('mouseup',   () => onEnd());
            wrap.addEventListener('touchstart', e => { if(e.target.closest('button')) return; onStart(e.touches[0].clientY); }, { passive: true });
            document.addEventListener('touchmove',  e => onMove(e.touches[0].clientY), { passive: true });
            document.addEventListener('touchend',   () => onEnd());
        })();

        function hideFloatLocation() {
            document.getElementById('float-location-wrap').style.display = 'none';
        }
    </script>

    <!-- ═══════════════════════════════════════ -->
    <!-- MENU IMAGE GALLERY MODAL (official pages) -->
    <!-- ═══════════════════════════════════════ -->
    <div id="menuModal" class="fixed inset-0 bg-black/95 z-[60]" style="display:none;">
        <div class="relative w-full h-full flex flex-col">
            <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
                <h3 class="text-white text-lg font-bold" id="menuModalTitle">Khattak Hotel Menu</h3>
                <button onclick="closeMenuModal()" class="text-white text-xl bg-[#D60040] w-10 h-10 rounded-full hover:bg-[#9F0030] transition flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-hidden flex items-center justify-center relative px-2">
                <button onclick="menuGalleryPrev()" class="absolute left-2 md:left-6 text-white bg-white/10 hover:bg-[#D60040] w-10 h-10 rounded-full flex items-center justify-center transition z-10">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <img id="menuModalImg" src="" alt="" class="max-h-[80vh] max-w-full object-contain rounded-lg">
                <button onclick="menuGalleryNext()" class="absolute right-2 md:right-6 text-white bg-white/10 hover:bg-[#D60040] w-10 h-10 rounded-full flex items-center justify-center transition z-10">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="text-center text-white/60 text-xs py-3" id="menuModalCounter"></div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- SCRIPTS                                -->
    <!-- ═══════════════════════════════════════ -->
    <script>
        let currentTab = 'karahi';

        function showCategory(id, btn) {
            currentTab = id;
            document.querySelectorAll('.menu-section').forEach(sec => {
                sec.classList.toggle('hidden', !(id === 'all' || sec.dataset.cat === id));
            });
            document.querySelectorAll('.menu-card').forEach(c => c.style.display = '');
            document.querySelectorAll('.cat-block').forEach(b => b.style.display = '');
            document.getElementById('noResultsMsg').classList.add('hidden');

            document.querySelectorAll('.tab-pill').forEach(p => p.classList.remove('active'));
            if (btn) {
                btn.classList.add('active');
            } else {
                const match = document.querySelector('.tab-pill[data-cat="' + id + '"]');
                if (match) match.classList.add('active');
            }

            const searchInput = document.getElementById('menuSearch');
            if (searchInput) searchInput.value = '';
        }

        function jumpToCategory(id) {
            document.getElementById('menu').scrollIntoView({ behavior: 'smooth' });
            const btn = document.querySelector('.tab-pill[data-cat="' + id + '"]');
            showCategory(id, btn);
        }

        function filterMenu(query) {
            query = query.trim().toLowerCase();
            if (!query) {
                showCategory(currentTab, document.querySelector('.tab-pill[data-cat="' + currentTab + '"]'));
                return;
            }
            document.querySelectorAll('.menu-section').forEach(sec => sec.classList.remove('hidden'));
            document.querySelectorAll('.tab-pill').forEach(p => p.classList.remove('active'));

            let totalVisible = 0;
            document.querySelectorAll('.cat-block').forEach(block => {
                let anyVisible = false;
                block.querySelectorAll('.menu-card').forEach(card => {
                    const match = (card.dataset.search || '').includes(query);
                    card.style.display = match ? '' : 'none';
                    if (match) { anyVisible = true; totalVisible++; }
                });
                block.style.display = anyVisible ? '' : 'none';
            });
            document.getElementById('noResultsMsg').classList.toggle('hidden', totalVisible > 0);
        }

        function orderWhatsapp(item) {
            const message = 'Hello Khattak Hotel, I would like to order: ' + item;
            window.open('https://wa.me/923285370000?text=' + encodeURIComponent(message), '_blank');
        }

        // Official menu image gallery (front cover, 5 pages, back cover)
        const menuGalleryImages = <?php echo json_encode(array_map(fn($p) => ['src' => $p['file'], 'label' => $p['label']], $menuGallery), JSON_UNESCAPED_SLASHES); ?>;
        let menuGalleryIndex = 0;

        function renderMenuGalleryImg() {
            const page = menuGalleryImages[menuGalleryIndex];
            document.getElementById('menuModalImg').src = page.src;
            document.getElementById('menuModalImg').alt = page.label;
            document.getElementById('menuModalTitle').textContent = page.label;
            document.getElementById('menuModalCounter').textContent = (menuGalleryIndex + 1) + ' / ' + menuGalleryImages.length;
        }

        function openMenuGalleryModal(index) {
            menuGalleryIndex = index || 0;
            renderMenuGalleryImg();
            document.getElementById('menuModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeMenuModal() {
            document.getElementById('menuModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function menuGalleryPrev() {
            menuGalleryIndex = (menuGalleryIndex - 1 + menuGalleryImages.length) % menuGalleryImages.length;
            renderMenuGalleryImg();
        }
        function menuGalleryNext() {
            menuGalleryIndex = (menuGalleryIndex + 1) % menuGalleryImages.length;
            renderMenuGalleryImg();
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeMenuModal();
            if (document.getElementById('menuModal').style.display === 'block') {
                if (e.key === 'ArrowLeft') menuGalleryPrev();
                if (e.key === 'ArrowRight') menuGalleryNext();
            }
        });

        document.getElementById('menuModal').addEventListener('click', function(e) {
            if (e.target === this) closeMenuModal();
        });

        // Photo gallery (Experience Khattak Hotel section)
        const galleryPhotosJs = <?php echo json_encode($galleryPhotos, JSON_UNESCAPED_SLASHES); ?>;
        let galleryVisibleIndexes = galleryPhotosJs.map((_, i) => i);
        let galleryLightboxIndex = 0;

        function filterGallery(cat, btn) {
            document.querySelectorAll('.tab-pill[data-gcat]').forEach(p => p.classList.remove('active'));
            if (btn) btn.classList.add('active');

            document.querySelectorAll('.gallery-thumb').forEach(thumb => {
                const match = cat === 'all' || thumb.dataset.gcat === cat;
                thumb.style.display = match ? '' : 'none';
            });

            galleryVisibleIndexes = galleryPhotosJs
                .map((p, i) => ({ p, i }))
                .filter(x => cat === 'all' || x.p.cat === cat)
                .map(x => x.i);
        }

        function renderGalleryLightboxImg() {
            const photo = galleryPhotosJs[galleryLightboxIndex];
            document.getElementById('galleryLightboxImg').src = photo.src;
            document.getElementById('galleryLightboxImg').alt = 'Khattak Hotel — ' + photo.label;
            document.getElementById('galleryLightboxTitle').textContent = photo.label;
            const pos = galleryVisibleIndexes.indexOf(galleryLightboxIndex) + 1;
            document.getElementById('galleryLightboxCounter').textContent = pos + ' / ' + galleryVisibleIndexes.length;
        }

        function openGalleryLightbox(index) {
            galleryLightboxIndex = index;
            renderGalleryLightboxImg();
            document.getElementById('galleryLightbox').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeGalleryLightbox() {
            document.getElementById('galleryLightbox').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function galleryLightboxStep(dir) {
            const pos = galleryVisibleIndexes.indexOf(galleryLightboxIndex);
            const nextPos = (pos + dir + galleryVisibleIndexes.length) % galleryVisibleIndexes.length;
            galleryLightboxIndex = galleryVisibleIndexes[nextPos];
            renderGalleryLightboxImg();
        }
        function galleryLightboxPrev() { galleryLightboxStep(-1); }
        function galleryLightboxNext() { galleryLightboxStep(1); }

        document.addEventListener('keydown', function(e) {
            if (document.getElementById('galleryLightbox').style.display === 'block') {
                if (e.key === 'Escape') closeGalleryLightbox();
                if (e.key === 'ArrowLeft') galleryLightboxPrev();
                if (e.key === 'ArrowRight') galleryLightboxNext();
            }
        });

        document.getElementById('galleryLightbox').addEventListener('click', function(e) {
            if (e.target === this) closeGalleryLightbox();
        });

        // Click-and-drag mouse scrolling for horizontal tab/chip rows
        // (Explore Menu tabs, Popular Categories, Gallery filters — all use .tabs-container)
        document.querySelectorAll('.tabs-container').forEach(function(container) {
            let isDown = false, startX = 0, startScroll = 0, moved = false;

            container.addEventListener('mousedown', function(e) {
                isDown = true;
                moved = false;
                startX = e.pageX;
                startScroll = container.scrollLeft;
                container.classList.add('dragging');
            });

            window.addEventListener('mousemove', function(e) {
                if (!isDown) return;
                const delta = e.pageX - startX;
                if (Math.abs(delta) > 4) moved = true;
                container.scrollLeft = startScroll - delta;
            });

            window.addEventListener('mouseup', function() {
                if (!isDown) return;
                isDown = false;
                container.classList.remove('dragging');
            });

            // Prevent the drag-release from triggering a tab-pill/chip click
            container.addEventListener('click', function(e) {
                if (moved) { e.preventDefault(); e.stopPropagation(); }
            }, true);
        });

        function installPWA(platform) {
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            if (platform === 'ios' || isIOS) {
                const iosBanner = document.getElementById('ios-install-banner');
                if (iosBanner) { iosBanner.classList.remove('hidden'); iosBanner.classList.add('pwa-banner-animate'); }
            } else {
                if (window.PWAInstall && typeof window.PWAInstall.forceShowBanner === 'function') {
                    window.PWAInstall.forceShowBanner();
                } else {
                    const installBanner = document.getElementById('pwa-install-banner');
                    if (installBanner) { installBanner.classList.remove('hidden'); installBanner.classList.add('pwa-banner-animate'); }
                }
            }
        }
    </script>

    <!-- PWA Install Banners -->
    <div id="pwa-install-banner" class="hidden">
        <button id="close-install-banner" aria-label="Close"><i class="fas fa-times"></i></button>
        <div class="pwa-banner-content">
            <img src="icons/khattak-hotel-logo.png" alt="Khattak Hotel" class="pwa-banner-icon">
            <div class="pwa-banner-text">
                <p class="pwa-banner-title">Install Khattak Hotel</p>
                <p class="pwa-banner-subtitle">Add to home screen for quick access</p>
            </div>
            <button id="pwa-install-btn"><i class="fas fa-download"></i> Install</button>
        </div>
    </div>

    <div id="ios-install-banner" class="hidden">
        <button id="close-ios-banner" aria-label="Close"><i class="fas fa-times"></i></button>
        <div class="ios-banner-content">
            <p class="ios-banner-title"><i class="fas fa-mobile-alt"></i> Install Khattak Hotel App</p>
            <div class="ios-banner-steps">
                <div class="ios-step">
                    <span class="ios-step-number">1</span>
                    <span class="ios-step-text">Tap the Share button <span class="ios-share-icon"><i class="fas fa-share-from-square"></i></span></span>
                </div>
                <div class="ios-step">
                    <span class="ios-step-number">2</span>
                    <span class="ios-step-text">Scroll and tap <strong>"Add to Home Screen"</strong> <i class="fas fa-plus-square ios-step-icon"></i></span>
                </div>
            </div>
        </div>
    </div>

    <script src="pwa-install.js"></script>
</body>
</html>
