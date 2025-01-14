<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
auth($RES_STATUS);

if (validID($_GET['id']) && !permit('exp', $_GET['id'])) header('Location: /res/');

$title = array(
    '/res/' => 'Researchers',
    '/res/exp/' => 'Experiment',
    '' => 'Edit Trials'
);

$styles = array(
    '#trial_builder, #image_chooser' => 'font-size: 80%; overflow:auto;',
    '#trial_builder img' => 'display: inline-block; min-width: 80px; min-height: 30px;',
    '#trial_builder img.trialimg, #img_list img, #trial_builder  #dv span' => 'width: 80px; border: 1px solid ' . THEME . '; margin: 3px;',
    '#trial_builder span.imgname' => 'display: none;',
    '#trial_builder.list img' => 'display: none;',
    '#trial_builder.list span.imgname, #trial_builder.list #dv span' => 'display: inline-block; border: 1px solid ' . THEME . '; min-height: 1em; width: 170px; padding: 5px; margin: 2px;',
    '#dv span' => 'background-color:' . THEME . '; color: white; text-align: center; display: inline-block; ',
    '#img_list img' => 'box-shadow: 2px 2px 4px rgba(0,0,0,.5);',
    '#img_list li:hover' => 'cursor: move;',
    '#img_list li' => 'padding:1px; margin:0; max-width: 30em;',
    '#image_search' => 'width: 300px;',
    '#listfill' => 'width: 100%; height: 150px;',
    '#trial_builder .img_column' => 'width: 80px;',
    '#trial_builder.list .img_column' => 'width: auto;',
    '#image_toggle a, #list_toggle a' => 'color: #999;',
    '#images_found' => 'float: right; padding-left: 1em;',
    '#search-bar' => 'float: right; width: 48%; text-align: right;',
    '#search-bar input' => 'width: 70%;',
    '.drop_hover' => 'border: 1px solid red !important;',
    '.trial' => 'border-bottom: 3px solid ' . THEME,
    '.trial p' => 'margin:0; padding:0; clear: left;',
    '.label_list' => 'display: inline-block; width: 120px;',
    '.label_list li' => 'padding:0; margin-left: 22px; font-size: 90%;',
    '.trial_icons' => 'float: right;',
    '.trial_icons a' => 'border: none;',
    'audio' => 'width: 100px; height: 30px; padding-top: 5px;'
);

function imgname($src) {
    $name = str_replace('/stimuli', '', $src);
    $name = str_replace('.jpg', '', $name);
    $name = str_replace('.ogg', '', $name);
    $name = str_replace('.mp3', '', $name);
    return $name;
}

/***************************************************/
/* !Get image list */
/***************************************************/

if (array_key_exists('search', $_GET)) {
    $searches = str_replace(array(' & ',    ' | ',      ' and ',    ' or '), 
                            array(' AND ',  ' OR ',     ' AND ',    ' OR '),
                            $_POST['image_search']);
                           
    $searches = my_clean($searches); // clean up search string
    
    $search_strings = preg_split("/( AND | OR |\(|\))/", $searches);
    
    $search_terms = array();
    foreach($search_strings as $string) {
        $term = trim($string);
        if (!empty($term)) {
            if (substr($term, 0, 1) =='!') {  // negate a term
                $search_terms[$term] = '(!LOCATE("' . substr($term,1) . '", path))';
            } else {
                $search_terms[$term] = '(LOCATE("' . $term . '", path) OR LOCATE("' . $term . '", description))';
            }
        }
    }
    
    $s = $searches;
    foreach ($search_terms as $term => $locate) {
        $s = str_replace($term, $locate, $s);
    }
    $s = "(LEFT(path,17) != '/stimuli/uploads/' OR LOCATE('/stimuli/uploads/{$_SESSION['user_id']}/', path)=1) AND {$s}";

    $query = new myQuery('SELECT id, CONCAT(id,":",path,":",type) as path FROM stimuli WHERE ' . $s . ' ORDER BY stimuli.path LIMIT 2000');
    $images = $query->get_key_val('id', 'path');
    
    //echo 'error ' . $query->get_query(); // debug query
    echo implode(';', $images);
    exit;
}

