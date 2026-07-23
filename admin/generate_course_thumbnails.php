<?php
/**
 * One-time script: Generate professional Black+Gold PNG thumbnails
 * for the 3 new courses using PHP GD library.
 */
declare(strict_types=1);

require_once __DIR__ . "/includes/auth_check.php";

if (!extension_loaded("gd")) {
    die("PHP GD extension is not available on this server.");
}

$outDir = dirname(__DIR__) . "/assets/images/courses/";
if (!is_dir($outDir)) mkdir($outDir, 0755, true);

$courses = [
    [
        "file"     => "dsa-interview-prep.jpg",
        "line1"    => "Data Structures",
        "line2"    => "& Algorithms",
        "line3"    => "Interview Prep",
        "icon"     => "DSA",
    ],
    [
        "file"     => "fullstack-web-dev.jpg",
        "line1"    => "Full Stack Web",
        "line2"    => "Development",
        "line3"    => "Masterclass",
        "icon"     => "FS",
    ],
    [
        "file"     => "react-masterclass.jpg",
        "line1"    => "React",
        "line2"    => "Frontend to Backend",
        "line3"    => "Masterclass",
        "icon"     => "RE",
    ],
];

$results = [];
foreach ($courses as $c) {
    $w = 1280; $h = 720;
    $img = imagecreatetruecolor($w, $h);
    $bg      = imagecolorallocate($img, 10, 10, 10);
    $accent  = imagecolorallocate($img, 255, 196, 0);
    $white   = imagecolorallocate($img, 255, 255, 255);
    $dark    = imagecolorallocate($img, 17, 17, 17);
    $gray    = imagecolorallocate($img, 100, 100, 100);
    $gold2   = imagecolorallocate($img, 128, 98, 0);

    imagefilledrectangle($img, 0, 0, $w, $h, $bg);
    imagefilledrectangle($img, 40, 40, $w-40, $h-40, $dark);
    imagefilledrectangle($img, 40, 40, $w-40, 50, $accent);
    imagefilledrectangle($img, 40, 40, 52, $h-40, $accent);

    // decorative dots
    for ($dx=0; $dx<6; $dx++) for ($dy=0; $dy<4; $dy++)
        imagefilledellipse($img, $w-240+$dx*18, 80+$dy*18, 5, 5, $gold2);

    // right circle
    $cx=$w-160; $cy=$h/2;
    imagefilledellipse($img, $cx, $cy, 180, 180, imagecolorallocate($img,30,30,30));
    for ($i=0;$i<360;$i+=2) {
        $rad=deg2rad($i);
        $x1=(int)round($cx+86*cos($rad)); $y1=(int)round($cy+86*sin($rad));
        imagefilledellipse($img,$x1,$y1,3,3,$accent);
    }
    $iconText=$c["icon"];
    $tw=imagefontwidth(5)*strlen($iconText); $th=imagefontheight(5);
    imagestring($img,5,$cx-intdiv($tw,2),$cy-intdiv($th,2),$iconText,$accent);

    // text
    $x=80;
    imagestring($img,5,$x,120,strtoupper($c["line1"]),$accent);
    imagestring($img,5,$x,150,strtoupper($c["line2"]),$white);
    imagestring($img,4,$x,185,$c["line3"],$gray);
    imagefilledrectangle($img,$x,$h-100,$x+220,$h-98,$gold2);
    imagefilledrectangle($img,$x,$h-95,$x+115,$h-73,$accent);
    imagestring($img,3,$x+5,$h-93,"PAID COURSE",$dark);
    imagestring($img,3,$x,$h-70,"CodeByTushu",$accent);

    $outPath = $outDir . $c["file"];
    imagejpeg($img, $outPath, 92);
    imagedestroy($img);
    $results[] = ["file"=>$c["file"],"ok"=>file_exists($outPath)];
}

echo "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Thumbnails</title>";
echo "<style>body{font-family:sans-serif;background:#111;color:#fff;padding:40px}h1{color:#ffc400}.ok{color:#22c55e}.err{color:#ef4444}img{max-width:400px;border:1px solid #333;margin:8px 0;display:block}.card{background:#1a1a1a;padding:16px;border-radius:8px;margin-bottom:12px}a.btn{display:inline-block;padding:10px 20px;background:#ffc400;color:#000;text-decoration:none;border-radius:8px;font-weight:bold;margin-top:12px}</style></head><body>";
echo "<h1>Course Thumbnails</h1>";
foreach ($results as $r) {
    $status = $r["ok"] ? "<span class=\"ok\">✓ Generated</span>" : "<span class=\"err\">✗ Failed</span>";
    echo "<div class=\"card\"><strong>{$r[\"file\"]}</strong> — {$status}<br>";
    if ($r["ok"]) echo "<img src=\"/assets/images/courses/{$r[\"file\"]}?t=".time()."\">";
    echo "</div>";
}
echo "<a class=\"btn\" href=\"/admin/import_courses.php\">▶ Next: Import Courses to Database</a></body></html>";

