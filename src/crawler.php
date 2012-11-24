<?php

class Crawler
{
    public function innerHTML($doc, $el)
    {
        // from http://php.net/manual/en/book.dom.php
        $html = trim($doc->saveHTML($el));
        $tag = $el->nodeName;
        return preg_replace('@^<' . $tag . '[^>]*>|</' . $tag . '>$@', '', $html);
    }

    public function findDomByCondition($doc, $tag, $key, $value)
    {
        $ret = [];
        foreach ($doc->getElementsByTagName($tag) as $dom) {
            if ($dom->getAttribute($key) !== $value) {
                continue;
            }
            $ret[] = $dom;
        }
        return $ret;

    }

    public function getBodyFromURL($url)
    {
        error_log($url);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        return curl_exec($curl);
    }

    public function main($url)
    {
        $doc = new DOMDocument();
        $full_body = $this->getBodyFromURL($url);
        @$doc->loadHTML($full_body);

        $persons = [];
        foreach ($this->findDomByCondition($doc, 'td', 'class', 'leg03_news_search_03') as $td_dom) {
            $person = new StdClass;

            $a_dom = $td_dom->getElementsByTagName('a')->item(0);
            $link = 'http://www.ly.gov.tw' . $a_dom->getAttribute('href');
            $name = trim($a_dom->nodeValue);

            $person->{'姓名'} = $name;
            $person->{'link'} = $link;

            $persondoc = new DOMDocument();
            @$persondoc->loadHTML($this->getBodyFromURL($link));

            // 1 - 簡介
            $ul_dom = $this->findDomByCondition($persondoc, 'ul', 'style', 'list-style-position:outside;')[0];

            $list = array('姓名', '英文姓名', '性別', '黨籍', '黨團', '選區', '生日');
            foreach ($ul_dom->getElementsByTagName('li') as $li_dom) {
                list($key, $value) = explode('：', trim($li_dom->nodeValue), 2);

                if (in_array($key, $list)) {
                    $person->{$key} = trim($value);
                } elseif ('委員會' == $key) {
                    $committees = array();
                    foreach (explode("<br>", $this->innerHTML($persondoc, $li_dom)) as $body) {
                        list($key, $value) = explode('：', $body);
                        if (trim($key) == '委員會') {
                        } elseif (trim($key) == '到職日期') {
                            $person->{'到職日期'} = trim($value);
                        } else {
                            $committees[] = array($key, trim($value));
                        }
                    }
                    $person->{'委員會'} = $committees;
                } else {
                    throw new Exception('出現其他東西');
                }
            }
            // 照片
            foreach ($persondoc->getElementsByTagName('img') as $img_dom) {
                if ($img_dom->getAttribute('class') != 'leg03_pic') {
                    continue;
                }
                $person->{'pic'} = 'http://www.ly.gov.tw' . $img_dom->getAttribute('src');

            }

            // 學歷
            $ul_doms = $this->findDomByCondition($persondoc, 'ul', 'style', 'list-style-position:outside;');
            $map = array(
                1 => '學歷',
                2 => '電話',
                3 => '經歷',
                4 => '傳真',
                5 => '通訊處',
            );

            foreach ($map as $n) {
                $person->{$n} = array();
            }

            foreach ($ul_doms as $ul_dom) {
                $name = strval($ul_dom->previousSibling->previousSibling->nodeValue);

                $rows = array();
                if ($ul_dom->getElementsByTagName('div')->length == 0) {
                    $person->{$name} = $rows;
                    continue;
                }
                foreach (explode('<br>', $this->innerHTML($persondoc, $ul_dom->getElementsByTagName('div')->item(0))) as $text) {
                    if ('' !== trim($text)) {
                        $rows[] = $text;
                    }
                }
                $person->{$name} = $rows;
            }
            $persons[] = $person;
        }
        echo json_encode($persons, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

if ($_SERVER['argv'][1]) {
    $url = $_SERVER['argv'][1];
} else {
    $url = 'http://www.ly.gov.tw/03_leg/0301_main/legList.action';
}
$c = new Crawler;
$c->main($url);
