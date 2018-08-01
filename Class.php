<?php 
	
class ClassAmaster1
{
	/**
     * @var Document Model
     */
    protected $modelaocument;

    /**
     * @var Post Share Model
     */
    protected $modelShare;

    /**
     * @var Array Support
     */
    protected $arraySupport;

    /**
     * @var Compare Support
     */
    protected $compareSupport;

    /**
     * PostsSupport constructor.
     *
     * @param Document $document
     */
    public function __construct(
        Document $document,
        ShareEmail $shareEmail,
        ArraySupport $arraySupport,
        CompareSupport $compareSupport
    ){
        $this->modelDocument    = $document;
        $this->modelShare       = $shareEmail;
        $this->arraySupport     = $arraySupport;
        $this->compareSupport   = $compareSupport;
    }

    /**
     * Create ball revision for Data
     *
     * @param null $postID
     * @return mixed Array data
     * @throws \Exception
     * @author Ducnn
     * @edited hoatq, moved from DocumentControler
     */
    public function createBallForRevision($postID = null)
    {
        if (is_null($postID)) {
            throw new \Exception('Error! The Post ID is NULL');
        }

        $allList = $this->modelDocument->postListCount($postID, 'desc');

        $countRevision = count($allList);
        if ($countRevision > 1) {
            $notBall = 1;
            for ($i = 0; $i < $countRevision  ; $i++) {
                if ($i == 0) {
                    $post_id = $allList[$i]->key_id;
                    $user_id = $allList[$i]->user_id;
                    $ballNo =  $this->arraySupport->convertObjectToArray(
                        $this->modelShare->getOneBallById($post_id, $user_id), 'ball');

                    if ($allList[$i]->user_id == $allList[$i + 1]->user_id) {
                        $notBall = 0;
                        if ($ballNo[0] == 2) {
                            $allList[$i]->ball = 1;
                        } else {
                            $allList[$i]->ball = 0;
                        }
                    } else {
                        if ($ballNo[0] == 2) {
                            $allList[$i]->ball = 1;
                        } else {
                            $allList[$i]->ball = 0;
                        }
                    }
                } elseif ($i == ($countRevision - 1)) {
                    if ($allList[$i]->user_id == $allList[$i - 1]->user_id) {
                        $allList[$i]->ball = 0;
                    } else {
                        $allList[$i]->ball = 1;
                    }
                } else {
                    if ($notBall == 1) {
                        $allList[$i]->ball = 1;
                        $notBall = 0;
                    } else {
                        if($allList[$i]->user_id == $allList[$i + 1]->user_id) {
                            $allList[$i]->ball = 0;
                            $notBall = 0;
                        } else {
                            $allList[$i]->ball = 0;
                            $notBall = 1;
                        }
                    }
                }
            }
        } else {
            $post_id = $allList[0]->key_id;
            $user_id = $allList[0]->user_id;
            $ballNo =  $this->arraySupport->convertObjectToArray($this->modelShare->getOneBallById($post_id, $user_id ), 'ball');
            if ($ballNo[0] == 2) {
                $allList[0]->ball = 1;
            } else {
                $allList[0]->ball = 0;
            }
        }

        return $allList;
    }

    /**
     * Replace input revision
     *
     * @param $content the String
     * @author thinhpm
     * @date 14/05/2018
     * @edited hoatq move to DocumentController
     */
    public function replaceInputRevision($content){
        $content = str_replace(' style="clear: both;" ', '', $content);
        $content = str_replace(' style="clear: both;"', '', $content);
        $content = str_replace('<p><br></p>', '', $content);
        $content = str_replace('<br>', '&br&', $content);
        return $content;
    }

    /**
     * Replace output revision
     *
     * @param $content the String
     * @author thinhpm
     * @date 14/05/2018
     * @edited hoatq move to DocumentController
     */
    public function replaceOutputRevision($content){
        $content = str_replace('&br&', '<br>', $content);
        $content = str_replace('<div class="diffBlank"></div>', '', $content);
        $content = str_replace('<div class="diffUnmodified"></div>', '', $content);
        $content = str_replace('<li></li>', '', $content);

        return $content;
    }

