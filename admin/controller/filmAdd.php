<?php
include('../conn.php');
// Tüm hata türlerini göster
error_reporting(E_ALL);

// Hataları ekrana yazdır
ini_set('display_errors', 1);

// Hataları ekrana yazdırmayı açık hale getir  
ini_set('display_startup_errors', 1);
// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
    // Film ile ilgili form verilerini alıyoruz     
    $filmadi = $_POST['filmadi']; 
    $vizyonTarihi = $_POST['vizyonTarihi']; 
    $dagitimListesi = $_POST['dagitimListesi']; // Array
    $studyoListesi = $_POST['studyoListesi']; // Array
    $ulkeListesi = $_POST['ulkeListesi']; // Array
    $filmturuListesi = $_POST['filmturuListesi']; // Array
    
    $yonetmenListesi = $_POST['yonetmenListesi']; // Array
    $senaryoListesi = $_POST['senaryoListesi']; // Array
    $gyonetmeniListesi = $_POST['gyonetmeniListesi']; // Array
    $kurguListesi = $_POST['kurguListesi']; // Array
    $müzikListesi = $_POST['müzikListesi']; // Array
    $oyuncuListesi = $_POST['oyuncuListesi']; // Array

    $kategoriIdMap = [
        'yonetmen' => 34,
        'senaryo' => 38,
        'gyonetmen' => 35, // Buraya uygun kategori_id ekleyin
        'kurgu' => 37, // Buraya uygun kategori_id ekleyin
        'müzik' => 36, // Buraya uygun kategori_id ekleyin
        'oyuncu' => 29, // Buraya uygun kategori_id ekleyin
    ];

    // Fotoğraflar için dizinler
    $kapakFotoDizin = "../../kapakfoto/";
    $galeriDizin = "../../galeri/";

    // Kapak fotoğrafını yükleme
    if (!empty($_FILES['kapakfotograf']['name'][0])) { // İlk fotoğrafın adı boş değilse, demek ki dosyalar var
        $fotoSayisi = count($_FILES['kapakfotograf']['name']); // Seçilen fotoğraf sayısını al
        $kapakFotoYollari = []; // Yüklenecek fotoğrafların yollarını tutacak dizi
        
        for ($i = 0; $i < $fotoSayisi; $i++) {
            // Dosyanın orijinal adını ve uzantısını al
            $orijinalAd = basename($_FILES['kapakfotograf']['name'][$i]);
            $dosyaUzantisi = pathinfo($orijinalAd, PATHINFO_EXTENSION); // Dosya uzantısını al (örneğin: jpg, png)
            
            // Benzersiz dosya adı oluştur (örneğin: zaman_rastgeleSayi.jpg)
            $benzersizAd = time() . '_' . $filmadi. '.' . $dosyaUzantisi;
            
            // Dosya yolunu belirle
            $kapakFotoYolu = $kapakFotoDizin . $benzersizAd;
            
            // Fotoğrafı sunucuya yükle
            if (move_uploaded_file($_FILES['kapakfotograf']['tmp_name'][$i], $kapakFotoYolu)) {
                $kapakFotoYollari[] = $benzersizAd; // Yüklendiği ismi kaydet
            } else {
                echo "Fotoğraf yüklenirken bir hata oluştu: " . $_FILES['kapakfotograf']['name'][$i];
            }
        }
    
       
    } else {
        echo "Hiç fotoğraf seçilmedi.";
    }

    // Film bilgilerini veritabanına ekleme
    $stmt = $con->prepare("INSERT INTO filmler (film_adi,vizyon_tarihi, kapak_resmi) VALUES (?,?, ?)");
    $stmt->execute([$filmadi, $vizyonTarihi, $kapakFotoYollari[0]]);
    $film_id = $con->lastInsertId(); // Eklenen filmin ID'sini alıyoruz

    $f=0;
    // Galeri fotoğraflarını yükleme
