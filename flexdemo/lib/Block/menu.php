<?php

$block_name = _("Horde Menu");

/**
 * $Horde: horde/lib/Block/menu.php,v 1.0 2008/01/10 5:11:00 elier $
 *
 * @package Horde_Block
 */
class Horde_Block_flexdemo_menu extends Horde_Block {

    var $_app = 'horde';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("Menu");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        $html = '<h2>Menu</h2>';
        return $html;
    }


    function toHtml()
    {
        return $this->_content();
    }



}