    /**
     * Convert tag Image to String
     *
     * @param $string
     * @return mixed
     */
    public function convertTagImageToString($string)
    {
        $string = str_replace('<img', "&lt;img", $string);

        return $string;
    }

    /**
     * Get array content Post
     *
     * @param $contentCompare the String
     * @param $content the String
     * @author thinhpm
     * 14/05/2018
     * @edited hoatq move to DocumentController
     */
    public function getArrayPostRevision($contentCompare, $content){
        $arr_post['main'] = '';
        $arr_post['revision'] = '';
        $match = $this->compareSupport->preg_cell($contentCompare, $content[0] . "\n" . $content[1]);

        if(count($match['left'][0]) > 0 || count($match['right'][0]) > 0){
            $contentCompare = preg_replace('/\<ol[^>]*\>(.*?)\<\/ol\>/','<tempol>', $contentCompare);
            $main_revision_content = preg_replace('/\<ol[^>]*\>(.*?)\<\/ol\>/','<tempol>', $content[0] . "\n" . $content[1]);
            $contentCompare = preg_replace('/\<ul[^>]*\>(.*?)\<\/ul\>/','<tempol>', $contentCompare);
            $main_revision_content = preg_replace('/\<ul[^>]*\>(.*?)\<\/ul\>/','<tempol>', $main_revision_content);

            $arr_split_main_post = explode('<tempol>', $contentCompare);
            $arr_split_revision_post = explode('<tempol>', $main_revision_content);
            $i = 0;
            $j = 0;

            for ($key = 0; $key < count($arr_split_main_post) + count($match['left'][0]); $key++) {
                if($key == 0){
                    $tmp = preg_split('/\n|\r\n?/', $arr_split_main_post[$j]);

                    foreach ($tmp as $key2 => $value2) {
                        if($value2 != ''){
                            $arr_main_post[] = $value2;
                        }
                    }
                    $j++;
                }
                else
                    if($key % 2 != 0 && !empty($match['left'][0][$i])){
                        $arr_main_post[] = preg_replace('/\R/', '', $match['left'][0][$i]);
                        $i++;
                    }
                    else{
                        $tmp = preg_split('/\n|\r\n?/', $arr_split_main_post[$j]);

                        foreach ($tmp as $key2 => $value2) {
                            if($value2 != ''){
                                $arr_main_post[] = $value2;
                            }
                        }
                        $j++;
                    }
            }

            $i = 0;
            $j = 0;
            for ($key = 0; $key < count($arr_split_revision_post) + count($match['right'][0]); $key++) {
                if($key == 0){
                    $tmp = preg_split('/\n|\r\n?/', $arr_split_revision_post[$j]);
                    foreach ($tmp as $key2 => $value2) {
                        if($value2 != ''){
                            $arr_revision_post[] = $value2;
                        }
                    }
                    $j++;

                }
                else
                    if($key % 2 != 0 && !empty($match['right'][0][$i])){
                        $arr_revision_post[] = preg_replace('/\R/', '', $match['right'][0][$i]);
                        $i++;
                    }
                    else{
                        $tmp = preg_split('/\n|\r\n?/', $arr_split_revision_post[$j]);
                        foreach ($tmp as $key2 => $value2) {
                            if($value2 != ''){
                                $arr_revision_post[] = $value2;
                            }
                        }
                        $j++;

                    }
            }

        }

        else{
            $arr_main_post = preg_split('/\n|\r\n?/', $contentCompare);
            $arr_revision_post = preg_split('/\n|\r\n?/', $content[0] . "\n" . $content[1]);
        }

        foreach ($arr_main_post as $key => $value) {
            if(strlen(trim($value)) == 0){
                unset($arr_main_post[$key]);
            }
        }
        foreach ($arr_revision_post as $key => $value) {
            if(strlen(trim($value)) == 0){
                unset($arr_revision_post[$key]);
            }
        }
        $arr_main_post = array_values($arr_main_post);
        $arr_revision_post = array_values($arr_revision_post);
        $arr_post['main'] = $arr_main_post;
        $arr_post['revision'] = $arr_revision_post;

        return $arr_post;
    }

