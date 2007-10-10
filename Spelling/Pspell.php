<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * Pspell 'driver' for HTML_QuickForm_Rule_Spelling
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
class HTML_QuickForm_Rule_Spelling_Pspell
{
    /**
     * Pspell config id
     * 
     * @var int
     * @access private
     */
    var $_pspell_link;

    /**
     * Constructor
     *
     * @access public
     */
    function HTML_QuickForm_Rule_Spelling_Pspell($pspell_config = null)
    {
        if (is_null($pspell_config)) {
            $pspell_config = pspell_config_create('en');
        }

        $this->_pspell_link = pspell_new_config($pspell_config);
    }

    /**
     * Check the presence of word in the dictionary
     *
     * @access public
     * @param $word Word to check
     * @return bool
     */
    function check($word)
    {
        return pspell_check($this->_pspell_link, $word);
    }

    /**
     * Generate a list of suggested words from a certain word
     *
     * @access public
     * @param $word Word to generate suggestion list from
     * @return array
     */
    function suggest($word)
    {
        return pspell_suggest($this->_pspell_link, $word);
    }

    /**
     * Add a word to the dictionary
     *
     * @access public
     * @param $word Word to add to dictionary
     * @return bool
     */
    function add($word)
    {
        return pspell_add_to_personal($this->_pspell_link, $word);
    }

    /**
     * Save dictionary
     *
     * @access public
     * @return bool
     */
    function save()
    {
        return pspell_save_wordlist($this->_pspell_link);
    }
}

?>
