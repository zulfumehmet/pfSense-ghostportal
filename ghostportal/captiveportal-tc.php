<?php
header("Content-type: text/html; charset=utf-8");
include ("captiveportal-config.php");
$sorgu = mysqli_fetch_array(mysqli_query($baglan,"select * from ghost_settings"));
$expdate =  $sorgu['passwordexptime'];

function rand_string( $length ) {

    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    return substr(str_shuffle($chars),0,$length);

}

function rand_numstring( $length ) {

    $chars = "0123456789";
    return substr(str_shuffle($chars),0,$length);

}

function karakter_duzeltme($gelen){
    $karakterler = array("ç","ğ","ı","i","ö","ş","ü");
    $degistir = array("Ç","Ğ","I","İ","Ö","Ş","Ü");
    return str_replace($karakterler, $degistir, $gelen);
}

function karakter_duzeltme2($gelen){
    $karakterler = array("ç","Ç","ğ","Ğ","ı","İ","ö","Ö","ş","Ş","ü","Ü");
    $degistir = array("c","c","g","g","i","i","o","o","s","s","u","u");
    return str_replace($karakterler, $degistir, strtolower($gelen));
}

if(isset($_POST["tc"])){


    $ad = strtoupper(karakter_duzeltme(trim($_POST["ad"])));
    $soyad = strtoupper(karakter_duzeltme(trim($_POST["soyad"])));
    $dogum_yili = trim($_POST["dogum"]);
    $tc_no = trim($_POST["tc"]);
    settype($tc_no, "double");


    try {
		$veriler = array(
            "TCKimlikNo" => $tc_no,
            "Ad" => $ad,
            "Soyad" => $soyad,
            "DogumYili" => $dogum_yili
        );

        $baglanTC = new SoapClient("https://tckimlik.nvi.gov.tr/Service/KPSPublic.asmx?WSDL");
        $sonuc = $baglanTC->TCKimlikNoDogrula($veriler);

        if ($sonuc->TCKimlikNoDogrulaResult){

			$user =  substr(karakter_duzeltme2($ad),0,1).karakter_duzeltme2($soyad).rand_numstring(2);
			$pass =  rand_string(5);
			$bugun = date("Y-m-d");
			$yenitarih = strtotime($expdate.' day',strtotime($bugun));
			$tarih = date('j M Y' ,$yenitarih );
            // Kullanıcının veritabanında olup olmadığını kontrol eden sorgu
            $checkUserQuery = "SELECT * FROM radcheck WHERE username = '".$tc_no."'";
            $result = mysqli_query($baglan, $checkUserQuery);

            if (mysqli_num_rows($result) == 0) {
                // Kullanıcı mevcut değilse, yeni kayıt ekleyin
                $insertQuery = "INSERT INTO radcheck (username, attribute, op, value, tip, tcno, adsoyad, tarih, sifre, dtarih) 
                                VALUES ('".$tc_no."', 'Cleartext-Password', ':=', '".$dogum_yili."', 2, '".$tc_no."', '".$ad.' '.$soyad."', '".$bugun."', '".$dogum_yili."', '".$dogum_yili."')";

                if (mysqli_query($baglan, $insertQuery)) {
                    echo $tc_no.",".$dogum_yili;
                } else {
                    echo "Hata: " . $insertQuery . "<br>" . mysqli_error($baglan);
                }
            } else {
                echo $tc_no.",".$dogum_yili;
            }




			//mysqli_query($baglan,"INSERT INTO radcheck(username,attribute,op,value,tip,tcno,adsoyad,tarih,sifre,dtarih) values('".$tc_no."','Cleartext-Password',':=','".$dogum_yili."',2,'".$tc_no."','".$ad.' '.$soyad."', '".$bugun."','".$dogum_yili."','".$dogum_yili."')");
			//mysqli_query($baglan,"INSERT INTO radcheck(username,attribute,op,value) values('".$tc_no."','Expiration',':=', '".$tarih."')");
            // eger islem basariliysa -->
           
			//echo "Not alınız. kadi: " . $user.', şifreniz: ' .$pass;

        }else {
           echo 0;
        }

    }catch (Exception $hata){
        echo 'T.C Kimlik Numarası Bulunmamaktadır...';
    }
}



?>