    /**
     * Compare Content
     *
     * @param $contentCompare the String
     * @param $content the String
     * @author thinhpm
     * 14/05/2018
     * @edited hoatq move to DocumentController
     */
    public function compareContent($contentCompare, $content){
        $string['left'] = '';
        $string['right'] = '';
        $count_main_post = count($contentCompare);
        $count_revision_post = count($content);

        if($count_main_post == $count_revision_post){
            foreach ($contentCompare as $key => $value) {
                if((gettype(strpos($contentCompare[$key], '<img')) == 'integer' && strpos($contentCompare[$key], '<img') > 0) || (gettype(strpos($content[$key], '<img')) == 'integer' && strpos($content[$key], '<img') > 0) ){
                    $compareTable = $this->compareSupport->compare($contentCompare[$key], $content[$key]);
                    $temp = $this->compareSupport->toTable($compareTable);

                    $string['left'] .= $temp['left'];
                    $string['right'] .= $temp['right'];
                }else{

                    $temp_tag_main = preg_match_all('~<([^/][^>]*?)>~', $contentCompare[$key], $tag_main, PREG_PATTERN_ORDER);
                    $temp_tag_revision = preg_match_all('~<([^/][^>]*?)>~', $content[$key], $tag_revision, PREG_PATTERN_ORDER);

                    if(!empty($tag_main[0]) && !empty($tag_revision[0])){
                        if(gettype(strpos($tag_revision[0][0], '<ol')) == 'integer'){
                            $tag_revision[0][0] = '<ol>';
                            $tag_revision[1][0] = 'ol';
                        }
                        else if(gettype(strpos($tag_revision[0][0], '<ul')) == 'integer'){
                            $tag_revision[0][0] = '<ul>';
                            $tag_revision[1][0] = 'ul';
                        }
                        if(gettype(strpos($tag_main[0][0], '<ol')) == 'integer'){
                            $tag_main[0][0] = '<ol>';
                            $tag_main[1][0] = 'ol';
                        }
                        else if(gettype(strpos($tag_main[0][0], '<ul')) == 'integer'){
                            $tag_main[0][0] = '<ul>';
                            $tag_main[1][0] = 'ul';
                        }
                    }
                    
                    if(count($tag_main[0]) == 1 && count($tag_revision[0]) == 1 ){
                        $contentCompare[$key] = strip_tags($contentCompare[$key], '<br>');
                        $content[$key] = strip_tags($content[$key], '<br>');
                        $compareTable = $this->compareSupport->compare($contentCompare[$key], $content[$key], true);
                        $temp = $this->compareSupport->toTable($compareTable, '', '', '', '1');

                        if($contentCompare[$key] == $content[$key]){
                            if($tag_main[1][0] == $tag_revision[1][0]){
                                $string['left'] .= '<'. $tag_main[1][0] .'>'. $temp['left'] . '</'. $tag_main[1][0] .'>';
                                $string['right'] .= '<'. $tag_revision[1][0] .'>'. $temp['right'] .'</'. $tag_revision[1][0] .'>';
                            }
                            else{

                                $string['left'] .= '<div class="diffDeleted"><'. $tag_main[1][0] .'>'. $temp['left'] . '</'. $tag_main[1][0] .'></div>';
                                $string['right'] .= '<div class="diffInserted"><'. $tag_revision[1][0] .'>'. $temp['right'] .'</'. $tag_revision[1][0] .'></div>' ;
                            }
                        }
                        else{
                            $temp['left'] = str_replace('<p>', $tag_main[0][0], $temp['left']);
                            $temp['left'] = str_replace('</p>', '', $temp['left']);
                            $temp['right'] = str_replace('<p>', $tag_revision[0][0], $temp['right']);
                            $temp['right'] = str_replace('</p>', '', $temp['right']);

                            $string['left'] .= $temp['left'];
                            $string['right'] .= $temp['right'];
                        }
                    }
                    else{

                        $compareTable = $this->compareSupport->compare($contentCompare[$key], $content[$key]);
                        $temp = $this->compareSupport->toTable($compareTable, '', '', trans('site.content_revision'));

                        if((count($tag_main[0]) > 0 && $tag_main[0][0] != '<p>' && count($tag_revision[0]) > 1 && $tag_revision[0][0] != '<p>')  && ($tag_revision[0][0] != $tag_main[0][0]) ){

                            if(strpos($tag_main[0][0], '<table') > -1 || strpos($tag_revision[0][0], '<table') > -1){
                                $string['left'] .= $temp['left'];
                                $string['right'] .= $temp['right'];
                            }
                            else{
                                $string['left'] .= '<div class="diffDeleted">' .$temp['left']. '</div>';
                                $string['right'] .= '<div class="diffInserted">' .$temp['right']. '</div>';
                            }

                        }else{
                            $string['left'] .= $temp['left'];
                            $string['right'] .= $temp['right'];

                        }
                    }
                }
            }
        }
        else{
            $main_post_content = implode("\r", $contentCompare);
            $revision_post = implode("\r", $content);
            $compareTable = $this->compareSupport->compare($main_post_content, $revision_post);
            $string =  $this->compareSupport->toTable($compareTable, '', '', trans('site.content_revision'));
        }

        return $string;
    }
	/**
	 * A1BC ok 122
	 *
	 * ok
	 */
	public function abcd()
	{
		$a = 1;
		$b =1
		$t1 = 1;
		$t2 = 2;
		$t22=2;
		$main_post_content = implode("\r", $contentCompare);
            $revision_post = implode("\r", $content);
            $compareTable = $this->compareSupport->compare($main_post_content, $revision_post);
            $string =  $this->compareSupport->toTable($compareTable, '', '', trans('site.content_revision'));
		$t23 = 2;
	}
	/**
     * @var Document Model
     */
    protected $modelDocument;

