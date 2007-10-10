<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * HTML QuickForm Spelling Rule
 *
 * Note: This file must be included after HTML/QuickForm.php
 *
 * A HTML_QuickForm rule plugin that checks the spelling of its value(s)
 *
 * PHP Versions 4 and 5
 *
 * @category    HTML
 * @package     HTML_QuickForm_Rule_Spelling
 * @author      David Sanders (shangxiao@php.net)
 * @license     http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version     Release: @package_version@
 * @link        http://pear.php.net/package/HTML_QuickForm_Rule_Spelling
 */

require_once 'HTML/QuickForm/Rule.php';

$GLOBALS['_HTML_QuickForm_Rule_Spelling_options']['allow_ignore']  = true;
$GLOBALS['_HTML_QuickForm_Rule_Spelling_options']['allow_add']     = true;


/**
 * HTML QuickForm Spelling Rule
 *
 * A HTML_QuickForm rule plugin that checks the spelling of its value(s).  This
 * rule uses drivers, of which the default is HTML_QuickForm_Rule_Spelling_Pspell. 
 *
 * Each value is broken up into words which are then checked individually. If 
 * any words are found to be misspelt, they will be added to a list to be used 
 * on the client side by the javascript frontend.  This frontend is a dialog 
 * that will cycle through each misspelt word for all the applied fields and 
 * give the user the choice to either ignore the word, add it to the dictionary
 * on the server or change the word to one of the listed suggestions.
 * 
 * This rule may be applied to multiple fields via the use of an array, or 
 * applied singular fields one at a time.  The spellchecker will go cycle 
 * through each misspelt word of each specified field for that form (and only 
 * that form).
 * 
 * The following parameters are required to be passed in through the options field:
 *   - form          A reference to the HTML_QuickForm object.
 *   - element_name  The name of the element the rule applies to. If this rule 
 *                   was applied to an array of elements, this must be an array
 *                   of names in the same order.
 *
 * The following options are set statically via HTML_QuickForm_Rule_Spelling::setOption():
 *   - allow_ignore  Whether to allow words to be ignored.
 *   - allow_add     Whether to allow words to be added to the dictionary.
 *   - spellchecker  Spellchecking driver.  Either create your own or use 
 *                   HTML_QuickForm_Rule_Spelling_Pspell.
 * 
 * Known Bugs/Limitations
 *
 *  - Firefox has a bug where if a textarea is ever disabled, then the highlight of
 *    a selection does not show
 *    Ref: https://bugzilla.mozilla.org/show_bug.cgi?id=396403
 *  - IE and Safari do not support multiple selections
 *
 *
 * Example:
 *
 * require_once 'HTML/QuickForm.php';
 * require_once 'HTML/QuickForm/Rule/Spelling.php';
 *
 * $pspell_config = pspell_config_create('en', 'british');
 * pspell_config_personal($pspell_config, './personal.pws');
 * require_once 'HTML/QuickForm/Rule/Spelling/Pspell.php';
 * HTML_QuickForm_Rule_Spelling::setOption('spellchecker', new HTML_QuickForm_Rule_Spelling_Pspell($pspell_config));
 * 
 * 
 * // Uncomment the following line to disable ignoring words
 * //HTML_QuickForm_Rule_Spelling::setOption('allow_ignore', false);
 * 
 * // Uncomment the following line to disable adding words
 * //HTML_QuickForm_Rule_Spelling::setOption('allow_add', false);
 * 
 * 
 * $form = new HTML_QuickForm;
 * $form->addElement('textarea', 'text_1', 'Text 1');
 * $form->addElement('textarea', 'text_2', 'Text 2');
 * $form->addElement('submit', 'submit', 'Submit');
 * 
 * $form->addRule('text_1', 'Please correct the spelling mistakes', 'spelling',
 *  array('form'         => $form,
 *        'element_name' => 'text_1'));
 * $form->addRule('text_2', 'Please correct the spelling mistakes', 'spelling',
 *  array('form'         => $form,
 *        'element_name' => 'text_2'));
 * 
 * // or alternatively apply the rule in one go
 * //$form->addRule(array('text_1','text_2'), 'Please correct the spelling mistakes', 'spelling',
 * //    array('form'         => $form,
 * //          'element_name' => array('text_1','text_2')));
 *
 * $form->validate();
 * $form->display();
 *
 *
 * 
 * @category    HTML
 * @package     HTML_QuickForm_Rule_Spelling
 * @author      David Sanders (shangxiao@php.net)
 * @license     http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version     Release: @package_version@
 * @link        http://pear.php.net/package/HTML_QuickForm_Rule_Spelling
 */