if (!empty($_FILES['galerifotograf']['name'][0])) {
    foreach ($_FILES['galerifotograf']['name'] as $key => $galeriFotoAdi) {
        // Film adını al (örneğin: $_POST['film_adi'])

        // Dosya uzantısını al
        $dosyaUzantisi = pathinfo($galeriFotoAdi, PATHINFO_EXTENSION); // Örneğin: jpg, png
        
        // Zaman damgası ve film adı ile yeni dosya adı oluştur
        $yeniFotoAdi = time() .$f . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $filmadi) . '.' . $dosyaUzantisi; // Geçersiz karakterleri temizle
        
        // Galeri dizinini kontrol et
        if (!is_dir($galeriDizin)) {
            mkdir($galeriDizin, 0755, true); // Dizin yoksa oluştur
        }

        $galeriFotoYolu = $galeriDizin . $yeniFotoAdi; // Yolu oluştur

        // Fotoğrafı yükle
        if (move_uploaded_file($_FILES['galerifotograf']['tmp_name'][$key], $galeriFotoYolu)) {
            // Galeri resmini veritabanına kaydetme
            $stmt = $con->prepare("INSERT INTO film_galeri (film_id, resim_yolu) VALUES (?, ?)");
            $stmt->execute([$film_id, $yeniFotoAdi]);
        } else {
            echo "Fotoğraf yüklenirken bir hata oluştu: " . $yeniFotoAdi;
        }
        $f++;
    }
}


    // Dağıtım şirketlerini kaydetme
    if (!empty($dagitimListesi)) {
        foreach ($dagitimListesi as $dagitim_id) {
            $stmt = $con->prepare("INSERT INTO film_dagitim (film_id, dagitim_id) VALUES (?, ?)");
            $stmt->execute([$film_id, $dagitim_id]);
        }
    }

    // Stüdyoları kaydetme
    if (!empty($studyoListesi)) {
        foreach ($studyoListesi as $studyo_id) {
            $stmt = $con->prepare("INSERT INTO film_studyolar (film_id, studyo_id) VALUES (?, ?)");
            $stmt->execute([$film_id, $studyo_id]);
        }
    }

    // Ülkeleri kaydetme
    if (!empty($ulkeListesi)) {
        foreach ($ulkeListesi as $ulke_id) {
            $stmt = $con->prepare("INSERT INTO film_ulkeler (film_id, ulke_id) VALUES (?, ?)");
            $stmt->execute([$film_id, $ulke_id]);
        }
    }

    // Film türlerini kaydetme
    if (!empty($filmturuListesi)) {
        foreach ($filmturuListesi as $filmturu_id) {
            $stmt = $con->prepare("INSERT INTO film_filmturu (film_id, filmturu_id) VALUES (?, ?)");
            $stmt->execute([$film_id, $filmturu_id]);
        }
    }




    // Dağıtım şirketlerini kaydetme
if (!empty($yonetmenListesi)) {
    foreach ($yonetmenListesi as $oyuncu_id) {
        $stmt = $con->prepare("INSERT INTO oyuncuiliski (film_id, oyuncu_id, kategori_id) VALUES (?, ?, ?)");
        $stmt->execute([$film_id, $oyuncu_id, $kategoriIdMap['yonetmen']]);
    }
}

if (!empty($senaryoListesi)) {
    foreach ($senaryoListesi as $oyuncu_id) {
        $stmt = $con->prepare("INSERT INTO oyuncuiliski (film_id, oyuncu_id, kategori_id) VALUES (?, ?, ?)");
        $stmt->execute([$film_id, $oyuncu_id, $kategoriIdMap['senaryo']]);
    }
}

if (!empty($gyonetmeniListesi)) {
    foreach ($gyonetmeniListesi as $oyuncu_id) {
        $stmt = $con->prepare("INSERT INTO oyuncuiliski (film_id, oyuncu_id, kategori_id) VALUES (?, ?, ?)");
        $stmt->execute([$film_id, $oyuncu_id, $kategoriIdMap['gyonetmen']]);
    }
}

if (!empty($kurguListesi)) {
    foreach ($kurguListesi as $oyuncu_id) {
        $stmt = $con->prepare("INSERT INTO oyuncuiliski (film_id, oyuncu_id, kategori_id) VALUES (?, ?, ?)");
        $stmt->execute([$film_id, $oyuncu_id, $kategoriIdMap['kurgu']]);
    }
}

if (!empty($müzikListesi)) {
    foreach ($müzikListesi as $oyuncu_id) {
        $stmt = $con->prepare("INSERT INTO oyuncuiliski (film_id, oyuncu_id, kategori_id) VALUES (?, ?, ?)");
        $stmt->execute([$film_id, $oyuncu_id, $kategoriIdMap['müzik']]);
    }
}

if (!empty($oyuncuListesi)) {
    foreach ($oyuncuListesi as $oyuncu_id) {
        $stmt = $con->prepare("INSERT INTO oyuncuiliski (film_id, oyuncu_id, kategori_id) VALUES (?, ?, ?)");
        $stmt->execute([$film_id, $oyuncu_id, $kategoriIdMap['oyuncu']]);
    }
}

    


   
} catch (Exception $e) {
    // Hata durumunda catch bloğu çalışacak ve hatayı yakalayacağız
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
}

?>