    /**
     * @var Post Share Model
     */
    protected $modelShare;

    /**
     * @var Array Support
     */
    protected $arraySupport;

    /**
     * @var Compare Support
     */
    protected $compareSupport;

    /**
     * PostsSupport constructor.
     *
     * @param Document $document
     */
    public function __construct(
        Document $document,
        ShareEmail $shareEmail,
        ArraySupport $arraySupport,
        CompareSupport $compareSupport
    ){
        $this->modelDocument    = $document;
        $this->modelShare       = $shareEmail;
        $this->arraySupport     = $arraySupport;
        $this->compareSupport   = $compareSupport;
    }

    /**
     * Create ball revision for Data
     *
     * @param null $postID
     * @return mixed Array data
     * @throws \Exception
     * @author Ducnn
     * @edited hoatq, moved from DocumentControler
     */
    public function createBallForRevision($postID = null)
    {
        if (is_null($postID)) {
            throw new \Exception('Error! The Post ID is NULL');
        }

        $allList = $this->modelDocument->postListCount($postID, 'desc');

        $countRevision = count($allList);
        if ($countRevision > 1) {
            $notBall = 1;
            for ($i = 0; $i < $countRevision  ; $i++) {
                if ($i == 0) {
                    $post_id = $allList[$i]->key_id;
                    $user_id = $allList[$i]->user_id;
                    $ballNo =  $this->arraySupport->convertObjectToArray(
                        $this->modelShare->getOneBallById($post_id, $user_id), 'ball');

                    if ($allList[$i]->user_id == $allList[$i + 1]->user_id) {
                        $notBall = 0;
                        if ($ballNo[0] == 2) {
                            $allList[$i]->ball = 1;
                        } else {
                            $allList[$i]->ball = 0;
                        }
                    } else {
                        if ($ballNo[0] == 2) {
                            $allList[$i]->ball = 1;
                        } else {
                            $allList[$i]->ball = 0;
                        }
                    }
                } elseif ($i == ($countRevision - 1)) {
                    if ($allList[$i]->user_id == $allList[$i - 1]->user_id) {
                        $allList[$i]->ball = 0;
                    } else {
                        $allList[$i]->ball = 1;
                    }
                } else {
                    if ($notBall == 1) {
                        $allList[$i]->ball = 1;
                        $notBall = 0;
                    } else {
                        if($allList[$i]->user_id == $allList[$i + 1]->user_id) {
                            $allList[$i]->ball = 0;
                            $notBall = 0;
                        } else {
                            $allList[$i]->ball = 0;
                            $notBall = 1;
                        }
                    }
                }
            }
        } else {
            $post_id = $allList[0]->key_id;
            $user_id = $allList[0]->user_id;
            $ballNo =  $this->arraySupport->convertObjectToArray($this->modelShare->getOneBallById($post_id, $user_id ), 'ball');
            if ($ballNo[0] == 2) {
                $allList[0]->ball = 1;
            } else {
                $allList[0]->ball = 0;
            }
        }

        return $allList;
    }