/***************************************************/
/* !Save trials */
/***************************************************/
 
if (array_key_exists('save', $_GET)) {  
    $exp_id = $_GET['exp_id'];
    
    $q = new myQuery('DELETE FROM trial WHERE exp_id=' . $exp_id);
    $q = new myQuery('DELETE FROM xafc WHERE exp_id=' . $exp_id);

    foreach ($_POST as $trial => $tdata) {
        if (!empty($tdata['limg']) && !empty($tdata['cimg']) && !empty($tdata['rimg'])) {
            $insertlist = 'left_img, center_img, right_img,';
            $idlist = 'limg.id, cimg.id, rimg.id';
            $imagelist = 'stimuli as limg, stimuli AS cimg, stimuli AS rimg';
            $where = 'limg.path = "' . $tdata['limg'] . '" AND cimg.path = "' . $tdata['cimg'] . '" AND rimg.path = "' . $tdata['rimg'] . '"';
        } else if (!empty($tdata['limg']) && empty($tdata['cimg']) && !empty($tdata['rimg'])) {
            $insertlist = 'left_img, right_img,';
            $idlist = 'limg.id, rimg.id';
            $imagelist = 'stimuli as limg, stimuli AS rimg';
            $where = 'limg.path = "' . $tdata['limg'] . '" AND rimg.path = "' . $tdata['rimg'] . '"';
        } else if (empty($tdata['limg']) && !empty($tdata['cimg']) && empty($tdata['rimg'])) {
            $insertlist = 'center_img,';
            $idlist = 'cimg.id';
            $imagelist = 'stimuli as cimg';
            $where = 'cimg.path = "' . $tdata['cimg'] . '"';
        } else if (empty($tdata['limg']) && empty($tdata['cimg']) && empty($tdata['rimg'])) {
            $insertlist = 'center_img,';
            $idlist = 'NULL';
            $imagelist = 'stimuli';
            $where = 'stimuli.id=1';
        }
    
        $query = sprintf('INSERT INTO trial (exp_id, trial_n, name, 
            %s 
            question, label1, label2, label3, label4) 
            SELECT %d, %d, "%s", 
            %s, %s, %s, %s, %s, %s
            FROM %s 
            WHERE %s',
            $insertlist,
            $exp_id,
            $trial,
            $tdata['name'],
            $idlist,
            (empty($tdata['question'])) ? 'NULL' : '"' . $tdata['question'] . '"' ,
            (empty($tdata['label1'])) ? 'NULL' : '"' . $tdata['label1'] . '"' ,
            (empty($tdata['label2'])) ? 'NULL' : '"' . $tdata['label2'] . '"' ,
            (empty($tdata['label3'])) ? 'NULL' : '"' . $tdata['label3'] . '"' ,
            (empty($tdata['label4'])) ? 'NULL' : '"' . $tdata['label4'] . '"' ,
            $imagelist,
            $where
        );
        
        $query = str_replace("{{blank}}", "", $query);
        
        $q = new myQuery($query);
        
        // add images to xafc table if an xafc or sort type
        if (!empty($tdata['xafc'])) {
            $xafc = array();
            foreach ($tdata['xafc'] as $i => $path) {
                $n = $i+1;
                $q = new myQuery("INSERT INTO xafc (exp_id, trial_n, n, image) 
                                    SELECT $exp_id, $trial, $n, stimuli.id 
                                    FROM stimuli 
                                    WHERE path='$path'");
            }
        }   
    }
    
    echo 'Trials saved.';

    exit;
} 

/***************************************************/
/* !Get trial information */
/***************************************************/

$exp_id=$_GET['id'];

if (!validID($exp_id)) { header('Location: /'); exit; }

// get experiment info
$query = new myQuery('SELECT res_name, question, label1, total_stim, random_stim, exptype, subtype FROM exp WHERE id=' . $exp_id);
$exp_info = $query-> get_assoc(0);
$title[] = $exp_info['res_name'];

