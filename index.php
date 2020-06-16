<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Последние 15 новостей с сайта РБК</title>
        <?php echo "<link rel='stylesheet' href='styles.css'>"; ?>
    </head>
    <body class="overflow_hidden">
        <div class="preloader">
            <div class="preloader-item">
                <p class="preloader-item__text">Подождите, идет парсинг новостей...</p>
                <div class="preloader-item__img"></div>            
            </div>
        </div>
<?php 
include 'simple_html_dom.php';

$url = 'https://www.rbc.ru/v10/ajax/get-news-feed/project/rbcnews/lastDate/';      // основная часть ссылки запроса
$curDate = time();          // дата в unix формате
$limit = '/limit/15';        // ограничение по количеству новостей 
$fullUrl =  $url.$curDate.$limit;

$strCode = parse($fullUrl);  // парсим 15 новостей в формате json
$news = json_decode($strCode, true);  // декодим json
$pattern = '/(href="(.+?)")/i';  // регулярное выражение для вытскивания ссылки на полную новость
$allItems = array();     // создадим будущий контейнер для полных статей 
$shortText = '';    // переменная для укороченного текста

function parse($str) {
    // обходим защиту сайта
    // start
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_URL, $str);
    curl_setopt($ch, CURLOPT_REFERER, $str);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $result = curl_exec($ch);
    curl_close($ch);
    // end

    return $result;
}

function writeFullNews($element) {
    $fp = fopen('fullNews.json', "w");
    fwrite($fp, json_encode($element, JSON_UNESCAPED_UNICODE));
    fclose($fp);
}

$count = 1;
echo "<ul class='all_news'>";
foreach ($news['items'] as $value) {
    //echo date('d.m.Y H:i:s', $value['publish_date_t']);  // конвертируем дату публикации новости из unix формата
    echo "<li><b>{$count}.</b> {$value['html']} </li>";
    $count++;
}
echo "</ul>";
echo '<div class="article_wrapper">';

foreach($news['items'] as $value) { // выборка всех новостей на странице
    preg_match($pattern, $value['html'], $matches);
    $fullNews = parse($matches[2]);
    $htmlNew = str_get_html($fullNews);

    if ($htmlNew->innertext!='') {
        if ($htmlNew->find('.js-slide-title', 0)) {
            $item['title']  = $htmlNew->find('.js-slide-title', 0)->plaintext;
            
            if ($htmlNew->find('.js-rbcslider-image', 0)) {
                $item['image']  = $htmlNew->find('.js-rbcslider-image', 0)->src;
            }
            
            $item['text'] = []; 
            $item['link'] = $matches[2];
            
          foreach($htmlNew->find('.article__text') as $wrapper){
            foreach($wrapper->find('p') as $p){
                if (!($p->find('.article__inline-item__title'))) {
                    array_push($item['text'], $p->plaintext);
                }
            }
          }
        }
        
        if (isset($item['title'])) {
            for ($i = 0; strlen($shortText) <= 200; $i++) {
                $shortText .= isset($item['text'][$i]) ? $item['text'][$i] : '...';
            }
            
            if (strlen($shortText) > 200) {
                $shortText = substr($shortText, 0, 200);
                $shortText .= '...';
            }
            
            echo "
                <div class='article'>
                    <h1>{$item['title']}</h1>
                    ".(isset($item['image']) ? '<img src="'.$item['image'].'" class="article-image">' : '')."
                    <a href='{$matches[2]}'>Подробнее</a>
                    <div class='article-text'>
                        <p class='article-text__short'>{$shortText} <span class='read_more'>Читать далее</span> </p>
                        <div class='article-text__long hidden'>";
                        foreach($item['text'] as $p) {
                            echo '<p>'.$p.'</p>';
                        }
                        
                        echo "<span class='read_less'>Скрыть</span>
                        </div>
                    </div>
                </div>
            ";
            
            $shortText = '';
            array_push($allItems, $item);
            $item = [];
            $htmlNew->clear(); // подчищаем за собой
        }
    }
}

echo '</div>';
        
writeFullNews($allItems);

?>
    <a href="./fullNews.json" download="fullNews.json" class="download_file">Скачать файл с новостями <br> в формате JSON</a>
    <script>
        window.onload = () => {
            document.querySelector('.preloader').classList.toggle('hidden')
            document.body.classList.toggle('overflow_hidden')
        }
        let allItems
        let request = new XMLHttpRequest()
        request.open('GET', './fullNews.json')
        request.responseType = 'json'
        request.send()
        request.onload = function() {
            allItems = request.response
        }
        // let articles = document.querySelectorAll(".article")
        document.addEventListener('click', (e) =>     {
            if (e.target.classList.contains('read_more') || e.target.classList.contains('read_less')) {
                if (e.target.parentElement.parentElement.parentElement.querySelector('img')) {
                    e.target.parentElement.parentElement.parentElement.querySelector('img').classList.toggle("show")
                }
                if (e.target.classList.contains('read_more')) {
                    e.target.parentElement.nextElementSibling.classList.toggle("hidden")
                } else {
                    e.target.parentElement.previousElementSibling.classList.toggle("hidden")
                }
                
                e.target.parentElement.classList.toggle("hidden")
                e.target.parentElement.parentElement.previousElementSibling.classList.toggle("hidden")
            }
        })
    </script>
    </body>
</html>