    /**
     * Replace input revision
     *
     * @param $content the String
     * @author thinhpm
     * @date 14/05/2018
     * @edited hoatq move to DocumentController
     */
    public function replaceInputRevision($content){
        $content = str_replace(' style="clear: both;" ', '', $content);
        $content = str_replace(' style="clear: both;"', '', $content);
        $content = str_replace('<p><br></p>', '', $content);
        $content = str_replace('<br>', '&br&', $content);
        return $content;
    }

    /**
     * Replace output revision
     *
     * @param $content the String
     * @author thinhpm
     * @date 14/05/2018
     * @edited hoatq move to DocumentController
     */
    public function replaceOutputRevision($content){
        $content = str_replace('&br&', '<br>', $content);
        $content = str_replace('<div class="diffBlank"></div>', '', $content);
        $content = str_replace('<div class="diffUnmodified"></div>', '', $content);
        $content = str_replace('<li></li>', '', $content);

        return $content;
    }

    /**
     * Convert tag Image to String
     *
     * @param $string
     * @return mixed
     */
    public function convertTagImageToString($string)
    {
        $string = str_replace('<img', "&lt;img", $string);

        return $string;
    }

    /**
     * Get array content Post
     *
     * @param $contentCompare the String
     * @param $content the String
     * @author thinhpm
     * 14/05/2018
     * @edited hoatq move to DocumentController
     */
    public function getArrayPostRevision($contentCompare, $content){
        $arr_post['main'] = '';
        $arr_post['revision'] = '';
        $match = $this->compareSupport->preg_cell($contentCompare, $content[0] . "\n" . $content[1]);

        if(count($match['left'][0]) > 0 || count($match['right'][0]) > 0){
            $contentCompare = preg_replace('/\<ol[^>]*\>(.*?)\<\/ol\>/','<tempol>', $contentCompare);
            $main_revision_content = preg_replace('/\<ol[^>]*\>(.*?)\<\/ol\>/','<tempol>', $content[0] . "\n" . $content[1]);
            $contentCompare = preg_replace('/\<ul[^>]*\>(.*?)\<\/ul\>/','<tempol>', $contentCompare);
            $main_revision_content = preg_replace('/\<ul[^>]*\>(.*?)\<\/ul\>/','<tempol>', $main_revision_content);

            $arr_split_main_post = explode('<tempol>', $contentCompare);
            $arr_split_revision_post = explode('<tempol>', $main_revision_content);
            $i = 0;
            $j = 0;

            for ($key = 0; $key < count($arr_split_main_post) + count($match['left'][0]); $key++) {
                if($key == 0){
                    $tmp = preg_split('/\n|\r\n?/', $arr_split_main_post[$j]);

                    foreach ($tmp as $key2 => $value2) {
                        if($value2 != ''){
                            $arr_main_post[] = $value2;
                        }
                    }
                    $j++;
                }
                else
                    if($key % 2 != 0 && !empty($match['left'][0][$i])){
                        $arr_main_post[] = preg_replace('/\R/', '', $match['left'][0][$i]);
                        $i++;
                    }
                    else{
                        $tmp = preg_split('/\n|\r\n?/', $arr_split_main_post[$j]);

                        foreach ($tmp as $key2 => $value2) {
                            if($value2 != ''){
                                $arr_main_post[] = $value2;
                            }
                        }
                        $j++;
                    }
            }

            $i = 0;
            $j = 0;
            for ($key = 0; $key < count($arr_split_revision_post) + count($match['right'][0]); $key++) {
                if($key == 0){
                    $tmp = preg_split('/\n|\r\n?/', $arr_split_revision_post[$j]);
                    foreach ($tmp as $key2 => $value2) {
                        if($value2 != ''){
                            $arr_revision_post[] = $value2;
                        }
                    }
                    $j++;

                }
                else
                    if($key % 2 != 0 && !empty($match['right'][0][$i])){
                        $arr_revision_post[] = preg_replace('/\R/', '', $match['right'][0][$i]);
                        $i++;
                    }
                    else{
                        $tmp = preg_split('/\n|\r\n?/', $arr_split_revision_post[$j]);
                        foreach ($tmp as $key2 => $value2) {
                            if($value2 != ''){
                                $arr_revision_post[] = $value2;
                            }
                        }
                        $j++;

                    }
            }

        }

        else{
            $arr_main_post = preg_split('/\n|\r\n?/', $contentCompare);
            $arr_revision_post = preg_split('/\n|\r\n?/', $content[0] . "\n" . $content[1]);
        }

        foreach ($arr_main_post as $key => $value) {
            if(strlen(trim($value)) == 0){
                unset($arr_main_post[$key]);
            }
        }
        foreach ($arr_revision_post as $key => $value) {
            if(strlen(trim($value)) == 0){
                unset($arr_revision_post[$key]);
            }
        }
        $arr_main_post = array_values($arr_main_post);
        $arr_revision_post = array_values($arr_revision_post);
        $arr_post['main'] = $arr_main_post;
        $arr_post['revision'] = $arr_revision_post;

        return $arr_post;
    }

