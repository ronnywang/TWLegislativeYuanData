<?php

if (!$body = file_get_contents($_SERVER['argv'][1])) {
    die("用 php parser.php [raw-file.html]");
}
$doc = new DOMDocument();
$full_body = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></html><body>' . $body . '</body></html>';
@$doc->loadHTML($full_body);

$tbody_doms = $doc->getElementsByTagName('tbody');
$persons = array();
foreach ($tbody_doms as $tbody_dom) {
    foreach ($tbody_dom->getElementsByTagName('tr') as $tr_dom) {
        $person = new StdClass;
        $name = trim($tr_dom->getElementsByTagName('td')->item(0)->nodeValue);
        $text_committees = trim($doc->saveHTML($tr_dom->getElementsByTagName('td')->item(1)));
        $party1 = trim($tr_dom->getElementsByTagName('td')->item(2)->nodeValue);
        $party2 = trim($tr_dom->getElementsByTagName('td')->item(3)->nodeValue);
        $area = trim($tr_dom->getElementsByTagName('td')->item(4)->nodeValue);
        $contact = trim($tr_dom->getElementsByTagName('td')->item(5)->nodeValue);
        $committees = array();
        foreach (explode('<br>', $text_committees) as $text_committee) {
            if ('' !== trim(strip_tags($text_committee))) {
                $committees[] = trim(strip_tags($text_committee));
            }
        }
        $person->{'姓名'} = $name;
        $person->{'委員會'} = $committees;
        $person->{'黨籍'} = $party1;
        $person->{'黨團'} = $party2;
        $person->{'選區'} = $area;
        $person->{'研究室及服務處通訊資料'} = $contact;

        $persons[] = $person;
    }
}

echo json_encode($persons, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