// get existing trial info
$query = new myQuery('SELECT trial_n, name, label1, label2, label3, label4, question, q_image, color, 
        li.path as limg,
        ci.path as cimg,
        ri.path as rimg
    FROM trial
    LEFT JOIN stimuli AS li ON (li.id=left_img)
    LEFT JOIN stimuli AS ci ON (ci.id=center_img)
    LEFT JOIN stimuli AS ri ON (ri.id=right_img)
    WHERE exp_id=' . $exp_id . ' 
    AND trial_n <= ' . $exp_info['total_stim'] . ' 
    ORDER BY trial_n');
    
$trials = $query->get_assoc();

if ($exp_info['exptype'] == 'xafc' || $exp_info['exptype'] == 'sort') {
    $query = new myQuery('SELECT trial_n, n, stimuli.path as path 
        FROM xafc 
        LEFT JOIN stimuli ON (stimuli.id=xafc.image) 
        WHERE exp_id=' . $exp_id . ' 
        AND trial_n <= ' . $exp_info['total_stim'] . ' 
        ORDER BY trial_n');
    $xafc_trials = $query->get_assoc();
    foreach ($xafc_trials as $xt) {
        $i = $xt['trial_n'] - 1;
        $trials[$i]['xafc'][] = $xt['path'];
    }
}

// get total trials
$total_trials = max(1, $exp_info['total_stim']);

// add more trials if the experiment table holds more trials than the trials table
if (count($trials) > 0 && count($trials) < $total_trials) {
    for ($i=count($trials)+1; $i<=$total_trials; $i++) {
        $trials[$i] = $trials[0];
        $trials[$i]['trial_n'] = $i;
        $trials[$i]['name'] = 't' . $i;
    }
}

if (count($trials) == 0) {
    // no trials exist, set up trials
    $trials = array();
    
    $maxtrials = max(1,$exp_info['random_stim'], $total_trials);
    
    for ($i=0; $i<$maxtrials; $i++) {
        $trials[$i] = array(
            'trial_n' => ($i+1),
            'name' => 't' . ($i+1),
        );
        
        if (strpos($_GET['images'], 'l') !== false) $trials[$i]['limg'] = '/stimuli/blankface.jpg';
        if (strpos($_GET['images'], 'c') !== false) $trials[$i]['cimg'] = '/stimuli/blankface.jpg';
        if (strpos($_GET['images'], 'r') !== false) $trials[$i]['rimg'] = '/stimuli/blankface.jpg';
        
        if (is_numeric($_GET['images'])) {
            // this is an xafc, so display as many images as needed
            for ($x = 0; $x < $_GET['images']; $x++) {
                $trials[$i]['xafc'][] = '/stimuli/blankface.jpg';
            }
        }
/*      
        if (empty($exp_info['question'])) $trials[$i]['question'] = 'Question';
        
        if (empty($exp_info['label1']) && $exp_info['exptype'] == 'jnd') {
            $trials[$i]['label1'] = 'Slightly More Attractive';
            $trials[$i]['label2'] = 'Somewhat More Attractive';
            $trials[$i]['label3'] = 'More Attractive';
            $trials[$i]['label4'] = 'Much More Attractive';
        }
*/
    }
} elseif (isset($_GET['images'])) {
    // update image if $_GET['images'] is inconsistent with existing images
    $to_update = array();
    if (strpos($_GET['images'], 'l') !== false && empty($trials[0]['limg'])) $to_update['limg'] = 'add';
    if (strpos($_GET['images'], 'c') !== false && empty($trials[0]['cimg'])) $to_update['cimg'] = 'add';
    if (strpos($_GET['images'], 'r') !== false && empty($trials[0]['rimg'])) $to_update['rimg'] = 'add';
    if (strpos($_GET['images'], 'l') === false && !empty($trials[0]['limg'])) $to_update['limg'] = 'delete';
    if (strpos($_GET['images'], 'c') === false && !empty($trials[0]['cimg'])) $to_update['cimg'] = 'delete';
    if (strpos($_GET['images'], 'r') === false && !empty($trials[0]['rimg'])) $to_update['rimg'] = 'delete';
    
    if (count($to_update)>0) {
        foreach($trials as $i => $trial) {
            foreach($to_update as $img => $action) {
                if ($action == 'add') $trials[$i][$img] = '/stimuli/blankface';
                if ($action == 'delete') unset($trials[$i][$img]);
            }
        }
    }
}

// set up table width and margins to display trials and image chooser correctly
$tablewidth = ((empty($trials[0]['limg'])) ? 0 : 90) +
              ((empty($trials[0]['cimg'])) ? 0 : 90) +
              ((empty($trials[0]['rimg'])) ? 0 : 90) + 
              ((empty($trials[0]['label1'])) ? 0 : 120) +
              ((empty($trials[0]['xafc'])) ? 0 : 90 * count($trials[0]['xafc'])) + 25;
              
if ($tablewidth > 500) $tablewidth = 500;
if ($tablewidth < 100) $tablewidth = 100;
              
$styles['#trial_builder'] = 'float: left; width: ' . $tablewidth . 'px; margin-right: -' . ($tablewidth+20) . 'px';
$styles['#image_chooser'] = 'margin-left: ' . ($tablewidth+20) . 'px;';
$styles['#trial_builder.list'] = 'max-width: 50%; width: '. ($tablewidth*2) .'px; margin-right: -' . ($tablewidth*2+20) . 'px';
$styles['#trial_builder.list + #image_chooser'] = 'margin-left: ' . ($tablewidth*2+20) . 'px;';

/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<hidden name="exp_id" id="exp_id" value="=<?= $exp_id ?>" />

<div class="toolbar">
    <div class="toolbar-line">
        <button id="save-trials" class='.ui-state-active'>Save Trials</button>
        <span style="padding-left: 1em;">Experiment:</span>
        <button id="start-exp">Go</button>
        <button id="edit-exp">Edit</button>
        <button id="exp-info">Info</button>
        
        <span id="search-bar">
            <input type="search" 
                placeholder="Search for images"
                id="image_search" 
                name="image_search" 
                onchange="showImages(50);"  />
    
            <span id="image_list_toggle">
                <input type="radio" id="list_toggle" name="radio" checked="checked" />
                <label for="list_toggle">List</label>
                <input type="radio" id="image_toggle" name="radio" />
                <label for="image_toggle">Images</label>
            </span>
        </span>
    </div>
    
    <div class="toolbar-line">              
        <button id="fill-from-list">Fill From List</button>
        Common Path: <span id="common_path"></span>
        
        <span id="images_found"></span>
    </div>
</div>

<div id="trial_builder" class="list">

<div id="dv">

<?php
    
if (!empty($trials[0]['limg'])) {
    if ($exp_info['exptype'] == '2afc') {
        echo '<span>DV = 1</span>' . ENDLINE;
    } else if ($exp_info['exptype'] == 'jnd') {
        echo '<span>DV = 4-7</span>' . ENDLINE;
    }
}

if (!empty($trials[0]['cimg'])) {
    if ($exp_info['exptype'] == '2afc') {
        echo '<span></span>' . ENDLINE;
    } else if ($exp_info['exptype'] == 'jnd') {
        echo '<span></span>' . ENDLINE;
    }
}


if (!empty($trials[0]['rimg'])) {
    if ($exp_info['exptype'] == '2afc') {
        echo '<span>DV = 0</span>' . ENDLINE;
    } else if ($exp_info['exptype'] == 'jnd') {
        echo '<span>DV = 0-3</span>' . ENDLINE;
    }
}

if (!empty($trials[0]['xafc'])) {
    foreach ($trial['xafc'] as $i => $x) {
        echo '<span>DV = '.$i.'</span>' . ENDLINE;
    }
}

echo '</div>'. ENDLINE;

foreach ($trials as $trial) {

    echo '<div id="trial_' . $trial['trial_n'] . '" class="trial">'. ENDLINE;
    
    echo '  <p>t' . $trial['trial_n'] . ': <span id="name_' . $trial['trial_n'] . '" class="editText" title="Click to edit!">' . $trial['name' ] . '</span></p>' . ENDLINE;
    
    if (empty($exp_info['question'])) { 
        echo '<p>Q' . $trial['trial_n'] . ': <span id="question_' . $trial['trial_n'] . '" class="editText" title="Click to edit!">' . $trial['question' ] . '</span></p>' . ENDLINE; 
    }
    
    if ($exp_info['exptype'] == 'jnd' && empty($exp_info['label1'])) {
        echo '  <ol class="label_list">' . ENDLINE;
        echo '  <li><span id="label1_' . $trial['trial_n'] . '" class="editText" title="Click to edit!">' . $trial['label1' ] . '</li>' . ENDLINE;
        echo '  <li><span id="label2_' . $trial['trial_n'] . '" class="editText" title="Click to edit!">' . $trial['label2' ] . '</li>' . ENDLINE;
        echo '  <li><span id="label3_' . $trial['trial_n'] . '" class="editText" title="Click to edit!">' . $trial['label3' ] . '</li>' . ENDLINE;
        echo '  <li><span id="label4_' . $trial['trial_n'] . '" class="editText" title="Click to edit!">' . $trial['label4' ] . '</li>' . ENDLINE;
        echo '  </ol>' . ENDLINE;
    }
    
    
    if (!empty($trial['limg'])) { 
        $imagelist[] = $trial['limg']; // add image to master image list
        echo '<img class="trialimg" id="limg_' . $trial['trial_n'] . '" 
            src="' . (substr($trial['limg'],0,7) == '/audio/' ? '/images/linearicons/rocket.php' : $trial['limg']) . '" 
            title="' . $trial['limg'] . '" />' . ENDLINE .
            '<span class="imgname">' . imgname($trial['limg']) . '</span>' . ENDLINE; 
    }
    if (!empty($trial['cimg'])) { 
        $imagelist[] = $trial['cimg']; // add image to master image list
        echo '<img class="trialimg" id="cimg_' . $trial['trial_n'] . '" 
            src="' . (substr($trial['cimg'],0,7) == '/audio/' ? '/images/linearicons/rocket.php' : $trial['cimg']) . '" 
            title="' . $trial['cimg'] . '" />' . ENDLINE .
            '<span class="imgname">' . imgname($trial['cimg']) . '</span>' . ENDLINE; 
    }
    if (!empty($trial['rimg'])) { 
        $imagelist[] = $trial['rimg']; // add image to master image list
        echo '<img class="trialimg" id="rimg_' . $trial['trial_n'] . '" 
            src="' . (substr($trial['rimg'],0,7) == '/audio/' ? '/images/linearicons/rocket.php' : $trial['rimg']) . '" 
            title="' . $trial['rimg'] . '" />' . ENDLINE .
            '<span class="imgname">' . imgname($trial['rimg']) . '</span>' . ENDLINE; 
    }
    if (!empty($trial['xafc'])) {
        echo '<span id="xafc_' . $trial['trial_n'] . '">' . ENDLINE;
        foreach ($trial['xafc'] as $i => $x) {
            $imagelist[] = $x; // add image to master image list
                $n = $i+1;
            echo '<img class="trialimg" id="xafc_' . $n . '_img_' . $trial['trial_n'] . '" 
                src="' . (substr($x,0,7) == '/audio/' ? '/images/linearicons/rocket.php' : $x) . '" 
                title="' . $x . '" />' . ENDLINE .
            '<span class="imgname">' . imgname($x) . '</span>' . ENDLINE; 
        }
        echo '</span>' . ENDLINE;
    }
    echo '</div>' . ENDLINE;
}

?>

</div>

<div id="image_chooser">
    <!--
    <div id="imagebox">
        <div id='imageurl'></div>
        <img />
    </div>
    <div id="finder"></div>
    -->
    <ul id="img_list"></ul>
</div>

<div id="dialog-form-fill" class="modal" title="Fill fields from list">
    <p>Paste an Excel column or type in a list of trial names, etc.</p>
    <textarea id="listfill"></textarea>
</div>

<div id='dialog-saver' class="modal" title='Trial Saver'></div>

<div id="help" title="Trial Builder Help">
    <h1>Searching for Images</h1>
    <ul>
        <li>Type into the search box and press Return to search the image database.</li>
        <li>Both the full image name (e.g., <kbd>/stimuli/canada2003/sexdim/female/fem/white</kbd>) and the description (if set) are searched.</li>
        <li>Use <kbd>AND</kbd> or <kbd>OR</kbd> to search multiple terms (e.g., <kbd>composites AND (1ns OR sss)</kbd>).</li>
        <li>Use <kbd>!</kbd> to remove items with a term (e.g., <kbd>kdef AND !profile</kbd>).</li>
    </ul>
    
    <h1>Setting the Trials</h1>
    <ul>
        <li>Double-click an image in the trial builder on the left to fill all following images from the list on the right.</li>
        <li>You can set individual images by dragging images or image names from the list on the right.</li>
        <li>Images that will get high scores (1 on FC or 4-7 on JND) go on the left. Images that will get low scores (0 on FC or 0-3 on JND) go on the right.</li>
    </ul>
</div>


<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>

    function imgname(src) {
        var name = src.replace('/stimuli', '');
        name = name.replace('.jpg', '');
        name = name.replace('.gif', '');
        name = name.replace('.png', '');
        name = name.replace('.ogg', '');
        name = name.replace('.mp3', '');
        return name;
    }

    $(function() {
        
        $("#trial_builder img.trialimg, #trial_builder span.imgname").droppable({
            tolerance: "pointer",
            hoverClass: "drop_hover",
            drop: function( event, ui ) {
                if ($(this).hasClass('imgname')) {
                    var theImg = $(this).prev('img.trialimg');
                    var theSpan = $(this);
                } else {
                    var theSpan = $(this).next('span.imgname');
                    var theImg = $(this);
                }
            
                var theSrc = $(ui.draggable).attr('title');
                
                theImg.attr({
                    'src': theSrc,
                    'title': theSrc
                });
                theSpan.html(imgname(theSrc));
            }
        });
        
        $("#trial_builder img.trialimg, #trial_builder span.imgname").dblclick( function() {
            if ($(this).hasClass('imgname')) {
                var coldata = $(this).prev('img.trialimg').attr('id').split('img_');
            } else {
                var coldata = $(this).attr('id').split('img_');
            }

            fill(coldata[0], coldata[1]);
        });
        
        // resize lists to window height
        $(window).resize(resizeContent);
        resizeContent();
        
        // add common path to common_path
        var common_path = "<?= str_replace('/stimuli', '', common_path($imagelist)) ?>";
        $("#common_path").html(common_path);
        

        // add functions to buttons
        $( "#image_list_toggle" ).buttonset();
        $( "#list_toggle" ).click(function() { toggleImages(0); });
        $( "#image_toggle" ).click(function() { toggleImages(1); });
        
        $('#start-exp').button().click( function() {
            window.location = '/exp?id=<?= $exp_id ?>';
        });
        
        $('#edit-exp').button().click( function() {
            window.location = '/res/exp/builder?id=<?= $exp_id ?>';
        });
        
        $( "#exp-info" ).button().click( function() {
            window.location.href='/res/exp/info?id=<?= $exp_id ?>'; 
        });
        
        $( "#save-trials" ).button().click(function() {
            var dataArray = {};
    
            $('#trial_builder div.trial').each( function() {
                var n = this.id.replace('trial_', '');          
                dataArray[n] = {};
                
                dataArray[n]['name'] = $('#name_' + n).html();
                if ($('#limg_' + n).length > 0) dataArray[n]['limg'] = $('#limg_' + n).attr('title');
                if ($('#cimg_' + n).length > 0) dataArray[n]['cimg'] = $('#cimg_' + n).attr('title');
                if ($('#rimg_' + n).length > 0) dataArray[n]['rimg'] = $('#rimg_' + n).attr('title');
                if ($('#label1_' + n).length > 0) {
                    dataArray[n]['label1'] = $('#label1_' + n).html();
                    dataArray[n]['label2'] = $('#label2_' + n).html();
                    dataArray[n]['label3'] = $('#label3_' + n).html();
                    dataArray[n]['label4'] = $('#label4_' + n).html();
                }
                if ($('#question_' + n).length > 0) dataArray[n]['question'] = $('#question_' + n).html();
                if ($('#xafc_' + n + ' img.trialimg').length > 0) {
                    // get array of all images in xafc
                    dataArray[n]['xafc'] = {};
                    $('#xafc_' + n + ' img.trialimg').each( function(i) {
                        dataArray[n]['xafc'][i] = $(this).attr('title'); 
                    });
                }
            });
            
            $.ajax({
                type: 'POST',
                url: './trials?save&exp_id=<?= $exp_id ?>',
                data: dataArray,
                success: function(response) {
                    <?php if (substr($exp_info['subtype'], 0, 5) == 'adapt') {
                        echo "window.location = 'adapt?id={$exp_id}';";
                    } ?>
                    
                    if (response == "Trials saved.") {
                        window.location = 'info?id=<?= $exp_id ?>';
                    }
                    
                    growl(response);
                }
            });
        });
        
        $('#dialog-saver').dialog({
            autoOpen: false,
            show: "scale",
            hide: "scale",
            height: 200,
            width: 350,
            modal: true,
            buttons: {
                'Start Exp': function() { window.location = '/exp?id=<?= $exp_id ?>'; },
                'Exp Info': function() { window.location = '/res/exp/info?id=<?= $exp_id ?>'; },
            }
        });
        
        $( "#fill-from-list" ).button().click(function() { $( "#dialog-form-fill" ).dialog( "open" ); }); 
        $( "#dialog-form-fill" ).dialog({
            autoOpen: false,
            show: "scale",
            hide: "scale",
            height: 350,
            width: 350,
            modal: true,
            buttons: {
                "Fill Trial Names": function() {
                    var bValid = true;

                    if ( bValid ) {
                        var rows = $('#listfill').val().split("\n");
                        
                        for (var i=0; i < rows.length; i++) {
                            var c = '#name_' + (i+1);
                            if ($(c).length > 0 ) { $(c).html(rows[i]); }
                        }
                        $( this ).dialog( "close" );
                    }
                },
<?php if (empty($exp_info['question'])) { ?>
                "Fill Questions": function() {
                    var bValid = true;

                    if ( bValid ) {
                        var rows = $('#listfill').val().split("\n");
                        
                        for (var i=0; i < rows.length; i++) {
                            var c = '#question_' + (i+1);
                            if ($(c).length > 0 ) { $(c).html(rows[i]); }
                        }
                        $( this ).dialog( "close" );
                    }
                },
<?php } 
      if (empty($exp_info['label1']) && $exp_info['exptype'] == 'jnd') { ?>

                "Fill Labels": function() {
                    var bValid = true;

                    if ( bValid ) {
                        var rows = $('#listfill').val().split("\n");
                        
                        for (var i=0; i < rows.length; i++) {
                            var labels = rows[i].split("\t");
                            for (var n=0; n<4; n++) {
                                var c = '#label' + (n+1) + '_' + (i+1);
                                if ($(c).length > 0 ) { $(c).html(labels[n]); }
                            }
                        }
            
                        $( this ).dialog( "close" );
                    }
                },
<?php } ?>
                Cancel: function() {
                    $( this ).dialog( "close" );
                }
            },
        });
    });
    
    function fill(column, start) {
        // auto-fill image columns with selected images
    
        var i = 1;
        // get list of images
        var imagelist = [];
        $('#img_list li').each( function() {
            imagelist[i] = $(this).attr('title');
            i++
        });
        
        if (i == 1) {
            $('<div />').html('Search for images by typing part of the image folder name into the search box above.').dialog('open');
            return false;
        }
        
        // add images to the trial builder
        i = 1;
        var lastTrial = $('#trial_builder div').length;
        
        for (n = start; n <= lastTrial; n++) {
            var theimage = $('#' + column + 'img_' + n);
            
            if (theimage.length == 0) return false; // stop iterating when trials are done
            if (i >= imagelist.length) i = 1; // restart image list if more trials remain
            
            if (imagelist[i].substring(0,7) == '/audio/') {
                theimage.attr('src', '/images/icons/glyphish/icons/icons-theme/264-sound-on@2x');
            } else {
                theimage.attr('src', imagelist[i]);
            }
            theimage.attr('title', imagelist[i]);
            
            theimage.next('span.imgname').html(imgname(imagelist[i]));
            
            i++
        }       
    }
    
    function resizeContent() {
        var content_height = $(window).height() - $('#trial_builder').offset().top - $('#footer').height()-30;
        $('#trial_builder').height(content_height);
        $('#image_chooser').height(content_height);
    }
    
    function addDraggable() {
        $('#img_list li').draggable({
            helper: "clone",
            cursorAt: { top: 0, left: 0 }
        });
    }
    
    var imgToggle = 0;
    function toggleImages(t) {
        // if t == 0, turn off images and turn on list view in image chooser
        // if t == 1, turn off list view and turn on images in image chooser
        
        imgToggle = t;
        
        // make current option unclickable so you dont keep searching
        if (imgToggle == 0) {
            //$('#image_toggle').html("<a href='javascript:toggleImages(1);'>images</a>");
            //$('#list_toggle').html('list');
            $('#trial_builder').addClass('list');
        } else if (imgToggle == 1)  {
            //$('#list_toggle').html("<a href='javascript:toggleImages(0);'>list</a>");
            //$('#image_toggle').html('images');
            $('#trial_builder').removeClass('list');
        }
        
        showImages(50);
    }

    function showImages(max_images) {
        // exit if no search text is found
        if ($('#image_search').val() == "") {
            return false;
        }
    
        // retrieve image list asynchronously
        $.ajax({
            url: 'trials?search', 
            type: 'POST',
            data: $('#image_search').serialize(),
            success: function(resp) {
                if (resp.substr(0,5) == "error") {
                    alert(resp);
                } else {
                    $('#img_list').empty();
                    var id_path = resp.split(";");
                    var len = id_path.length;
                    var plus = '';
                    if (len == 2000) { plus = '+'; }
                    
                    $('#images_found').html(len + plus + '&nbsp;images&nbsp;found');
                    
                    for (var i = 0; i<len; ++i ){
                        var img = id_path[i].split(":");
                        if (imgToggle == 1) {
                            if (img[2] == "audio") {
                                var shortpath = img[1].split("/");
                                $('#img_list').append('<li id="img' + img[0] + '" title="' + img[1] + '"><audio controls="controls" src="' + img[1] + '.ogg" /></audio> ' + shortpath[(shortpath.length - 1)] + '<br /></li>');
                            } else {
                                $('#img_list').append('<li id="img' + img[0] + '" title="' + img[1] + '"><img src="' + img[1] + '" /></li>');
                            }
                            
                            if (i >= max_images) {
                                $('#img_list').append('<a href="javascript:showImages('+(max_images+50)+')">View more...</a>');
                                break;
                                
                            }
                        } else {
                            var shortname = img[1].replace('/stimuli', '');
                            $('#img_list').append('<li id="img' + img[0] + '" title="' + img[1] + '">' + shortname + '</li>');
                        }
                    }
                    
                    // make sure each li displays correctly and is draggable
                    if (imgToggle == 1) $('#img_list li').css('display','inline');
                    if (imgToggle == 0) {
                        $('#img_list li').css('display','block');
                        $('#img_list li:odd').addClass('odd');
                        $('#img_list li:even').addClass('even');
                    }
                    addDraggable();
                }
            }
        }); 
        
    }

/*
    $(function() {
        window.onresize = sizeToViewport;
        $('#finder').hide();
        
        // get directory structure via ajax
        $.ajax({
            url: '/res/scripts/browse?dir=/stimuli/', 
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                //$('#finder').html(JSON.stringify(data));
                folderize(data, $('#finder'));
                
                // hide loading animation and show finder
                $('#msg').hide();
                $('#finder').show();
                sizeToViewport();
            }
        });
    });
*/

</script>

<!-- enable instant edits -->
<script src="/include/js/instantedit.js" type="text/javascript"></script>

<?php

$page->displayFooter();

?>