    /**
     * Compare Content
     *
     * @param $contentCompare the String
     * @param $content the String
     * @author thinhpm
     * 14/05/2018
     * @edited hoatq move to DocumentController
     */
    public function compareContent($contentCompare, $content){
        $string['left'] = '';
        $string['right'] = '';
        $count_main_post = count($contentCompare);
        $count_revision_post = count($content);

        if($count_main_post == $count_revision_post){
            foreach ($contentCompare as $key => $value) {
                if((gettype(strpos($contentCompare[$key], '<img')) == 'integer' && strpos($contentCompare[$key], '<img') > 0) || (gettype(strpos($content[$key], '<img')) == 'integer' && strpos($content[$key], '<img') > 0) ){
                    $compareTable = $this->compareSupport->compare($contentCompare[$key], $content[$key]);
                    $temp = $this->compareSupport->toTable($compareTable);

                    $string['left'] .= $temp['left'];
                    $string['right'] .= $temp['right'];
                }else{

                    $temp_tag_main = preg_match_all('~<([^/][^>]*?)>~', $contentCompare[$key], $tag_main, PREG_PATTERN_ORDER);
                    $temp_tag_revision = preg_match_all('~<([^/][^>]*?)>~', $content[$key], $tag_revision, PREG_PATTERN_ORDER);

                    if(!empty($tag_main[0]) && !empty($tag_revision[0])){
                        if(gettype(strpos($tag_revision[0][0], '<ol')) == 'integer'){
                            $tag_revision[0][0] = '<ol>';
                            $tag_revision[1][0] = 'ol';
                        }
                        else if(gettype(strpos($tag_revision[0][0], '<ul')) == 'integer'){
                            $tag_revision[0][0] = '<ul>';
                            $tag_revision[1][0] = 'ul';
                        }
                        if(gettype(strpos($tag_main[0][0], '<ol')) == 'integer'){
                            $tag_main[0][0] = '<ol>';
                            $tag_main[1][0] = 'ol';
                        }
                        else if(gettype(strpos($tag_main[0][0], '<ul')) == 'integer'){
                            $tag_main[0][0] = '<ul>';
                            $tag_main[1][0] = 'ul';
                        }
                    }
                    
                    if(count($tag_main[0]) == 1 && count($tag_revision[0]) == 1 ){
                        $contentCompare[$key] = strip_tags($contentCompare[$key], '<br>');
                        $content[$key] = strip_tags($content[$key], '<br>');
                        $compareTable = $this->compareSupport->compare($contentCompare[$key], $content[$key], true);
                        $temp = $this->compareSupport->toTable($compareTable, '', '', '', '1');

                        if($contentCompare[$key] == $content[$key]){
                            if($tag_main[1][0] == $tag_revision[1][0]){
                                $string['left'] .= '<'. $tag_main[1][0] .'>'. $temp['left'] . '</'. $tag_main[1][0] .'>';
                                $string['right'] .= '<'. $tag_revision[1][0] .'>'. $temp['right'] .'</'. $tag_revision[1][0] .'>';
                            }
                            else{

                                $string['left'] .= '<div class="diffDeleted"><'. $tag_main[1][0] .'>'. $temp['left'] . '</'. $tag_main[1][0] .'></div>';
                                $string['right'] .= '<div class="diffInserted"><'. $tag_revision[1][0] .'>'. $temp['right'] .'</'. $tag_revision[1][0] .'></div>' ;
                            }
                        }
                        else{
                            $temp['left'] = str_replace('<p>', $tag_main[0][0], $temp['left']);
                            $temp['left'] = str_replace('</p>', '', $temp['left']);
                            $temp['right'] = str_replace('<p>', $tag_revision[0][0], $temp['right']);
                            $temp['right'] = str_replace('</p>', '', $temp['right']);

                            $string['left'] .= $temp['left'];
                            $string['right'] .= $temp['right'];
                        }
                    }
                    else{

                        $compareTable = $this->compareSupport->compare($contentCompare[$key], $content[$key]);
                        $temp = $this->compareSupport->toTable($compareTable, '', '', trans('site.content_revision'));

                        if((count($tag_main[0]) > 0 && $tag_main[0][0] != '<p>' && count($tag_revision[0]) > 1 && $tag_revision[0][0] != '<p>')  && ($tag_revision[0][0] != $tag_main[0][0]) ){

                            if(strpos($tag_main[0][0], '<table') > -1 || strpos($tag_revision[0][0], '<table') > -1){
                                $string['left'] .= $temp['left'];
                                $string['right'] .= $temp['right'];
                            }
                            else{
                                $string['left'] .= '<div class="diffDeleted">' .$temp['left']. '</div>';
                                $string['right'] .= '<div class="diffInserted">' .$temp['right']. '</div>';
                            }

                        }else{
                            $string['left'] .= $temp['left'];
                            $string['right'] .= $temp['right'];

                        }
                    }
                }
            }
        }
        else{
            $main_post_content = implode("\r", $contentCompare);
            $revision_post = implode("\r", $content);
            $compareTable = $this->compareSupport->compare($main_post_content, $revision_post);
            $xtringmaster2 =  $this->compareSupport->toTable($compareTable, '', '', trans('site.content_revision'));
        }

        return $string;
    }
}