class HTML_QuickForm_Rule_Spelling extends HTML_QuickForm_Rule
{
    /**
     * Driver containing spellchecking callbacks
     *
     * @var object
     * @access private
     */
    var $_spellchecker;

    /**
     * Word delimiter (regular expression character class)
     *
     * @var string
     * @access private
     */
    var $_word_delimiter = '[^A-Za-z\']';

    /**
     * Validate hook.  Checks the spelling.
     *
     * @param   value   The value(s) passed from HTML_QuickForm
     * @param   options Any extra information passed to the rule
     * @access  public
     * @return  bool    False if a word is not in the dictionary, true otherwise
     */
    function validate($values, $options)
    {
        if (!isset($options['form'])                   ||
            !is_a($options['form'], 'HTML_QuickForm')  ||
            !isset($options['element_name'])) {
            PEAR::raiseError('HTML_QuickForm_Rule_Spelling: Usage error');
            return true;
        }

        if (!extension_loaded('json')) {
            PEAR::raiseError('HTML_QuickForm_Rule_Spelling: The json extension is required');
            return true;
        }

        if (!isset($this->_spellchecker)) {
            $this->_spellchecker = $this->getOption('spellchecker');
            if (is_null($this->_spellchecker)) {
                require_once 'HTML/QuickForm/Rule/Spelling/Pspell.php';
                $this->_spellchecker =& new HTML_QuickForm_Rule_Spelling_Pspell;
            }
        }

        $allow_ignore = $this->getOption('allow_ignore');
        $allow_add = $this->getOption('allow_add');

        // deal with either a single value or array of values
        if (!is_array($values)) {
            $values = array($values);
        }
        if (!is_array($options['element_name'])) {
            $options['element_name'] = array($options['element_name']);
        }

        if ($allow_add === true &&
            isset($_REQUEST['qf_rule_spelling_addword'])  &&
            is_array($_REQUEST['qf_rule_spelling_addword'])) {
            foreach ($_REQUEST['qf_rule_spelling_addword'] as $word) {
                $this->_spellchecker->add($word);
            }
            $this->_spellchecker->save();
        }

        $element_index = 0;
        $misspelt_words = array();

        foreach ($values as $value) {

            if (!$options['form']->elementExists($options['element_name'][$element_index])) {
                PEAR::raiseError('HTML_QuickForm_Rule_Spelling: Element ' . 
                                 $options['element_name'][$element_index] .
                                 ' does not exist');
                return true;
            }

            $the_element =& $options['form']->getElement($options['element_name'][$element_index]);
            $id = $the_element->getAttribute('id');
            if ($id === '' || is_null($id)) {
                $the_element->_generateId();
                $id = $the_element->getAttribute('id');
            }

            $wordlist = preg_split('/' . $this->_word_delimiter . '+/',
                                   $value, -1, PREG_SPLIT_NO_EMPTY);
            if ($allow_ignore === true &&
                isset($_REQUEST['qf_rule_spelling_ignoreword'])  &&
                is_array($_REQUEST['qf_rule_spelling_ignoreword'])) {
                $wordlist = array_diff($wordlist, $_REQUEST['qf_rule_spelling_ignoreword']);
            }

            foreach ($wordlist as $word) {
                $word = preg_replace("/^'/", '', preg_replace("/'$/", '', $word));

                if (!$this->_spellchecker->check($word)) {

                    $suggestions = $this->_spellchecker->suggest($word);

                    $misspelt_words[] = array(
                        'id'          => $id,
                        'word'        => $word,
                        'word_re'     => '(^|' . $this->_word_delimiter . ')' .
                                         preg_quote($word) .
                                         '(' . $this->_word_delimiter . '|$)',
                        'suggestions' => $suggestions,
                        );
                }
            }

            $element_index++;
        }

        if (!empty($misspelt_words)) {

            // define once per form
            if (!defined('HTML_QUICKFORM_RULE_SPELLING_BEGINSPELLCHECK_' .
                $options['form']->getAttribute('id'))) {

                define('HTML_QUICKFORM_RULE_SPELLING_BEGINSPELLCHECK_' .
                    $options['form']->getAttribute('id'), true);

                $options['form']->addElement('button',
                                             'qf_rule_spelling_startspellcheck',
                                             'Run through spelling mistakes',
                                             array('onclick' => 'window.spellcheck.startSpellCheck(this.form)'));
            }


            // define once per page
            if (!defined('HTML_QUICKFORM_RULE_SPELLING_SPELLCHECKER')) {

                define('HTML_QUICKFORM_RULE_SPELLING_SPELLCHECKER', true);
                $options['form']->addElement('static',
                                             'qf_rule_spelling_spellcheck',
                                             $this->_getSpellcheckJavascript($allow_ignore, $allow_add));
            }

            // define every time rule is called
            $misspelt_words_js = json_encode($misspelt_words);
            $javascript = <<<EOT
<script type="text/javascript">
//<![CDATA[
spellcheck.addList($misspelt_words_js)
//]]>
</script>
EOT;
            $options['form']->addElement('static',
                                         'qf_rule_spelling_wordlist_' . uniqid(),
                                         $javascript);

            return false;
        }

        return true;
    }

