<?php
/**
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Shared code for handling special mailboxes preferences.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Prefs_Special_SpecialMboxes
{
    const PREF_DEFAULT = "default\0";
    const PREF_NO_MBOX = "nombox\0";
    const PREF_SPECIALUSE = "specialuse\0";

    /**
     * Cached mailbox list.
     *
     * @var array
     */
    protected $_cache = null;

    /**
     * Update special mailbox preferences.
     *
     * @param string $pref             The pref name to update.
     * @param IMP_Mailbox $form        The form data.
     * @param string $new              The new mailbox name.
     * @param string $type             Special use attribute (RFC 6154).
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     *
     * @return boolean  True if preferences were updated.
     */
    protected function _updateSpecialMboxes($pref, $form, $new, $type,
                                            Horde_Core_Prefs_Ui $ui)
    {
        global $injector, $prefs;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        if (!$imp_imap->access(IMP_Imap::ACCESS_FOLDERS) ||
            $prefs->isLocked($pref)) {
            return false;
        }

        $cache = $injector->getInstance('IMP_Mailbox_SessionCache');
        if ($mbox_ob = IMP_Mailbox::getPref($pref)) {
            $cache->expire(
                array(
                    IMP_Mailbox_SessionCache::CACHE_DISPLAY,
                    IMP_Mailbox_SessionCache::CACHE_LABEL,
                    IMP_Mailbox_SessionCache::CACHE_SPECIALMBOXES
                ),
                $mbox_ob
            );
        }

        if ($form == self::PREF_NO_MBOX) {
            return $prefs->setValue($pref, '');
        }

        if (strpos($form, self::PREF_SPECIALUSE) === 0) {
            $mbox = IMP_Mailbox::get(substr($form, strlen(self::PREF_SPECIALUSE)));
        } elseif (!empty($new)) {
            $mbox = IMP_Mailbox::get($new)->namespace_append;

            $opts = is_null($type)
                ? array()
                : array('special_use' => array($type));

            if (!$mbox->create($opts)) {
                $mbox = null;
            }
        } else {
            $mbox = $form;
        }

        if (!$mbox) {
            return false;
        }

        $cache->expire(
            array(
                IMP_Mailbox_SessionCache::CACHE_DISPLAY,
                IMP_Mailbox_SessionCache::CACHE_LABEL
            ),
            $mbox
        );

        return $prefs->setValue($pref, $mbox->pref_to);
    }

    /**
     * Get the list of special use mailboxes of a certain type.
     *
     * @param string $use  The special-use flag.
     *
     * @return string  HTML code.
     */
    protected function _getSpecialUse($use)
    {
        global $injector;

        if (is_null($this->_cache)) {
            $this->_cache = $injector->getInstance('IMP_Factory_Imap')->create()->listMailboxes('*', Horde_Imap_Client::MBOX_ALL, array(
                'attributes' => true,
                'special_use' => true,
                'sort' => true
            ));
        }

        $special_use = array();
        foreach ($this->_cache as $val) {
            if (in_array($use, $val['attributes'])) {
                $mbox_ob = IMP_Mailbox::get($val['mailbox']);
                $special_use[] = array(
                    'l' => $mbox_ob->label,
                    'v' => IMP_Mailbox::formTo(self::PREF_SPECIALUSE . $mbox_ob)
                );
            }
        }

        if (empty($special_use)) {
            return '';
        }

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/prefs'
        ));
        $view->addHelper('Text');

        $view->special_use = $special_use;

        return $view->render('specialuse');
    }

}
