<?php

require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/Rule/Spelling.php';


//
// Create your own spellchecker by defining a class with the following api,
// and passing the object to 
// HTML_QuickForm_Rule_Spelling::setOption('spellchecker', $mySpellchecker);
//
//class mySpellchecker
//{
//    function check($word)
//    {
//        return true if $word in dictionary, false otherwise;
//    }
//
//    function suggest($word)
//    {
//        return list of suggestions given $word
//    }
//
//    function add($word)
//    {
//        add $word to the dictionary
//    }
//
//    function save()
//    {
//        save dictionary
//    }
//}
//
//
// Alternatively you may change the default driver's pspell configuration by 
// instantiating it and passing a config to the constructor and then setting 
// this new driver.
//
$pspell_config = pspell_config_create('en', 'british');
pspell_config_personal($pspell_config, './personal.pws');
require_once 'HTML/QuickForm/Rule/Spelling/Pspell.php';
HTML_QuickForm_Rule_Spelling::setOption('spellchecker', new HTML_QuickForm_Rule_Spelling_Pspell($pspell_config));


// Uncomment the following line to disable ignoring words
//HTML_QuickForm_Rule_Spelling::setOption('allow_ignore', false);

// Uncomment the following line to disable adding words
//HTML_QuickForm_Rule_Spelling::setOption('allow_add', false);


$form = new HTML_QuickForm;
$form->addElement('textarea', 'text_1', 'Text 1');
$form->addElement('textarea', 'text_2', 'Text 2');
$form->addElement('submit', 'submit', 'Submit');

$form->addRule('text_1', 'Please correct the spelling mistakes', 'spelling',
    array('form'         => $form,
          'element_name' => 'text_1'));
$form->addRule('text_2', 'Please correct the spelling mistakes', 'spelling',
    array('form'         => $form,
          'element_name' => 'text_2'));

// or alternatively apply the rule in one go
//$form->addRule(array('text_1','text_2'), 'Please correct the spelling mistakes', 'spelling',
//    array('form'         => $form,
//          'element_name' => array('text_1','text_2')));

$form->validate();
$form->display();

?>