    /**
     * Generate the frontend javascript.
     *
     * @access private
     * @param $allow_ignore bool Show/hide ignore word button
     * @param $allow_add bool Show/hide add word button
     * @return string
     */
    function _getSpellcheckJavascript($allow_ignore, $allow_add)
    {
        $javascript = <<<EOT
<script type="text/javascript">
//<![CDATA[

function qf_rule_spelling_spellcheck()
{
    this.ignore_list = new Array;
    this.next_index;
    this.curr_index;
    this.misspelt_words = new Array;
    this.form;
}

qf_rule_spelling_spellcheck.prototype.inArray = function(the_array, value)
{
    var i;
    for (i = 0; i < the_array.length; i++) {
        if (the_array[i] == value) {
            return true;
        }
    }
    return false;
};

qf_rule_spelling_spellcheck.prototype.addList = function(list)
{
    this.misspelt_words = this.misspelt_words.concat(list);
}

qf_rule_spelling_spellcheck.prototype.startSpellCheck = function(form)
{
    this.form = form;
    this.curr_index = undefined;
    this.next_index = 0;

    this.showDialog();
    this.loopSpellCheck();
}

qf_rule_spelling_spellcheck.prototype.loopSpellCheck = function()
{
    while (true)
    {
        if (this.next_index >= this.misspelt_words.length)
        {
            this.closeDialog();
            if (this.curr_index == undefined) { 
                alert('There were no words to check');
            } else {
                alert('Spellcheck complete');
            }
            return;
        }

        var o = this.misspelt_words[this.next_index];
        var e = document.getElementById(o.id);
        o.word_re_regexp = new RegExp(o.word_re);

        if (!this.inArray(this.ignore_list, this.next_index) &&
            e.value.search(o.word_re_regexp) != -1) {
            break;
        }

        this.next_index++;
    }

    if (this.curr_index != undefined)
    {
        var o_curr = this.misspelt_words[this.curr_index];
        var e_curr = document.getElementById(o_curr.id);
        e_curr.disabled = false;
        if (e_curr.setSelectionRange) {
            e_curr.setSelectionRange(0,0);
        }
    }

    var incorrect = document.getElementById('qf_rule_spelling_incorrect_text');
    incorrect.value = e.value;
    var suggestions = document.getElementById('qf_rule_spelling_suggestions');
    this.fillOptions(suggestions, o.suggestions);
    var change_button = document.getElementById('qf_rule_spelling_spellcheck_change');
    change_button.disabled = (o.suggestions.length == 0);
    // safari provides a blue highlighting for focused elements
    e.focus();
    // if need to select, need to disable the element first
    e.disabled = true;
    // ie and safari will remove the selection anyway 
    this.highlightWord(o.id, o.word, o.word_re_regexp);
    this.highlightWord('qf_rule_spelling_incorrect_text', o.word, o.word_re_regexp);

    this.curr_index = this.next_index;
    this.next_index++;
}

qf_rule_spelling_spellcheck.prototype.ignoreWord = function()
{
    var o = this.misspelt_words[this.curr_index];
    var new_element = document.createElement('input');
    new_element.type = 'hidden';
    new_element.name = 'qf_rule_spelling_ignoreword[]';
    new_element.value = o.word;
    this.form.appendChild(new_element);

    this.loopSpellCheck();
}

qf_rule_spelling_spellcheck.prototype.addWord = function()
{
    var o = this.misspelt_words[this.curr_index];
    var new_element = document.createElement('input');
    new_element.type = 'hidden';
    new_element.name = 'qf_rule_spelling_addword[]';
    new_element.value = o.word;
    this.form.appendChild(new_element);

    this.ignore_list.push(this.curr_index);

    this.loopSpellCheck();
}

qf_rule_spelling_spellcheck.prototype.changeWord = function()
{
    this.ignore_list.push(this.curr_index);

    var suggestions = document.getElementById('qf_rule_spelling_suggestions');
    var o = this.misspelt_words[this.curr_index];
    var e = document.getElementById(o.id);
    e.value = e.value.replace(o.word_re_regexp, '$1'+suggestions.options[suggestions.selectedIndex].text+'$2');

    var incorrect = document.getElementById('qf_rule_spelling_incorrect_text');
    incorrect.value = '';

    this.loopSpellCheck();
}

qf_rule_spelling_spellcheck.prototype.highlightWord = function(id, word, word_re_regexp)
{
    var e = document.getElementById(id);
    var str = e.value;
    var j = str.search(word_re_regexp);
    var matches = str.match(word_re_regexp);
    j += matches[0].indexOf(word);
    var k = j + word.length;
    if(document.selection && document.selection.createRange) {
        var rng = e.createTextRange();
        rng.moveStart('character', j);
        rng.moveEnd('character', word.length + j - str.length);
        rng.select();
    } else if (e.setSelectionRange) {
        e.setSelectionRange(j,k);
    }
}

qf_rule_spelling_spellcheck.prototype.showDialog = function()
{
    this.form.qf_rule_spelling_startspellcheck.disabled = true;
    document.getElementById('qf_rule_spelling_spellcheck_dialog').style.display = '';
}

qf_rule_spelling_spellcheck.prototype.closeDialog = function()
{
    this.form.qf_rule_spelling_startspellcheck.disabled = false;
    document.getElementById('qf_rule_spelling_spellcheck_dialog').style.display = 'none';
    if (this.curr_index != undefined) {
        var o = this.misspelt_words[this.curr_index];
        var e = document.getElementById(o.id);
        e.disabled = false;
        if(document.selection && document.selection.createRange) {
            var rng = e.createTextRange();
            rng.moveStart('character', 0);
            rng.moveEnd('character', -e.value.length);
            rng.select();
        } else if (e.setSelectionRange) {
            e.setSelectionRange(0,0);
        }
    }
}

qf_rule_spelling_spellcheck.prototype.fillOptions = function(select, options)
{
    select.options.length = 0;
    for (var i in options)
    {
        select.options[select.options.length] = new Option(options[i]);
    }
    select.selectedIndex = 0;
}

var spellcheck = new qf_rule_spelling_spellcheck();

//]]>
</script>
<div id="qf_rule_spelling_spellcheck_dialog" style="position: absolute; top: 0px; right: 0px; border: 1px solid black; width: 450px; padding: 2px; background: #eeeeee; display: none;">
<table cellpadding="0" cellspacing="0" border="0" style="width: 450px;">
<tr><td colspan="2">Not in dictionary:</td></tr>
<tr><td rowspan="2"><textarea rows="3" style="width: 300px; resize: none;" id="qf_rule_spelling_incorrect_text" readonly="readonly"></textarea></td><td style="width: 200px;" align="center">
EOT;

        if ($allow_ignore === true) {
            $javascript .= <<<EOT
<button type="button" style="width: 140px; margin-left: 4px;" onclick="window.spellcheck.ignoreWord()">Ignore</button>
EOT;
        }

        $javascript .= <<<EOT
</td></tr>
<tr><td align="center">
EOT;
        if ($allow_add === true) {
            $javascript .= <<<EOT
<button type="button" style="width: 140px; margin-left: 4px;" onclick="window.spellcheck.addWord()">Add to Dictionary</button>
EOT;
        }

        $javascript .= <<<EOT
</td></tr>
<tr><td colspan="2">Suggestions:</td></tr>
<tr><td rowspan="2"><select style="width: 300px;" id="qf_rule_spelling_suggestions" size="5" multiple="multiple"></select></td><td align="center" valign="top"><button type="button" style="width: 140px; margin-left: 4px;" onclick="window.spellcheck.changeWord()" id="qf_rule_spelling_spellcheck_change">Change</button></td></tr>
<tr><td align="center" valign="bottom"><button type="button" style="width: 140px; margin-left: 4px;" onclick="window.spellcheck.closeDialog()">Close</button></td></tr>
</table>
</div>
EOT;

        return $javascript;
    }

    /**
     * Option Setter
     *
     * @param option option to set
     * @param value value to set option to
     * @access public
     */
    function setOption($option, $value = null)
    {
        if (is_array($option)) {
            foreach ($option as $key => $value) {
                $GLOBALS['_HTML_QuickForm_Rule_Spelling_options'][$key] = $value;
            }
        } else {
            $GLOBALS['_HTML_QuickForm_Rule_Spelling_options'][$option] = $value;
        }
    }

    /**
     * Option Getter
     *
     * @param option option to get
     * @access public
     * @return string or null
     */
    function getOption($option)
    {
        return isset($GLOBALS['_HTML_QuickForm_Rule_Spelling_options'][$option]) ?
               $GLOBALS['_HTML_QuickForm_Rule_Spelling_options'][$option] :
               null;
    }
}

if (class_exists('HTML_QuickForm')) {
    HTML_QuickForm::registerRule('spelling',
                                 null,
                                 'HTML_QuickForm_Rule_Spelling',
                                 'HTML/QuickForm/Rule/Spelling.php');
}

?>
